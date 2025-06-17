<?php

namespace Viterzbayraku\Filament;

use Filament\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;
use function config_path;
use function public_path;

class FilamentTitleWithSlugServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Публікація конфіга
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__.'/../config/filament-title-with-slug.php' => config_path('filament-title-with-slug.php'),
            ], 'config');
        }

        // Завантаження views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-title-with-slug');

        // Завантаження перекладів
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'filament-title-with-slug');

        // Публікація стилів (опційно)
        if (function_exists('public_path')) {
            $this->publishes([
                __DIR__.'/../resources/css/filament-title-with-slug.css' => public_path('vendor/filament-title-with-slug/filament-title-with-slug.css'),
            ], 'public');
        }

        // Підключення кастомного CSS для Tabs
        if (class_exists(\Filament\Facades\FilamentAsset::class)) {
            FilamentAsset::register([
                __DIR__.'/../resources/css/filament-title-with-slug-tabs.css',
            ], 'viterzbayraku-filament-title-with-slug');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/filament-title-with-slug.php',
            'filament-title-with-slug'
        );
    }
}
