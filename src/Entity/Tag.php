<?php declare(strict_types=1);

namespace Inno\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\TagRepository;

#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deleted_at = null;

    /**
     * @var Collection<int, Taggable>
     */
    #[ORM\OneToMany(targetEntity: Taggable::class, mappedBy: 'tag')]
    private Collection $taggables;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->taggables = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    /**
     * @param \DateTimeImmutable $created_at
     * @return $this
     */
    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deleted_at;
    }

    /**
     * @param \DateTimeImmutable|null $deleted_at
     * @return $this
     */
    public function setDeletedAt(?\DateTimeImmutable $deleted_at): static
    {
        $this->deleted_at = $deleted_at;

        return $this;
    }

    /**
     * @return Collection<int, Taggable>
     */
    public function getTaggables(): Collection
    {
        return $this->taggables;
    }

    /**
     * @param Taggable $taggable
     * @return $this
     */
    public function addTaggable(Taggable $taggable): static
    {
        if (!$this->taggables->contains($taggable)) {
            $this->taggables->add($taggable);
            $taggable->setTag($this);
        }

        return $this;
    }

    /**
     * @param Taggable $taggable
     * @return $this
     */
    public function removeTaggable(Taggable $taggable): static
    {
        if ($this->taggables->removeElement($taggable)) {
            // set the owning side to null (unless already changed)
            if ($taggable->getTag() === $this) {
                $taggable->setTag(null);
            }
        }

        return $this;
    }
}
