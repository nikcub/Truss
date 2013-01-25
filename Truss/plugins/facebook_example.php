<?php
    /* PHP DEBUG */
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', '1');

    /* Start the session */
    session_start();

    /* Logout Function */
    if ($_GET["logout"] == "true") {
        session_destroy();
    }

    /* Include the Facebook class/sdk */
    require_once 'facebook.php';

    /* Setup some variables */
    $fb_AppID  = "";
    $fb_Secret = "";
    $SiteUrl   = "";

    /* Setup the facebook SDK */
    $facebook = new Facebook( array(
        'appId'  => $fb_AppID,
        'secret' => $fb_Secret,
        'cookies'=> 'true'
    ));

    if ($facebook->getUser()) {
        /* We are authenticated */
        $fb_profile = $facebook->api('/me/?field=name,email');

        echo "<a href='?logout=true'>Logout</a><br/><br/>";
        echo "<pre>";
        print_r($fb_profile);
        echo "</pre>";
    } else {
        /* User is not authenticated */
        $Login_url = $facebook->getLoginUrl(array(
                                    "scope"=>"email",
                                    "redirect_uri"=>$SiteUrl
                                ));

        echo "Please login: <a href='{$Login_url}'>HERE</a>";
    }