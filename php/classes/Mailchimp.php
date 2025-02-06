<?php
namespace SIM\MAILCHIMP;
use SIM;
use WP_Error;

//https://mailchimp.com/developer/marketing

require_once( MODULE_PATH  . 'lib/vendor/autoload.php');

if(class_exists(__NAMESPACE__.'\Mailchimp')){
	return;
}

class Mailchimp{
	public $userId;
	public $settings;
	public $user;
	public $phonenumbers;
	public $mailchimpStatus;
	public $client;

	public function __construct($userId=''){
		global $Modules;

		$this->settings		= $Modules[MODULE_SLUG];

		if(is_numeric($userId)){
			$this->user			= get_userdata($userId);

			//Get phone number
			$this->phonenumbers = get_user_meta( $this->user->ID, "phonenumbers", true);

			//Get mailchimp status from db
			$this->mailchimpStatus = get_user_meta($this->user->ID, "MailchimpStatus", true);
		}

		$api = explode('-', $this->settings['apikey']);
		$this->client = new \MailchimpMarketing\ApiClient();
		$this->client->setConfig([
			'apiKey' => $api[0],
			'server' => $api[1],
		]);
	}

	/**
	 * Creates Mailchimp merge tags
	 *
	 * @return	array	merge tags
	 */
	public function buildMergeTags(){
		$mergeFields = array(
			'FNAME' 	=> $this->user->first_name,
			'LNAME' 	=> $this->user->last_name,
		);

		if(is_array($this->phonenumbers)){
			$mergeFields['PHONE'] = $this->phonenumbers[1];
		}

		$birthday = get_user_meta( $this->user->ID, "birthday",true);
		if(!empty($birthday)){
			$birthday				= explode('-',$birthday);
			//Mailchimp wants only the month and the day
			$mergeFields['BIRTHDAY'] = $birthday[1].'/'.$birthday[2];
		}

		return $mergeFields;
	}

	/**
	 * Add user to mailchimp list
	 */
	public function addToMailchimp($email='', $firstName='', $lastName='', $phoneNumber='', $birthday='', $address=''){
		if(!empty($email)){
			$mergeFields = [];
			
			if(!empty($firstName)){
				$mergeFields['FNAME']	= $firstName;
			}

			if(!empty($lastName)){
				$mergeFields['LNAME']	= $lastName;
			}

			if(!empty($phoneNumber)){
				$mergeFields['PHONE']	= $phoneNumber;
			}

			if(!empty($birthday)){
				$birthday					= explode('-', $birthday);
				$mergeFields['BIRTHDAY']	= $birthday[1].'/'.$birthday[2];
				$mergeFields['BIRTHDATE']	= $birthday[1].'/'.$birthday[2].'/'.$birthday[0];
			}

			if(!empty($address)){
				$mergeFields['ADDRESS']	= $address;
			}

			return $this->subscribeMember($mergeFields, $email);
		}

		//Only do if valid e-mail
		elseif(!empty($this->user->user_email) && !str_contains($this->user->user_email,'.empty') && $_SERVER['HTTP_HOST'] != 'localhost'){
			SIM\printArray("Adding '{$this->user->user_email}' to Mailchimp");

			//First add to the audience
			$this->subscribeMember($this->buildMergeTags());

			//Build tag list
			$roles = $this->user->roles;

			$confidentialGroups	= (array)SIM\getModuleOption('contentfilter', 'confidential-roles');
			if(array_intersect($confidentialGroups, $roles)){
				$tags = explode(',', $this->settings['user_tags']);
			}else{
				$tags = array_merge(explode(',', $this->settings['user_tags']), explode(',', $this->settings['missionary_tags']));
			}

			$this->changeTags($tags, 'active');
		}
	}

