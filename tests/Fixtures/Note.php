<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentDeletedRecords\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;

/**
 * @property int $id
 * @property string $body
 */
class Note extends Model
{
    use KeepsDeletedModels;

    protected $guarded = [];
}
