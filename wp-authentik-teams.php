<?php
/*
Plugin Name: Authentik WP Teams
Plugin URI: https://github.com/schnadoslin/wp-authentik-teams
Description: Provide Wordpress Team-Management with Authentik IAM.
Version: 0.2
Author: schnadoslin
Author URI: https://github.com/schnadoslin
License: A "Slug" license name e.g. GPL2
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Load vendors
require_once(__DIR__ . '\\codegen\\out\\vendor\\autoload.php');
require_once(__DIR__ . '\\vendor\\autoload.php');


//require_once(__DIR__ . '\\codegen\\out\\lib');
include dirname( __FILE__ ) .'/menu.php';
include dirname( __FILE__ ) .'/api_utils.php';
include dirname( __FILE__ ) .'/views/view_all.php';
include dirname( __FILE__ ) .'/views/create_team.php';
include dirname( __FILE__ ) .'/views/edit_team.php';



function open_wp_auth_teams( $atts )
{
    $view = $_GET['view'] ?? 'default';
    switch ($view) {
        case 'create':
            return get_create_teams_view();
            break;
        case 'edit':
            return get_edit_teams_view();
            break;
        case 'view':
            return get_all_teams_view();
            break;
        default:
            // Laden Sie ein Standard-Twig
            return get_all_teams_view();
            break;
    }
  wp_die("Unknown View requested:". $view ." - The wp_auth_teams plugin is confused.");
}


add_shortcode( "all_teams", "open_wp_auth_teams" );