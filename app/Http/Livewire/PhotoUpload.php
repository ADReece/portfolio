<?php

namespace App\Http\Livewire;

use Livewire\Component;

class PhotoUpload extends Component
{
    public $setId;
    public $uploadCount = 0;

    protected $listeners = ['fileUploaded' => 'handleFileUploaded'];

    public function mount($setId = null)
    {
        $this->setId = $setId;
    }

    public function handleFileUploaded()
    {
        $this->uploadCount++;

        // Emit event to refresh the photos grid
        $this->emit('photoUploaded');
    }

    public function render()
    {
        $user = auth()->user();
        $limit = $user?->subscriptionPlan?->photo_limit;
        $unlimited = ! $user || $user->isFeatureOverrideActive() || $user->is_admin || $limit === null;
        $used = $unlimited ? null : $user->photos()->count();

        return view('livewire.photo-upload', [
            'photoLimit' => $unlimited ? null : (int) $limit,
            'photosUsed' => $used,
            'photosRemaining' => $unlimited ? null : max(0, (int) $limit - $used),
        ]);
    }
}
