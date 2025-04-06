<?php

namespace App\Service;

use App\Entity\File;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mime\MimeTypes;

class FileService
{
    private string $projectDir;

    public function __construct(KernelInterface $kernel)
    {
        $this->projectDir = $kernel->getProjectDir();
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    public function handleUpload(UploadedFile $uploadedFile): string
    {
        $fileName = uniqid() . '.' . $uploadedFile->guessExtension();
        $targetDirectory = $this->getProjectDir() . '/public/uploads/files';
        $targetPath = $targetDirectory . '/' . $fileName;

        $uploadedFile->move($targetDirectory, $fileName);

        if (!file_exists($targetPath)) {
            throw new RuntimeException(sprintf('File was not moved successfully to %s', $targetPath));
        }

        return $fileName;
    }

    /**
     * Get the file size in a human-readable format (bytes, KB, MB).
     *
     * @param int $filePath
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

    public function streamFileForDownload(File $file): StreamedResponse
    {
        $filePath = $this->projectDir . '/public/uploads/files/' . $file->getStoredName();

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

    public function getFileSizeInBytes(string $storedName): ?int
    {
        $filePath = $this->projectDir . '/public/uploads/files/' . $storedName;

        $filesystem = new Filesystem();
        if ($filesystem->exists($filePath)) {
            return filesize($filePath);
        }

        return null;
    }
}