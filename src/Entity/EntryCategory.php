<?php declare(strict_types=1);

namespace Inno\Entity;

use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\EntryCategoryRepository;

#[ORM\Entity(repositoryClass: EntryCategoryRepository::class)]
class EntryCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'entryCategories')]
    private ?Entry $entry = null;

    #[ORM\ManyToOne(inversedBy: 'entryCategories')]
    private ?Category $category = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntry(): ?Entry
    {
        return $this->entry;
    }

    public function setEntry(?Entry $entry): static
    {
        $this->entry = $entry;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }
}
