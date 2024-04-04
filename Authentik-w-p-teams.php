<?php

/*
Plugin Name: Authentik WP Teams
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Provide Wordpress Team-Management with Authentik IAM.
Version: 1.0
Author: schnadoslin
Author URI: https://github.com/schnadoslin
License: A "Slug" license name e.g. GPL2
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once(__DIR__ . '\\codegen\\out\\vendor\\autoload.php');
//require_once(__DIR__ . '\\codegen\\out\\lib');
include dirname( __FILE__ ) .'/menu.php';
include dirname( __FILE__ ) .'/api_utils.php';




function show_all_teams_func( $atts ) {
    return "test demo";
}


add_shortcode( "all_teams", "show_all_teams_func" );