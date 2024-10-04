<?php
namespace SIM\MAILCHIMP;
use SIM;

// shows a mailchimp campaign on the page
add_shortcode("mailchimp", function($atts){
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

	return "<style>table,td{border: none !important;}</style>".$dom->saveHTML($dom->getElementsByTagName('style')->item(0)).$content;
});