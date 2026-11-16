<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\Domain\Cobranca\OrcamentoRepository::class, \Infrastructure\Persistence\EloquentOrcamentoRepository::class);
        $this->app->when(\Application\Cobranca\GerarOrcamento::class)->needs('$urlPublica')->giveConfig('app.url');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
