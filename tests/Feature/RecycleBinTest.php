<?php

declare(strict_types=1);

use AmjadIqbal\FilamentDeletedRecords\DeletedRecords;
use AmjadIqbal\FilamentDeletedRecords\DeletedRecordsPlugin;
use AmjadIqbal\FilamentDeletedRecords\Resources\DeletedRecordResource;
use AmjadIqbal\FilamentDeletedRecords\Resources\DeletedRecordResource\Pages\ListDeletedRecords;
use AmjadIqbal\FilamentDeletedRecords\Tests\Fixtures\Invoice;
use AmjadIqbal\FilamentDeletedRecords\Tests\Fixtures\InvoicePolicy;
use AmjadIqbal\FilamentDeletedRecords\Tests\Fixtures\Note;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\DeletedModels\Models\DeletedModel;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    InvoicePolicy::$canRestore = true;
    InvoicePolicy::$canForceDelete = true;
    Invoice::$restoredHookCalls = 0;

    $this->admin = $this->makeUser('Admin');
    $this->actingAs($this->admin);

    $this->invoice = $this->makeInvoice(['number' => 'INV-1042', 'secret' => 'shh']);
    $this->invoice->delete();
    $this->deletedInvoice = DeletedModel::query()->where('model', $this->invoice->getMorphClass())->sole();

    $this->note = Note::query()->create(['body' => 'Call back']);
    $this->note->delete();
    $this->deletedNote = DeletedModel::query()->where('model', $this->note->getMorphClass())->sole();
});

function plugin(): DeletedRecordsPlugin
{
    /** @var DeletedRecordsPlugin */
    return Filament::getPanel('admin')->getPlugin(DeletedRecordsPlugin::ID);
}

it('lists every deleted record with a title, type and who deleted it', function (): void {
    livewire(ListDeletedRecords::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->deletedInvoice, $this->deletedNote])
        ->assertTableColumnStateSet('record', 'INV-1042', $this->deletedInvoice)
        ->assertTableColumnStateSet('record', '#'.$this->note->id, $this->deletedNote)
        ->assertTableColumnFormattedStateSet('model', 'Invoice', $this->deletedInvoice)
        ->assertTableColumnStateSet('deleted_by', 'Admin', $this->deletedInvoice);
});

it('records who deleted the model', function (): void {
    expect($this->deletedInvoice->deleted_by_id)->toBe((string) $this->admin->id)
        ->and(DeletedRecords::deletedByName($this->deletedInvoice))->toBe('Admin');
});

it('leaves deleted_by empty when nobody is signed in', function (): void {
    auth()->logout();

    $invoice = $this->makeInvoice(['number' => 'INV-2000']);
    $invoice->delete();

    expect(DeletedModel::query()->where('key', $invoice->id)->where('model', $invoice->getMorphClass())->sole()->deleted_by_id)->toBeNull();
});

it('filters by type', function (): void {
    livewire(ListDeletedRecords::class)
        ->filterTable('model', [$this->invoice->getMorphClass()])
        ->assertCanSeeTableRecords([$this->deletedInvoice])
        ->assertCanNotSeeTableRecords([$this->deletedNote]);
});

it('filters by who deleted it', function (): void {
    $other = $this->makeUser('Omar');
    $this->actingAs($other);
    $invoice = $this->makeInvoice(['number' => 'INV-3000']);
    $invoice->delete();
    $byOmar = DeletedModel::query()->where('key', $invoice->id)->where('model', $invoice->getMorphClass())->sole();

    expect(DeletedRecords::deletedByOptions())->toBe([(string) $this->admin->id => 'Admin', (string) $other->id => 'Omar']);

    livewire(ListDeletedRecords::class)
        ->filterTable('deleted_by_id', (string) $other->id)
        ->assertCanSeeTableRecords([$byOmar])
        ->assertCanNotSeeTableRecords([$this->deletedInvoice, $this->deletedNote]);
});

