<?php

// Ensure compatibility with PHP 7.2 ("object" is a reserved word),
// with SilverStripe 3.6 (using Object) and SilverStripe 3.7 (using SS_Object)
if (!class_exists('SS_Object')) class_alias('Object', 'SS_Object');

$dir = basename(dirname(__FILE__));
if($dir != "dashboard") {
	user_error('Dashboard: Directory name must be "dashboard" (currently "'.$dir.'")',E_USER_ERROR);
}

LeftAndMain::require_css("dashboard/css/dashboard_icon.css");