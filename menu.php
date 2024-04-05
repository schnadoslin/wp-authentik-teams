<?php

// Register the menu.
add_action( "admin_menu", "authentik_team_plugin_menu_func" );
function authentik_team_plugin_menu_func() {
    add_submenu_page( "options-general.php",  // Which menu parent
        "Authentik Teams",            // Page title
        "Authentik Teams",            // Menu title
        "manage_options",       // Minimum capability (manage_options is an easy way to target administrators)
        "authentik_teams",            // Menu slug
        "authentik_team_plugin_options"     // Callback that prints the markup
    );
}

// Print the markup for the page
function authentik_team_plugin_options() {
    if ( !current_user_can( "manage_options" ) )  {
        wp_die( __( "You do not have sufficient permissions to access this page." ) );
    }
    if ( isset($_GET['status']) && $_GET['status']=='success') {
        ?>
        <div id="message" class="updated notice is-dismissible">
            <p><?php _e("Settings updated!", "github-api"); ?></p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php _e("Dismiss this notice.", "github-api"); ?></span>
            </button>
        </div>
        <?php
    }

    ?>
    <form method="post" action="<?php echo admin_url( 'admin-post.php'); ?>">

        <input type="hidden" name="action" value="update_authentik_settings" />

        <h3>Authentik connection settings</h3>

        <p>
            <label><?php _e("Authentik API Token:", "authentik-teams"); ?></label>
            <input class="" size = 100 type="text" name="api_token" value="<?php echo get_option('api_token')?>" />
        </p>
        <p>
            <label><?php _e("Authentik Base URL:", "authentik-teams"); ?></label>
            <input class="" size = 100 type="text" name="base_url" value="<?php echo get_option('base_url')?>" />
        </p>
        <p>
            <label><?php _e("Admin Prefix:", "authentik-teams"); ?></label>
            <input class="" size = 100 type="text" name="admin_prefix" value="<?php echo get_option('admin_prefix')?>" />
        </p>
        <p>
            <label><?php _e("Only Allow active users:", "authentik-teams"); ?></label>
            <input class="" size = 100 type="checkbox" name="ony_allow_active" <?php checked(1, get_option('ony_allow_active'), true); ?> value="1" />
        </p>
        <p>
            <label><?php _e("Group Prefix:", "authentik-teams"); ?></label>
            <input class="" size = 100 type="text" name="group_prefix" value="<?php echo get_option('group_prefix')?>" />
        </p>

        <input class="button button-primary" type="submit" value="<?php _e("Save", "authentik-teams"); ?>" />
        <h4>Additional Requirements</h4>
        <ul>
            <li>Default Admin: akadmin has to exist
            </li>
        </ul>
    </form>
    <?php

}

add_action( 'admin_post_update_authentik_settings', 'authentik_handle_save' );

function authentik_handle_save()
{
    // If the form was just submitted, save the values
    if (isset($_POST["api_token"]) &&
        isset($_POST["base_url"])
    ) {

        update_option("api_token", $_POST["api_token"], TRUE);
        update_option("base_url", $_POST["base_url"], TRUE);
        update_option("admin_prefix", $_POST["admin_prefix"], TRUE);
        update_option("ony_allow_active", $_POST["ony_allow_active"], TRUE);
        update_option("group_prefix", $_POST["group_prefix"], TRUE);

    }

    // API - Check
    if(get_option("api_token") != "")
    {
        init_api_func();
    }
    // Redirect back to settings page
    // The ?page=github corresponds to the "slug"
    // set in the fourth parameter of add_submenu_page() above.

    $redirect_url = get_bloginfo("url") . "/wp-admin/options-general.php?page=authentik_teams&status=success";
    header("Location: ".$redirect_url);
    exit;
}

function generate_code_func()
{
    $ch = curl_init(get_option("base_url")+"/api/v3/schema/");
    $fp = fopen("myspecs.yaml", "w");

    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    curl_exec($ch);
    if(curl_error($ch)) {
        fwrite($fp, curl_error($ch));
    }
    curl_close($ch);
    fclose($fp);

    shell_exec('sh ./generate_php_api.sh');
}



