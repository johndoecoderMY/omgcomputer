<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttachmentStorage
{
    public static function validationRules(): array
    {
        return [
            'photos' => ['required', 'array', 'min:1', 'max:8'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'caption' => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function validationRulesWithoutPhotos(): array
    {
        return [
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'caption' => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function documentValidationRules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1', 'max:8'],
            'documents.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'caption' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function store(MorphMany $attachments, array $photos, User $user, ?string $caption = null): void
    {
        foreach ($photos as $photo) {
            if (! $photo instanceof UploadedFile) {
                continue;
            }

            [$contents, $mimeType, $extension] = $this->compress($photo);
            $path = 'attachments/'.$attachments->getParent()->getTable().'/'.Str::uuid().'.'.$extension;
            if (! Storage::disk('local')->put($path, $contents)) {
                throw ValidationException::withMessages(['photos' => 'The server could not save the uploaded image. Check storage permissions.']);
            }
            $attachments->create([
                'uploaded_by' => $user->id,
                'path' => $path,
                'original_name' => $photo->getClientOriginalName(),
                'mime_type' => $mimeType,
                'size' => strlen($contents),
                'caption' => $caption,
            ]);
        }
    }

    public function storeDocuments(MorphMany $attachments, array $documents, User $user, ?string $caption = null): void
    {
        foreach ($documents as $document) {
            if (! $document instanceof UploadedFile) {
                continue;
            }

            $mimeType = $document->getMimeType();
            if (str_starts_with((string) $mimeType, 'image/')) {
                [$contents, $mimeType, $extension] = $this->compress($document, 'documents');
            } else {
                $contents = file_get_contents($document->getPathname());
                if ($contents === false) {
                    throw ValidationException::withMessages(['documents' => 'One of the uploaded files could not be read.']);
                }
                $mimeType = 'application/pdf';
                $extension = 'pdf';
            }

            $path = 'attachments/'.$attachments->getParent()->getTable().'/'.Str::uuid().'.'.$extension;
            if (! Storage::disk('local')->put($path, $contents)) {
                throw ValidationException::withMessages(['documents' => 'The server could not save the uploaded file. Check storage permissions.']);
            }
            $attachments->create([
                'uploaded_by' => $user->id,
                'path' => $path,
                'original_name' => $document->getClientOriginalName(),
                'mime_type' => $mimeType,
                'size' => strlen($contents),
                'caption' => $caption,
            ]);
        }
    }

    private function compress(UploadedFile $photo, string $field = 'photos'): array
    {
        $originalContents = file_get_contents($photo->getPathname());
        if ($originalContents === false) {
            throw ValidationException::withMessages([$field => 'One of the uploaded images could not be read.']);
        }

        $original = [
            $originalContents,
            $photo->getMimeType() ?: 'application/octet-stream',
            $photo->guessExtension() ?: 'img',
        ];

        if (! function_exists('imagecreatefromstring')) {
            return $original;
        }

        $dimensions = @getimagesize($photo->getPathname());
        if (! $dimensions || ($dimensions[0] * $dimensions[1]) > 12_000_000) {
            return $original;
        }

        $source = @imagecreatefromstring($originalContents);
        if (! $source) {
            return $original;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, 1920 / $sourceWidth, 1920 / $sourceHeight);
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));
        $resized = @imagecreatetruecolor($width, $height);
        if (! $resized) {
            imagedestroy($source);

            return $original;
        }
        $webp = function_exists('imagewebp');
        if (! $webp && ! function_exists('imagejpeg')) {
            imagedestroy($source);
            imagedestroy($resized);

            return $original;
        }

        if ($webp) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefill($resized, 0, 0, $transparent);
        } else {
            $white = imagecolorallocate($resized, 255, 255, 255);
            imagefill($resized, 0, 0, $white);
        }

        imagecopyresampled($resized, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
        ob_start();
        $encoded = $webp ? imagewebp($resized, null, 80) : imagejpeg($resized, null, 82);
        $contents = ob_get_clean();
        imagedestroy($source);
        imagedestroy($resized);

        if (! $encoded || $contents === false || $contents === '') {
            return $original;
        }

        return [$contents, $webp ? 'image/webp' : 'image/jpeg', $webp ? 'webp' : 'jpg'];
    }
}
