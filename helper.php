<?php


use OpenAPI\Client\Model\Group;
use OpenAPI\Client\Model\User;
use OpenAPI\Client\Api\CoreApi;
/**
 *  Return filtered users. Users are filtered by following aspects:
 *  - only active users
 *  - only users without a given admin_prefix (settings)

 * @param  CoreApi $client
 * @return User[]
 */
function get_filtered_Users($client)
{
    $users = $client->coreUsersList();

    $users = $users->getResults();


    if (get_option('admin_prefix'))
    {       // remove admin_prefix
        $users = array_filter($users, function ($item) {
            return strncmp($item->getUsername(), get_option('admin_prefix'), strlen(get_option('admin_prefix'))) !== 0;
        });
    }
    // remove default ak - users (as outposts)
    $users = array_filter($users, function ($item) {
        return  strncmp($item->getUsername(), "ak-", strlen("ak-")) !==0;
    });

    if (get_option('ony_allow_active'))
    {       // remove admin_prefix
        $users = array_filter($users, function ($item) {
            return $item->getIsActive();
        });
    }

    return $users;
}


/**
 * Return filtered groups. Groups are filtered by following aspects:
 * - only non-empty groups
 * - only groups with a given prefix (settings)
 * @param $client
 * @return Group[]
 */
function get_filtered_groups($client)
{
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
    return $groups;
}

// TWIG - Funktions.

/**
 * Checks if User is in Group
 * @param User $target_user
 * @param Group $group
 * @return string
 */
function is_in_group($target_user,$group)
{
    $filtered_objects = array_filter($group->getUsersObj(), function ($user) use ($target_user) {
        return $user->getUsername() === $target_user->getUsername();
    });

    return !empty($filtered_objects);}


/**
 * Returns the Users Displayname of the Teamlead of the passed group
 * @param \OpenAPI\Client\Model\Group $group
 * @param \OpenAPI\Client\Model\User[] $users
 * @return string
 * @throws Exception
 */
function get_team_leader($group, $users) {
    // Konzept: Username der TeamLeader werden in Gruppenattributen als Leader gespeichert.
    $leader = null;
    if (isset($group->getAttributes()['Leader'])) {
        $leader = $group->getAttributes()['Leader'];
    } else {
        throw new Exception("no team leader found");
        //$leader = $group->getUsersObj()[0]->getUsername();
    }

    foreach ($users as $u)
    {
        if($u->getUsername()==$leader)
            return  $u->getName();
    }
    throw new Exception("no user found with " . $leader . " as username");
}


/**
 * Returns if the current logged in Wordpress-User is the Leader of the passed Authentik Group
 * @param Users $users
 * @param Group $group
 * @return string
 */
function is_leader($group, $users)
{
    $leader = $group->getAttributes()["Leader"];

    $cur = get_current_Authentik_User($users);
    if ( $cur->getUsername() == $leader )
        return true;

     return false;}


/**
 * @param Group $group
 * @return string
 */
function get_team_name($group) {
    return $group->getName();
}
/**
 * @param Group $group
 * @return string
 */
function get_team_id($group) {
    return $group->getPk();
}

/**
 * @param User $user
 * @return mixed
 */
function get_user_name($user)
{
    return $user->getName();
}

/**
 * @param User $user
 * @return mixed
 */
function get_user_id($user)
{
    return $user->getUuid();
}
/**
 * returns if the passed Authentik-User is the Wordpress User by comparing the Wordpress Username
 * @param User $user
 * @return mixed
 */
function is_current_user($user)
{
    $current_user = wp_get_current_user()->user_login;
    if ($user->getUsername() == $current_user)
        return true;
    return false;
}

/**
 * returns the Authentik User who is the logged in Wordpress User
 * @param User[] $users filtered Users
 * @return mixed
 */
function get_current_Authentik_User($users)
{
    $matchingCurrentUser = array_reduce($users, function ($carry, $user) {
        if (is_current_user($user)) {
            return $user;
        }
        return $carry;
    }, null);

    return $matchingCurrentUser;
}

// design and javascript linker

/**
 * returns the JavaScript or CSS files in the view directory
 * @param $cssfile
 * @return string
 */
function asset($file) {
    return WP_CONTENT_URL  . '/plugins/authentik_teams/views/' . $file;
}