<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentDeletedRecords;

use AmjadIqbal\FilamentDeletedRecords\Resources\DeletedRecordResource;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Throwable;

class DeletedRecordsPlugin implements Plugin
{
    public const ID = 'deleted-records';

    protected bool $hasResource = true;

    protected ?string $resource = null;

    protected ?string $navigationGroup = null;

    protected ?int $navigationSort = null;

    protected ?string $navigationLabel = null;

    protected bool $hasNavigationBadge = true;

    protected ?Closure $authorizeUsing = null;

    /** @var array<int, string> */
    protected array $models = [];

    /** @var array<string, string> */
    protected array $modelLabels = [];

    /** @var array<int, string> */
    protected array $titleAttributes = ['name', 'title', 'label', 'subject', 'email', 'number', 'reference', 'slug'];

    /** @var array<int, string> */
    protected array $maskedAttributes = [
        'password',
        'remember_token',
        'api_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected string $userTitleAttribute = 'name';

    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * The plugin registered on the current panel, or a default instance outside a panel.
     */
    public static function get(): static
    {
        try {
            $panel = Filament::getCurrentOrDefaultPanel();

            if ($panel?->hasPlugin(static::ID)) {
                /** @var static */
                return $panel->getPlugin(static::ID);
            }
        } catch (Throwable) {
            // No panels registered.
        }

        return app(static::class);
    }

    public function getId(): string
    {
        return static::ID;
    }

    public function register(Panel $panel): void
    {
        if ($this->hasResource) {
            $panel->resources([$this->getResource()]);
        }
    }

    public function boot(Panel $panel): void {}

    public function resource(bool|string $condition = true): static
    {
        if (is_string($condition)) {
            $this->resource = $condition;
            $condition = true;
        }

        $this->hasResource = $condition;

        return $this;
    }

    /**
     * @return class-string<DeletedRecordResource>
     */
    public function getResource(): string
    {
        /** @var class-string<DeletedRecordResource> */
        return $this->resource ?? DeletedRecordResource::class;
    }

    public function navigationGroup(?string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    public function navigationLabel(?string $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function getNavigationLabel(): ?string
    {
        return $this->navigationLabel;
    }

    public function navigationBadge(bool $condition = true): static
    {
        $this->hasNavigationBadge = $condition;

        return $this;
    }

    public function hasNavigationBadge(): bool
    {
        return $this->hasNavigationBadge;
    }

    /**
     * Only show deleted records of these models (class names or morph aliases). Empty shows every model.
     *
     * @param  array<int, string>  $models
     */
    public function models(array $models): static
    {
        $this->models = array_values($models);

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getModels(): array
    {
        return $this->models;
    }

    /**
     * Human labels for model types, e.g. [Invoice::class => 'Invoice'].
     *
     * @param  array<string, string>  $labels
     */
    public function modelLabels(array $labels): static
    {
        $this->modelLabels = [...$this->modelLabels, ...$labels];

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getModelLabels(): array
    {
        return $this->modelLabels;
    }

    /**
     * Stored attributes tried in order to name a deleted record in the list.
     *
     * @param  array<int, string>  $attributes
     */
    public function titleAttributes(array $attributes): static
    {
        $this->titleAttributes = array_values($attributes);

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getTitleAttributes(): array
    {
        return $this->titleAttributes;
    }

    /**
     * Attributes whose stored values are never shown in the preview.
     *
     * @param  array<int, string>  $attributes
     */
    public function maskedAttributes(array $attributes, bool $merge = true): static
    {
        $this->maskedAttributes = $merge
            ? array_values(array_unique([...$this->maskedAttributes, ...$attributes]))
            : array_values($attributes);

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getMaskedAttributes(): array
    {
        return $this->maskedAttributes;
    }

    /**
     * The user attribute shown in the "Deleted by" column and filter.
     */
    public function userTitleAttribute(string $attribute): static
    {
        $this->userTitleAttribute = $attribute;

        return $this;
    }

    public function getUserTitleAttribute(): string
    {
        return $this->userTitleAttribute;
    }

    /**
     * Decide who may see, restore or permanently delete deleted records.
     * The closure receives `$ability` (viewAny, view, restore, forceDelete), `$record` (the deleted record, or null
     * for viewAny) and `$user`, and returns a bool. It replaces the policy checks.
     */
    public function authorizeUsing(?Closure $callback): static
    {
        $this->authorizeUsing = $callback;

        return $this;
    }

    public function getAuthorizeUsing(): ?Closure
    {
        return $this->authorizeUsing;
    }
}
