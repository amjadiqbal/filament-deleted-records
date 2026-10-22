<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentDeletedRecords\Actions;

use AmjadIqbal\FilamentDeletedRecords\DeletedRecords;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Spatie\DeletedModels\Models\DeletedModel;

class ForceDeleteDeletedRecordAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'forceDeleteDeletedRecord';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('filament-deleted-records::deleted-records.actions.force_delete.label'))
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedTrash)
            ->modalIconColor('gray')
            ->modalHeading(fn (DeletedModel $record): string => __('filament-deleted-records::deleted-records.actions.force_delete.modal_heading', [
                'record' => RestoreDeletedRecordAction::describe($record),
            ]))
            ->modalDescription(__('filament-deleted-records::deleted-records.actions.force_delete.modal_description'))
            ->modalSubmitActionLabel(__('filament-deleted-records::deleted-records.actions.force_delete.label'))
            ->authorize(fn (DeletedModel $record): bool => DeletedRecords::can('forceDelete', $record))
            ->action(function (DeletedModel $record): void {
                $record->delete();

                Notification::make()
                    ->success()
                    ->title(__('filament-deleted-records::deleted-records.actions.force_delete.success'))
                    ->send();
            });
    }
}
