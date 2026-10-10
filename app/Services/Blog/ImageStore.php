<?php

namespace App\Services\Blog;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Saves uploaded pictures inside /public/uploads (no storage:link needed). Pictures are resized (max 1600 px wide),
 * converted to WebP and get a 800 px sibling (name-800.webp) for cards and lists. Needs PHP's GD (standard on shared hosting).
 * Paths returned are relative to /public, e.g. "uploads/blog/2026/10/abc.webp", so asset($path) is the public URL.
 */
class ImageStore
{
    public const MAX_PIXELS = 40000000;

    private function disk()
    {
        return Storage::disk('uploads');
    }

    /**
     * @param  string  $folder  blog | avatars
     * @param  int|null  $square  crop to a square of this size (profile photos)
     */
    public function store(UploadedFile $file, string $folder = 'blog', int $maxWidth = 1600, ?int $square = null): string
    {
        $binary = (string) file_get_contents($file->getRealPath());
        $info = @getimagesizefromstring($binary);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
            throw new \InvalidArgumentException('Please upload a JPG, PNG, WebP or GIF picture.');
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw new \InvalidArgumentException('That picture is too large (over 40 megapixels). Please resize it first.');
        }

        $base = $folder . '/' . date('Y/m') . '/' . Str::uuid();

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            // No GD on this server: keep the original so uploads still work.
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $this->disk()->put($base . '.' . $ext, $binary);

            return 'uploads/' . $base . '.' . $ext;
        }

        $img = imagecreatefromstring($binary);
        if (! $img) {
            throw new \InvalidArgumentException('That picture could not be read.');
        }
        $img = $this->orient($img, $info[2], $file->getRealPath());

        if ($square) {
            $img = $this->cropSquare($img, $square);
        } else {
            $img = $this->fit($img, $maxWidth);
        }
        $this->disk()->put($base . '.webp', $this->encode($img));

        if (! $square) {
            $this->disk()->put($base . '-800.webp', $this->encode($this->fit($img, 800)));
        }
        imagedestroy($img);

        return 'uploads/' . $base . '.webp';
    }

    /** Removes a picture and its -800 sibling. Only touches files inside uploads/. */
    public function delete(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'uploads/') || str_contains($path, '..')) {
            return;
        }
        $rel = substr($path, strlen('uploads/'));
        $this->disk()->delete($rel);
        $this->disk()->delete(preg_replace('/\.(webp|jpe?g|png)$/i', '-800.$1', $rel));
    }

    /* ------------------------------------------------------------ GD helpers */

    private function encode($img): string
    {
        ob_start();
        imagepalettetotruecolor($img);
        imagealphablending($img, true);
        imagesavealpha($img, true);
        imagewebp($img, null, 82);

        return (string) ob_get_clean();
    }

    private function fit($img, int $maxWidth)
    {
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w <= $maxWidth) {
            return $img;
        }
        $nh = (int) round($h * $maxWidth / $w);
        $out = imagecreatetruecolor($maxWidth, $nh);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagecopyresampled($out, $img, 0, 0, 0, 0, $maxWidth, $nh, $w, $h);
        imagedestroy($img);

        return $out;
    }

    private function cropSquare($img, int $size)
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $side = min($w, $h);
        $out = imagecreatetruecolor($size, $size);
        imagecopyresampled($out, $img, 0, 0, (int) (($w - $side) / 2), (int) (($h - $side) / 2), $size, $size, $side, $side);
        imagedestroy($img);

        return $out;
    }

    /** Phone photos carry a rotation flag in EXIF: apply it so portraits are not sideways. */
    private function orient($img, int $type, string $path)
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($path);
        $o = (int) ($exif['Orientation'] ?? 1);
        $angle = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($angle) {
            $rot = imagerotate($img, $angle, 0);
            if ($rot) {
                imagedestroy($img);

                return $rot;
            }
        }

        return $img;
    }
}
