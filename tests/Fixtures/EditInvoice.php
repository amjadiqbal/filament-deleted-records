<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentDeletedRecords\Tests\Fixtures;

use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;
}
