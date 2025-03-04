<?php

namespace Vangelis\RepoPHP\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

class RepoPHP extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'repophp';
    }
}
