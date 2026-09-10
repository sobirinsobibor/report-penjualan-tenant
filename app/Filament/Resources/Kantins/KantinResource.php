<?php

namespace App\Filament\Resources\Kantins;

use App\Filament\Resources\Kantins\Pages\ManageKantins;
use App\Models\Kantin;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class KantinResource extends Resource
{
    protected static ?string $model = Kantin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static UnitEnum|string|null $navigationGroup = 'Data Master';

    protected static ?string $modelLabel = 'Kantin';

    protected static ?string $pluralModelLabel = 'Data Kantin';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAbility('kantin.view_any') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAbility('kantin.create') ?? false;
    }

    public static function canEdit(mixed $record): bool
    {
        return auth()->user()?->hasAbility('kantin.update') ?? false;
    }

    public static function canDelete(mixed $record): bool
    {
        return auth()->user()?->hasAbility('kantin.delete') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Kantin')
                    ->placeholder('Misal: Kantin Utama, Kantin Gedung B')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kantin')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('tenants_count')
                    ->counts('tenants')
                    ->label('Jumlah Tenant')
                    ->sortable(),
                TextColumn::make('sales_imports_count')
                    ->counts('salesImports')
                    ->label('Total Import')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->label('Dibuat')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageKantins::route('/'),
        ];
    }
}
