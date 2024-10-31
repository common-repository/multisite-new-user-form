<?php

/*
Plugin Name: Multisite New User Form
Description: This plugin allows you to create users with custom password on multisite website.
Version: 1.0
Author: Robert Kampas
Author URI: https://uk.linkedin.com/in/robertkampas
Text Domain: multisite-new-user-form
License: GPLv2 or later
*/

if (defined('ABSPATH') && !class_exists('MultisiteNewUserForm'))
{
    require_once('class/MultisiteNewUserForm.php');
    
    new MultisiteNewUserForm();
    register_activation_hook(__FILE__, array('MultisiteNewUserForm', 'install'));
}