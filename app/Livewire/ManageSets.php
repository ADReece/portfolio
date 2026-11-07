<?php

namespace App\Livewire;

use App\Models\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Models\Set;

class ManageSets extends Component
{
    use WithFileUploads;

    public $collectionId;
    public $setName = '';
    public $editingSetId = null;
    public $editingSetName = '';

    public function mount($collectionId)
    {
        $this->collectionId = $collectionId;
    }

    protected $rules = [
        'setName' => 'required|string|max:255',
    ];

    public function createSet()
    {
        $this->validate();

        $collection = Collection::findOrFail($this->collectionId);

        // Verify user owns this collection
        if ($collection->user_id !== auth()->id()) {
            abort(403);
        }

        $collection->sets()->create([
            'name' => $this->setName,
        ]);

        $this->setName = '';
        session()->flash('message', 'Set created successfully.');
    }

    public function editSet($setId)
    {
        $set = Set::findOrFail($setId);
        $this->editingSetId = $setId;
        $this->editingSetName = $set->name;
    }

    public function updateSet()
    {
        $this->validate(['editingSetName' => 'required|string|max:255']);

        $set = Set::findOrFail($this->editingSetId);

        if ($set->collection->user_id !== auth()->id()) {
            abort(403);
        }

        $set->update(['name' => $this->editingSetName]);

        $this->editingSetId = null;
        $this->editingSetName = '';
        session()->flash('message', 'Set updated successfully.');
    }

    public function cancelEdit()
    {
        $this->editingSetId = null;
        $this->editingSetName = '';
    }

    public function deleteSet($setId)
    {
        $set = Set::findOrFail($setId);

        if ($set->collection->user_id !== auth()->id()) {
            abort(403);
        }

        $set->delete();
        session()->flash('message', 'Set deleted successfully.');
    }

    public function render()
    {
        $collection = Collection::findOrFail($this->collectionId);

        // Verify user owns this collection
        if ($collection->user_id !== auth()->id()) {
            abort(403);
        }

        return view('livewire.manage-sets', [
            'collection' => $collection,
            'sets' => $collection->sets()->with('photos')->get(),
        ]);
    }
}
