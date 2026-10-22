<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentDeletedRecords\Actions;

use AmjadIqbal\FilamentDeletedRecords\DeletedRecords;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Spatie\DeletedModels\Models\DeletedModel;
use Throwable;

class RestoreDeletedRecordAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'restoreDeletedRecord';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('filament-deleted-records::deleted-records.actions.restore.label'))
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('primary')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedArrowUturnLeft)
            ->modalIconColor('gray')
            ->modalHeading(fn (DeletedModel $record): string => __('filament-deleted-records::deleted-records.actions.restore.modal_heading', [
                'record' => static::describe($record),
            ]))
            ->modalDescription(__('filament-deleted-records::deleted-records.actions.restore.modal_description'))
            ->modalSubmitActionLabel(__('filament-deleted-records::deleted-records.actions.restore.label'))
            ->authorize(fn (DeletedModel $record): bool => DeletedRecords::can('restore', $record))
            ->action(function (DeletedModel $record, Action $action): void {
                $name = static::describe($record);

                try {
                    $restored = DeletedRecords::restore($record);
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->danger()
                        ->title(__('filament-deleted-records::deleted-records.actions.restore.failed', ['record' => $name]))
                        ->body(static::failureReason($record))
                        ->send();

                    $action->halt();

                    return;
                }

                $url = DeletedRecords::recordUrl($restored);

                Notification::make()
                    ->success()
                    ->title(__('filament-deleted-records::deleted-records.actions.restore.success', ['record' => $name]))
                    ->actions($url ? [
                        Action::make('open')
                            ->label(__('filament-deleted-records::deleted-records.actions.restore.open'))
                            ->url($url)
                            ->link(),
                    ] : [])
                    ->send();
            });
    }

    public static function describe(DeletedModel $record): string
    {
        return DeletedRecords::modelLabel((string) $record->model).' '.DeletedRecords::recordTitle($record);
    }

    public static function failureReason(DeletedModel $record): string
    {
        try {
            $status = DeletedRecords::restoreStatus($record);
        } catch (Throwable) {
            $status = null;
        }

        return match ($status) {
            DeletedRecords::STATUS_CONFLICT => __('filament-deleted-records::deleted-records.preview.status_conflict'),
            DeletedRecords::STATUS_MISSING_CLASS => __('filament-deleted-records::deleted-records.preview.status_missing_class'),
            default => '',
        };
    }
}
