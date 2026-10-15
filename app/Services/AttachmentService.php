<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
class AttachmentService
{
    public function store(UploadedFile $file): array
    {
        if (!$file->isValid() || $file->getSize() > 5 * 1024 * 1024 || !in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'webp']) || !in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'])) {
            throw ValidationException::withMessages(['file' => 'Format atau ukuran foto tidak valid.']);
        }
        $info = @getimagesize($file->getRealPath());
        if (!$info || $info[0] * $info[1] > 20000000) {
            throw ValidationException::withMessages(['file' => 'Foto tidak dapat dibaca atau melebihi 20 megapixel.']);
        }
        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (!$source) {
            throw ValidationException::withMessages(['file' => 'Foto tidak valid.']);
        }
        $width = min(400, $info[0]);
        $height = max(1, (int) round($info[1] * $width / $info[0]));
        $thumb = imagecreatetruecolor($width, $height);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
        ob_start();
        imagejpeg($thumb, null, 80);
        $bytes = ob_get_clean();
        imagedestroy($source);
        imagedestroy($thumb);
        $path = $file->store('evidence', 'local');
        $thumbnail = 'thumbnails/' . bin2hex(random_bytes(16)) . '.jpg';
        Storage::disk('local')->put($thumbnail, $bytes);
        return ['path' => $path, 'thumbnail_path' => $thumbnail, 'mime_type' => $file->getMimeType(), 'size' => $file->getSize()];
    }
}
