<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static UnitEnum|string|null $navigationGroup = 'Data Master';

    protected static ?string $modelLabel = 'Akun Pengguna';

    protected static ?string $pluralModelLabel = 'Kelola Pengguna';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email / Username')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->default('password')
                    ->helperText('Password default: password'),
                Select::make('role')
                    ->label('Role Akses')
                    ->options([
                        'superadmin' => 'Superadmin',
                        'admin' => 'Admin',
                        'tenant' => 'Tenant',
                    ])
                    ->default('tenant')
                    ->required()
                    ->live(),
                Select::make('tenant_id')
                    ->label('Tautkan ke Tenant')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) => $get('role') === 'tenant')
                    ->required(fn ($get) => $get('role') === 'tenant')
                    ->helperText('Wajib dipilih jika role adalah Tenant'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'superadmin' => 'danger',
                        'admin' => 'warning',
                        'tenant' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('tenant.name')
                    ->label('Tenant Tertaut')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'superadmin' => 'Superadmin',
                        'admin' => 'Admin',
                        'tenant' => 'Tenant',
                    ]),
            ])
            ->recordActions([
                Action::make('resetPassword')
                    ->label('Reset Pwd')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Password')
                    ->modalDescription('Apakah Anda yakin ingin mereset password akun ini ke default (password)?')
                    ->action(function (User $record) {
                        $record->update([
                            'password' => Hash::make('password'),
                        ]);
                        Notification::make()
                            ->title('Password berhasil direset')
                            ->body("Password untuk {$record->name} telah diatur ulang ke 'password'.")
                            ->success()
                            ->send();
                    }),
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
            'index' => ManageUsers::route('/'),
        ];
    }
}
