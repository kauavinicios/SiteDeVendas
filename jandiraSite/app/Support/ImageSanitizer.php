<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageSanitizer
{
    /**
     * Armazena uma imagem com nome UUID aleatório e, quando a extensão GD
     * está disponível, recodifica o arquivo para remover metadados EXIF/GPS
     * e descartar bytes anexos fora do stream de imagem.
     */
    public function storeSanitized(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $directory = trim($directory, '/');
        $mimeType = (string) ($file->getMimeType() ?: $file->getClientMimeType());

        $extension = match ($mimeType) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $filename = Str::uuid()->toString().'.'.$extension;
        $relativePath = "{$directory}/{$filename}";

        if (extension_loaded('gd')) {
            $reencoded = $this->reencodeWithGd($file->getRealPath(), $extension);

            if ($reencoded !== null) {
                Storage::disk($disk)->put($relativePath, $reencoded);

                return $relativePath;
            }
        }

        return (string) $file->storeAs($directory, $filename, $disk);
    }

    private function reencodeWithGd(string|false $sourcePath, string $extension): ?string
    {
        if ($sourcePath === false || ! is_file($sourcePath)) {
            return null;
        }

        $image = match ($extension) {
            'png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($sourcePath) : false,
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($sourcePath) : false,
        };

        if ($image === false) {
            return null;
        }

        ob_start();

        $encoded = match ($extension) {
            'png' => $this->outputPng($image),
            'webp' => function_exists('imagewebp') ? @imagewebp($image, null, 88) : false,
            default => function_exists('imagejpeg') ? @imagejpeg($image, null, 88) : false,
        };

        $binary = ob_get_clean();
        unset($image);

        if (! $encoded || ! is_string($binary) || $binary === '') {
            return null;
        }

        return $binary;
    }

    private function outputPng(mixed $image): bool
    {
        if (! function_exists('imagepng')) {
            return false;
        }

        @imagealphablending($image, false);
        @imagesavealpha($image, true);

        return @imagepng($image, null, 6);
    }
}
