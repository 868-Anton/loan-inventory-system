<?php

namespace App\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class FilamentThemeServiceProvider extends ServiceProvider
{
  public function register(): void
  {
    //
  }

  public function boot(): void
  {
    FilamentAsset::register([
      Css::make('custom-theme', __DIR__ . '/../../resources/css/filament/admin/theme.css'),
    ]);
  }
}
