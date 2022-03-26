<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Okipa\LaravelTable\Abstracts\AbstractTableConfiguration;
use Okipa\LaravelTable\CellActions\Display;
use Okipa\LaravelTable\CellActions\Toggle;
use Okipa\LaravelTable\Column;
use Okipa\LaravelTable\Table;
use Tests\Models\User;
use Tests\TestCase;

class ColumnCellActionTest extends TestCase
{
    /** @test */
    public function it_can_set_column_set_action(): void
    {
        app('router')->get('/user/{user}/show', ['as' => 'user.show']);
        Config::set('laravel-table.icon.active', 'active-icon');
        Config::set('laravel-table.icon.inactive', 'inactive-icon');
        Config::set('laravel-table.icon.display', 'display-icon');
        $users = User::factory()->count(2)->state(new Sequence(
            ['active' => true],
            ['active' => false],
        ))->create();
        $config = new class extends AbstractTableConfiguration {
            protected function table(): Table
            {
                return Table::make()->model(User::class);
            }

            protected function columns(): array
            {
                return [
                    Column::make('Name'),
                    Column::make('Display')->cellAction(fn(User $user) => new Display(route('user.show', $user))),
                    Column::make('Toggle', 'active')->cellAction(fn() => new Toggle()),
                ];
            }
        };
        Livewire::test(\Okipa\LaravelTable\Livewire\Table::class, ['config' => $config::class])
            ->call('init')
            ->assertSeeHtmlInOrder([
                '<tbody>',
                '<tr wire:key="row-' . $users->first()->id . '" class="border-bottom">',
                '<a wire:click.prevent="cellAction(\'' . $users->first()->id . '\', \'display\', 0)"',
                'class="btn btn-outline-primary"',
                'title="Display">',
                'display-icon',
                '<a wire:click.prevent="cellAction(\'' . $users->first()->id . '\', \'active\', 0)"',
                'class="link-danger p-1"',
                'title="Toggle">',
                'inactive-icon',
                '</tr>',
                '<tr wire:key="row-' . $users->last()->id . '" class="border-bottom">',
                '<a wire:click.prevent="cellAction(\'' . $users->last()->id . '\', \'display\', 0)"',
                'class="btn btn-outline-primary"',
                'title="Display">',
                'display-icon',
                '<a wire:click.prevent="cellAction(\'' . $users->last()->id . '\', \'active\', 0)"',
                'class="link-success p-1"',
                'title="Toggle">',
                'active-icon',
                '</tr>',
                '</tbody>',
            ])
            ->call('cellAction', $users->first()->id, 'display', false)
            ->assertRedirect(route('user.show', $users->first()))
            ->call('cellAction', $users->last()->id, 'display', false)
            ->assertRedirect(route('user.show', $users->last()))
            ->call('cellAction', $users->first()->id, 'active', false)
            ->call('cellAction', $users->last()->id, 'active', false);
        $this->assertFalse($users->first()->fresh()->active);
        $this->assertTrue($users->last()->fresh()->active);
    }

    /** @test */
    public function it_can_display_row_action_conditionally(): void
    {
        app('router')->get('/user/{user}/show', ['as' => 'user.show']);
        $users = User::factory()->count(2)->state(new Sequence(
            ['active' => true],
            ['active' => false],
        ))->create();
        $config = new class extends AbstractTableConfiguration {
            protected function table(): Table
            {
                return Table::make()->model(User::class);
            }

            protected function columns(): array
            {
                return [
                    Column::make('Name'),
                    Column::make('Display')
                        ->cellAction(fn(User $user) => (new Display(route('user.show', $user)))
                            ->onlyWhen(fn(User $user) => $user->active)),
                    Column::make('Toggle', 'active')
                        ->cellAction(fn() => (new Toggle())
                            ->onlyWhen(fn(User $user) => ! Auth::user()->is($user))),
                ];
            }
        };
        Livewire::actingAs($users->first())
            ->test(\Okipa\LaravelTable\Livewire\Table::class, ['config' => $config::class])
            ->call('init')
            ->assertSeeHtmlInOrder([
                '<tbody>',
                '<tr wire:key="row-' . $users->first()->id . '" class="border-bottom">',
                '<a wire:click.prevent="cellAction(\'' . $users->first()->id . '\', \'display\', 0)"',
                '</tr>',
                '<tr wire:key="row-' . $users->last()->id . '" class="border-bottom">',
                '<a wire:click.prevent="cellAction(\'' . $users->last()->id . '\', \'active\', 0)"',
                '</tr>',
                '</tbody>',
            ])
            ->assertDontSeeHtml([
                '<a wire:click.prevent="cellAction(\'' . $users->last()->id . '\', \'display\', 0)"',
                '<a wire:click.prevent="cellAction(\'' . $users->first()->id . '\', \'active\', 0)"',
            ]);
    }
}
