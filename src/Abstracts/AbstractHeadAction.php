<?php

namespace Okipa\LaravelTable\Abstracts;

use Illuminate\Contracts\View\View;
use Livewire\Component;

abstract class AbstractHeadAction
{
    public string $rowActionClass;

    protected string|null $class;

    protected string $title;

    protected string $icon;

    abstract protected function class(): string|null;

    abstract protected function title(): string;

    abstract protected function icon(): string;

    /** @return mixed|void */
    abstract public function action(Component $livewire);

    public function setup(): void
    {
        $this->rowActionClass = $this::class;
    }

    public static function make(array $rowActionArray): self
    {
        $headActionInstance = app($rowActionArray['rowActionClass'], $rowActionArray);
        $headActionInstance->rowActionClass = $rowActionArray['rowActionClass'];

        return $headActionInstance;
    }

    public function render(): View
    {
        return view('laravel-table::' . config('laravel-table.ui') . '.head-action', [
            'class' => $this->class(),
            'title' => $this->title(),
            'icon' => $this->icon(),
        ]);
    }
}
