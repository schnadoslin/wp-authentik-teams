<?php

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

include_once  dirname( __DIR__ ) .'/helper.php';


function get_team_leader($group) {
    // Konzept: Username der TeamLeader werden in Gruppenattributen als Leader gespeichert.
    $leader = null;
    if (isset($group->getAttributes()['Leader'])) {
        $leader = $group->getAttributes()['Leader'];
    } else {
        $leader = $group->getUsersObj()[0]->getUsername();
    }
    return get_auth_user_by_username($leader)->getName();
}




/**
 * Zeigt in einer Tabelle alle Teammitglieder und die Teamadmins an.
 * @return string
 * @throws \OpenAPI\Client\ApiException *
 */
function get_all_teams_view()
{
    // Backend initialisieren

    $client = get_API_instance_func();
    $groups = $client->coreGroupsList()->getResults();
    $groups = array_filter($groups, function ($item){return !empty($item->getUsersObj());});


    if (get_option("group_prefix")) {
        $groups = array_filter($groups, function ($item) {
            return strpos($item->getName(), get_option("group_prefix")) === 0;
        });
        $prefixLength = strlen(get_option("group_prefix"));
        foreach ($groups as $item) {
            $name = $item->getName();
            if (strpos($name, get_option("group_prefix")) === 0) {
                $item->setName(substr($name, $prefixLength));
            }
        }
   }
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