	/**
	 * Add or remove mailchimp tags
	 *
	 * @param	array	$tags	The tags to add to a user
	 * @param	string	$status	On of active or inactive
	 */
	public function changeTags($tags, $status){
		if(!is_array($this->mailchimpStatus)){
			$this->mailchimpStatus = [];
		}

		if($this->user->user_mail == '' || str_contains($this->user->user_mail,'.empty')){
			return;
		}

		//Loop over all the segments
		foreach($tags as $tag){
			//Only update if needed
			if($tag != ""){
				if($status == 'active' && (!isset($this->mailchimpStatus[$tag]) || $this->mailchimpStatus[$tag] != 'succes')){
					//Process tag
					$response = $this->setTag($tag, $status);

					//Subscription succesfull
					if( $response){
						SIM\printArray("Succesfully added the $tag tag to {$this->user->display_name}");
					//Subscription failed
					}else{
						SIM\printArray("Tag $tag  was not added to user wih email {$this->user->user_mail}} because: $response" );
					}

					//Store result
					$this->mailchimpStatus[$tag] = $response;
				}elseif($status == 'inactive' && isset($this->mailchimpStatus[$tag])){
					//Process tag
					$response = $this->setTag($tag, $status);

					//Unsubscription succesfull
					if( $response){
						SIM\printArray("Succesfully removed the $tag tag from {$this->user->display_name}");
						unset($this->mailchimpStatus[$tag]);
					//Subscription failed
					}else{
						SIM\printArray("Tag $tag  was not removed from user {$this->user->display_name} because: $response" );
					}
				}
			}
		}

		//Store results in db
		update_user_meta($this->user->ID, "MailchimpStatus", $this->mailchimpStatus);
	}

	/**
	 * Get a list of lists (audiences)
	 *
	 * @return	array|string	the lists or an error string
	 */
	public function getLists(){
		try {
			/** @disregard [OPTIONAL CODE] [OPTIONAL DESCRIPTION] */
			$lists = $this->client->lists->getAllLists(null, null, 999, 'saved');
			return $lists->lists;
		}

		//catch exception
		catch(\GuzzleHttp\Exception\ClientException $e){
			$result			= json_decode($e->getResponse()->getBody()->getContents());
			$errorResult	= $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return $errorResult;
		}catch(\Exception $e) {
			$errorResult = $e->getMessage();
			SIM\printArray($errorResult);
			return $errorResult;
		}
	}

	/**
	 * Get a list of members of a certain audience
	 *
	 * @param	string	$listId		The id of the list you want to get the members of
	 */
	public function getListMembersInfo($listId){
		/** @disregard [OPTIONAL CODE] [OPTIONAL DESCRIPTION] */
		$members = $this->client->lists->getListMembersInfo($listId, null, null, 999, '0', null, "subscribed")->members;
		
		return $members;
	}

	/**
	 * Add someone to the audience of Mailchimp
	 *
	 * @param	array	$mergeFields	The extra data for the user
	 * @param	string	$email			Optional email adres to use, default current users e-mail
	 *
	 * @return	array|string	The result or error
	 */
	public function subscribeMember($mergeFields, $email=''){
		try {
			if(empty($email)){
				$email = $this->user->user_email;
			}
			
			/** @disregard [OPTIONAL CODE] [OPTIONAL DESCRIPTION] */
			return $this->client->lists->setListMember(
				$this->settings['audienceids'][0],
				md5(strtolower($email)),
				[
					"email_address" => strtolower($email),
					"status_if_new" => 'subscribed',
					"merge_fields" 	=> $mergeFields
				]
			);
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return $errorResult;
		}catch(\Exception $e) {
			$errorResult = $e->getMessage();
			SIM\printArray($errorResult);
			return $errorResult;
		}
	}

	/**
	 * Add a tag to the current user
	 *
	 * @param	string	$tagname	the name of the tag
	 * @param	string	$status		active or inactive
	 *
	 * @return	true|WP_Error		true on succes else failure
	 */
	public function setTag($tagname, $status){
		try {

			/** @disregard [OPTIONAL CODE] [OPTIONAL DESCRIPTION] */
			$this->client->lists->updateListMemberTags(
				$this->settings['audienceids'][0],
				md5(strtolower($this->user->user_email)),
				['tags'=> [
					[
						"name" 		=> $tagname,
						"status" 	=> $status
					]
				]]
			);

			return true;
		}

		//catch exception
		catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());

