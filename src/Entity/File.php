<?php

namespace App\Entity;

namespace App\Entity;

use App\Repository\FileRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Guid\Guid;

#[ORM\Entity(repositoryClass: FileRepository::class)]
class File
{
    #[ORM\Id]
    #[ORM\Column(type: "guid", unique: true)]
    private string $id;

    #[ORM\Column(type: "datetime")]
    private DateTime $createdAt;

    #[ORM\Column(type: 'string', length: 255)]
    private string $originalName;

    #[ORM\Column(type: 'string', length: 255)]
    private string $storedName;

    #[ORM\Column(type: 'string', length: 10)]
    private string $extension;

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
}
