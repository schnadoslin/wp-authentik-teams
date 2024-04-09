<?php


use Twig\Environment;
use Twig\Loader\FilesystemLoader;
include_once  dirname( __DIR__ ) .'/helper.php';

function get_edit_teams_view()
{

    // Fügen Sie die Option hinzu, wenn sie noch nicht existiert
    if (!get_option('success')) {
        add_option('success', '');
    }

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
    $twig->addFunction(new \Twig\TwigFunction('is_leader','is_leader'));
    $twig->addFunction(new \Twig\TwigFunction('get_user_id','get_user_id'));
    $twig->addFunction(new \Twig\TwigFunction('get_user_name','get_user_name'));


    // Vorlage laden und rendern
    $template = $twig->load('edit_team.twig');

    $html = $template->render(array(
        'team_id' => $group->getPk(), // hier muss man den prefix noch entfernen,
        'team' => $group,
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
    $client = get_API_instance_func();

    $users = $client->coreUsersList()->getResults();

    $selectedOptions = $_POST['selectedOptions'];
    $selectedOptions = json_decode(stripslashes($selectedOptions));
    if(empty($selectedOptions))
    {
        update_option('user_not_in_team', 'You have to be in your own team ...');
        wp_redirect(wp_get_referer());
        exit();
    }
    $matchingUsers = array_filter($users, function ($user) use ($selectedOptions) {
        return in_array($user->getUuid(), $selectedOptions);
    });

    $matchingCurrentUser = array_filter($matchingUsers, function ($user) {
        return $user['name'] == wp_get_current_user()->display_name;
    });
    if(empty($matchingCurrentUser))
    {
        update_option('user_not_in_team', 'You have to be in your own team ...');
        wp_redirect(wp_get_referer());
        exit();
    }


     $group_id = $_POST['team_id'];
     $mygroup = $client->coreGroupsRetrieve($group_id);

    $pk = str_replace("-", "", $group_id);

    $org_users = $mygroup->getUsersObj();

// Liste der Nutzer, die entfernt werden sollen (basierend auf Benutzer-IDs)
    $removeUsers = array_filter($org_users, function ($user) use ($matchingUsers) {
        return !in_array($user->getPk(), array_map(function ($user) {
            return $user->getPk();
        }, $matchingUsers));
    });

// Liste der Nutzer, die hinzugefügt werden sollen (basierend auf Benutzer-IDs)
    $newUsers = array_filter($matchingUsers, function ($user) use ($org_users) {
        return !in_array($user->getPk(), array_map(function ($user) {
            return $user->getPk();
        }, $org_users));
    });


    foreach ( $removeUsers as $ru) {
        $client->coreGroupsRemoveUserCreate($pk,
            new \OpenAPI\Client\Model\UserAccountRequest(
                [
                    "pk" => $ru->getPk()
                ]
            )

        );
    }
    /* add User */


    foreach ( $newUsers as $nu)
    {
    $client->coreGroupsAddUserCreate($pk,
        new \OpenAPI\Client\Model\UserAccountRequest(
            [
                "pk"=>$nu->getPk()
            ]
        )

    );
    }

    // Redirect to overview after sucessfully changed team
    $referer = wp_get_referer();
    $url_without_view = remove_query_arg('view', wp_get_referer());
    header('Location:' . $url_without_view);
    exit;
}


