<?php


use Twig\Environment;
use Twig\Loader\FilesystemLoader;
include_once  dirname( __DIR__ ) .'/helper.php';

function get_edit_teams_view()
{
    session_start();
    $team_id = $_SESSION['team_id'];


    $client = get_API_instance_func();
    $users = get_filtered_Users($client);

    $group = $client->coreGroupsRetrieve(
        str_replace("-", "", $team_id),
    );

    $name = $group->getName();
    $prefixLength = strlen(get_option("group_prefix"));
    if (strpos($name, get_option("group_prefix")) === 0) {
        $group->setName(substr($name, $prefixLength));
    }

    // Twig-Loader und -Environment initialisieren
    $loader = new FilesystemLoader('wp-content\\plugins\\authentik_teams\\views\\templates');
    $twig = new Environment($loader);
    // Funktionen laden
    $twig->addFunction(new \Twig\TwigFunction('get_team_name','get_team_name'));
    $twig->addFunction(new \Twig\TwigFunction('asset','asset'));
    $twig->addFunction(new \Twig\TwigFunction('is_in_group','is_in_group'));
    $twig->addFunction(new \Twig\TwigFunction('get_user_id','get_user_id'));
    $twig->addFunction(new \Twig\TwigFunction('get_user_name','get_user_name'));


    // Vorlage laden und rendern
    $template = $twig->load('edit_team.twig');

    $html = $template->render(array(
        'team' => $group, // hier muss man den prefix noch entfernen,
        'admin_post_url' => admin_url('admin-post.php'),
        'all_users' => $users

    ));

    return $html;
}



// TODO: edit programmieren und nur für team-leader anzeigbar machen
// TODO: optik - zurück zur Teamübersicht

add_action('admin_post_edit_team', 'action_edit_team');

function action_edit_team()
{


}