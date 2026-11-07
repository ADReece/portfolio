<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Collection;
use Illuminate\Support\Facades\Hash;

class CollectionForm extends Component
{
    public $name;
    public $event_date;
    public $private = false;
    public $password;

    protected $rules = [
        'name' => 'required|string|max:255',
        'event_date' => 'required|date',
        'private' => 'boolean',
        'password' => 'nullable|string|min:6|required_if:private,true',
    ];

    public function saveCollection()
    {
        $this->validate();

        $collection = Collection::create([
            'name' => $this->name,
            'user_id' => auth()->id(),
            'status' => 'Draft',
            'event_date' => $this->event_date,
            'private' => $this->private,
            'password' => $this->private ? Hash::make($this->password) : null,
        ]);

        session()->flash('message', 'Collection successfully created.');

        // Redirect to the set creation page after collection is created
        return redirect()->route('sets.create', $collection->id);
    }

    public function render()
    {
        return view('livewire.collection-form');
    }
}
