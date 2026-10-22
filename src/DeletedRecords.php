<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentDeletedRecords;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;
use Spatie\DeletedModels\Models\DeletedModel;
use Throwable;

class DeletedRecords
{
    public const STATUS_READY = 'ready';

    public const STATUS_CONFLICT = 'conflict';

    public const STATUS_MISSING_CLASS = 'missing_class';

    /** The relation to the user who deleted the model, registered at boot with resolveRelationUsing(). */
    public const DELETED_BY_RELATION = 'deletedBy';

    protected static ?bool $tracksDeletedBy = null;

    public static function plugin(): DeletedRecordsPlugin
    {
        return DeletedRecordsPlugin::get();
    }

    /**
     * The model that stores deleted records (`deleted-models.model`).
     *
     * @return class-string<DeletedModel>
     */
    public static function deletedModelClass(): string
    {
        /** @var class-string<DeletedModel> */
        return config('deleted-models.model', DeletedModel::class);
    }

    /**
     * @return Builder<DeletedModel>
     */
    public static function query(): Builder
    {
        return static::scope(static::deletedModelClass()::query());
    }

    /**
     * Limit a deleted-records query to the models the plugin is limited to.
     *
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public static function scope(Builder $query): Builder
    {
        $models = static::plugin()->getModels();

        if ($models !== []) {
            $query->whereIn('model', static::storedTypes($models));
        }

        return $query;
    }

    /**
     * The values the `model` column can hold for these classes or aliases (class name and morph alias).
     *
     * @param  array<int, string>  $models
     * @return array<int, string>
     */
    public static function storedTypes(array $models): array
    {
        $types = [];

        foreach ($models as $model) {
            $types[] = $model;

            if (class_exists($model) && is_subclass_of($model, Model::class)) {
                $types[] = (new $model)->getMorphClass();
            }
        }

        return array_values(array_unique($types));
    }

    /**
     * @return class-string<Model>|string
     */
    public static function targetClass(string $type): string
    {
        return Relation::getMorphedModel($type) ?? $type;
    }

    public static function modelLabel(string $type): string
    {
        $labels = static::plugin()->getModelLabels();
        $class = static::targetClass($type);

        return $labels[$type] ?? $labels[$class] ?? Str::headline(class_basename($class));
    }

    public static function recordTitle(DeletedModel $record): string
    {
        foreach (static::plugin()->getTitleAttributes() as $attribute) {
            $value = $record->value($attribute);

            if (is_scalar($value) && filled($value)) {
                return Str::limit((string) $value, 80);
            }
        }

        return '#'.$record->getAttribute('key');
    }

    /**
     * The stored attributes as display strings, with masked attributes hidden.
     *
     * @return array<string, string>
     */
    public static function previewState(DeletedModel $record): array
    {
        $masked = array_map('strtolower', static::plugin()->getMaskedAttributes());
        $state = [];

        foreach ((array) $record->values as $attribute => $value) {
            $state[(string) $attribute] = in_array(strtolower((string) $attribute), $masked, true)
                ? __('filament-deleted-records::deleted-records.preview.masked')
                : static::formatValue($value);
        }

        return $state;
    }

