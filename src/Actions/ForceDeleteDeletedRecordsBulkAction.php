<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentDeletedRecords\Actions;

use AmjadIqbal\FilamentDeletedRecords\DeletedRecords;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Spatie\DeletedModels\Models\DeletedModel;

class ForceDeleteDeletedRecordsBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'forceDeleteDeletedRecords';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('filament-deleted-records::deleted-records.actions.force_delete_bulk.label'))
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedTrash)
            ->modalIconColor('gray')
            ->modalHeading(__('filament-deleted-records::deleted-records.actions.force_delete_bulk.modal_heading'))
            ->modalDescription(__('filament-deleted-records::deleted-records.actions.force_delete_bulk.modal_description'))
            ->modalSubmitActionLabel(__('filament-deleted-records::deleted-records.actions.force_delete.label'))
            ->deselectRecordsAfterCompletion()
            ->authorize(fn (): bool => DeletedRecords::can('forceDeleteAny'))
            ->action(function (Collection $records): void {
                $deleted = 0;
                $skipped = 0;

                /** @var DeletedModel $record */
                foreach ($records as $record) {
                    if (! DeletedRecords::can('forceDelete', $record)) {
                        $skipped++;

                        continue;
                    }

                    $record->delete();
                    $deleted++;
                }

                Notification::make()
                    ->status($skipped ? 'danger' : 'success')
                    ->title(trans_choice('filament-deleted-records::deleted-records.actions.force_delete_bulk.success', $deleted, ['count' => $deleted]))
                    ->body($skipped ? trans_choice('filament-deleted-records::deleted-records.actions.bulk_skipped', $skipped, ['count' => $skipped]) : null)
                    ->send();
            });
    }
}
