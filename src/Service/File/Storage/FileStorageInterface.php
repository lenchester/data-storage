<?php

namespace App\Service\File\Storage;

use App\Entity\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface FileStorageInterface
{
    public function store(UploadedFile $file): string;
    public function delete(string $filename): bool;
    public function exists(string $filename): bool;
    public function getSize(string $filename): int;
    public function getStream(File $file): StreamedResponse;

}