it('filters by date deleted', function (): void {
    $this->deletedNote->forceFill(['created_at' => now()->subDays(10)])->save();

    livewire(ListDeletedRecords::class)
        ->filterTable('deleted_at', ['deleted_from' => now()->subDay()->toDateString(), 'deleted_until' => null])
        ->assertCanSeeTableRecords([$this->deletedInvoice])
        ->assertCanNotSeeTableRecords([$this->deletedNote])
        ->filterTable('deleted_at', ['deleted_from' => null, 'deleted_until' => now()->subDays(5)->toDateString()])
        ->assertCanSeeTableRecords([$this->deletedNote])
        ->assertCanNotSeeTableRecords([$this->deletedInvoice]);
});

it('searches the stored attributes and the key', function (): void {
    livewire(ListDeletedRecords::class)
        ->searchTable('Call back')
        ->assertCanSeeTableRecords([$this->deletedNote])
        ->assertCanNotSeeTableRecords([$this->deletedInvoice])
        ->searchTable('INV-1042')
        ->assertCanSeeTableRecords([$this->deletedInvoice])
        ->assertCanNotSeeTableRecords([$this->deletedNote])
        ->searchTable('100%')
        ->assertCanNotSeeTableRecords([$this->deletedInvoice, $this->deletedNote]);
});

it('restores a record through the model, so its restore hooks run', function (): void {
    livewire(ListDeletedRecords::class)
        ->callTableAction('restoreDeletedRecord', $this->deletedInvoice)
        ->assertNotified('Restored Invoice INV-1042');

    $restored = Invoice::query()->find($this->invoice->id);

    expect($restored)->not->toBeNull()
        ->and($restored->number)->toBe('INV-1042')
        ->and($restored->lines)->toBe([['item' => 'Widget', 'qty' => 2]])
        ->and($restored->secret)->toBe('shh')
        ->and(DeletedModel::query()->find($this->deletedInvoice->id))->toBeNull()
        ->and(Invoice::$restoredHookCalls)->toBe(1);
});

it('links the restored record to its resource page', function (): void {
    $restored = DeletedRecords::restore($this->deletedInvoice);

    expect(DeletedRecords::recordUrl($restored))->toEndWith('/admin/invoices/'.$restored->id.'/edit');
});

it('explains a restore that cannot work and keeps the stored copy', function (): void {
    DB::table('invoices')->insert(['id' => $this->invoice->id, 'number' => 'INV-NEW', 'total' => 1, 'created_at' => now(), 'updated_at' => now()]);

    expect(DeletedRecords::restoreStatus($this->deletedInvoice))->toBe(DeletedRecords::STATUS_CONFLICT);

    livewire(ListDeletedRecords::class)
        ->callTableAction('restoreDeletedRecord', $this->deletedInvoice)
        ->assertNotified('Could not restore Invoice INV-1042');

    expect(DeletedModel::query()->find($this->deletedInvoice->id))->not->toBeNull()
        ->and(Invoice::query()->find($this->invoice->id)->number)->toBe('INV-NEW');
});

it('reports a stored record whose model class is gone', function (): void {
    $this->deletedNote->forceFill(['model' => 'App\\Models\\Removed'])->save();

    expect(DeletedRecords::restoreStatus($this->deletedNote))->toBe(DeletedRecords::STATUS_MISSING_CLASS)
        ->and(DeletedRecords::modelLabel('App\\Models\\Removed'))->toBe('Removed');
});

it('deletes a stored copy permanently', function (): void {
    livewire(ListDeletedRecords::class)
        ->callTableAction('forceDeleteDeletedRecord', $this->deletedNote)
        ->assertNotified('Deleted permanently');

    expect(DeletedModel::query()->find($this->deletedNote->id))->toBeNull()
        ->and(Note::query()->find($this->note->id))->toBeNull();
});

