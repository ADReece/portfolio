<?php
namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\ImageManagerStatic as Image;

class LogoUploader extends Component
{
    use WithFileUploads;

    public $logo; // temporary uploaded file
    public $user;
    public $canUpload = false;
    public $currentLogoUrl;

    protected $listeners = ['refreshLogo' => 'loadLogo'];

    public function mount($user)
    {
        $this->user = $user;
        $this->canUpload = $this->user->hasFeature('upload_logo') || $this->user->isFeatureOverrideActive();
        $this->loadLogo();
    }

    public function loadLogo(): void
    {
        if ($this->user->logo_thumb_path ?? $this->user->logo_path) {
            $cacheKey = 'user_logo_url_'.$this->user->id;
            $this->currentLogoUrl = Cache::remember($cacheKey, 600, function () {
                return $this->user->logo_thumb_path
                    ? Storage::disk('s3')->url($this->user->logo_thumb_path)
                    : Storage::disk('s3')->url($this->user->logo_path);
            });
        } else {
            $this->currentLogoUrl = null;
        }
    }

    public function updatedLogo(): void
    {
        if (!$this->canUpload) {
            $this->addError('logo', 'Logo uploads are available to subscribers only.');
            $this->logo = null;
            return;
        }

        $this->validate([
            'logo' => 'file|max:2048|mimes:png,jpg,jpeg,webp,svg'
        ]);
        // If SVG, we skip raster integrity check.
    }

    public function save(): void
    {
        if (!$this->canUpload) {
            $this->addError('logo', 'Subscribers only.');
            return;
        }
        if (!$this->logo) return;

        // Remove previous assets
        if ($this->user->logo_path) {
            Storage::disk('s3')->delete($this->user->logo_path);
        }
        if ($this->user->logo_thumb_path) {
            Storage::disk('s3')->delete($this->user->logo_thumb_path);
        }

        // Store original
        $originalPath = $this->logo->store('logos', 's3');
        $this->user->logo_path = $originalPath;

        // Generate thumbnail for raster formats
        if (strtolower($this->logo->getClientOriginalExtension()) !== 'svg') {
            $image = Image::make($this->logo->getRealPath())
                ->resize(160, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            $thumbName = 'logos/thumb_'.uniqid().'.'.$this->logo->getClientOriginalExtension();
            Storage::disk('s3')->put($thumbName, (string) $image->encode());
            $this->user->logo_thumb_path = $thumbName;
        } else {
            $this->user->logo_thumb_path = null;
        }

        $this->user->save();
        Cache::forget('user_logo_url_'.$this->user->id);
        $this->logo = null;
        $this->loadLogo();
        $this->dispatch('logo-updated', url: $this->currentLogoUrl ?? asset('favicon.ico'));
        $this->dispatch('refreshLogo');
        session()->flash('logo-status', 'Logo updated successfully');
    }

    public function remove(): void
    {
        if ($this->user->logo_path) {
            Storage::disk('s3')->delete($this->user->logo_path);
        }
        if ($this->user->logo_thumb_path) {
            Storage::disk('s3')->delete($this->user->logo_thumb_path);
        }
        $this->user->logo_path = null;
        $this->user->logo_thumb_path = null;
        $this->user->save();
        Cache::forget('user_logo_url_'.$this->user->id);
        $this->currentLogoUrl = null;
        $this->dispatch('logo-updated', url: asset('favicon.ico'));
        session()->flash('logo-status', 'Logo removed');
    }

    public function render()
    {
        return view('livewire.logo-uploader');
    }
}
