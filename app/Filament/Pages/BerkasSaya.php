<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BerkasSaya extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static UnitEnum|string|null $navigationGroup = 'Data Tenant';

    protected static ?string $navigationLabel = 'Upload Berkas Saya';

    protected static ?string $title = 'Berkas & Identitas Tenant';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.berkas-saya';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isTenant() || $user->hasAbility('berkas.update'));
    }

    public function mount(): void
    {
        $user = auth()->user();
        $tenant = $user?->tenant;

        if ($tenant) {
            $this->form->fill($tenant->toArray());
        } else {
            $this->form->fill();
        }
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Informasi & Identitas Pemilik')
                    ->description('Lengkapi data identitas resmi pemilik tenant foodcourt.')
                    ->schema([
                        TextInput::make('owner_name')
                            ->label('Nama Pemilik')
                            ->placeholder('Misal: Heni Sri Wahyuni')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nik')
                            ->label('NIK Pemilik (KTP)')
                            ->placeholder('16 Digit NIK')
                            ->required()
                            ->maxLength(30),
                        TextInput::make('no_kk')
                            ->label('Nomor Kartu Keluarga (KK)')
                            ->placeholder('16 Digit No. KK')
                            ->required()
                            ->maxLength(30),
                        TextInput::make('phone')
                            ->label('No. HP / WhatsApp')
                            ->placeholder('081234567890')
                            ->required()
                            ->maxLength(30),
                        TextInput::make('birth_place_date')
                            ->label('Tempat, Tanggal Lahir (TTL)')
                            ->placeholder('Misal: Surabaya, 15 Januari 1990')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nomor_halal')
                            ->label('Nomor Sertifikat Halal')
                            ->placeholder('Nomor registrasi halal (jika ada)')
                            ->nullable()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('Alamat Lengkap Pemilik')
                            ->placeholder('Alamat sesuai KTP/domisili')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Unggah Dokumen Berkas (Foto / Image)')
                    ->description('Unggah foto/scan dokumen KTP, KK, NPWP, dan Sertifikat Layak Higienitas (Format: JPG, PNG, max 5MB per file).')
                    ->schema([
                        FileUpload::make('file_ktp')
                            ->label('Foto / Scan KTP')
                            ->image()
                            ->directory('tenant-documents')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('150')
                            ->helperText('Unggah foto KTP asli yang jelas dan dapat dibaca')
                            ->required(),
                        FileUpload::make('file_kk')
                            ->label('Foto / Scan Kartu Keluarga (KK)')
                            ->image()
                            ->directory('tenant-documents')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('150')
                            ->helperText('Unggah foto Kartu Keluarga asli')
                            ->required(),
                        FileUpload::make('file_npwp')
                            ->label('Foto / Scan NPWP')
                            ->image()
                            ->directory('tenant-documents')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('150')
                            ->helperText('Unggah foto kart NPWP pribadi/badan')
                            ->required(),
                        FileUpload::make('file_sertifikat_higenitas')
                            ->label('Foto / Scan Sertifikat Layak Higienitas')
                            ->image()
                            ->directory('tenant-documents')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('150')
                            ->helperText('Unggah sertifikat higienitas pangan / sanitasi outlet')
                            ->required(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $user = auth()->user();
        $tenant = $user?->tenant;

        if (!$tenant) {
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Akun Anda belum ditautkan ke profil Tenant oleh Admin.')
                ->danger()
                ->send();
            return;
        }

        $data = $this->form->getState();
        $tenant->update($data);

        $status = $tenant->fresh()->document_status;

        Notification::make()
            ->title('Berkas Berhasil Diperbarui!')
            ->body("Status kelengkapan berkas Anda saat ini: {$status}")
            ->success()
            ->send();
    }
}
