<?php

namespace App\Service\File\Builder;

use App\Entity\File;
use App\Entity\User;
use App\Service\File\FileService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileEntityBuilder
{
    public function __construct(private FileService $fileService)
    {
    }

    public function build(File $file, UploadedFile $uploadedFile, User $user): File
    {
        $file->setUser($user);

        $originalName = $uploadedFile->getClientOriginalName();
        $nameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

        // Sanitize name but keep whitespaces (remove special characters)
        $safeName = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $nameWithoutExtension);
        $safeName = trim($safeName);
        $safeExtension = preg_replace('/[^a-zA-Z0-9]/', '', $extension);

        $file->setOriginalName($safeName);
        $file->setExtension($safeExtension);

        $storedName = $this->fileService->handleUpload($uploadedFile);
        $file->setStoredName($storedName);

        $fileSize = $this->fileService->getFileSizeInBytes($storedName);
        if ($fileSize !== null) {
            $file->setSizeInBytes($fileSize);
        }

        return $file;
    }
}