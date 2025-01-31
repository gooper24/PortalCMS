<?php

use PortalCMS\Controllers\LoginController;
use PortalCMS\Core\Session\Session;

require $_SERVER['DOCUMENT_ROOT'] . '/Init.php';

require 'config.php';

$helper = $fb->getRedirectLoginHelper();

try {
    $accessToken = $helper->getAccessToken();
} catch (Facebook\Exceptions\FacebookResponseException $e) {
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}

if (!isset($accessToken)) {
    if ($helper->getError()) {
        header('HTTP/1.0 401 Unauthorized');
        echo 'Error: ' . $helper->getError() . "\n";
        echo 'Error Code: ' . $helper->getErrorCode() . "\n";
        echo 'Error Reason: ' . $helper->getErrorReason() . "\n";
        echo 'Error Description: ' . $helper->getErrorDescription() . "\n";
    } else {
        header('HTTP/1.0 400 Bad Request');
        echo 'Bad request';
    }
    exit;
}

// Logged in
// echo '<h3>Access Token</h3>';
// var_dump($accessToken->getValue());

// The OAuth 2.0 client handler helps us manage access tokens
$oAuth2Client = $fb->getOAuth2Client();

// // Get the access token metadata from /debug_token
// $tokenMetadata = $oAuth2Client->debugToken($accessToken);
// echo '<h3>Metadata</h3>';
// var_dump($tokenMetadata);

// // Validation (these will throw FacebookSDKException's when they fail)
// $tokenMetadata->validateAppId(FB_APP_ID);
// // If you know the user ID this access token belongs to, you can validate it here
// //$tokenMetadata->validateUserId('123');
// $tokenMetadata->validateExpiration();

if (!$accessToken->isLongLived()) {
    // Exchanges a short-lived access token for a long-lived one
    try {
        $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        Session::add('feedback_negative', 'Error getting long-lived access token: ' . $e->getMessage());
        exit;
    }
    // echo '<h3>Long-lived</h3>';
    // var_dump($accessToken->getValue());
}

$_SESSION['fb_access_token'] = (string) $accessToken;

try {
    $response = $fb->get('/me?fields=id,name,email', $_SESSION['fb_access_token']);
} catch (Facebook\Exceptions\FacebookResponseException $e) {
    Session::add('feedback_negative', 'Graph returned an error: ' . $e->getMessage());
    exit;
} catch (Facebook\Exceptions\FacebookSDKException $e) {
    Session::add('feedback_negative', 'Facebook SDK returned an error: ' . $e->getMessage());
    exit;
}

$user = $response->getGraphUser();
LoginController::loginWithFacebook($user['id']);
