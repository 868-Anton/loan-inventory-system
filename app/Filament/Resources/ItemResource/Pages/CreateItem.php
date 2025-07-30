<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;

    protected function getRedirectUrl(): string
    {
        // If category_id was passed in the query string, redirect back to that category's items
        $categoryId = request()->query('category_id');
        if ($categoryId) {
            return route('categories.items', ['category' => $categoryId]);
        }

        // Otherwise, redirect to the items list
        return $this->getResource()::getUrl('index');
    }
}