it('restores in bulk and reports the ones that failed', function (): void {
    DB::table('invoices')->insert(['id' => $this->invoice->id, 'number' => 'INV-NEW', 'total' => 1, 'created_at' => now(), 'updated_at' => now()]);

    livewire(ListDeletedRecords::class)
        ->callTableBulkAction('restoreDeletedRecords', [$this->deletedInvoice, $this->deletedNote])
        ->assertNotified('Restored 1 record');

    expect(Note::query()->find($this->note->id))->not->toBeNull()
        ->and(DeletedModel::query()->pluck('id')->all())->toBe([$this->deletedInvoice->id]);
});

it('deletes in bulk permanently', function (): void {
    livewire(ListDeletedRecords::class)
        ->callTableBulkAction('forceDeleteDeletedRecords', [$this->deletedInvoice, $this->deletedNote])
        ->assertNotified('Deleted 2 records permanently');

    expect(DeletedModel::query()->count())->toBe(0);
});

it('previews the stored attributes with secrets masked', function (): void {
    plugin()->maskedAttributes(['secret']);

    $state = DeletedRecords::previewState($this->deletedInvoice);

    expect($state['number'])->toBe('INV-1042')
        ->and($state['paid'])->toBe('false')
        ->and($state['lines'])->toBe('[{"item":"Widget","qty":2}]')
        ->and($state['secret'])->toBe('•••••• (hidden)');

    expect(DeletedRecords::restoreStatus($this->deletedInvoice))->toBe(DeletedRecords::STATUS_READY);

    livewire(ListDeletedRecords::class)
        ->mountAction(TestAction::make('view')->table($this->deletedInvoice))
        ->assertActionMounted(TestAction::make('view')->table($this->deletedInvoice));
});

it('follows the original model policy for restore and permanent delete', function (): void {
    Gate::policy(Invoice::class, InvoicePolicy::class);
    InvoicePolicy::$canRestore = false;
    InvoicePolicy::$canForceDelete = false;

    livewire(ListDeletedRecords::class)
        ->assertTableActionHidden('restoreDeletedRecord', $this->deletedInvoice)
        ->assertTableActionHidden('forceDeleteDeletedRecord', $this->deletedInvoice)
        ->assertTableActionVisible('restoreDeletedRecord', $this->deletedNote)
        ->callTableBulkAction('restoreDeletedRecords', [$this->deletedInvoice, $this->deletedNote]);

    expect(Invoice::query()->find($this->invoice->id))->toBeNull()
        ->and(Note::query()->find($this->note->id))->not->toBeNull();
});

it('lets authorizeUsing() decide every ability', function (): void {
    plugin()->authorizeUsing(fn (string $ability): bool => $ability !== 'viewAny');

    expect(DeletedRecordResource::canViewAny())->toBeFalse();

    $this->get(DeletedRecordResource::getUrl('index'))->assertForbidden();
});

it('shows only the models it is limited to', function (): void {
    plugin()->models([Invoice::class]);

    livewire(ListDeletedRecords::class)
        ->assertCanSeeTableRecords([$this->deletedInvoice])
        ->assertCanNotSeeTableRecords([$this->deletedNote]);

    expect(DeletedRecordResource::getNavigationBadge())->toBe('1');
});

it('uses custom model labels and navigation settings', function (): void {
    plugin()->modelLabels([Invoice::class => 'Sales invoice'])->navigationGroup('System')->navigationLabel('Recycle bin');

    expect(DeletedRecords::modelLabel($this->invoice->getMorphClass()))->toBe('Sales invoice')
        ->and(DeletedRecordResource::getNavigationGroup())->toBe('System')
        ->and(DeletedRecordResource::getNavigationLabel())->toBe('Recycle bin')
        ->and(DeletedRecordResource::getNavigationBadge())->toBe('2');
});

it('renders the page over HTTP', function (): void {
    $this->get(DeletedRecordResource::getUrl('index'))
        ->assertOk()
        ->assertSee('INV-1042');
});
