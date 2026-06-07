<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CloudinaryUploader
{
    public function __construct(
        private readonly string $cloudinaryUrl
    ) {}

    /**
     * Upload any UploadedFile to a given Cloudinary folder.
     * Returns the secure HTTPS URL.
     */
    public function upload(UploadedFile $file, string $folder): string
    {
        $cloudinary = new Cloudinary($this->cloudinaryUrl);

        $uploadResult = $cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder'        => $folder,
            'resource_type' => 'image',
        ]);

        return (string) ($uploadResult['secure_url'] ?? '');
    }

    /**
     * Upload raw binary image data (e.g. AI-generated images) to Cloudinary.
     * Returns the secure HTTPS URL.
     */
    public function uploadFromBinary(string $binary, string $folder): string
    {
        // Write to a temp file then upload
        $tmpFile = tempnam(sys_get_temp_dir(), 'cld_') . '.png';
        file_put_contents($tmpFile, $binary);

        $cloudinary = new Cloudinary($this->cloudinaryUrl);

        $uploadResult = $cloudinary->uploadApi()->upload($tmpFile, [
            'folder'        => $folder,
            'resource_type' => 'image',
        ]);

        @unlink($tmpFile);

        return (string) ($uploadResult['secure_url'] ?? '');
    }

    // ── Convenience wrappers ──────────────────────────────────────────────────

    public function uploadPhoto(UploadedFile $file): string
    {
        return $this->upload($file, 'slimenify/photos');
    }

    public function uploadBlogImage(UploadedFile $file): string
    {
        return $this->upload($file, 'slimenify/blogs');
    }

    public function uploadEventImage(UploadedFile $file): string
    {
        return $this->upload($file, 'slimenify/events');
    }

    public function uploadDiploma(UploadedFile $file): string
    {
        return $this->upload($file, 'slimenify/diplomas');
    }

    /** @deprecated kept for backward compatibility */
    public function uploadQuestionImage(UploadedFile $file): string
    {
        return $this->upload($file, 'slimenify/questions');
    }
}
