<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use App\Models\EquipmentAssignment;
use App\Services\AssignmentService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class EditAssignment extends EditRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        /** @var EquipmentAssignment $record */
        $record = $this->record;

        return [
            Actions\Action::make('return')
                ->label('Return Equipment')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn () => $record->isActive())
                ->form([
                    Forms\Components\DatePicker::make('returned_at')
                        ->label('Date Returned')
                        ->required()
                        ->default(now()),
                    Forms\Components\Textarea::make('notes')
                        ->label('Return Notes')
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->action(function (array $data) use ($record): void {
                    try {
                        app(AssignmentService::class)->return($record, $data, Auth::user());
                        Notification::make()->title('Equipment returned')->success()->send();
                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (RuntimeException $e) {
                        Notification::make()
                            ->title('Cannot return equipment')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
