<?php
namespace SIM\MAILCHIMP;
use SIM;

add_action('delete_user', __NAMESPACE__.'\deleteUser');
function deleteUser($userId){
    //remove category from mailchimp
    $userTags			= SIM\getModuleOption(MODULE_SLUG, 'user_tags');
    $missionaryTags	    = SIM\getModuleOption(MODULE_SLUG, 'user_tags');
    $tags               = array_merge(explode(',', $userTags), explode(',', $missionaryTags));
    $Mailchimp          = new Mailchimp($userId);

    $Mailchimp->changeTags($tags, 'inactive');
}