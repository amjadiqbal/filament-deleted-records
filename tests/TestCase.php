<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentDeletedRecords\Tests;

use AmjadIqbal\FilamentDeletedRecords\DeletedRecords;
use AmjadIqbal\FilamentDeletedRecords\FilamentDeletedRecordsServiceProvider;
use AmjadIqbal\FilamentDeletedRecords\Tests\Fixtures\AdminPanelProvider;
use AmjadIqbal\FilamentDeletedRecords\Tests\Fixtures\Invoice;
use AmjadIqbal\FilamentDeletedRecords\Tests\Fixtures\User;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kirschbaum\PowerJoins\PowerJoinsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Spatie\DeletedModels\DeletedModelsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        DeletedRecords::flushCache();
    }

    protected function getPackageProviders($app): array
    {
        $providers = [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            PowerJoinsServiceProvider::class,
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            'Filament\\QueryBuilder\\QueryBuilderServiceProvider',
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            DeletedModelsServiceProvider::class,
            FilamentDeletedRecordsServiceProvider::class,
            AdminPanelProvider::class,
        ];

        return array_values(array_filter($providers, 'class_exists'));
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('number');
            $table->string('customer')->nullable();
            $table->unsignedInteger('total')->default(0);
            $table->boolean('paid')->default(false);
            $table->json('lines')->nullable();
            $table->string('secret')->nullable();
            $table->timestamps();
        });

        Schema::create('notes', function (Blueprint $table): void {
            $table->id();
            $table->string('body');
            $table->timestamps();
        });

        (include __DIR__.'/../vendor/spatie/laravel-deleted-models/database/migrations/create_deleted_models_table.php')->up();
        (include __DIR__.'/../database/migrations/add_deleted_by_to_deleted_models_table.php.stub')->up();
    }

    protected function makeUser(string $name = 'Admin'): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => strtolower($name).'@example.test',
            'password' => 'secret-password',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeInvoice(array $attributes = []): Invoice
    {
        return Invoice::query()->create([
            'number' => 'INV-'.random_int(1000, 9999),
            'customer' => 'Acme',
            'total' => 1200,
            'paid' => false,
            'lines' => [['item' => 'Widget', 'qty' => 2]],
            ...$attributes,
        ]);
    }
}
