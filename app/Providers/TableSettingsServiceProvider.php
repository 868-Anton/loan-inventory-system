<?php

namespace App\Providers;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

class TableSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register custom JavaScript for tables
        FilamentAsset::register([
            Js::make('enhanced-tables', __DIR__ . '/../../resources/js/enhanced-tables.js'),
        ]);

        // Apply global table settings
        $this->configureTableSettings();
    }

    protected function configureTableSettings(): void
    {
        Table::configureUsing(function (Table $table): void {
            // Enable column toggling for all tables by default
            $table->toggleColumnsTriggerAction()
                ->iconButton()
                ->tooltip('Toggle columns');

            // Set default filters layout
            $table->filtersLayout(FiltersLayout::AboveContent);

            // Note: We're using our own custom implementation in enhanced-tables.js
            // to persist column visibility and column order in localStorage
            // The built-in Filament persistence methods were causing issues
            // $table->persistColumnSearchesInSession(true);
            // $table->persistFiltersInSession(true);
            // $table->persistSortInSession(true);

            // Make tables reorderable
            if (!$table->isReorderable()) {
                $table->reorderable('sort_order');
            }
        });
    }
}
