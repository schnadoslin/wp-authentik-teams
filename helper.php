<?php


use OpenAPI\Client\Model\Group;
use OpenAPI\Client\Model\User;
use OpenAPI\Client\Api\CoreApi;
/**
 * @param  CoreApi $client
 * @return User[]
 */
function get_filtered_Users($client)
{
    $users = $client->coreUsersList()->getResults();
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
 * @param $username
 * @return User
 */
function get_auth_user_by_username ($username)
{
    $client = get_API_instance_func();
    foreach (array_filter($client->coreUsersList()->getResults()) as $item)
    {
        if ($item->getUsername()==$username)
            return $item;
    }


    /*$filtered = array_filter($client->coreUsersList()->getResults(),function ($item) use ($username){return $item->getUsername()==$username;});
    throw new Exception($filtered ."User not found by username " . $username);
    if(empty($filtered))*/
    throw new Exception("User not found by username " . $username);
    /*else
        return $filtered[0];*/
}


// TWIG - Funktions.

/**
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
 * @param User $target_user
 * @param Group $group
 * @return string
 */
function is_leader($group)
{
    $leader = $group->getAttributes()["Leader"];
    $cur = get_current_Authentik_User();

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
 * @param User $user
 * @return mixed
 */
function is_current_user($user)
{
    $current_user = wp_get_current_user()->display_name;
    if ($user->getName() == $current_user)
        return true;
    return false;
}

/**
 * @return User
 */
function get_current_Authentik_User()
{
    $client = get_API_instance_func();
    $users = get_filtered_Users($client);
    $matchingCurrentUser = array_filter($users, function ($user) {
        return $user['name'] == wp_get_current_user()->display_name;
    });
    return $matchingCurrentUser[0];
}

// design and javascript linker

function asset($cssfile) {
    return WP_CONTENT_URL  . '/plugins/authentik_teams/views/' . $cssfile;
}