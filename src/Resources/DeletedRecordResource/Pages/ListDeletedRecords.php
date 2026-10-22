<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentDeletedRecords\Resources\DeletedRecordResource\Pages;

use AmjadIqbal\FilamentDeletedRecords\DeletedRecords;
use Filament\Resources\Pages\ListRecords;

class ListDeletedRecords extends ListRecords
{
    public static function getResource(): string
    {
        return DeletedRecords::plugin()->getResource();
    }
}
