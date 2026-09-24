<?php

namespace App\Filament\Resources\Announcements;

use App\Filament\Resources\Announcements\Pages\ManageAnnouncements;
use App\Models\Announcement;
use App\Models\Kantin;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static UnitEnum|string|null $navigationGroup = 'Operasional';

    protected static ?string $modelLabel = 'Pengumuman Operasional';

    protected static ?string $pluralModelLabel = 'Pengumuman Operasional';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canEdit(mixed $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete(mixed $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kantin_id')
                ->label('Kantin Target')
                ->options(Kantin::pluck('name', 'id'))
                ->placeholder('Semua Kantin (Global)')
                ->nullable(),
            TextInput::make('title')
                ->label('Judul Pengumuman')
                ->required()
                ->maxLength(255),
            Select::make('category')
                ->label('Kategori')
                ->options([
                    'maintenance' => 'Maintenance Listrik / Fasilitas',
                    'stock_opname' => 'Jadwal Stock Opname',
                    'promo' => 'Program Promo Foodcourt',
                    'general' => 'Informasi Umum',
                ])
                ->default('general')
                ->required(),
            DatePicker::make('event_date')
                ->label('Tanggal Kegiatan / Event')
                ->nullable(),
            Textarea::make('content')
                ->label('Isi Pengumuman')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->label('Publikasikan Sekarang (Aktif)')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->formatStateUsing(fn ($record) => $record->category_label)
                    ->badge()
                    ->color(fn ($record) => $record->category_badge_color),
                TextColumn::make('kantin.name')
                    ->label('Kantin Target')
                    ->placeholder('Semua Kantin'),
                TextColumn::make('event_date')
                    ->label('Tgl Event')
                    ->date('d M Y')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible($isAdmin)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()->visible($isAdmin),
                DeleteAction::make()->visible($isAdmin),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible($isAdmin),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAnnouncements::route('/'),
        ];
    }
}
