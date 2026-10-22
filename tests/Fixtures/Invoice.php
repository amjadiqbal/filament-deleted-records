<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentDeletedRecords\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;
use Spatie\DeletedModels\Models\DeletedModel;

/**
 * @property int $id
 * @property string $number
 * @property string|null $customer
 * @property int $total
 * @property bool $paid
 * @property array<int, array<string, mixed>>|null $lines
 * @property string|null $secret
 */
class Invoice extends Model
{
    use KeepsDeletedModels;

    public static int $restoredHookCalls = 0;

    protected $guarded = [];

    protected $hidden = ['secret'];

    protected $casts = [
        'paid' => 'boolean',
        'lines' => 'array',
    ];

    public static function afterRestoringModel(Model $restoredMode, DeletedModel $deletedModel): void
    {
        static::$restoredHookCalls++;
    }
}
