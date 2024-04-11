<?php

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

include_once  dirname( __DIR__ ) .'/helper.php';

/**
 * Zeigt in einer Tabelle alle Teammitglieder und die Teamadmins an.
 * @return string
 * @throws \OpenAPI\Client\ApiException *
 */
function get_all_teams_view($users, $groups)
{
    // Backend initialisieren
    $target_user = wp_get_current_user()->display_name;

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

    // Twig-Loader und -Environment initialisieren
    $loader = new FilesystemLoader('wp-content/plugins/authentik_teams/views/templates');
    $twig = new Environment($loader);
    // Funktionen laden
    $twig->addFunction(new \Twig\TwigFunction('get_team_leader','get_team_leader'));
    $twig->addFunction(new \Twig\TwigFunction('get_team_id','get_team_id'));
    $twig->addFunction(new \Twig\TwigFunction('get_team_name','get_team_name'));
    $twig->addFunction(new \Twig\TwigFunction('asset','asset'));


    // Vorlage laden und rendern
    $template = $twig->load('view_all.twig');

    $html = $template->render(array(
    'users' => $users,
    'all_teams' => $groups,
    'my_teams' => $mygroups,
    'admin_post_url' => admin_url('admin-post.php')

    ));

    return $html;
}

add_action('admin_post_change_to_create_team', 'change_to_create_team');

function change_to_create_team()
{
    header('Location:' . wp_get_referer() .'?view=create');
    exit;
}


add_action('admin_post_change_to_edit_team', 'change_to_edit_team');

function change_to_edit_team()
{
    session_start();
    $_SESSION['team_id'] = $_POST['team_id'];
    header('Location:' . wp_get_referer() .'?view=edit');
    exit;
}
