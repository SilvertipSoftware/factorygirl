<?php namespace SilvertipSoftware\FactoryGirl\Facades;

use Illuminate\Support\Facades\Facade;

class FactoryGirl extends Facade {

    protected static function getFacadeAccessor() {
        return 'factorygirl';
    }

}
