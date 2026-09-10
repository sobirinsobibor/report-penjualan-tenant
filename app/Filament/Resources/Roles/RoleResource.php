<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\ManageRoles;
use App\Models\Role;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static UnitEnum|string|null $navigationGroup = 'Data Master';

    protected static ?string $modelLabel = 'Role & Hak Akses';

    protected static ?string $pluralModelLabel = 'Kelola Role (RBAC)';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('superadmin') || (auth()->user()?->hasAbility('role.view_any') ?? false);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Kode Role (slug)')
                    ->placeholder('misal: admin, tenant, auditor')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                TextInput::make('display_name')
                    ->label('Nama Tampilan Role')
                    ->placeholder('misal: Administrator Kantin')
                    ->required()
                    ->maxLength(100),
                Textarea::make('description')
                    ->label('Deskripsi Role')
                    ->placeholder('Penjelasan tanggung jawab dan batasan role ini')
                    ->rows(2)
                    ->columnSpanFull(),
                CheckboxList::make('permissions')
                    ->label('Insert Abilities / Hak Akses ke Role')
                    ->relationship('permissions', 'display_name')
                    ->columns(2)
                    ->columnSpanFull()
                    ->helperText('Centang ability untuk menyematkan izin akses ke role ini.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label('Nama Role')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('name')
                    ->label('Kode')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Total Abilities')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Total Pengguna')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ManageRoles::route('/'),
        ];
    }
}
