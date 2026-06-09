<?php

namespace App\Filament\Resources\PpePhysicalCountResource\Pages;

use App\Filament\Resources\PpePhysicalCountResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPpePhysicalCount extends ViewRecord
{
    protected static string $resource = PpePhysicalCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('print_pdf')
                ->label('Print PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('ppe-physical-count.pdf', $this->record))
                ->openUrlInNewTab(),

            Actions\Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn () => route('ppe-physical-count.excel', $this->record))
                ->openUrlInNewTab(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Physical Count Information')
                ->schema([
                    Infolists\Components\TextEntry::make('count_number')
                        ->label('Count No.')
                        ->fontFamily('mono')
                        ->color('primary')
                        ->weight('bold'),

                    Infolists\Components\TextEntry::make('school.name')
                        ->label('Office / School'),

                    Infolists\Components\TextEntry::make('location')
                        ->label('Building / Location')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('inventory_date')
                        ->label('Inventory Date')
                        ->date(),

                    Infolists\Components\TextEntry::make('inventory_period')
                        ->label('Inventory Period')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (PhysicalCountStatus $state): string => $state->label())
                        ->color(fn (PhysicalCountStatus $state): string => $state->color()),

                    Infolists\Components\TextEntry::make('conductedByEmployee.full_name')
                        ->label('Conducted By')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('verifiedByEmployee.full_name')
                        ->label('Verified By')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('remarks')
                        ->label('Remarks')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Infolists\Components\Section::make('Summary')
                ->schema([
                    Infolists\Components\TextEntry::make('total_items_count')
                        ->label('Total PPE Items Counted')
                        ->weight('bold')
                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                    Infolists\Components\TextEntry::make('total_shortage_value')
                        ->label('Total Shortage Value')
                        ->money('PHP')
                        ->color('danger')
                        ->weight('bold')
                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                    Infolists\Components\TextEntry::make('total_overage_value')
                        ->label('Total Overage Value')
                        ->money('PHP')
                        ->color('success')
                        ->weight('bold')
                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                ])
                ->columns(3),

            Infolists\Components\Section::make('PPE Items')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('items')
                        ->schema([
                            Infolists\Components\TextEntry::make('article')
                                ->label('Article'),

                            Infolists\Components\TextEntry::make('description')
                                ->label('Description')
                                ->placeholder('—'),

                            Infolists\Components\TextEntry::make('property_number')
                                ->label('Property No.')
                                ->fontFamily('mono'),

                            Infolists\Components\TextEntry::make('unit_of_measure')
                                ->label('UoM'),

                            Infolists\Components\TextEntry::make('unit_value')
                                ->label('Unit Value')
                                ->money('PHP'),

                            Infolists\Components\TextEntry::make('quantity_property_card')
                                ->label('Qty (Card)'),

                            Infolists\Components\TextEntry::make('quantity_physical_count')
                                ->label('Qty (Count)'),

                            Infolists\Components\TextEntry::make('shortage_quantity')
                                ->label('Shortage Qty')
                                ->color(fn ($state) => $state > 0 ? 'danger' : null),

                            Infolists\Components\TextEntry::make('shortage_value')
                                ->label('Shortage Value')
                                ->money('PHP')
                                ->color(fn ($state) => $state > 0 ? 'danger' : null),

                            Infolists\Components\TextEntry::make('overage_quantity')
                                ->label('Overage Qty')
                                ->color(fn ($state) => $state > 0 ? 'success' : null),

                            Infolists\Components\TextEntry::make('overage_value')
                                ->label('Overage Value')
                                ->money('PHP')
                                ->color(fn ($state) => $state > 0 ? 'success' : null),

                            Infolists\Components\TextEntry::make('remarks')
                                ->label('Remarks')
                                ->placeholder('—'),
                        ])
                        ->columns(4),
                ]),
        ]);
    }
}
