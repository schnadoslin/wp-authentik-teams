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
require_once(__DIR__ . '/codegen/out/vendor/autoload.php');
require_once(__DIR__ . '/vendor/autoload.php');


//require_once(__DIR__ . '\\codegen\\out\\lib');
include dirname( __FILE__ ) .'/menu.php';
include dirname( __FILE__ ) .'/api_utils.php';
include dirname( __FILE__ ) .'/views/view_all.php';
include dirname( __FILE__ ) .'/views/create_team.php';
include dirname( __FILE__ ) .'/views/edit_team.php';



function open_wp_auth_teams( $atts )
{
    // Load all data once
    $client = get_API_instance_func();
    $users = get_filtered_Users($client);
    $groups = get_filtered_groups($client);

    // ERROR-Options
    if (!get_option('teamname_taken')) {
        add_option('teamname_taken', '');
    }
    // Fügen Sie die Option hinzu, wenn sie noch nicht existiert
    if (!get_option('user_not_in_team')) {
        add_option('user_not_in_team', '');
    }

    // ROUTING
    $view = $_GET['view'] ?? 'default';
    switch ($view) {
        case 'create':
            return get_create_teams_view($users, $groups);
            break;
        case 'edit':
            return get_edit_teams_view($users, $groups);
            break;
        case 'view':
            return get_all_teams_view($users, $groups);
            break;
        default:
            // Laden Sie ein Standard-Twig
            return get_all_teams_view($users, $groups);
            break;
    }
  wp_die("Unknown View requested:". $view ." - The wp_auth_teams plugin is confused.");
}

// WORDPRESS SHORTCODE TO USE THE PLUGIN
add_shortcode( "all_teams", "open_wp_auth_teams" );



// ERROR Handling:
add_action('wp_footer', 'my_error_notice');
/**
 * If an ERROR-OPTION changed, a javascript alert will pop up.
 * @return void
 */
function my_error_notice() {
    $errortypes = ['teamname_taken','user_not_in_team' ] ;
    $error = [];
    foreach ($errortypes as $errortype)
    {
        $error = get_option($errortype);
        if (!empty($error)) {
            ?>
            <script type="text/javascript">
                alert('<?php echo $error; ?>');
            </script>
            <?php
            update_option($errortype, '');
        }
    }
}