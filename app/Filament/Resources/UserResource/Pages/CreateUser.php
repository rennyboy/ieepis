<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Employee;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?int $linkedEmployeeId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->linkedEmployeeId = $data['employee_id'] ?? null;
        unset($data['employee_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->linkedEmployeeId === null) {
            return;
        }

        Employee::query()
            ->whereKey($this->linkedEmployeeId)
            ->update(['user_id' => $this->record->getKey()]);
    }
}