			if($result->detail == "The requested resource could not be found."){
				$this->addToMailchimp();
			}

			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return new WP_Error('mailchimp', $errorResult);
		}catch(\Exception $e) {
			$errorResult = $e->getMessage();
			SIM\printArray($errorResult);
			return new WP_Error('mailchimp', $errorResult);
		}
	}

	/** 
	 * Replace urls with clickable links or video previews
	 *
	 * @param	string	$content	The content to look for links in
	 *
	 * @return	string				The content with clickale links and mailchimp video tags
	*/
	private function processLinks($content, $postId){
		// Find all urls
		$pattern = '~https?://([a-z]*?)\.([a-z]+)\.?([a-z]*)([^\s">]*)~';
		preg_match_all($pattern, $content, $matches);

		foreach($matches[0] as $index => $url){
			$mailchimpSupportedVideoProviders   = [
				'bliptv'    => 'BLIPTV',
				'vimeo'     => 'VIMEO',
				'wistia'    => 'WISTIA',
				'youtube'   => 'YOUTUBE',
				'youtu'     => 'YOUTUBE'
			];

			$provider   = false;

			if(in_array($matches[1][$index], array_keys($mailchimpSupportedVideoProviders))){
				$provider   = $mailchimpSupportedVideoProviders[$matches[1][$index]];
			}elseif(in_array($matches[2][$index], array_keys($mailchimpSupportedVideoProviders))){
				$provider   = $mailchimpSupportedVideoProviders[$matches[2][$index]];
			}

			$newUrl	= '';
			if($provider){
				switch ($provider){
					case 'BLIPTV':
						$id = explode('.', explode('/play/', $matches[4][$index])[1])[0];
						break;
					case 'VIMEO':
						$id = explode('/', $matches[4][$index])[1];
						break;
					case 'WISTIA':
						$id = explode('/', $matches[4][$index])[2];
						break;
					case 'YOUTUBE':
						if(str_contains($matches[4][$index], '/watch?v=')){
							$id = explode('/watch?v=', $matches[4][$index])[1];
						}elseif(str_contains($matches[4][$index], '/embed/')){
							$id = explode('/', $matches[4][$index])[2];
							$id	= explode('?', $id)[0];
						}else{
							$id = explode('/', $matches[4][$index])[1];
						}

						// YOUTUBE does not work, get an clickable picture instead
						$thumbnailUrl = "https://img.youtube.com/vi/$id/hqdefault.jpg";

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $thumbnailUrl);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_NOBODY, 1);
						curl_exec($ch);
						$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						curl_close($ch);

						if($responseCode != 200) {
							$thumbnailUrl = "https://img.youtube.com/vi/$id/mqdefault.jpg";
						}

						// Merge with a playbutton
						// Create image instances
						$dest		= imagecreatefromjpeg($thumbnailUrl);
						if(!$dest){
							SIM\printArray("Creating image failed for $thumbnailUrl");

							return false;
						}

						$width  	= imagesx($dest);
						$height 	= imagesy($dest);

						$src		= imagecreatefrompng(ABSPATH."wp-content/sim-modules/mailchimp/pictures/play-mq.png");
						if(!$src){
							SIM\printArray("Creating image failed for ".ABSPATH."wp-content/sim-modules/mailchimp/pictures/play-mq.png");

							return false;
						}

						$srcWidth  	= imagesx($src);
						$srcHeight 	= imagesy($src);
						
						// Copy and merge
						imagecopy($dest, $src, ($width - $srcWidth)/2, ($height - $srcHeight) / 2, 0, 0, $srcWidth, $srcHeight);
						
						// Output and free from memory
						$path			= ABSPATH."wp-content/sim-modules/mailchimp/pictures/$postId.jpg";

						imagejpeg($dest, $path);

						$thumbnailUrl	= SIM\pathToUrl($path);
						
						imagedestroy($dest);
						imagedestroy($src);

						$url			= trim($url, '"');

						$newUrl		= "<a href='$url&autoplay=1'><img src='$thumbnailUrl'/></a>";

						break;
					default:
						$id = explode('/', $matches[4][$index])[1];
				}

				if(empty($newUrl)){
					$newUrl    = "*|$provider:[\$vid=$id]|*";
				}
			}else{
				if(is_array(getimagesize($url))){
					$alt	= explode('/', $url);
					$alt	= $alt[count($alt) - 1];
					$url	= "<img src='$url' alt='$alt'>";
				}
				$newUrl	= "<a href='$url'>$url</a>";
			}

			//Check if in iframe
			$pregUrl	= trim(preg_quote($url, '/'), '"');
				
			if(preg_match("/<iframe.*src=\"$pregUrl\".*><\/iframe>/isU", $content, $iframes)){
				$content    = str_replace($iframes[0], $newUrl, $content);
			}elseif(preg_match("/<div class=\"wp-block-image\">.*$pregUrl.*<\/div>/isU", $content, $blocks)){
				$content    = str_replace($blocks[0], $newUrl, $content);
			}else{
				$content    = str_replace($url, $newUrl, $content);
			}
		}

		return $content;
	}

	private function removeGreeting($postContent){
		$lines      = preg_split('/([(\r)(\n)(,)(.)])/', $postContent, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		$firstLine  = strtolower($lines[0]);

		if(
			str_contains($firstLine, 'hi ') || 
			str_contains($firstLine, 'dear') ||
			str_contains($firstLine, 'good afternoon') || 
			str_contains($firstLine, 'good morning') || 
			str_contains($firstLine, 'good evening') || 
			str_contains($firstLine, 'hey ')
		){
			unset($lines[0], $lines[1]);
			$postContent    = trim(force_balance_tags(implode('', $lines)));
		}

		return $postContent;
	}

	/**
	 * Send an e-mail via Mailchimp
	 *
	 * @param	int		$postId			The post id to send
	 * @param	int		$segmentId		The id of the Mailchimp segment to e-mail to
	 * @param	string	$from			The from e-mail to use
	 * @param	string	$extraMessage	The extra message to prepend the -mail contents with
	 * @param	bool	$full			Whether or not to send the full post content or only a summary
	 * @param	string	$finalMessage	The extra message to add to the mail content
	 */
	public function sendEmail(int $postId, int $segmentId, $from='', $extraMessage='', $full=true, $finalMessage=''){
		try {
			if($_SERVER['HTTP_HOST'] == 'localhost' || get_option("wpstg_is_staging_site") == "true"){
				return 'Not sending from localhost';
			}

			$post 			= get_post($postId);

			$title			= $post->post_title;

			$excerpt 		= html_entity_decode(wp_trim_words($post->post_content, 20));
			$excerpt 		= strip_tags(str_replace('<br>',"\n",$excerpt)).'...';

			if($from == ''){
				$email		= get_userdata($post->post_author)->user_email;
			}else{
				$email		= $from;
			}

			//Create an empty campain
			try{
				/** @disregard [OPTIONAL CODE] [OPTIONAL DESCRIPTION] */
				$createResult = $this->client->campaigns->create(
					[
						"type" 			=> "regular",
						"recipients"	=> [
							"list_id"		=> $this->settings['audienceids'][0],
							"segment_opts"	=> [
								"saved_segment_id"	=> $segmentId
							]
						],
						"settings"		=> [
							"subject_line"	=> $title,
							"preview_text"	=> $excerpt,
							"title"			=> $title,
							"from_name"		=> SITENAME,
							"reply_to"		=> $email,
							"to_name"		=> "*|FNAME|*",
							//"template_id"	=> (int)$this->settings['templateid']
						]
					]
				);
			}catch(\GuzzleHttp\Exception\ClientException $e){
				$result = json_decode($e->getResponse()->getBody()->getContents());
				$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
				SIM\printArray($errorResult);
				return $errorResult;
			}catch(\Exception $e) {
				$errorResult = $e->getMessage();
				SIM\printArray($errorResult);
				return $errorResult;
			}

			//Get the campain id
			$campainId 			= $createResult->id;

			// Get the rendered mail content
			$mailContent 		= apply_filters( 'the_content', get_the_content(null, false, $postId));

			//Update the html
			$mailContent		= $extraMessage.'<br>'.$this->removeGreeting($mailContent ).$finalMessage;

			$mailContent		= $this->processLinks($mailContent, $postId);

			$template			= SIM\getModuleOption(MODULE_SLUG, 'mailchimp_html');

			$mailContent		= apply_filters('sim_before_mailchimp_send', $mailContent, $post);

			$mailContent 		= str_replace('%content%', $mailContent, $template, $count);

			//Push the new content
			/** @disregard [OPTIONAL CODE] [OPTIONAL DESCRIPTION] */
			$setContentResult = $this->client->campaigns->setContent(
				$campainId,
				[
					"html"			=> $mailContent,
				]
			);

			//Send the campain
			/** @disregard [OPTIONAL CODE] [OPTIONAL DESCRIPTION] */
			$sendResult = $this->client->campaigns->send($campainId);

			// Indicate as send
			update_metadata( 'post', $postId, 'mailchimp_message_send', $segmentId);

			// Store campaign id
			update_metadata( 'post', $postId, 'mailchimp_campaign_id', $campainId);

			//SIM\printArray("Mailchimp campain send succesfully");
			return 'succes';
		}

		//catch exception
		catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return $errorResult;
		}catch(\Exception $e) {
			$errorResult = $e->getMessage();
			SIM\printArray($errorResult);
			return $errorResult;
		}
	}

	/**
	 * Get an array of available segements in the audience
	 * Store in transient for 24 hours as this is a slow action
	 *
	 * @param	string			$type	The Segment type one of "all", "saved", "static", or "fuzzy"
	 * @return	array|string			Segments array or error string
	 */
	public function getSegments($type='saved'){
		if(empty($this->settings['audienceids'][0])){
			$error	= 'No Audience defined in mailchimp module settings';
			SIM\printArray($error);
			return new \WP_Error('mailchimp', $error);
		}

		$segments	= get_transient( 'mailchimp_segments' );
		if(is_array($segments)){
			return $segments;
		}

		$params	= [
			$this->settings['audienceids'][0], 	//Audience id
			null, 						// Fields to return
			null,						// Fields to return
			999,						// Maximum amount of segments
			0,							// Offset
		];

		if($type != 'all'){
			$params[]	= $type;
		}

		try {
			/** @disregard [OPTIONAL CODE] [OPTIONAL DESCRIPTION] */
			$response = $this->client->lists->listSegments(	...$params);

			usort($response->segments, function ($list1, $list2) { 
				return strtolower($list1->name) > strtolower($list2->name); 
			} ); 

			set_transient( 'mailchimp_segments', $response->segments, DAY_IN_SECONDS );

			return $response->segments;
		}

		//catch exception
		catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			return $result->detail."<pre>".print_r($result->errors,true)."</pre>";
		}catch(\Exception $e) {
			$errorResult = $e->getMessage();
			SIM\printArray($errorResult);
			return $errorResult;
		}
	}

	/**
	 * Get an array of templates
	 *
	 * @return	array|string	Templates or error string
	 */
	public function getTemplates(){
		try {
			/** @disregard [OPTIONAL CODE] [OPTIONAL DESCRIPTION] */
			$response = $this->client->templates->list(
				null,		//fields
				null,		// excludeFields
				'1000',		// count
				'0',		// offset
				null,		// createdBy
				null,		// sinceDateCreated
				null,		// beforeDateCreated
				'user'		// type
			);

			return $response->templates;
		}

		//catch exception
		catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return $errorResult;
		}catch(\Exception $e) {
			$errorResult = $e->getMessage();
			SIM\printArray($errorResult);
			return $errorResult;
		}
	}

	/**
	 * Add or remove mailchimp tags for families
	 * @param	array	$tags	array of tags
	 * @param	string	$status	active or inactive
	 */
	public function updateFamilyTags($tags, $status){
		$this->changeTags($this->user->ID, $tags, $status);

		//Update the meta key for all family members as well
		$family = SIM\familyFlatArray($this->user->ID);
		if (count($family)>0){
			foreach($family as $relative){
				//Update the marker for the relative as well
				$this->changeTags($relative, $tags, $status);
			}
		}
	}

	/**
	 * Gets a campaign by id
	 *
	 * @param	int		$id		The campaign Id.
	 */
	public function getCampaign($id){
		try{
			/** @disregard [OPTIONAL CODE] [OPTIONAL DESCRIPTION] */
			return $this->client->campaigns->get($id);
		}
		//catch exception
		catch(\GuzzleHttp\Exception\ClientException $e){
			$result			= json_decode($e->getResponse()->getBody()->getContents());
			$errorResult	= $result->detail;
			if(isset($result->errors)){
				$errorResult	.= "<pre>".print_r($result->errors, true)."</pre>";
			}
			
			return $errorResult;
		}catch(\Exception $e) {
			$errorResult = $e->getMessage();
			SIM\printArray($errorResult);
			return $errorResult;
		}
	}

	/**
	 * Gets all Mailchimp campaigns created after a certain date
	 *
	 * @param	string	$sendAfter	The string in the format '2023-10-21T15:41:36+00:00'
	 *
	 * @return	object					Object containing all campaigns
	 */
	public function getCampaigns($sendAfter){
		$count			= 1000;
		$sort			= "send_time";

		//lint:ignore S1001 test
		/** @disregard [OPTIONAL CODE] [OPTIONAL DESCRIPTION] */
		return $this->client->campaigns->list(null, null, $count, 0, null, null, null, $sendAfter, null, null, null, null, null, $sort, 'DSC');
	}

	/**
	 * Deletes a given campaign
	 *
	 * @param	string	$campaignId		The id of the campaign
	 */
	public function deleteCampaign($campaignId){
		try{
			/** @disregard [OPTIONAL CODE] [OPTIONAL DESCRIPTION] */
			$response = $this->client->campaigns->remove($campaignId);
		}
		//catch exception
		catch(\GuzzleHttp\Exception\ClientException $e){
			$result			= json_decode($e->getResponse()->getBody()->getContents());
			$errorResult	= $result->detail;
			if(isset($result->errors)){
				$errorResult	.= "<pre>".print_r($result->errors, true)."</pre>";
			}
			
			return $errorResult;
		}catch(\Exception $e) {
			$errorResult = $e->getMessage();
			SIM\printArray($errorResult);
			return $errorResult;
		}

		return $response;
	}
}
