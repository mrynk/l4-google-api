<?php

/*
 Visit https://code.google.com/apis/console?api=plus to generate your
 oauth2_client_id, oauth2_client_secret, and to register your
 oauth2_redirect_uri.
*/

return array(

    # your generated oauth2_client_id
    'clientId' => null,

    # your generated oauth2_client_secret
    'clientSecret' => null,

    # your generated oauth2_redirect_uri
    'redirectUri' => URL::to('/') . '/', // URL:to('/') doesn't include the trailing slash

    # your developerKey
    'developerKey' => null,

    # when using the auth provider you can limit authentications to a specific domain. Leave null to disable this feature.
    'hd' => null,

    # Application name as registered in the API console
    'applicationName' => null,
    
    # An array of scopes (see https://developers.google.com/apis-explorer/#p/ for available scopes )
    'scopes' => array(
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
    ),
    
    /*
     The effect of this property is documented at
     https://developers.google.com/accounts/docs/OAuth2WebServer#offline;
     if an access token is being requested, the client does not receive
     a refresh token unless offline is specified.
     Possible values for access_type include:
     "offline" to request offline access from the user. (This is the default value)
     "online" to request online access from the user.
    */
    'access_type' => 'offline',

);