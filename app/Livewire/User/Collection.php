<?php

namespace App\Livewire\User;

use Carbon\Carbon;
use Livewire\Component;

class Collection extends Component
{

    public string $name;
    public Carbon $date;

    public function mount()
    {

    }

    public function save()
    {

    }
    public function render()
    {
        return view('livewire.user.collection');
    }
}
