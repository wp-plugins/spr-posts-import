<?php
/*
Plugin Name: SPR Posts Import
Plugin URI: 
Description: Import full post data from site to site, restricting by category if desired. <a href="plugins.php?page=spr-posts-import.php">Plugin page</a>
Version: .1
Author: Sprise Media
Author URI: http://www.sprisemedia.com
*/

/*
LICENSE
*/
if(!defined('ABSPATH')) exit; // Exit if accessed directly

require_once('app/controllers/core.php');
require_once('app/controllers/app.php');
require_once('app/models/model.php');

// Get the party started
$spr_migrate = \SPR_Migrate\App::get_instance();  
