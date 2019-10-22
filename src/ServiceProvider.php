<?php

namespace Pace\ScheduleList;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Pace\ScheduleList\Console\ScheduleList;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ScheduleList::class]);
        }
    }
}
