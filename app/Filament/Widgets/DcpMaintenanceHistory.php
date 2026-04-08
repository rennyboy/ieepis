<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class DcpMaintenanceHistory extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'DCP Maintenance & Repairs History';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MaintenanceLog::query()->latest('date_performed')
            )
            ->columns([
                Tables\Columns\TextColumn::make('equipment.equipment_type')
                    ->label('Equipment Type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('equipment.property_no')
                    ->label('Property No.')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('technician.name')
                    ->label('Technician'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'resolved' => 'success',
                        'repaired' => 'info',
                        'replaced' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('date_performed')
                    ->label('Date Fixed')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
