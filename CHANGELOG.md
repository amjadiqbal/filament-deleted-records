# Changelog

All notable changes to `amjadiqbal/filament-deleted-records` are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project uses
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.1] - 2026-10-22

### Security

- **Search no longer looks inside every stored attribute.** It matched the whole stored JSON with LIKE, so a user could
  probe values the preview masks (for example, a password hash) by searching for them. Search now matches the key
  exactly and the title attributes case-insensitively, and never a masked attribute. Please update.

### Changed

- Bulk restore and bulk permanent delete report records skipped for permission separately from failed restores. Before,
  bulk restore counted skipped records as "could not be restored", and bulk delete did not mention them.
- The README states the defaults plainly: without a policy or callback, any panel user can restore and purge. It also
  covers tenancy, the limits of "Deleted by", and that masking is on screen only.
- Homepage and docs links point to https://amjad.com.pk/filament/plugins/filament-deleted-records.

### Added

- The tests run on MySQL or Postgres with `DB_TEST_DRIVER`. Checked on MySQL 9.7, Postgres 17 and SQLite.

## [1.0.0] - 2026-10-22

First release.

### Added

- Deleted records page for records kept by `spatie/laravel-deleted-models`: title, key, type, who deleted it and when.
- Preview modal with every stored attribute (secrets masked) and a restore check (key taken, model class missing).
- Restore through the model's own `restore()`, with a link to the restored record when the panel has a resource for it.
- Delete permanently, plus bulk restore and bulk permanent delete.
- Filters by type, by who deleted it and by date range; search over the key and stored attributes.
- Optional `deleted_by_id` migration, filled with the signed-in user.
- Permissions: `authorizeUsing()`, a policy for the deleted-record model, or the original model's policy.
- Filament 4 and 5, Laravel 12 and 13, PHP 8.3 to 8.5. English translations.

[Unreleased]: https://github.com/amjadiqbal/filament-deleted-records/compare/v1.0.1...HEAD
[1.0.1]: https://github.com/amjadiqbal/filament-deleted-records/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/amjadiqbal/filament-deleted-records/releases/tag/v1.0.0
