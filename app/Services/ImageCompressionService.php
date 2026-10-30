<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageCompressionService
{
    /**
     * Kompres dan simpan file gambar secara otomatis & cerdas ke Storage.
     *
     * @param UploadedFile|null $file
     * @param string $directory Direktori penyimpanan (misal: 'maintenance_photos')
     * @param string $disk Disk Storage (default: 'public')
     * @param int $maxWidth Resolusi lebar maksimum (default: 1920)
     * @param int $maxHeight Resolusi tinggi maksimum (default: 1920)
     * @param int $quality Kualitas JPEG 0-100 (default: 82)
     * @return string|null Path penyimpanan relatif, atau null jika file tidak valid
     */
    public static function compressAndStore(
        ?UploadedFile $file,
        string $directory = 'photos',
        string $disk = 'public',
        int $maxWidth = 1920,
        int $maxHeight = 1920,
        int $quality = 82
    ): ?string {
        if (!$file || !$file->isValid()) {
            return null;
        }

        $mime = strtolower((string) $file->getMimeType());

        // Jika bukan gambar atau GD tidak aktif atau file SVG/GIF animasi, simpan normal
        if (!str_starts_with($mime, 'image/') || str_contains($mime, 'svg') || str_contains($mime, 'gif') || !extension_loaded('gd')) {
            return $file->store($directory, $disk);
        }

        try {
            $realPath = $file->getRealPath();
            if (!$realPath || !file_exists($realPath)) {
                return $file->store($directory, $disk);
            }

            // Muat gambar dengan GD
            $srcImage = match ($mime) {
                'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($realPath),
                'image/png' => @imagecreatefrompng($realPath),
                'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($realPath) : null,
                default => null,
            };

            if (!$srcImage) {
                return $file->store($directory, $disk);
            }

            // Perbaiki orientasi EXIF (kamera smartphone seringkali menyimpan orientasi di EXIF)
            if (($mime === 'image/jpeg' || $mime === 'image/jpg') && function_exists('exif_read_data')) {
                try {
                    $exif = @exif_read_data($realPath);
                    if (!empty($exif['Orientation'])) {
                        $srcImage = match ((int) $exif['Orientation']) {
                            3 => imagerotate($srcImage, 180, 0),
                            6 => imagerotate($srcImage, -90, 0),
                            8 => imagerotate($srcImage, 90, 0),
                            default => $srcImage,
                        };
                    }
                } catch (\Throwable $e) {
                    // Abaikan kesalahan pembacaan EXIF jika metadata korup
                }
            }

            $origWidth = imagesx($srcImage);
            $origHeight = imagesy($srcImage);

            if ($origWidth <= 0 || $origHeight <= 0) {
                imagedestroy($srcImage);
                return $file->store($directory, $disk);
            }

            // Hitung resolusi cerdas dengan mempertahankan aspek rasio
            $targetWidth = $origWidth;
            $targetHeight = $origHeight;

            if ($targetWidth > $maxWidth || $targetHeight > $maxHeight) {
                $ratio = min($maxWidth / $targetWidth, $maxHeight / $targetHeight);
                $targetWidth = (int) round($targetWidth * $ratio);
                $targetHeight = (int) round($targetHeight * $ratio);
            }

            // Buat kanvas truecolor baru
            $dstImage = imagecreatetruecolor($targetWidth, $targetHeight);

            // Berikan latar belakang putih bersih
            $white = imagecolorallocate($dstImage, 255, 255, 255);
            imagefilledrectangle($dstImage, 0, 0, $targetWidth, $targetHeight, $white);

            // Resample dengan interpolasi halus
            imagecopyresampled(
                $dstImage,
                $srcImage,
                0, 0, 0, 0,
                $targetWidth,
                $targetHeight,
                $origWidth,
                $origHeight
            );

            // Bersihkan resource gambar awal
            imagedestroy($srcImage);

            // Buat file sementara untuk output JPEG yang terkompresi
            $tempPath = tempnam(sys_get_temp_dir(), 'apm_comp_') . '.jpg';
            imagejpeg($dstImage, $tempPath, $quality);
            imagedestroy($dstImage);

            $compressedSize = file_exists($tempPath) ? filesize($tempPath) : 0;
            $originalSize = $file->getSize() ?: 0;

            // Generate nama file unik
            $filename = Str::random(40) . '.jpg';
            $targetStoragePath = trim($directory, '/') . '/' . $filename;

            // Jika hasil kompresi valid dan ukurannya lebih kecil atau sama, simpan hasil kompresi
            if ($compressedSize > 0 && ($compressedSize <= $originalSize || $originalSize === 0)) {
                $contents = file_get_contents($tempPath);
                Storage::disk($disk)->put($targetStoragePath, $contents);
                @unlink($tempPath);
                return $targetStoragePath;
            }

            // Fallback: jika kompresi entah mengapa lebih besar, simpan file asli
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
            return $file->store($directory, $disk);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Server-side image compression fallback to original: ' . $e->getMessage());
            return $file->store($directory, $disk);
        }
    }
}

