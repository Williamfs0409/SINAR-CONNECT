<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ImageUploadService
{
    private const MAX_DIMENSION = 1920;

    public function store(UploadedFile $file, string $directory, string $inputName = 'file'): string
    {
        $imageInfo = @getimagesize($file->getPathname());
        $mime = $imageInfo['mime'] ?? null;

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return $this->storeOriginal($file, $directory);
        }

        if ($imageInfo[0] * $imageInfo[1] > 20_000_000) {
            throw ValidationException::withMessages([
                $inputName => 'Resolusi gambar terlalu tinggi. Kecilkan ukuran gambar lalu unggah kembali.',
            ]);
        }

        $imageData = file_get_contents($file->getPathname());
        if ($imageData === false) {
            throw new RuntimeException('Unable to read uploaded image.');
        }

        $image = @imagecreatefromstring($imageData);
        if (! $image instanceof \GdImage) {
            throw ValidationException::withMessages([
                $inputName => 'Gambar tidak dapat dibaca. Pilih file gambar lain.',
            ]);
        }
        $image = $this->correctOrientation($image, $file, $mime);

        $canvas = null;
        $temporaryPath = tempnam(sys_get_temp_dir(), 'sinar-image-');

        try {
            if ($temporaryPath === false) {
                throw new RuntimeException('Unable to create a temporary image file.');
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $scale = min(1, self::MAX_DIMENSION / max($width, $height));
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));
            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

            if (in_array($mime, ['image/png', 'image/webp'], true)) {
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefill($canvas, 0, 0, $transparent);
            } else {
                $white = imagecolorallocate($canvas, 255, 255, 255);
                imagefill($canvas, 0, 0, $white);
            }

            if (! imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height)) {
                throw new RuntimeException('Unable to resize uploaded image.');
            }

            $extension = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            };
            $encoded = match ($mime) {
                'image/jpeg' => imagejpeg($canvas, $temporaryPath, 82),
                'image/png' => imagepng($canvas, $temporaryPath, 7),
                'image/webp' => function_exists('imagewebp') && imagewebp($canvas, $temporaryPath, 80),
            };

            if (! $encoded) {
                throw new RuntimeException('Unable to compress uploaded image.');
            }

            $storedPath = trim($directory, '/').'/'.Str::uuid().'.'.$extension;
            $stream = fopen($temporaryPath, 'rb');
            if ($stream === false) {
                throw new RuntimeException('Unable to read compressed image.');
            }

            try {
                if (! Storage::disk('local')->put($storedPath, $stream)) {
                    throw new RuntimeException('Unable to store compressed image.');
                }
            } finally {
                fclose($stream);
            }

            return $storedPath;
        } finally {
            unset($image, $canvas);
            if (is_string($temporaryPath) && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    private function storeOriginal(UploadedFile $file, string $directory): string
    {
        $storedPath = $file->store($directory, 'local');
        if ($storedPath === false) {
            throw new RuntimeException('Unable to store uploaded file.');
        }

        return $storedPath;
    }

    private function correctOrientation(\GdImage $image, UploadedFile $file, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $metadata = @exif_read_data($file->getPathname());
        $orientation = (int) ($metadata['Orientation'] ?? 1);
        $rotation = match ($orientation) {
            3 => 180,
            5, 6 => -90,
            7, 8 => 90,
            default => 0,
        };
        $flip = match ($orientation) {
            2, 5, 7 => IMG_FLIP_HORIZONTAL,
            4 => IMG_FLIP_VERTICAL,
            default => null,
        };
        if ($rotation === 0 && $flip === null) {
            return $image;
        }

        if ($rotation !== 0) {
            $rotated = imagerotate($image, $rotation, 0);
            if (! $rotated instanceof \GdImage) {
                throw new RuntimeException('Unable to orient uploaded image.');
            }

            $image = $rotated;
        }

        if ($flip !== null && ! imageflip($image, $flip)) {
            throw new RuntimeException('Unable to orient uploaded image.');
        }

        return $image;
    }
}
