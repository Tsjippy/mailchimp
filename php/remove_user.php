<?php
namespace TSJIPPY\MAILCHIMP;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('delete_user', __NAMESPACE__.'\deleteUser');
function deleteUser($userId){
    //remove category from mailchimp
    $userTags			= SETTINGS['user-tags'] ?? false;
    $missionaryTags	    = SETTINGS['missionary-tags'] ?? false;
    $tags               = array_merge(explode(',', $userTags), explode(',', $missionaryTags));
    $Mailchimp          = new Mailchimp($userId);

    $Mailchimp->changeTags($tags, 'inactive');
}