<?php

namespace App\Livewire;

use App\Models\Set;
use App\Models\Photo;
use Livewire\Component;
use Illuminate\Support\Facades\Storage;

class SetPhotos extends Component
{
    public $setId;
    public $set;
    public $editingPhotoId = null;
    public $editingCaption = '';
    public $editingDescription = '';
    public $editingHideFromPortfolio = false;

    protected $listeners = ['photoUploaded' => '$refresh'];

    public function mount($setId)
    {
        $this->setId = $setId;
        $this->loadSet();
    }

    public function loadSet()
    {
        $this->set = Set::with(['photos', 'collection'])->findOrFail($this->setId);

        // Verify user owns this set
        if ($this->set->collection->user_id !== auth()->id()) {
            abort(403);
        }
    }

    public function editPhoto($photoId)
    {
        $photo = Photo::findOrFail($photoId);

        if ($photo->user_id !== auth()->id()) {
            abort(403);
        }

        $this->editingPhotoId = $photoId;
        $this->editingCaption = $photo->caption ?? '';
        $this->editingDescription = $photo->description ?? '';
        $this->editingHideFromPortfolio = $photo->hide_from_portfolio ?? false;
    }

    public function updatePhoto()
    {
        $this->validate([
            'editingCaption' => 'nullable|string|max:255',
            'editingDescription' => 'nullable|string|max:1000',
            'editingHideFromPortfolio' => 'nullable|boolean',
        ]);

        $photo = Photo::findOrFail($this->editingPhotoId);

        if ($photo->user_id !== auth()->id()) {
            abort(403);
        }

        $photo->update([
            'caption' => $this->editingCaption,
            'description' => $this->editingDescription,
            'hide_from_portfolio' => $this->editingHideFromPortfolio ?? false,
        ]);

        $this->editingPhotoId = null;
        session()->flash('message', 'Photo updated successfully.');
        $this->loadSet();
    }

    public function deletePhoto($photoId)
    {
        $photo = Photo::findOrFail($photoId);

        if ($photo->user_id !== auth()->id()) {
            abort(403);
        }

        // Delete from S3
        Storage::disk('s3')->delete($photo->url);
        Storage::disk('s3')->delete(str_replace('photos/', 'thumbnails/', $photo->url));
        Storage::disk('s3')->delete(str_replace('photos/', 'watermarked/', $photo->url));

        $photo->delete();

        session()->flash('message', 'Photo deleted successfully.');
        $this->loadSet();
    }

    public function bulkDelete($photoIds)
    {
        $photos = Photo::whereIn('id', $photoIds)
            ->where('user_id', auth()->id())
            ->get();

        foreach ($photos as $photo) {
            Storage::disk('s3')->delete($photo->url);
            Storage::disk('s3')->delete(str_replace('photos/', 'thumbnails/', $photo->url));
            Storage::disk('s3')->delete(str_replace('photos/', 'watermarked/', $photo->url));
            $photo->delete();
        }

        session()->flash('message', count($photos) . ' photos deleted successfully.');
        $this->loadSet();
    }

    public function setAsCover($photoId)
    {
        $photo = Photo::findOrFail($photoId);

        // Verify photo belongs to this set and user owns it
        if ($photo->set_id !== $this->setId || $photo->user_id !== auth()->id()) {
            abort(403);
        }
        if (! $photo->isReady()) {
            return; // only finished photos can be the cover
        }

        // Update collection cover photo
        $this->set->collection->update([
            'cover_photo_id' => $photoId
        ]);

        session()->flash('message', 'Cover photo updated successfully.');
        $this->loadSet();
    }

    /** Called by wire:poll while photos are being prepared. */
    public function refreshPhotos(): void
    {
        $this->loadSet();
    }

    public function render()
    {
        return view('livewire.set-photos');
    }
}

