<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/amjadiqbal/filament-deleted-records/main/art/banner-dark.png">
    <img alt="Deleted Records: a recycle bin for your Filament panel" src="https://raw.githubusercontent.com/amjadiqbal/filament-deleted-records/main/art/banner-light.png" width="100%">
  </picture>
</p>

# Deleted Records for Filament

A recycle bin for your Filament panel. It lists every record that
[spatie/laravel-deleted-models](https://github.com/spatie/laravel-deleted-models) kept when it was deleted, shows the
stored attributes, and lets you restore a record or delete it permanently, one at a time or in bulk. It works with
Filament 4 and 5.

**Docs:** [amjad.com.pk/filament/plugins/filament-deleted-records](https://amjad.com.pk/filament/plugins/filament-deleted-records)

[![Latest Version](https://img.shields.io/packagist/v/amjadiqbal/filament-deleted-records.svg?style=flat-square)](https://packagist.org/packages/amjadiqbal/filament-deleted-records)
[![PHP Version](https://img.shields.io/packagist/php-v/amjadiqbal/filament-deleted-records?style=flat-square)](https://packagist.org/packages/amjadiqbal/filament-deleted-records)
[![License](https://img.shields.io/packagist/l/amjadiqbal/filament-deleted-records.svg?style=flat-square)](LICENSE.md)

![The Deleted records page in a Filament panel](https://raw.githubusercontent.com/amjadiqbal/filament-deleted-records/main/art/screenshots/list-light.png)

<p align="center">
  <img alt="Preview of a deleted record's stored attributes, in dark mode" src="https://raw.githubusercontent.com/amjadiqbal/filament-deleted-records/main/art/screenshots/preview-dark.png" width="80%">
</p>

## What it does

`spatie/laravel-deleted-models` copies a model's attributes to a `deleted_models` table when you delete it, and gives
you `Model::restore($key)` in code. This plugin adds the screen for it:

- **One list of everything deleted**, newest first. Each row shows a readable title (the first of `name`, `title`,
  `label`, `subject`, `email`, `number`, `reference` or `slug` that is filled), the key, the model type, who deleted
  it and when.
- **Preview before you restore.** A modal shows every stored attribute and whether a restore can work. It warns you if a
  row with that key is still in the table (soft-deleted rows count, because the key is taken), or if the model class
  no longer exists. The warning does not disable Restore: a restore that fails is reported and the copy is kept.
  Passwords, remember tokens, API tokens and two-factor secrets are masked on screen. They are still stored in
  `deleted_models`, because the base package keeps hidden attributes so a restore is complete.
- **Restore** puts the record back with the same key and attributes. It goes through the model's own `restore()`, so
  your `beforeRestoringModel` and `afterRestoringModel` hooks and the package's events run. When the panel has a
  resource for the model, the notification links to the restored record.
- **Delete permanently** removes the stored copy for good.
- **Bulk restore and bulk permanent delete.** The notification counts what was done, what failed (for example, the key
  is taken; the copy is kept) and what was skipped because the user may not touch it.
- **Filters** by model type, by who deleted it and by date range. Search matches the key exactly, and the title
  attributes (see `titleAttributes()`) case-insensitively. Other stored attributes are not searched, so masked values
  cannot be probed through search.
- **Who deleted it.** An optional migration adds a `deleted_by_id` column, and the plugin fills it with the user signed
  in to the panel's guard when a copy is written. Deletes from queued jobs, console commands and scheduled tasks have
  no signed-in user, so the column stays empty for them. A user who was later removed shows as `#id`.
- **Permissions.** By default, anyone who can open the panel can list, preview, restore and permanently delete. To
  narrow that, give the original model's policy `restore` / `forceDelete` methods, register a policy for the
  deleted-record model, or pass one callback. See [Permissions](#permissions).
- Works in light and dark mode with no custom theme or build step. Translatable (English included).
- **Multi-tenant panels:** the page is not scoped to a tenant, so it lists every tenant's deleted records together. Use
  `authorizeUsing()` to keep it to central admins, or extend the resource and scope `getEloquentQuery()` yourself.

## Requirements

- PHP 8.3+ (PHP 8.4+ on Laravel 13, which `spatie/laravel-deleted-models` requires there)
- Laravel 12 or 13
- Filament 4 or 5

## Installation

```bash
composer require amjadiqbal/filament-deleted-records
```

This also installs `spatie/laravel-deleted-models`. Publish and run both migrations: the `deleted_models` table, then
the optional `deleted_by_id` column. The column is what powers the "Deleted by" column and filter.

```bash
php artisan vendor:publish --tag="deleted-models-migrations"
php artisan vendor:publish --tag="filament-deleted-records-migrations"
php artisan migrate
```

Add the `KeepsDeletedModels` trait to every model whose deleted records you want to keep:

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;

class Invoice extends Model
{
    use KeepsDeletedModels;
}
```

Register the plugin on your panel:

```php
use AmjadIqbal\FilamentDeletedRecords\DeletedRecordsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(DeletedRecordsPlugin::make());
}
```

A "Deleted records" page now appears in the navigation, with a badge showing how many records are in the bin.

Stored copies are kept for 365 days by default. To prune older ones, schedule Laravel's `model:prune` for the
`DeletedModel` class, as described in the
[spatie/laravel-deleted-models README](https://github.com/spatie/laravel-deleted-models#pruning-deleted-models).

## Configuration

Every option is optional:

```php
DeletedRecordsPlugin::make()
    ->navigationGroup('System')
    ->navigationLabel('Recycle bin')
    ->navigationSort(90)
    ->navigationBadge(false)                    // hide the count badge
    ->models([Invoice::class, Customer::class]) // only these models; empty shows all
    ->modelLabels([Invoice::class => 'Sales invoice'])
    ->titleAttributes(['number', 'name'])       // attributes tried, in order, for the row title
    ->maskedAttributes(['iban', 'secret'])      // merged with the defaults; pass false as the 2nd argument to replace them
    ->userTitleAttribute('email')               // shown in "Deleted by" (default: name)
    ->resource(MyDeletedRecordResource::class);  // extend the resource to change anything else
```

To change the tracking of who deleted a record, publish the config file:

```bash
php artisan vendor:publish --tag="filament-deleted-records-config"
```

```php
return [
    'track_deleted_by' => true, // set to false to stop recording deleted_by_id
    'user_model' => null,       // null uses the default auth guard's user model; set it if your panel uses another guard
];
```

## Permissions

The plugin checks each action in this order:

1. **`authorizeUsing()`**, if you pass a callback. It decides everything:

   ```php
   DeletedRecordsPlugin::make()
       ->authorizeUsing(fn (string $ability, ?DeletedModel $record, ?User $user): bool => $user?->isAdmin() ?? false);
   ```

   `$ability` is one of `viewAny`, `view`, `restore`, `forceDelete`, `restoreAny` or `forceDeleteAny`.
2. **A policy for the deleted-record model** (`Spatie\DeletedModels\Models\DeletedModel`, or your own class set in
   `deleted-models.model`), if you register one. It gets the same abilities.
3. **The original model's policy.** For a record, if that policy has a method named exactly `view`, `restore` or
   `forceDelete`, it decides, and it receives the record as it would be restored. So `InvoicePolicy::restore()`
   decides who can restore a deleted invoice. A policy without that method does not block the action.
4. Otherwise the action is allowed. Access to the panel is then the only gate. This includes `viewAny`, `restoreAny`
   and `forceDeleteAny` (listing the page and showing the bulk actions) whenever neither 1 nor 2 applies.

Bulk actions also check each record, skip the ones the user may not touch, and say how many were skipped.

## Testing

```bash
composer test
```

`scripts/ci-local.sh` runs Pint, PHPStan and the tests on PHP 8.3 to 8.5, Laravel 12 and 13, and Filament 4 and 5.
The tests use SQLite; set `DB_TEST_DRIVER=mysql` or `pgsql` (with `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`,
`DB_DATABASE`) to run them on a real server.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Support

- 🐛 **Bug or feature request**: [open an issue](https://github.com/amjadiqbal/filament-deleted-records/issues)
- 🔒 **Security issue**: please report it privately through GitHub's security tab

## Need this customised, or something built?

I'm available for custom development, package integration, and technical consulting.

[![Hire me on Upwork](https://img.shields.io/badge/Hire%20me-Upwork-6FDA44?style=flat-square&logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/amjadkhatri)

## More packages

This is part of a family of open-source packages. See [all of them](https://github.com/amjadiqbal?tab=repositories).

| | |
|---|---|
| [filament-change-requests](https://github.com/amjadiqbal/filament-change-requests) | Hold record edits in Filament for approval, with a field-by-field diff |
| [filament-url-image-uploader](https://github.com/amjadiqbal/filament-url-image-uploader) | Upload images to Filament from a URL |
| [refresher](https://github.com/amjadiqbal/refresher) | Restore a cached test database instead of migrate:fresh |

## Credits

- [Amjad Iqbal](https://github.com/amjadiqbal)
- Storage and restore: [spatie/laravel-deleted-models](https://github.com/spatie/laravel-deleted-models) by Spatie (MIT).
  This plugin is not affiliated with or endorsed by Spatie.

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
