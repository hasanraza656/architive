<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Server-side checks for the files sent with a Permit Revisions enquiry. The page checks the same things in the browser for quick
 * feedback, but a browser check is only a courtesy: every rule is repeated here on the real bytes.
 * Files are only ever stored privately and downloaded through the portal (never opened by the web server), so this is about
 * refusing wrong or harmful uploads early, not about trusting what is inside.
 */
final class PermitFiles
{
    /** The first bytes a real file of each type starts with (null = checked another way). */
    private const MAGIC = [
        'pdf' => "%PDF-",
        'zip' => "PK",                                  // PK\x03\x04 (or PK\x05\x06 for an empty archive)
        'dwg' => "AC1",                                 // AutoCAD drawings: AC1009 ... AC1032 (and the very old AC1.xx)
        'rvt' => "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1",     // Revit files are OLE2 compound documents
    ];

    /** @return string|null a plain message for the visitor, or null when the file is fine */
    public static function problem(UploadedFile $file): ?string
    {
        $name = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());
        $allowed = config('permit.allowed_extensions');

        if (! $file->isValid()) {
            return "“{$name}” could not be uploaded. It may be bigger than the server allows.";
        }
        if (! in_array($ext, $allowed, true)) {
            return "“{$name}”: this file type is not supported. Use " . strtoupper(implode(', ', $allowed)) . '.';
        }
        $size = (int) $file->getSize();
        if ($size === 0) {
            return "“{$name}” appears to be empty.";
        }
        if ($size > config('permit.max_file_mb') * 1048576) {
            return "“{$name}” is over " . config('permit.max_file_mb') . ' MB. Compress it or send it after your enquiry.';
        }

        $path = $file->getRealPath();
        $head = (string) @file_get_contents($path, false, null, 0, 16);

        $bad = "“{$name}” does not look like a real ." . strtoupper($ext) . ' file.';
        switch ($ext) {
            case 'pdf':
                // the signature may sit within the first 1024 bytes (some producers add a few leading bytes)
                return str_contains((string) @file_get_contents($path, false, null, 0, 1024), self::MAGIC['pdf']) ? null : "“{$name}” does not look like a valid PDF.";
            case 'zip':
                return str_starts_with($head, "PK\x03\x04") || str_starts_with($head, "PK\x05\x06") ? null : $bad;
            case 'dwg':
                return str_starts_with($head, self::MAGIC['dwg']) ? null : $bad;
            case 'rvt':
                return str_starts_with($head, self::MAGIC['rvt']) ? null : $bad;
            case 'png':
            case 'jpg':
            case 'jpeg':
            case 'webp':
                $info = @getimagesize($path);
                $type = $info[2] ?? null;
                $ok = match ($ext) {
                    'png' => $type === IMAGETYPE_PNG,
                    'jpg', 'jpeg' => $type === IMAGETYPE_JPEG,
                    'webp' => $type === IMAGETYPE_WEBP,
                };

                return $ok ? null : $bad;
            case 'heic':
                return substr($head, 4, 4) === 'ftyp' ? null : $bad;      // HEIC / HEIF files start with an "ftyp" box
        }

        return $bad;
    }

    /** Largest total request the server accepts, in MB (the smaller of upload_max_filesize x files and post_max_size). */
    public static function maxPostMb(): float
    {
        $post = self::bytes((string) ini_get('post_max_size'));
        $one = self::bytes((string) ini_get('upload_max_filesize'));

        return round(($post > 0 ? min($post, $one * config('permit.max_files')) : $one * config('permit.max_files')) / 1048576, 1);
    }

    private static function bytes(string $v): int
    {
        $v = trim($v);
        if ($v === '' || $v === '-1') {
            return 0;
        }
        $n = (float) $v;

        return (int) match (strtolower(substr($v, -1))) {
            'g' => $n * 1073741824,
            'm' => $n * 1048576,
            'k' => $n * 1024,
            default => $n,
        };
    }
}
