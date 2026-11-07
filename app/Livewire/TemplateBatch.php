<?php

namespace App\Livewire;

use App\Jobs\ApplyTemplateToPrint;
use App\Models\Collection;
use App\Models\GeneratedPrint;
use App\Models\Template;
use Livewire\Component;

class TemplateBatch extends Component
{
    // Step 1: pick template
    public string $step = 'select_template';
    public ?string $selectedTemplateId = null;

    // Collection context
    public string $collectionId;

    // Per-photo data keyed by photo_id: ['player_name' => '...', 'jersey_number' => '...']
    public array $photoData = [];

    // Tracks generated print IDs so we can poll status
    public array $generatedPrintIds = [];

    public bool $submitted = false;

    public function mount(string $collectionId): void
    {
        $this->collectionId = $collectionId;
    }

    public function selectTemplate(string $templateId): void
    {
        $this->selectedTemplateId = $templateId;
    }

    public function proceed(): void
    {
        $this->validate(['selectedTemplateId' => 'required|exists:templates,id']);

        // Confirm template belongs to user
        $template = Template::where('id', $this->selectedTemplateId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Pre-populate photoData with empty strings for each field key
        $fieldKeys = array_column($template->fields, 'key');
        $photos    = $this->getPhotos();

        foreach ($photos as $photo) {
            $this->photoData[$photo->id] = array_fill_keys($fieldKeys, '');
        }

        $this->step = 'enter_data';
    }

    public function generate(): void
    {
        $template = Template::where('id', $this->selectedTemplateId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $photos = $this->getPhotos();

        foreach ($photos as $photo) {
            $data = $this->photoData[$photo->id] ?? [];

            $print = GeneratedPrint::create([
                'photo_id'    => $photo->id,
                'template_id' => $template->id,
                'print_data'  => $data,
                'status'      => 'pending',
            ]);

            ApplyTemplateToPrint::dispatch($print);

            $this->generatedPrintIds[] = $print->id;
        }

        $this->submitted = true;
        $this->step      = 'processing';
    }

    public function render()
    {
        $templates = Template::where('user_id', auth()->id())
            ->where('active', true)
            ->latest()
            ->get();

        $selectedTemplate = $this->selectedTemplateId
            ? $templates->firstWhere('id', $this->selectedTemplateId)
            : null;

        $photos = $this->step !== 'select_template' ? $this->getPhotos() : collect();

        $prints = $this->step === 'processing'
            ? GeneratedPrint::whereIn('id', $this->generatedPrintIds)
                ->with('photo')
                ->get()
            : collect();

        $allDone = $this->step === 'processing' && $prints->isNotEmpty()
            && $prints->every(fn ($p) => !$p->isPending());

        $collection = auth()->user()->collections()->findOrFail($this->collectionId);

        return view('livewire.template-batch', compact(
            'collection', 'templates', 'selectedTemplate', 'photos', 'prints', 'allDone'
        ))->title('Make prints · '.$collection->name);
    }

    private function getPhotos()
    {
        $collection = auth()->user()->collections()->findOrFail($this->collectionId);
        return $collection->sets()
            ->with('photos')
            ->get()
            ->pluck('photos')
            ->flatten();
    }
}
