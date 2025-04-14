<?php

namespace App\Providers;

use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use App\Http\Middleware\CheckAdminRole;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Model::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });

        Route::middleware('web')
            ->middleware(CheckAdminRole::class) // Daftar manual untuk dipakai langsung
            ->group(function () {
                // now available as middleware('App\Http\Middleware\CheckAdminRole')
            });

        Route::aliasMiddleware('admin.only', CheckAdminRole::class);
    }
}
