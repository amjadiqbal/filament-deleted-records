<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentDeletedRecords;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentDeletedRecordsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-deleted-records';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile('deleted-records')
            ->hasTranslations()
            ->hasMigration('add_deleted_by_to_deleted_models_table');
    }

    public function packageBooted(): void
    {
        $this->app->booted(function (): void {
            $class = DeletedRecords::deletedModelClass();

            if (! class_exists($class)) {
                return;
            }

            // Who deleted it: filled when the copy is written, if the deleted_by_id column exists.
            $class::creating(function (Model $deleted): void {
                if (blank($deleted->getAttribute('deleted_by_id')) && DeletedRecords::tracksDeletedBy()) {
                    $deleted->setAttribute('deleted_by_id', DeletedRecords::user()?->getAuthIdentifier());
                }
            });

            $class::resolveRelationUsing(DeletedRecords::DELETED_BY_RELATION, function (Model $deleted): BelongsTo {
                return $deleted->belongsTo(DeletedRecords::userModel() ?? $deleted::class, 'deleted_by_id');
            });
        });
    }
}
