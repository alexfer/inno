<?php declare(strict_types=1);

namespace Inno\Entity;

use Doctrine\ORM\Mapping as ORM;
use Inno\Entity\Enum\EnumAttachment;
use Inno\Repository\FileManagerRepository;

#[ORM\Entity(repositoryClass: FileManagerRepository::class)]
class FileManager
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: EnumAttachment::class)]
    private ?EnumAttachment $type = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Attach $file = null;

    #[ORM\ManyToOne(inversedBy: 'fileManagers')]
    private ?User $owner = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return EnumAttachment|null
     */
    public function getType(): ?EnumAttachment
    {
        return $this->type;
    }

    /**
     * @param EnumAttachment $type
     * @return $this
     */
    public function setType(EnumAttachment $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Attach|null
     */
    public function getFile(): ?Attach
    {
        return $this->file;
    }

    /**
     * @param Attach|null $file
     * @return $this
     */
    public function setFile(?Attach $file): static
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    /**
     * @param User|null $owner
     * @return $this
     */
    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}
