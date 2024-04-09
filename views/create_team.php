<?php

use OpenAPI\Client\Model\User;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

include_once  dirname( __DIR__ ) .'/helper.php';







/**
 * Zeigt in einer Tabelle alle Teammitglieder und die Teamadmins an.
 * @return string
 * @throws \OpenAPI\Client\ApiException *
 */
function get_create_teams_view()
{
    // Fügen Sie die Option hinzu, wenn sie noch nicht existiert
    if (!get_option('teamname_taken')) {
        add_option('teamname_taken', '');
    }
    // Fügen Sie die Option hinzu, wenn sie noch nicht existiert
    if (!get_option('user_not_in_team')) {
        add_option('user_not_in_team', '');
    }

    // Backend initialisieren

    $client = get_API_instance_func();
    $users = get_filtered_Users($client);

    // Twig-Loader und -Environment initialisieren
    $loader = new FilesystemLoader('wp-content\\plugins\\authentik_teams\\views\\templates');
    $twig = new Environment($loader);
    // Funktionen laden
    $twig->addFunction(new \Twig\TwigFunction('get_user_name', 'get_user_name'));
    $twig->addFunction(new \Twig\TwigFunction('get_user_id', 'get_user_id'));
    $twig->addFunction(new \Twig\TwigFunction('asset', 'asset'));
    $twig->addFunction(new \Twig\TwigFunction('is_current_user', 'is_current_user'));


    // Vorlage laden und rendern
    $template = $twig->load('create_team.twig');

    $html = $template->render(array(
        'all_users' => $users,
        'admin_post_url' => admin_url('admin-post.php'),
        'current_user' => wp_get_current_user()
    ));

    return $html;
}

add_action('admin_post_create_team', 'action_create_team');

function action_create_team() {

        $teamname = $_POST['teamname'];
        if (empty($teamname))
        {
            update_option('teamname_taken', 'The team name is empty ...');
            wp_redirect(wp_get_referer());
            exit();
        }
        // Checks
        $client = get_API_instance_func();
        $groups = $client->coreGroupsList()->getResults();

    $filteredItems = array_filter($groups, function ($item)use ($teamname){return !empty($item->getName()===get_option('group_prefix').$teamname);});
        if (!empty($filteredItems)) {
            update_option('teamname_taken', 'The team name is already taken ...');

            wp_redirect(wp_get_referer());
            exit();
        }
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

    $newTeam = $client->coreGroupsCreate(
            new \OpenAPI\Client\Model\GroupRequest(
                    [
                            "name"=>get_option("group_prefix") . $teamname,
                            "users"=>[$matchingCurrentUser[0]->getPk()],
                            "attributes"=>array("Leader" => $matchingCurrentUser[0]->getUsername())
                    ]
            )
    );

foreach (   $matchingUsers as $newUser)
    if ($newUser ==$matchingCurrentUser[0])
        continue;
$client->coreGroupsAddUserCreate(str_replace("-", "", $newTeam->getPk()),
new \OpenAPI\Client\Model\UserAccountRequest(
        [
            "pk"=>$newUser->getPk()
        ]
)
);

    session_start();
    $_SESSION['team_id'] = $newTeam->getPk();
    $referer = wp_get_referer();
    $url_without_view = remove_query_arg('view', wp_get_referer());
    header('Location:' . $url_without_view . '?view=edit');
    exit;
}

add_action('wp_footer', 'my_error_notice');
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

add_action('admin_post_change_to_view_team', 'change_to_view_team');

function change_to_view_team()
{
    $referer = wp_get_referer();
    $url_without_view = remove_query_arg('view', wp_get_referer());
    header('Location:' . $url_without_view);
    exit;
}
