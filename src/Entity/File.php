<?php

namespace App\Entity;

namespace App\Entity;

use App\Repository\FileRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Guid\Guid;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FileRepository::class)]
#[ORM\HasLifecycleCallbacks]
class File
{
    #[ORM\Id]
    #[ORM\Column(type: "guid", unique: true)]
    private string $id;

    #[ORM\Column(type: "datetime")]
    private DateTime $createdAt;

    #[ORM\Column(type: 'string', length: 255)]
    private string $originalName;

    #[Assert\File(
        maxSize: '8M',
        maxSizeMessage: 'The file is too large. Allowed maximum size is 8M.'
    )]
    private ?UploadedFile $file = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $storedName;

    #[ORM\Column(type: 'string', length: 10)]
    private string $extension;

    #[ORM\Column(type: 'integer')]
    private int $sizeInBytes;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    public function __construct()
    {
        $this->id = Guid::uuid4()->toString();
        $this->createdAt = new DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): File
    {
        $this->id = $id;

        return $this;
    }

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    public function setFile(?UploadedFile $file)
    {
        $this->file = $file;
        /*if ($file) {
            $this->storedName = $file->getClientOriginalName();
        }*/
    }

    public function getSizeInBytes(): int
    {
        return $this->sizeInBytes;
    }

    public function setSizeInBytes(int $sizeInBytes): void
    {
        $this->sizeInBytes = $sizeInBytes;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getStoredName(): string
    {
        return $this->storedName;
    }

    public function setStoredName(string $storedName): self
    {
        $this->storedName = $storedName;

        return $this;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /*#[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setFileExtensionFromPath(): void
    {
        if ($this->originalName) {
            $this->extension = pathinfo($this->storedName, PATHINFO_EXTENSION);
        }
    }*/
}
