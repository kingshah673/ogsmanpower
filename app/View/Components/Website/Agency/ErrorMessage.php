<?php

namespace App\View\Components\Website\Agency;

use Illuminate\View\Component;

class ErrorMessage extends Component
{
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function render()
    {
        return view('components.website.agency.error-message');
    }
}
