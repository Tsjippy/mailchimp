<?php
namespace SIM\MAILCHIMP;
use SIM;

// Add to mailchimp on user creation
add_action( 'sim_approved_user', __NAMESPACE__.'\userApproved');
function userApproved($userId){
	//Add to mailchimp
	$mailchimp = new Mailchimp($userId);
	$mailchimp->addToMailchimp();
}
