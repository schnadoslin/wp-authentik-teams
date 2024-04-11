<?php

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

include_once  dirname( __DIR__ ) .'/helper.php';

/**
 * Displays all team members and team admins in a table.
 * @return string
 * @throws \OpenAPI\Client\ApiException *
 */
function get_all_teams_view($users, $groups)
{
    // Backend initialisieren
    $target_user = wp_get_current_user()->display_name;

    // Dividing all groups into my and other groups
    $mygroups =array_filter($groups, function ($group) use ($target_user) {
        foreach ($group->getUsersObj() as $member) {

            if ($member->getName() === $target_user) {
                return true;
            }
        }return false;}
    );

    $groups = array_filter($groups, function ($group) use ($mygroups) {
        return !in_array($group, $mygroups);
    });

    // Initialize Twig-Loader und -Environment
    $loader = new FilesystemLoader('wp-content/plugins/authentik_teams/views/templates');
    $twig = new Environment($loader);
    // Load Functionen
    $twig->addFunction(new \Twig\TwigFunction('get_team_leader','get_team_leader'));
    $twig->addFunction(new \Twig\TwigFunction('get_team_id','get_team_id'));
    $twig->addFunction(new \Twig\TwigFunction('get_team_name','get_team_name'));
    $twig->addFunction(new \Twig\TwigFunction('asset','asset'));


// load and render template
    $template = $twig->load('view_all.twig');
    $html = $template->render(array(
    'users' => $users,
    'all_teams' => $groups,
    'my_teams' => $mygroups,
    'admin_post_url' => admin_url('admin-post.php')

    ));

    return $html;
}

/**
 * Hook for the Create-Team-Button in Form view_all.twig
 */
add_action('admin_post_change_to_create_team', 'change_to_create_team');

/**
 * Redirect to the Team creation page
 * @return void
 */
function change_to_create_team()
{
    header('Location:' . wp_get_referer() .'?view=create');
    exit;
}

/**
 * Hook for the Edit-Team Page. Triggered by clicking table cells
 */
add_action('admin_post_change_to_edit_team', 'change_to_edit_team');

/**
 * Redirect to the Team creation page, forwarding the selected Teamid
 * @return void
 */
function change_to_edit_team()
{
    session_start();
    $_SESSION['team_id'] = $_POST['team_id'];
    header('Location:' . wp_get_referer() .'?view=edit');
    exit;
}
