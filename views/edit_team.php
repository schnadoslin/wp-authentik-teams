<?php


use Twig\Environment;
use Twig\Loader\FilesystemLoader;
include_once  dirname( __DIR__ ) .'/helper.php';

/**
 * Displays all team members and allows new members to be added or removed.
 * @param \OpenAPI\Client\Model\User[] $users
 * @param \OpenAPI\Client\Model\Group[] $groups
 * @return string
 * @throws \Twig\Error\LoaderError
 * @throws \Twig\Error\RuntimeError
 * @throws \Twig\Error\SyntaxError
 */
function get_edit_teams_view($users, $groups)
{
    // Determine the current group
    session_start();
    $team_id = $_SESSION['team_id'];
    /** @var \OpenAPI\Client\Model\Group $group */
    $group = array_reduce($groups, function ($carry, $group) use ($team_id) {
        if ($group->getPk() === $team_id) {
            return $group;
        }
        return $carry;
    }, null);

    // Displayfake
    $name = $group->getName();
    $prefixLength = strlen(get_option("group_prefix"));
    if (strpos($name, get_option("group_prefix")) === 0) {
        $group->setName(substr($name, $prefixLength));
    }


    // Initialize Twig-Loader und -Environment
    $loader = new FilesystemLoader('wp-content/plugins/authentik_teams/views/templates');
    $twig = new Environment($loader);

    // Load Functions
    $twig->addFunction(new \Twig\TwigFunction('get_team_name','get_team_name'));
    $twig->addFunction(new \Twig\TwigFunction('asset','asset'));
    $twig->addFunction(new \Twig\TwigFunction('is_in_group','is_in_group'));
    $twig->addFunction(new \Twig\TwigFunction('is_leader','is_leader'));
    $twig->addFunction(new \Twig\TwigFunction('get_user_id','get_user_id'));
    $twig->addFunction(new \Twig\TwigFunction('get_user_name','get_user_name'));

    // load and render template
    $template = $twig->load('edit_team.twig');
    $html = $template->render(array(
        'team_leader' => get_team_leader($group, $users),
        'team_id' => $group->getPk(), // hier muss man den prefix noch entfernen,
        'team' => $group,
        'admin_post_url' => admin_url('admin-post.php'),
        'all_users' => $users

    ));

    return $html;
}


/**
 * Hook for saving changes on team in Authentik
 */
add_action('admin_post_edit_team', 'action_edit_team');

/**
 * Forward changes to Authentik API. Redirects to overview.
 * @return void
 * @throws \OpenAPI\Client\ApiException
 */
function action_edit_team()
{
    $client = get_API_instance_func();
    $users = $client->coreUsersList()->getResults();

    $selectedOptions = $_POST['selectedOptions'];
    $selectedOptions = json_decode(stripslashes($selectedOptions));

    // Security checks
    if(empty($selectedOptions))
    {
        update_option('user_not_in_team', 'You have to be in your own team ...');
        wp_redirect(wp_get_referer());
        exit();
    }
    $matchingUsers = array_filter($users, function ($user) use ($selectedOptions) {
        return in_array($user->getUuid(), $selectedOptions);
    });

    /** @var \OpenAPI\Client\Model\User $user */
    $matchingCurrentUser = array_filter($matchingUsers, function ($user) {
        return $user->getUsername() == wp_get_current_user()->user_login;
    });

    if(empty($matchingCurrentUser))
    {
        update_option('user_not_in_team', 'You have to be in your own team ...');
        wp_redirect(wp_get_referer());
        exit();
    }

// Updating Group
     $group_id = $_POST['team_id'];
     $mygroup = $client->coreGroupsRetrieve($group_id);

    $pk = str_replace("-", "", $group_id);

    $org_users = $mygroup->getUsersObj();

// List of users to be removed (based on user IDs)
    $removeUsers = array_filter($org_users, function ($user) use ($matchingUsers) {
        return !in_array($user->getPk(), array_map(function ($user) {
            return $user->getPk();
        }, $matchingUsers));
    });

// List of users to be added (based on user IDs)
    $newUsers = array_filter($matchingUsers, function ($user) use ($org_users) {
        return !in_array($user->getPk(), array_map(function ($user) {
            return $user->getPk();
        }, $org_users));
    });

// API-CALL: remove
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

// API-CALL: add
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

    // Redirect to overview
    $referer = wp_get_referer();
    $url_without_view = remove_query_arg('view', wp_get_referer());
    header('Location:' . $url_without_view);
    exit;
}


