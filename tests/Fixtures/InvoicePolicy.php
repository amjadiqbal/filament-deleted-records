<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentDeletedRecords\Tests\Fixtures;

class InvoicePolicy
{
    public static bool $canRestore = true;

    public static bool $canForceDelete = true;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return true;
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return true;
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return static::$canRestore;
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return static::$canForceDelete;
    }
}
