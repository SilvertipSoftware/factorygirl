<?php namespace SilvertipSoftware\FactoryGirl;

use Illuminate\Support\ServiceProvider;


class FactoryGirlServiceProvider extends ServiceProvider {

	public function register()
	{
		// Register the package configuration with the loader.
		//$this->app['config']->package('silvertip/factorygirl', __DIR__.'/../config');

		// Because Laravel doesn't actually set a public path here we'll define out own. This may become
		// a limitation and hopefully will change at a later date.
		//$this->app['path.public'] = realpath($this->app['path.base'].'/'.$this->app['config']->get('basset::public'));

		$this->registerBindings();
	}

	/**
	 * Boot the service provider.
	 * 
	 * @return void
	 */
	public function boot()
	{
	}

	/**
	 * Register the application bindings.
	 * 
	 * @return void
	 */
	public function registerBindings()
	{
		$this->app['factorygirl'] = $this->app->share(function($app)
		{
			return new FactoryGirl($app);
		});
	}
}
