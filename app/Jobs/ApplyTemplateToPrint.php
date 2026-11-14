<?php

namespace App\Jobs;

use App\Models\Font;
use App\Models\GeneratedPrint;
use App\Models\Photo;
use App\Models\Template;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ApplyTemplateToPrint implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        public GeneratedPrint $generatedPrint
    ) {}

    public function handle(): void
    {
        $this->generatedPrint->update(['status' => 'processing']);

        try {
            $photo    = $this->generatedPrint->photo;
            $template = $this->generatedPrint->template;
            $data     = $this->generatedPrint->print_data ?? [];

            // ImageManager instantiated here (not in constructor) to keep the job serializable.
            $manager = new \Intervention\Image\ImageManager(['driver' => 'imagick']);

            // Load the original photo from S3
            $photoContent = Storage::disk('s3')->get($photo->url);
            $image = $manager->make($photoContent);
            $image->orientate();

            \App\Prints\TemplateRenderer::apply($image, $template, $data);

            // Save the generated print to S3
            $outputKey = 'prints/' . $this->generatedPrint->id . '.jpg';
            Storage::disk('s3')->put($outputKey, (string) $image->encode('jpg', 92));

            $this->generatedPrint->update([
                'output_path' => $outputKey,
                'status'      => 'completed',
            ]);
        } catch (\Throwable $e) {
            Log::error('ApplyTemplateToPrint failed', [
                'generated_print_id' => $this->generatedPrint->id,
                'error' => $e->getMessage(),
            ]);

            $this->generatedPrint->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
