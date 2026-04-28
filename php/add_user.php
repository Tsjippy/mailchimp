<?php
namespace TSJIPPY\MAILCHIMP;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add to mailchimp on user creation
add_action( 'tsjippy_approved_user', __NAMESPACE__.'\userApproved');
function userApproved($userId){
	//Add to mailchimp
	$mailchimp = new Mailchimp($userId);
	$mailchimp->addToMailchimp();
}
