<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InternetConnectionResource\Pages;
use App\Models\InternetConnection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InternetConnectionResource extends Resource
{
    protected static ?string $model = InternetConnection::class;
    protected static ?string $navigationIcon = 'heroicon-o-signal';
    protected static ?string $navigationGroup = 'Monitoring';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Connectivity';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('ISP Account Details')->schema([
                Forms\Components\Select::make('school_id')
                    ->label('School')->relationship('school', 'name')->searchable()->preload()->required(),
                Forms\Components\Select::make('isp')
                    ->options(['PLDT' => 'PLDT', 'Globe' => 'Globe', 'Converge' => 'Converge', 'Sky' => 'Sky', 'Smart' => 'Smart', 'DITO' => 'DITO', 'Others' => 'Others'])
                    ->required(),
                Forms\Components\TextInput::make('account_number')->label('ISP Account No.'),
                Forms\Components\TextInput::make('plan_name')->label('Plan / Package Name'),
                Forms\Components\Select::make('connection_type')
                    ->options(['Fiber' => 'Fiber', 'DSL' => 'DSL', 'Wireless' => 'Wireless', 'LTE' => 'LTE', 'Satellite' => 'Satellite']),
                Forms\Components\Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'])
                    ->default('active'),
                Forms\Components\TextInput::make('ip_address')->label('IP Address'),
                Forms\Components\TextInput::make('monthly_cost')->label('Monthly Cost (₱)')->numeric()->prefix('₱'),
                Forms\Components\DatePicker::make('subscription_start')->label('Subscription Start'),
                Forms\Components\DatePicker::make('subscription_end')->label('Subscription End'),
            ])->columns(2),

            Forms\Components\Section::make('Speed Test Results')->schema([
                Forms\Components\TextInput::make('contracted_download_speed')->label('Contracted Download (Mbps)')->numeric(),
                Forms\Components\TextInput::make('contracted_upload_speed')->label('Contracted Upload (Mbps)')->numeric(),
                Forms\Components\TextInput::make('actual_download_speed')->label('Actual Download (Mbps)')->numeric(),
                Forms\Components\TextInput::make('actual_upload_speed')->label('Actual Upload (Mbps)')->numeric(),
                Forms\Components\TextInput::make('latency_ms')->label('Latency (ms)')->numeric(),
                Forms\Components\DatePicker::make('speed_test_date')->label('Speed Test Date'),
                Forms\Components\Textarea::make('remarks')->rows(2)->columnSpanFull(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('school.name')->label('School')->searchable()->limit(30)->weight('bold'),
                Tables\Columns\TextColumn::make('isp')->badge()->color('primary'),
                Tables\Columns\TextColumn::make('plan_name')->label('Plan'),
                Tables\Columns\TextColumn::make('connection_type')->label('Type')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('actual_download_speed')->label('↓ Mbps')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) : '—')
                    ->color(fn ($state) => $state >= 50 ? 'success' : ($state >= 20 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('actual_upload_speed')->label('↑ Mbps')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) : '—'),
                Tables\Columns\TextColumn::make('latency_ms')->label('Latency ms'),
                Tables\Columns\TextColumn::make('speed_test_date')->label('Last Test')->date(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()->colors(['success' => 'active', 'danger' => 'inactive', 'warning' => 'suspended']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('school')->relationship('school', 'name')->searchable()->preload(),
                Tables\Filters\SelectFilter::make('isp')->options(['PLDT' => 'PLDT', 'Globe' => 'Globe', 'Converge' => 'Converge', 'Sky' => 'Sky', 'Smart' => 'Smart', 'DITO' => 'DITO']),
                Tables\Filters\SelectFilter::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended']),
            ])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->defaultSort('school_id');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInternetConnections::route('/'),
            'create' => Pages\CreateInternetConnection::route('/create'),
            'edit'   => Pages\EditInternetConnection::route('/{record}/edit'),
        ];
    }
}
