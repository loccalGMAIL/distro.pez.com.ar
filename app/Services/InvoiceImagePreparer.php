<?php

namespace App\Services;

use GdImage;

class InvoiceImagePreparer
{
    private const MAX_EDGE = 1600;

    private const JPEG_QUALITY = 80;

    /**
     * Normaliza una imagen de factura in-place: la achica a un máximo de
     * 1600px de borde y corrige la rotación EXIF. Defensa en profundidad por
     * si la compresión/rotación del lado del cliente no corrió. Los PDF se
     * dejan intactos.
     */
    public function prepare(string $absolutePath): void
    {
        $mime = mime_content_type($absolutePath);

        if ($mime === 'application/pdf') {
            return;
        }

        $create = match ($mime) {
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/webp' => 'imagecreatefromwebp',
            'image/gif' => 'imagecreatefromgif',
            default => null,
        };

        if (! $create || ! function_exists($create)) {
            return;
        }

        $image = @$create($absolutePath);

        if (! $image instanceof GdImage) {
            return;
        }

        $image = $this->correctOrientation($image, $absolutePath, $mime);
        $image = $this->downscale($image);

        $this->save($image, $absolutePath, $mime);

        imagedestroy($image);
    }

    private function correctOrientation(GdImage $image, string $path, string $mime): GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = $exif['Orientation'] ?? 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated instanceof GdImage ? $rotated : $image;
    }

    private function downscale(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $maxEdge = max($width, $height);

        if ($maxEdge <= self::MAX_EDGE) {
            return $image;
        }

        $scale = self::MAX_EDGE / $maxEdge;
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    private function save(GdImage $image, string $path, string $mime): void
    {
        match ($mime) {
            'image/jpeg' => imagejpeg($image, $path, self::JPEG_QUALITY),
            'image/png' => imagepng($image, $path),
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, $path) : null,
            'image/gif' => imagegif($image, $path),
            default => null,
        };
    }
}
