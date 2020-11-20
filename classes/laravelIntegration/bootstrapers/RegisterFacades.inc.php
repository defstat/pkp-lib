<?php

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Facade;

class RegisterFacades
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(PKPLaravelContainer $app)
    {
        Facade::clearResolvedInstances();

        Facade::setFacadeApplication($app);
    }
}