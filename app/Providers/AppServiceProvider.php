<?php

namespace App\Providers;

use App\Services\AnthropicService;
use App\Services\DocumentService;
use App\Services\RagService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(AnthropicService::class);
        $this->app->singleton(DocumentService::class);
        $this->app->singleton(RagService::class);
    }

    public function boot()
    {
        //
    }
}
