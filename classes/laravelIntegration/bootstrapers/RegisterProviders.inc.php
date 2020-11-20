<?php


use Illuminate\Contracts\Foundation\Application;

class RegisterProviders
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(PKPLaravelContainer $app)
    {
        $app->registerConfiguredProviders();
    }
}