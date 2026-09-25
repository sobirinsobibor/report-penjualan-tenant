<?php

namespace App\Filament\Resources\BerkasTenant;

use App\Filament\Resources\BerkasTenant\Pages\ManageBerkasTenant;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BerkasTenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static UnitEnum|string|null $navigationGroup = 'Data Master';

    protected static ?string $modelLabel = 'Berkas Tenant';

    protected static ?string $pluralModelLabel = 'Berkas & Dokumen Tenant';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Tenant & Pemilik')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Tenant')
                            ->disabled(),
                        TextInput::make('owner_name')
                            ->label('Nama Pemilik')
                            ->placeholder('Belum diisi')
                            ->disabled(),
                        TextInput::make('nik')
                            ->label('NIK Pemilik')
                            ->placeholder('Belum diisi')
                            ->disabled(),
                        TextInput::make('no_kk')
                            ->label('No. KK')
                            ->placeholder('Belum diisi')
                            ->disabled(),
                        TextInput::make('phone')
                            ->label('No. HP / WA')
                            ->placeholder('Belum diisi')
                            ->disabled(),
                        TextInput::make('birth_place_date')
                            ->label('Tempat, Tgl Lahir (TTL)')
                            ->placeholder('Belum diisi')
                            ->disabled(),
                        TextInput::make('nomor_halal')
                            ->label('No. Sertifikat Halal')
                            ->placeholder('Belum diisi')
                            ->disabled(),
                        Textarea::make('address')
                            ->label('Alamat Pemilik')
                            ->columnSpanFull()
                            ->disabled(),
                    ])->columns(2),

                Section::make('Berkas & Dokumen (Pratinjau Gambar)')
                    ->schema([
                        FileUpload::make('file_ktp')
                            ->label('Scan / Foto KTP')
                            ->image()
                            ->directory('tenant-documents')
                            ->visibility('public')
                            ->disabled(),
                        FileUpload::make('file_kk')
                            ->label('Scan / Foto KK')
                            ->image()
                            ->directory('tenant-documents')
                            ->visibility('public')
                            ->disabled(),
                        FileUpload::make('file_npwp')
                            ->label('Scan / Foto NPWP')
                            ->image()
                            ->directory('tenant-documents')
                            ->visibility('public')
                            ->disabled(),
                        FileUpload::make('file_sertifikat_higenitas')
                            ->label('Foto Sertifikat Layak Higenitas')
                            ->image()
                            ->directory('tenant-documents')
                            ->visibility('public')
                            ->disabled(),
                    ])->columns(2),
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
                TextColumn::make('owner_name')
                    ->label('Nama Pemilik')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Belum diisi'),
                TextColumn::make('phone')
                    ->label('No. HP / WA')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Belum diisi'),
                TextColumn::make('nomor_halal')
                    ->label('No. Halal')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Belum diisi'),
                TextColumn::make('document_status')
                    ->label('Status Berkas')
                    ->state(fn (Tenant $record) => $record->document_status)
                    ->badge()
                    ->color(fn ($state) => $state === 'Lengkap' ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kantin_id')
                    ->label('Filter Kantin')
                    ->relationship('kantin', 'name'),
                SelectFilter::make('status')
                    ->label('Status Kelengkapan Berkas')
                    ->options([
                        'lengkap' => 'Berkas Lengkap',
                        'belum_lengkap' => 'Berkas Belum Lengkap',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'lengkap') {
                            $query->whereNotNull('owner_name')
                                ->whereNotNull('nik')
                                ->whereNotNull('no_kk')
                                ->whereNotNull('phone')
                                ->whereNotNull('address')
                                ->whereNotNull('file_ktp')
                                ->whereNotNull('file_kk')
                                ->whereNotNull('file_npwp')
                                ->whereNotNull('file_sertifikat_higenitas');
                        } elseif ($data['value'] === 'belum_lengkap') {
                            $query->where(function ($q) {
                                $q->whereNull('owner_name')
                                    ->orWhereNull('nik')
                                    ->orWhereNull('no_kk')
                                    ->orWhereNull('phone')
                                    ->orWhereNull('address')
                                    ->orWhereNull('file_ktp')
                                    ->orWhereNull('file_kk')
                                    ->orWhereNull('file_npwp')
                                    ->orWhereNull('file_sertifikat_higenitas');
                            });
                        }
                    }),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->label('Lihat Berkas Tenant')
                    ->modalHeading('Rincian & Pratinjau Berkas Tenant'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBerkasTenant::route('/'),
        ];
    }
}
