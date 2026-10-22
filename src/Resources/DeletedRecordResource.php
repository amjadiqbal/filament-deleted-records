<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentDeletedRecords\Resources;

use AmjadIqbal\FilamentDeletedRecords\Actions\ForceDeleteDeletedRecordAction;
use AmjadIqbal\FilamentDeletedRecords\Actions\ForceDeleteDeletedRecordsBulkAction;
use AmjadIqbal\FilamentDeletedRecords\Actions\RestoreDeletedRecordAction;
use AmjadIqbal\FilamentDeletedRecords\Actions\RestoreDeletedRecordsBulkAction;
use AmjadIqbal\FilamentDeletedRecords\DeletedRecords;
use AmjadIqbal\FilamentDeletedRecords\Resources\DeletedRecordResource\Pages\ListDeletedRecords;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\DeletedModels\Models\DeletedModel;
use UnitEnum;

class DeletedRecordResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxXMark;

    protected static ?string $slug = 'deleted-records';

    protected static bool $isScopedToTenant = false;

    public static function getModel(): string
    {
        return DeletedRecords::deletedModelClass();
    }

    public static function getModelLabel(): string
    {
        return __('filament-deleted-records::deleted-records.resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-deleted-records::deleted-records.resource.plural_model_label');
    }

    public static function getNavigationLabel(): string
    {
        return DeletedRecords::plugin()->getNavigationLabel()
            ?? __('filament-deleted-records::deleted-records.resource.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return DeletedRecords::plugin()->getNavigationGroup() ?? parent::getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return DeletedRecords::plugin()->getNavigationSort() ?? parent::getNavigationSort();
    }

    public static function getNavigationBadge(): ?string
    {
        if (! DeletedRecords::plugin()->hasNavigationBadge()) {
            return null;
        }

        $count = DeletedRecords::query()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'gray';
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof DeletedModel
            ? RestoreDeletedRecordAction::describe($record)
            : (string) static::getModelLabel();
    }

    public static function canViewAny(): bool
    {
        return DeletedRecords::can('viewAny');
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof DeletedModel && DeletedRecords::can('view', $record);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = DeletedRecords::scope(parent::getEloquentQuery());

        if (DeletedRecords::tracksDeletedBy() && DeletedRecords::userModel()) {
            $query->with(DeletedRecords::DELETED_BY_RELATION);
        }

        return $query;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('filament-deleted-records::deleted-records.preview.summary'))
                ->columns(['default' => 1, 'sm' => 2])
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('model')
                        ->label(__('filament-deleted-records::deleted-records.columns.type'))
                        ->formatStateUsing(fn (string $state): string => DeletedRecords::modelLabel($state))
                        ->badge()
                        ->color('gray'),
                    TextEntry::make('key')
                        ->label(__('filament-deleted-records::deleted-records.columns.key'))
                        ->prefix('#')
                        ->copyable(),
                    TextEntry::make('created_at')
                        ->label(__('filament-deleted-records::deleted-records.columns.deleted_at'))
                        ->dateTime(),
                    TextEntry::make('deleted_by')
                        ->label(__('filament-deleted-records::deleted-records.columns.deleted_by'))
                        ->state(fn (DeletedModel $record): ?string => DeletedRecords::deletedByName($record))
                        ->placeholder('—')
                        ->visible(fn (): bool => DeletedRecords::tracksDeletedBy()),
                    TextEntry::make('restore_status')
                        ->label(__('filament-deleted-records::deleted-records.preview.status'))
                        ->state(fn (DeletedModel $record): string => DeletedRecords::restoreStatus($record))
                        ->formatStateUsing(fn (string $state): string => __("filament-deleted-records::deleted-records.preview.status_{$state}"))
                        ->icon(fn (string $state): Heroicon => $state === DeletedRecords::STATUS_READY
                            ? Heroicon::OutlinedCheckCircle
                            : Heroicon::OutlinedExclamationCircle)
                        ->iconColor(fn (string $state): string => $state === DeletedRecords::STATUS_READY ? 'success' : 'danger')
                        ->columnSpanFull(),
                ]),
            Section::make(__('filament-deleted-records::deleted-records.preview.attributes'))
                ->columnSpanFull()
                ->schema([
                    KeyValueEntry::make('values')
                        ->hiddenLabel()
                        ->keyLabel(__('filament-deleted-records::deleted-records.preview.attribute'))
                        ->valueLabel(__('filament-deleted-records::deleted-records.preview.value'))
                        ->state(fn (DeletedModel $record): array => DeletedRecords::previewState($record)),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('record')
                    ->label(__('filament-deleted-records::deleted-records.columns.record'))
                    ->state(fn (DeletedModel $record): string => DeletedRecords::recordTitle($record))
                    ->description(fn (DeletedModel $record): string => '#'.$record->getAttribute('key'))
                    ->weight('medium')
                    ->searchable(query: fn (Builder $query, string $search): Builder => DeletedRecords::applySearch($query, $search)),
                TextColumn::make('model')
                    ->label(__('filament-deleted-records::deleted-records.columns.type'))
                    ->formatStateUsing(fn (string $state): string => DeletedRecords::modelLabel($state))
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('deleted_by')
                    ->label(__('filament-deleted-records::deleted-records.columns.deleted_by'))
                    ->state(fn (DeletedModel $record): ?string => DeletedRecords::deletedByName($record))
                    ->placeholder('—')
                    ->visible(fn (): bool => DeletedRecords::tracksDeletedBy()),
                TextColumn::make('created_at')
                    ->label(__('filament-deleted-records::deleted-records.columns.deleted_at'))
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('model')
                    ->label(__('filament-deleted-records::deleted-records.filters.type'))
                    ->multiple()
                    ->options(fn (): array => DeletedRecords::typeOptions()),
                SelectFilter::make('deleted_by_id')
                    ->label(__('filament-deleted-records::deleted-records.filters.deleted_by'))
                    ->options(fn (): array => DeletedRecords::deletedByOptions())
                    ->visible(fn (): bool => DeletedRecords::tracksDeletedBy()),
                Filter::make('deleted_at')
                    ->schema([
                        DatePicker::make('deleted_from')
                            ->label(__('filament-deleted-records::deleted-records.filters.deleted_from')),
                        DatePicker::make('deleted_until')
                            ->label(__('filament-deleted-records::deleted-records.filters.deleted_until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['deleted_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['deleted_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['deleted_from'] ?? null) {
                            $indicators['deleted_from'] = __('filament-deleted-records::deleted-records.filters.deleted_from_indicator', [
                                'date' => Carbon::parse($data['deleted_from'])->toFormattedDateString(),
                            ]);
                        }

                        if ($data['deleted_until'] ?? null) {
                            $indicators['deleted_until'] = __('filament-deleted-records::deleted-records.filters.deleted_until_indicator', [
                                'date' => Carbon::parse($data['deleted_until'])->toFormattedDateString(),
                            ]);
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading(fn (DeletedModel $record): string => __('filament-deleted-records::deleted-records.preview.heading', [
                        'type' => DeletedRecords::modelLabel((string) $record->model),
                        'key' => $record->getAttribute('key'),
                    ]))
                    ->extraModalFooterActions([
                        RestoreDeletedRecordAction::make('restoreFromPreview')->cancelParentActions(),
                    ]),
                RestoreDeletedRecordAction::make(),
                ActionGroup::make([
                    ForceDeleteDeletedRecordAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreDeletedRecordsBulkAction::make(),
                    ForceDeleteDeletedRecordsBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('filament-deleted-records::deleted-records.resource.empty_heading'))
            ->emptyStateDescription(__('filament-deleted-records::deleted-records.resource.empty_description'))
            ->emptyStateIcon(Heroicon::OutlinedArchiveBoxXMark);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeletedRecords::route('/'),
        ];
    }
}
