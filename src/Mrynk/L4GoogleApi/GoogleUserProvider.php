<?php namespace Mrynk\L4GoogleApi;

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\UserProviderInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class GoogleUserProvider implements UserProviderInterface
{

    function __construct()
    {
        $this->client = App::make('google-client');
        $this->oauth2 = new \Google_Service_Oauth2( $this->client );

        if ( Session::has( $this->getTokenName() ) )
        {
            $this->client->setAccessToken( Session::get( $this->getTokenName() ) );
        }
    }

    public function retrieveByToken( $identifier, $token ){}
    public function updateRememberToken( UserInterface $user, $token ){}


    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed $identifier
     * @return \Illuminate\Auth\UserInterface|null
     */
    public function retrieveById( $identifier )
    {
        $user = $this->retrieveByCredentials(array());
        if ($user && $user->getAuthIdentifier() == $identifier) {
            return $user;
        }
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array $credentials
     * @return \Illuminate\Auth\UserInterface|null
     */
    public function retrieveByCredentials( array $credentials )
    {
        if ($this->client->getAccessToken()) {

            if( $this->client->isAccessTokenExpired() )
                if( Session::get( $this->getTokenName() . '_refresh' ) )
                    $this->client->refreshToken( Session::get( $this->getTokenName() . '_refresh' ) );
                else {
                    \Session::forget( $this->getTokenName() );
                    return null;
                }


            try {
                $userinfo = $this->oauth2->userinfo->get();
            } catch( Exception $e )
            {
                \Session::forget( $this->getTokenName() );
                return null;
            }
            $user = $this->createModel()->newQuery()->where( 'google_id', $userinfo->id )->first();


            if( !$user )
            {
                $user = $this->createModel();
            }
            if( $userinfo )
            {
                $fillWith = (array) $userinfo;
                foreach( \Config::get('l4-google-api::map_user_data') as $k => $v )
                {
                    $fillWith[ $v ] = $userinfo->$k;
                    unset( $fillWith[ $k ] );
                }

                if( \Session::has( $this->getTokenName() . '_refresh' ) && \Session::get( $this->getTokenName() . '_refresh' ) )
                    $user->refresh_token = \Session::get( $this->getTokenName() . '_refresh' );
                $user->fill( $fillWith )->save();

                if( $user->refresh_token )
                    Session::put( $this->getTokenName() . '_refresh', $user->refresh_token );
            }

            return $user;
            //return new GenericUser( (array) $userinfo );
        }
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Auth\UserInterface $user
     * @param  array $credentials
     * @return bool
     */
    public function validateCredentials( UserInterface $user, array $credentials )
    {
        // this method doesn't make sense for Google auth
        return false;
    }

    public function getAuthUrl()
    {
        return $this->client->createAuthUrl() . ( \Config::get('l4-google-api::hd') ? '&hd='.\Config::get('l4-google-api::hd') : '' );
    }


    /**
     * Get a unique identifier for the auth session value.
     *
     * @return string
     */
    public function getTokenName()
    {
        return 'googleauth_' . md5( get_class( $this ) );
    }

    /**
     * If this request is the redirect from a successful authorization grant, store the access token in the session
     * and return a Laravel redirect Response to send the user to their requested page. Otherwise returns null
     * @return Response or null
     */
    public function finishAuthenticationIfRequired()
    {
        if ( isset( $_GET['code'] ) )
        {
            $this->client->authenticate( $_GET['code'] );
            $data = json_decode( $this->client->getAccessToken(), true );
            Session::put( $this->getTokenName(), $this->client->getAccessToken() );
            if( isset( $data['refresh_token'] ) && $data['refresh_token'] )
                Session::put( $this->getTokenName() . '_refresh', $data['refresh_token'] );
            else
                Session::forget( $this->getTokenName() . '_refresh' );

            // strip the querystring from the current URL
            $url = rtrim(preg_replace('|&?code=[^&]+|', '', URL::full()), '?');

            return Redirect::to(filter_var($url, FILTER_SANITIZE_URL));
        }
        return null;
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        $class = '\\'.ltrim( \Config::get('auth.model'), '\\');

        return new $class;
    }

}