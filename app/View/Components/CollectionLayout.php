<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\User;

class CollectionLayout extends Component
{
    public $user;

    /**
     * @param  User|null  $user  The photographer whose page this is (branding).
     * @param  string|null  $title  Browser tab / link preview title.
     * @param  string|null  $description  Link preview description.
     * @param  string|null  $image  Link preview image URL.
     * @param  bool  $noindex  Keep search engines out (private galleries).
     */
    public function __construct(
        $user = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public bool $noindex = false,
    ) {
        $this->user = $user;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.collection-layout');
    }
}
