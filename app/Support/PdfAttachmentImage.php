<?php

namespace App\Support;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PdfAttachmentImage
{
    public function make(Attachment $attachment): array
    {
        $contents = Storage::disk('local')->get($attachment->path);
        $mimeType = $attachment->mime_type;

        if ($mimeType === 'image/webp') {
            if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
                throw new RuntimeException('WebP images cannot be embedded in the PDF because image conversion is unavailable.');
            }

            $image = imagecreatefromstring($contents);
            if (! $image) {
                throw new RuntimeException('An attached WebP image could not be converted for the PDF.');
            }

            ob_start();
            $encoded = imagejpeg($image, null, 88);
            $contents = ob_get_clean();
            imagedestroy($image);

            if (! $encoded || $contents === false || $contents === '') {
                throw new RuntimeException('An attached WebP image could not be converted for the PDF.');
            }

            $mimeType = 'image/jpeg';
        }

        if (! in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            throw new RuntimeException('An attached image has an unsupported format for PDF export.');
        }

        return [
            'src' => 'data:'.$mimeType.';base64,'.base64_encode($contents),
            'caption' => $attachment->caption ?: $attachment->original_name,
        ];
    }
}
