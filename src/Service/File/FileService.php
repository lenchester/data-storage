<?php

namespace App\Service\File;

use App\Entity\File;
use App\Service\File\Storage\FileStorageInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class FileService
{
    public function __construct(private readonly FileStorageInterface $storage)
    {
    }

    public function handleUpload(UploadedFile $uploadedFile): string
    {
        return $this->storage->store($uploadedFile);
    }

    public function delete(string $storedName): void
    {
        $this->storage->delete($storedName);
    }

    public function getFileSizeInBytes(string $storedName): ?int
    {
        if ($this->storage->exists($storedName)) {
            return $this->storage->getSize($storedName);
        }
        return null;
    }

    public function streamFileForDownload(File $file): StreamedResponse
    {
        if (!$this->storage->exists($file->getStoredName())) {
            throw new NotFoundHttpException('File not found.');
        }

        return $this->storage->getStream($file);
    }

    /**
     * Get the file size in a human-readable format (bytes, KB, MB).
     *
     * @param int $fileSize
     * @return string
     */
    public function getFileSizeReadable(int $fileSize): string
    {
        if ($fileSize < 1024) {
            return $fileSize . ' bytes';
        } elseif ($fileSize < 1048576) {
            return round($fileSize / 1024, 2) . ' KB';
        } else {
            return round($fileSize / 1048576, 2) . ' MB';
        }
    }
}