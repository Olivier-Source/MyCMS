<?php

namespace App\View\Components;

use App\Cms\Icons;
use Illuminate\View\Component;

/**
 * <x-icon name="call" class="text-[20px] text-primary" />
 * La taille suit la taille de police (1em), comme une police d'icônes.
 */
class Icon extends Component
{
    public function __construct(public ?string $name = null) {}

    public function render(): \Closure
    {
        return fn (array $data) => Icons::svg($this->name, (string) ($data['attributes']['class'] ?? ''));
    }
}
