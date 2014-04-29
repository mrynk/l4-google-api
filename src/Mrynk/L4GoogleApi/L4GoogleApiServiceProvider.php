<?php namespace Mrynk\L4GoogleApi;

use Illuminate\Auth\AuthServiceProvider;
use Mrynk\L4GoogleApi\GoogleUserProvider;
use Mrynk\L4GoogleApi\GoogleAuthGuard;

class L4GoogleApiServiceProvider extends AuthServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('mrynk/l4-google-api');
		parent::boot();
		$this->app['auth']->extend('google', function( $app )
		{
            return new GoogleAuthGuard( new GoogleUserProvider(), $app['session.store'] );
        });
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		parent::register();

		$app = $this->app;

        $app['google-client'] = $app->share(function($app)
        {
            $client = new \Google_Client();
            $client->setApplicationName($app['config']->get('l4-google-api::clientId'));
            $client->setClientId($app['config']->get('l4-google-api::clientId'));
            $client->setClientSecret($app['config']->get('l4-google-api::clientSecret'));
            $client->setRedirectUri($app['config']->get('l4-google-api::redirectUri'));
            $client->setDeveloperKey($app['config']->get('l4-google-api::developerKey'));
            $client->setScopes($app['config']->get('l4-google-api::scopes', array(
                'https://www.googleapis.com/auth/userinfo.profile',
                'https://www.googleapis.com/auth/userinfo.email',
            )));
            $client->setAccessType($app['config']->get( 'l4-google-api::access_type', 'offline' ) );

            return $client;
        });

        $app['router']->filter('google-finish-authentication', function( $route, $request ) use ( $app )
        {
            return $app['auth']->finishAuthenticationIfRequired();
        });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
