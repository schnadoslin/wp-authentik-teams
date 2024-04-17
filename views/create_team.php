<?php

use OpenAPI\Client\Model\User;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

include_once  dirname( __DIR__ ) .'/helper.php';

/**
 * A new team can be created with names and group members
 * @return string
 * @throws \OpenAPI\Client\ApiException *
 */
function get_create_teams_view($users, $groups)
{
    // Initialize Twig-Loader und -Environment
    $loader = new FilesystemLoader(WP_PLUGIN_DIR . '/authentik_teams/views/templates');
    $twig = new Environment($loader);
    // Load Functions
    $twig->addFunction(new \Twig\TwigFunction('get_user_name', 'get_user_name'));
    $twig->addFunction(new \Twig\TwigFunction('get_user_id', 'get_user_id'));
    $twig->addFunction(new \Twig\TwigFunction('asset', 'asset'));
    $twig->addFunction(new \Twig\TwigFunction('is_current_user', 'is_current_user'));

    // load and render template
    $template = $twig->load('create_team.twig');
    $html = $template->render(array(
        'all_users' => $users,
        'admin_post_url' => admin_url('admin-post.php'),
        'current_user' => wp_get_current_user()
    ));

    return $html;
}

/**
 * Hook for creating a team in Authentik
 */
add_action('admin_post_create_team', 'action_create_team');

/**
 * Forward creation to Authentik API. Redirects to edit page.
 * @return void
 * @throws \OpenAPI\Client\ApiException
 */
function action_create_team() {
    $client = get_API_instance_func();

    // security checks: Teamname
        $teamname = $_POST['teamname'];
        if (empty($teamname))
        {
            update_option('teamname_taken', 'The team name is empty ...');
            wp_redirect(wp_get_referer());
            exit();
        }

    $groups = $client->coreGroupsList()->getResults();

    $filteredItems = array_filter($groups, function ($item)use ($teamname){return !empty($item->getName()===get_option('group_prefix').$teamname);});
        if (!empty($filteredItems)) {
            update_option('teamname_taken', 'The team name is already taken ...');

            wp_redirect(wp_get_referer());
            exit();
        }

    // security checks: Team-membership
    list($users, $matchingUsers) = init_team_changes($client);

    /** @var User $user */
    $matchingCurrentUser = array_reduce($matchingUsers, function ($carry, $user) {
        if ($user->getUsername() === wp_get_current_user()->user_login) {
            return $user;
        }
        return $carry;
    }, null);

if(empty($matchingCurrentUser))
{
    update_option('user_not_in_team', 'You have to be in your own team ...');
    wp_redirect(wp_get_referer());
    exit();
}

// API-CALL: create group and set teamleader

    $newTeam = $client->coreGroupsCreate(
            new \OpenAPI\Client\Model\GroupRequest(
                    [
                            "name"=>get_option("group_prefix") . $teamname,
                            "users"=>[$matchingCurrentUser->getPk()],
                            "attributes"=>array("Leader" => $matchingCurrentUser->getUsername())
                    ]
            )
    );

// API-CALL: add the users to the  group
foreach ($matchingUsers as $newUser){
    if ($newUser ==$matchingCurrentUser[0])
        continue;

$client->coreGroupsAddUserCreate(str_replace("-", "", $newTeam->getPk()),
new \OpenAPI\Client\Model\UserAccountRequest(
        [
            "pk"=>$newUser->getPk()
        ]
)
);
}

  // Redirect to EditPage of the created team

    session_start();
    $_SESSION['team_id'] = $newTeam->getPk();
    $referer = wp_get_referer();
    $url_without_view = remove_query_arg('view', wp_get_referer());
    header('Location:' . $url_without_view . '?view=edit');
    exit;
}


/**
 * Hook for redirecting to overview. This hook is used in the CREATE and the EDIT Team page.
 */
add_action('admin_post_change_to_view_team', 'change_to_view_team');

/**
 * Redirects to overview
 * @return void
 */
function change_to_view_team()
{
    $referer = wp_get_referer();
    $url_without_view = remove_query_arg('view', wp_get_referer());
    header('Location:' . $url_without_view);
    exit;
}

/**
 * Get team from database and check current User. Used in Create & Edit
 * @param \OpenAPI\Client\Api\CoreApi $client
 * @return array|void
 * @throws \OpenAPI\Client\ApiException
 */
function init_team_changes(\OpenAPI\Client\Api\CoreApi $client)
{
    $users = $client->coreUsersList()->getResults();
    $selectedOptions = $_POST['selectedOptions'];
    $selectedOptions = json_decode(stripslashes($selectedOptions));

    // Sanity checks
    if (empty($selectedOptions)) {
        update_option('user_not_in_team', 'You have to be in your own team ...');
        wp_redirect(wp_get_referer());
        exit();
    }
    $matchingUsers = array_filter($users, function ($user) use ($selectedOptions) {
        return in_array($user->getUuid(), $selectedOptions);
    });
    return array($users, $matchingUsers);
}