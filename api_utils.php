<?php

use OpenAPI\Client\Model\User;
include dirname( __FILE__ ) .'/helper.php';

/**
 * @param User[] $users
 * @throws Exception
 */
function validate_api_func($users)
{
$found = false;
foreach ($users as $user) {
    if ($user->getUsername() === 'akadmin') {
                $found = true;
                break;
            }
    }
if ($found == false)
{
throw new Exception("Does akadmin exist?");
}}

function init_api_func()
{
    if(get_option("api_token")== "")
    {
        wp_die( __( "You do not have provided a valid API token." ) );
    }
// Configure API key authorization: authentik
    $config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setApiKey('Authorization', get_option("api_token"));
// setup prefix (e.g. Bearer) for API key, if needed
    $config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Authorization', 'Bearer');

    $apiInstance = new OpenAPI\Client\Api\CoreApi(
// If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
// This is optional, `GuzzleHttp\Client` will be used as default.
        new GuzzleHttp\Client(),
        $config
    );

    try {
        // random api point for testing
        $result = $apiInstance->coreUsersList()->getResults();
        validate_api_func($result);

    } catch (Exception $e) {
        wp_die('Exception when trying to call the API.' . $e->getMessage());
    }
}

function get_API_instance_func()
{
    $config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setApiKey('Authorization', get_option("api_token"));
    // setup prefix (e.g. Bearer) for API key, if needed
    $config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Authorization', 'Bearer');
    $apiInstance = new OpenAPI\Client\Api\CoreApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
        new GuzzleHttp\Client(),
        $config
    );
    return $apiInstance;
}

