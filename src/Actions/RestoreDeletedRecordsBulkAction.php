<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentDeletedRecords\Actions;

use AmjadIqbal\FilamentDeletedRecords\DeletedRecords;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Spatie\DeletedModels\Models\DeletedModel;
use Throwable;

class RestoreDeletedRecordsBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'restoreDeletedRecords';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('filament-deleted-records::deleted-records.actions.restore_bulk.label'))
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('primary')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedArrowUturnLeft)
            ->modalIconColor('gray')
            ->modalHeading(__('filament-deleted-records::deleted-records.actions.restore_bulk.modal_heading'))
            ->modalDescription(__('filament-deleted-records::deleted-records.actions.restore.modal_description'))
            ->modalSubmitActionLabel(__('filament-deleted-records::deleted-records.actions.restore.label'))
            ->deselectRecordsAfterCompletion()
            ->authorize(fn (): bool => DeletedRecords::can('restoreAny'))
            ->action(function (Collection $records): void {
                $restored = 0;
                $failed = 0;
                $skipped = 0;

                /** @var DeletedModel $record */
                foreach ($records as $record) {
                    if (! DeletedRecords::can('restore', $record)) {
                        $skipped++;

                        continue;
                    }

                    try {
                        DeletedRecords::restore($record);
                        $restored++;
                    } catch (Throwable $exception) {
                        report($exception);
                        $failed++;
                    }
                }

                $title = trans_choice('filament-deleted-records::deleted-records.actions.restore_bulk.success', $restored, ['count' => $restored]);
                $body = collect([
                    $failed ? trans_choice('filament-deleted-records::deleted-records.actions.restore_bulk.failed', $failed, ['count' => $failed]) : null,
                    $skipped ? trans_choice('filament-deleted-records::deleted-records.actions.bulk_skipped', $skipped, ['count' => $skipped]) : null,
                ])->filter()->implode(' ');

                Notification::make()
                    ->status($failed || $skipped ? 'danger' : 'success')
                    ->title($title)
                    ->body($body ?: null)
                    ->send();
            });
    }
}
