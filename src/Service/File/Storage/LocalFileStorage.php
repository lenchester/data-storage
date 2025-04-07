<?php

namespace App\Service\File\Storage;

use App\Entity\File;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mime\MimeTypes;

class LocalFileStorage implements FileStorageInterface
{
    private string $uploadDir;

    public function __construct(KernelInterface $kernel)
    {
        $this->uploadDir = $kernel->getProjectDir() . '/public/uploads/files/';
    }

    public function store(UploadedFile $file): string
    {
        $fileName = uniqid() . '.' . $file->guessExtension();
        $file->move($this->uploadDir, $fileName);

        if (!$this->exists($fileName)) {
            throw new FileException(sprintf('Failed to upload file %s', $fileName));
        }

        return $fileName;
    }

    public function delete(string $filename): bool
    {
        $filePath = $this->uploadDir . $filename;
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    public function exists(string $filename): bool
    {
        return file_exists($this->uploadDir . $filename);
    }

    public function getSize(string $filename): int
    {
        return filesize($this->uploadDir . $filename);
    }

    public function getStream(File $file): StreamedResponse
    {
        $filePath = $this->uploadDir . $file->getStoredName();

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('File not found.');
        }

        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->getMimeTypes($file->getExtension())[0] ?? 'application/octet-stream';

        $response = new StreamedResponse(function () use ($filePath) {
            readfile($filePath);
        });

        $originalName = $file->getOriginalName();
        $extension = $file->getExtension();

        if (!str_ends_with(strtolower($originalName), strtolower('.' . $extension))) {
            $fileName = $originalName . '.' . $extension;
        } else {
            $fileName = $originalName;
        }

        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $response->headers->set('Content-Length', filesize($filePath));

        return $response;
    }
}