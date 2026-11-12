<?php

namespace App\Http\Controllers;

use App\Jobs\ApplyTemplateToPrint;
use App\Models\Font;
use App\Models\GeneratedPrint;
use App\Models\Photo;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        $templates = auth()->user()->templates()->latest()->get();
        return view('templates.index', compact('templates'));
    }

    public function create(): View
    {
        $fonts = Font::availableFor(auth()->id())->get();
        return view('templates.create', compact('fonts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'overlay'     => 'nullable|file|mimes:png,psd|max:20480',
            'fields'      => 'nullable|array',
            'fields.*.key'       => 'required|string|max:50|alpha_dash',
            'fields.*.label'     => 'required|string|max:100',
            'fields.*.x'         => 'required|numeric|min:0|max:100',
            'fields.*.y'         => 'required|numeric|min:0|max:100',
            'fields.*.font_size' => 'required|integer|min:8|max:300',
            'fields.*.color'     => 'required|regex:/^#([A-Fa-f0-9]{6})$/',
            'fields.*.align'     => 'required|in:left,center,right',
            'fields.*.font_id'   => 'nullable|exists:fonts,id',
        ]);

        [$overlayPath, $psdPath] = $this->handleOverlayUpload($request, null, null);

        auth()->user()->templates()->create([
            'name'         => $validated['name'],
            'description'  => $validated['description'] ?? null,
            'overlay_path' => $overlayPath,
            'psd_path'     => $psdPath,
            'fields'       => $validated['fields'] ?? [],
        ]);

        return redirect()->route('templates.index')
            ->with('success', 'Template created successfully.');
    }

    public function edit(Template $template): View
    {
        $this->authorizeTemplate($template);
        $fonts = Font::availableFor(auth()->id())->get();
        return view('templates.edit', compact('template', 'fonts'));
    }

    public function update(Request $request, Template $template)
    {
        $this->authorizeTemplate($template);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'overlay'     => 'nullable|file|mimes:png,psd|max:20480',
            'fields'      => 'nullable|array',
            'fields.*.key'       => 'required|string|max:50|alpha_dash',
            'fields.*.label'     => 'required|string|max:100',
            'fields.*.x'         => 'required|numeric|min:0|max:100',
            'fields.*.y'         => 'required|numeric|min:0|max:100',
            'fields.*.font_size' => 'required|integer|min:8|max:300',
            'fields.*.color'     => 'required|regex:/^#([A-Fa-f0-9]{6})$/',
            'fields.*.align'     => 'required|in:left,center,right',
            'fields.*.font_id'   => 'nullable|exists:fonts,id',
        ]);

        [$overlayPath, $psdPath] = $this->handleOverlayUpload(
            $request,
            $template->overlay_path,
            $template->psd_path
        );

        $template->update([
            'name'         => $validated['name'],
            'description'  => $validated['description'] ?? null,
            'overlay_path' => $overlayPath,
            'psd_path'     => $psdPath,
            'fields'       => $validated['fields'] ?? [],
        ]);

        return redirect()->route('templates.edit', $template)
            ->with('success', 'Template updated.');
    }

    public function destroy(Template $template)
    {
        $this->authorizeTemplate($template);

        if ($template->overlay_path) {
            Storage::disk('s3')->delete($template->overlay_path);
        }
        if ($template->psd_path) {
            Storage::disk('s3')->delete($template->psd_path);
        }

        $template->delete();

        return redirect()->route('templates.index')
            ->with('success', 'Template deleted.');
    }

    // ─────────────────────────────────────────────────────────────
    // Template test — single-photo preview
    // ─────────────────────────────────────────────────────────────

    public function test(Template $template): View
    {
        $this->authorizeTemplate($template);

        $photos = Photo::where('user_id', auth()->id())
            ->latest()
            ->limit(50)
            ->get();

        return view('templates.test', compact('template', 'photos'));
    }

    public function runTest(Request $request, Template $template)
    {
        $this->authorizeTemplate($template);

        $request->validate([
            'photo_id'     => 'required|exists:photos,id',
            'field_values' => 'nullable|array',
        ]);

        $photo = Photo::where('id', $request->input('photo_id'))
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $gp = GeneratedPrint::create([
            'template_id' => $template->id,
            'photo_id'    => $photo->id,
            'print_data'  => $request->input('field_values', []),
            'status'      => 'pending',
        ]);

        // Run synchronously — test results are instant, no queue needed
        try {
            ApplyTemplateToPrint::dispatchSync($gp);
        } catch (\Throwable $e) {
            Log::error('Template test failed', ['error' => $e->getMessage()]);
        }

        $gp->refresh();

        return redirect()->route('templates.testResult', [$template, $gp]);
    }

    public function testResult(Template $template, GeneratedPrint $generatedPrint): View
    {
        $this->authorizeTemplate($template);

        if ($generatedPrint->template_id !== $template->id) {
            abort(404);
        }

        $outputUrl = null;
        if ($generatedPrint->output_path) {
            $outputUrl = Storage::disk('s3')->temporaryUrl($generatedPrint->output_path, now()->addHour());
        }

        return view('templates.test-result', compact('template', 'generatedPrint', 'outputUrl'));
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Handle PNG or PSD overlay upload.
     * PSD files are flattened to PNG via Imagick.
     * Returns [$overlayPath, $psdPath].
     */
    private function handleOverlayUpload(Request $request, ?string $existingOverlay, ?string $existingPsd): array
    {
        if (!$request->hasFile('overlay')) {
            return [$existingOverlay, $existingPsd];
        }

        if ($existingOverlay) {
            Storage::disk('s3')->delete($existingOverlay);
        }
        if ($existingPsd) {
            Storage::disk('s3')->delete($existingPsd);
        }

        $file      = $request->file('overlay');
        $extension = strtolower($file->getClientOriginalExtension());
        $prefix    = 'templates/' . auth()->id();

        if ($extension === 'psd') {
            return $this->processPsdUpload($file, $prefix);
        }

        return [$file->store($prefix, 's3'), null];
    }

    /**
     * Flatten a PSD using Imagick, store the resulting PNG as the overlay.
     * The original PSD is also kept for reference.
     * Returns [$overlayPath, $psdPath].
     */
    private function processPsdUpload(\Illuminate\Http\UploadedFile $file, string $prefix): array
    {
        $psdPath = $file->store($prefix . '/psd', 's3');

        try {
            $imagick = new \Imagick();
            $imagick->readImage($file->getRealPath());

            // Index 0 is the merged composite in PSD files
            $imagick->setIteratorIndex(0);
            $layer = $imagick->getImage();
            $layer->setImageFormat('png');
            $layer->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);

            $pngContent = $layer->getImageBlob();
            $layer->clear();
            $imagick->clear();

            $pngKey = $prefix . '/' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time() . '.png';
            Storage::disk('s3')->put($pngKey, $pngContent);

            return [$pngKey, $psdPath];
        } catch (\Throwable $e) {
            Log::error('PSD flatten failed', ['error' => $e->getMessage()]);
            return [$psdPath, $psdPath]; // use PSD path as fallback so admin can debug
        }
    }

    private function authorizeTemplate(Template $template): void
    {
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
