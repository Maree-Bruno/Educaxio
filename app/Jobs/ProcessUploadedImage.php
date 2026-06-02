<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ProcessUploadedImage implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $fullPathToOriginal,
        public string $filename,
        public string $configKey
    ) {}

    public function handle(): void
    {
        $config = config($this->configKey);
        $disk = $config['disk'];
        $imageData = Storage::disk($disk)->get($this->fullPathToOriginal);
        $image = Image::decode($imageData);

        foreach ($config['sizes'] as $size) {
            $variant = clone $image;
            $variant->scale($size['width'], $size['height']);

            $path = sprintf($config['reformat_path'], $size['width'], $size['height']);
            $encoded = $variant->encodeUsingFileExtension($config['format'][0], $config['compression']);

            Storage::disk($disk)->put($path.'/'.$this->filename, $encoded);
        }
    }
}
