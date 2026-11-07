<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Collection;
use App\Models\Set;
use Illuminate\Support\Facades\Hash;

class CollectionWizard extends Component
{
    // Step management
    public $currentStep = 1;

    // Collection properties
    public $collectionId = null;
    public $name;
    public $event_date;
    public $private = false;
    public $watermarked = false;
    public $hide_from_portfolio = false;
    public $password;

    // Set management
    public $setName = '';
    public $editingSetId = null;
    public $editingSetName = '';
    public $quickSetTemplates = [
        'Getting Ready',
        'Ceremony',
        'Reception',
        'Portraits',
        'Family',
        'Details',
    ];

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'event_date' => 'nullable|date',
            'private' => 'boolean',
            'watermarked' => 'boolean',
            'hide_from_portfolio' => 'boolean',
            'password' => 'nullable|string|min:6|required_if:private,true',
            'setName' => 'required|string|max:255',
        ];
    }

    public function mount($collectionId = null)
    {
        $this->event_date = now()->toDateString();

        if ($collectionId) {
            $this->collectionId = $collectionId;
            $collection = Collection::findOrFail($collectionId);

            // Verify ownership
            if ($collection->user_id !== auth()->id()) {
                abort(403);
            }

            // Skip to step 2 if editing existing collection
            $this->currentStep = 2;
        }
    }

    public function saveCollection()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'event_date' => 'nullable|date',
            'private' => 'boolean',
            'watermarked' => 'boolean',
            'hide_from_portfolio' => 'boolean',
            'password' => 'nullable|string|min:6|required_if:private,true',
        ]);

        $collection = Collection::create([
            'name' => $this->name,
            'user_id' => auth()->id(),
            'status' => 'Draft',
            'event_date' => $this->event_date ?: null,
            'private' => $this->private,
            'watermarked' => $this->watermarked,
            'hide_from_portfolio' => $this->hide_from_portfolio ?? $this->private,
            'password' => $this->private ? Hash::make($this->password) : null,
        ]);

        $this->collectionId = $collection->id;
        $this->currentStep = 2;

        session()->flash('message', 'Collection created successfully! Now create sets to organize your photos.');
    }

    public function createSet()
    {
        $this->validate(['setName' => 'required|string|max:255']);

        $this->createSetByName($this->setName);
    }

    public function createSetFromTemplate(string $templateName)
    {
        $this->createSetByName($templateName);
    }

    private function createSetByName(string $name): void
    {
        $normalized = trim($name);

        if ($normalized === '') {
            return;
        }

        $collection = auth()->user()->collections()->findOrFail($this->collectionId);

        if ($collection->user_id !== auth()->id()) {
            abort(403);
        }

        $exists = $collection->sets()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($normalized)])
            ->exists();

        if ($exists) {
            session()->flash('message', 'That set already exists. Try a different name.');
            return;
        }

        $collection->sets()->create([
            'name' => $normalized,
        ]);

        $this->setName = '';
        $this->dispatch('set-created');
        session()->flash('message', 'Set created.');
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

    public function finishAndRedirect()
    {
        // Land in the new collection so the next step (uploading photos) is right there.
        if ($this->collectionId) {
            return redirect()->route('collections.edit', $this->collectionId)
                ->with('success', 'Collection created. Next, upload photos to each set.');
        }

        return redirect()->route('collections.index');
    }

    public function render()
    {
        $collection = null;
        $sets = collect();

        if ($this->collectionId) {
            $collection = auth()->user()->collections()->findOrFail($this->collectionId);

            if ($collection->user_id !== auth()->id()) {
                abort(403);
            }

            $sets = $collection->sets()->withCount('photos')->get();
        }

        return view('livewire.collection-wizard', [
            'collection' => $collection,
            'sets' => $sets,
        ]);
    }
}

