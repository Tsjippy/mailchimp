<?php
namespace SIM\MAILCHIMP;
use SIM;

// shows a mailchimp campaign on the page
add_shortcode("mailchimp", __NAMESPACE__.'\mailchimpCode');
function mailchimpCode($atts){
	global $post;

	$height = get_post_meta($post->ID, 'mailchimp_height', true);

	$html	= '';
	if($height == ''){
		$mailchimp = new Mailchimp();

		$dom        = new \DomDocument();
		$dom->loadHTML($mailchimp->client->campaigns->getContent($atts['id'])->html);
		$href   	= $dom->getElementById('templateFooter');
		$href->parentNode->removeChild($href);

		$content	= $dom->saveHTML($dom->getElementsByTagName('body')->item(0));
		$mergeTags	= ['MC_PREVIEW_TEXT'];

		foreach($mergeTags as $tag){
			$content	= str_replace("*|$tag|*", '', $content);
		}

		$html	= "<style>table,td{border: none !important;};#awesomebar{display:none;}</style>";
		$html	.= "<script>
			document.addEventListener('DOMContentLoaded', e => {
				let formData = new FormData();
				formData.append('postid', $post->ID);
				formData.append('height', document.querySelector('.mailchimp-wrapper').offsetHeight);
				FormSubmit.fetchRestApi('mailchimp/store_height', formData);
			});
		</script>";
		$html	.= "<div class='mailchimp-wrapper'>".$dom->saveHTML($dom->getElementsByTagName('style')->item(0)).$content."</div>";
	}else{
		$url = get_post_meta($post->ID, 'mailchimp_url', true);
		if($url == ''){
			$mailchimp 	= new Mailchimp();

			$campaign 	= $mailchimp->getCampaign($atts['id']);

			$url		= $campaign->long_archive_url;

			update_post_meta($post->ID, 'mailchimp_url', $url);
		}


		$html	.= "<iframe style='width: 100vw; height: {$height}px; border: none;' src='$url'></iframe>";
	}

	return $html;
}