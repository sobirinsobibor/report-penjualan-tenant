<?php

namespace App\Filament\Resources\Tenants;

use App\Filament\Resources\Tenants\Pages\ManageTenants;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static UnitEnum|string|null $navigationGroup = 'Data Master';

    protected static ?string $modelLabel = 'Tenant';

    protected static ?string $pluralModelLabel = 'Data Tenant';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAbility('tenant.view_any') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAbility('tenant.create') ?? false;
    }

    public static function canEdit(mixed $record): bool
    {
        return auth()->user()?->hasAbility('tenant.update') ?? false;
    }

    public static function canDelete(mixed $record): bool
    {
        return auth()->user()?->hasAbility('tenant.delete') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('kantin_id')
                    ->label('Kantin')
                    ->relationship('kantin', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label('Nama Tenant (Menu Category)')
                    ->placeholder('Misal: Ayam Geprek Bu Sri, Kopi Kenangan')
                    ->required()
                    ->maxLength(255),
                TextInput::make('fee_percentage')
                    ->label('Bagi Hasil PKS (%)')
                    ->numeric()
                    ->default(15.00)
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%')
                    ->helperText('Persentase potongan bagi hasil dari total omzet tenant'),
                TextInput::make('target_omzet')
                    ->label('Target Omzet Bulanan')
                    ->prefix('Rp')
                    ->numeric()
                    ->default(55000000)
                    ->helperText('Target penjualan bulanan untuk indikator achievement'),
                TextInput::make('fixed_fee')
                    ->label('Biaya Operasional Tetap (Fixed Fee)')
                    ->prefix('Rp')
                    ->numeric()
                    ->default(0)
                    ->helperText('Biaya sewa/kebersihan/listrik dasar per periode'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Tenant')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('kantin.name')
                    ->label('Kantin')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('fee_percentage')
                    ->label('Bagi Hasil (%)')
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 2) . '%')
                    ->sortable()
                    ->color('warning'),
                TextColumn::make('target_omzet')
                    ->label('Target Bulanan')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Akun Tertaut')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->label('Dibuat')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kantin_id')
                    ->label('Filter Kantin')
                    ->relationship('kantin', 'name'),
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
            'index' => ManageTenants::route('/'),
        ];
    }
}
