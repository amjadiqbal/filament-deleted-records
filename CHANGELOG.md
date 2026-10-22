# Changelog

All notable changes to `amjadiqbal/filament-deleted-records` are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project uses
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/amjadiqbal/filament-deleted-records/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/amjadiqbal/filament-deleted-records/releases/tag/v1.0.0
