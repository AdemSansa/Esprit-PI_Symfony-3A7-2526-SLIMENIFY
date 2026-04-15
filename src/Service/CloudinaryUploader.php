<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CloudinaryUploader
{
    public function __construct(
        private readonly string $cloudinaryUrl
    ) {}

    public function uploadQuestionImage(UploadedFile $file): string
    {
        $cloudinary = new Cloudinary($this->cloudinaryUrl);

        $uploadResult = $cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder' => 'slimenify/questions',
            'resource_type' => 'image',
        ]);

        return (string) ($uploadResult['secure_url'] ?? '');
    }
}