    public static function formatValue(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        };
    }

    /**
     * Whether a restore can work: the model class still exists and no live record already has this key.
     */
    public static function restoreStatus(DeletedModel $record): string
    {
        $class = static::targetClass((string) $record->model);

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return static::STATUS_MISSING_CLASS;
        }

        $exists = $class::query()
            ->withoutGlobalScopes()
            ->whereKey($record->getAttribute('key'))
            ->exists();

        return $exists ? static::STATUS_CONFLICT : static::STATUS_READY;
    }

    /**
     * Restore through the model's own `restore()` when it uses KeepsDeletedModels, so its beforeRestoringModel and
     * afterRestoringModel hooks run, else through the stored record.
     */
    public static function restore(DeletedModel $record): Model
    {
        $class = static::targetClass((string) $record->model);

        if (class_exists($class) && in_array(KeepsDeletedModels::class, class_uses_recursive($class), true)) {
            /** @var Model */
            return $class::restore($record->getAttribute('key'));
        }

        return $record->restore();
    }

    /**
     * A URL for the restored record in the current panel (view page, else edit page), when the user may open it.
     */
    public static function recordUrl(Model $record): ?string
    {
        try {
            $resource = Filament::getCurrentOrDefaultPanel()?->getModelResource($record);

            if (! $resource) {
                return null;
            }

            foreach (['view' => 'canView', 'edit' => 'canEdit'] as $page => $ability) {
                if ($resource::hasPage($page) && $resource::$ability($record)) {
                    return $resource::getUrl($page, ['record' => $record]);
                }
            }
        } catch (Throwable) {
            // No panel, or the resource cannot build a URL for this record.
        }

        return null;
    }

    /**
     * May the current user do this? Order: the plugin's authorizeUsing() callback; else a policy registered for the
     * deleted-record model; else, for record abilities, the original model's policy method of the same name (checked
     * against the record as it would be restored); else allowed (panel access is the gate).
     */
    public static function can(string $ability, ?DeletedModel $record = null): bool
    {
        $user = static::user();

        if ($callback = static::plugin()->getAuthorizeUsing()) {
            return (bool) app()->call($callback, ['ability' => $ability, 'record' => $record, 'user' => $user]);
        }

        $gate = $user ? Gate::forUser($user) : Gate::getFacadeRoot();

        if (Gate::getPolicyFor(static::deletedModelClass())) {
            return $gate->allows($ability, $record ?? static::deletedModelClass());
        }

        if (! $record) {
            return true;
        }

        $class = static::targetClass((string) $record->model);

        if (! class_exists($class)) {
            return true;
        }

        $policy = Gate::getPolicyFor($class);

        if (! $policy || ! method_exists($policy, $ability)) {
            return true;
        }

        try {
            return $gate->allows($ability, $record->makeRestoredModel());
        } catch (Throwable) {
            return false;
        }
    }

    public static function user(): ?Authenticatable
    {
        try {
            return Filament::auth()->user();
        } catch (Throwable) {
            return auth()->user();
        }
    }

    public static function tracksDeletedBy(): bool
    {
        if (! config('deleted-records.track_deleted_by', true)) {
            return false;
        }

        try {
            return static::$tracksDeletedBy ??= Schema::hasColumn((new (static::deletedModelClass()))->getTable(), 'deleted_by_id');
        } catch (Throwable) {
            return false;
        }
    }

    public static function flushCache(): void
    {
        static::$tracksDeletedBy = null;
    }

    /**
     * @return class-string<Model>|null
     */
    public static function userModel(): ?string
    {
        $model = config('deleted-records.user_model');

        if (! $model) {
            $provider = config('auth.guards.'.config('auth.defaults.guard').'.provider');
            $model = config("auth.providers.{$provider}.model");
        }

        return is_string($model) && class_exists($model) ? $model : null;
    }

    public static function deletedByName(DeletedModel $record): ?string
    {
        if (! static::tracksDeletedBy() || blank($record->getAttribute('deleted_by_id'))) {
            return null;
        }

        $user = $record->getRelationValue(static::DELETED_BY_RELATION);

        if (! $user instanceof Model) {
            return '#'.$record->getAttribute('deleted_by_id');
        }

        return (string) ($user->getAttribute(static::plugin()->getUserTitleAttribute()) ?? '#'.$user->getKey());
    }

    /**
     * Options for the "Deleted by" filter: the users who appear in the bin.
     *
     * @return array<string, string>
     */
    public static function deletedByOptions(): array
    {
        $model = static::userModel();

        if (! $model || ! static::tracksDeletedBy()) {
            return [];
        }

        $ids = static::query()->whereNotNull('deleted_by_id')->distinct()->pluck('deleted_by_id')->all();
        $attribute = static::plugin()->getUserTitleAttribute();

        return $model::query()
            ->whereKey($ids)
            ->get()
            ->mapWithKeys(fn (Model $user): array => [(string) $user->getKey() => (string) ($user->getAttribute($attribute) ?? '#'.$user->getKey())])
            ->all();
    }

    /**
     * Options for the "Type" filter: the model types present in the bin.
     *
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return static::query()
            ->distinct()
            ->pluck('model')
            ->filter()
            ->mapWithKeys(fn (string $type): array => [$type => static::modelLabel($type)])
            ->sort()
            ->all();
    }

    /**
     * Search the key (exact) and the title attributes (case-insensitive, contains). Only the title attributes are
     * searched, never the whole stored JSON, so a search cannot probe masked values such as password hashes.
     *
     * @param  Builder<DeletedModel>  $query
     * @return Builder<DeletedModel>
     */
    public static function applySearch(Builder $query, string $search): Builder
    {
        $grammar = $query->getQuery()->getGrammar();
        $table = $query->getModel()->getTable();
        $escape = $query->getModel()->getConnection()->getDriverName() === 'sqlite' ? " ESCAPE '\\'" : '';
        $like = '%'.mb_strtolower(str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search)).'%';

        $masked = array_map('strtolower', static::plugin()->getMaskedAttributes());
        $attributes = array_filter(
            static::plugin()->getTitleAttributes(),
            fn (string $attribute): bool => preg_match('/^[A-Za-z0-9_]+$/', $attribute) === 1
                && ! in_array(strtolower($attribute), $masked, true),
        );

        return $query->where(function (Builder $query) use ($attributes, $escape, $grammar, $like, $search, $table): void {
            $query->where($table.'.key', $search);

            foreach ($attributes as $attribute) {
                $query->orWhereRaw('LOWER('.$grammar->wrap("{$table}.values->{$attribute}").') LIKE ?'.$escape, [$like]);
            }
        });
    }
}
