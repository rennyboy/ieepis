<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Employee;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?int $linkedEmployeeId = null;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->linkedEmployeeId = $data['employee_id'] ?? null;
        unset($data['employee_id']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->linkedEmployeeId === null) {
            return;
        }

        $userId = $this->record->getKey();

        Employee::query()->where('user_id', $userId)->update(['user_id' => null]);

        Employee::query()
            ->whereKey($this->linkedEmployeeId)
            ->update(['user_id' => $userId]);
    }
}
