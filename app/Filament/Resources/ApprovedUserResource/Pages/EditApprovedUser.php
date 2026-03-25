<?php

namespace App\Filament\Resources\ApprovedUserResource\Pages;

use App\Filament\Resources\ApprovedUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApprovedUser extends EditRecord
{
    protected static string $resource = ApprovedUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
