<?php

namespace App\Providers;

use App\Repositories\Contracts\TransferEventRepositoryInterface;
use App\Repositories\EloquentTransferEventRepository;
use App\Services\Contracts\TransferEventServiceInterface;
use App\Services\TransferEventService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            TransferEventRepositoryInterface::class,
            EloquentTransferEventRepository::class
        );

        // Service bindings
        $this->app->bind(
            TransferEventServiceInterface::class,
            TransferEventService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
