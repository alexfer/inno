<?php declare(strict_types=1);

namespace Inno\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\EntryRepository;

#[ORM\Entity(repositoryClass: EntryRepository::class)]
#[ORM\Index(name: 'idx', columns: ['status', 'type'])]
class Entry
{

    /**
     * @var array
     */
    const array TYPE = [
        'Other' => 'other',
        'Blog' => 'blog',
        'Article' => 'article',
        'News' => 'news',
        'Gallery' => 'gallery',
    ];
    const array STATUS = [
        'entry.info.draft' => 'draft',
        'entry.info.published' => 'published',
        'entry.info.trashed' => 'trashed',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: 'slug', type: Types::STRING, unique: true, nullable: true)]
    private ?string $slug = null;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\OneToOne(mappedBy: 'entry', cascade: ['persist', 'remove'])]
    private ?EntryDetails $entryDetails = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $type;

    #[ORM\Column(type: Types::STRING)]
    private ?string $status;

    #[ORM\Column]
    private ?int $comments;

    #[ORM\OneToMany(targetEntity: EntryAttachment::class, mappedBy: 'entry')]
    #[ORM\OrderBy(['id' => 'desc'])]
    private Collection $entryAttachments;

    #[ORM\OneToMany(targetEntity: EntryCategory::class, mappedBy: 'entry')]
    private Collection $entryCategories;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updated_at;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deleted_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $locked_to = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
        $this->type = self::TYPE['Other'];
        $this->status = self::STATUS['entry.info.draft'];
        $this->comments = 0;
        $this->entryAttachments = new ArrayCollection();
        $this->entryCategories = new ArrayCollection();
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
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @param string|null $slug
     * @return $this
     */
    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPrivate(): bool
    {
        return true;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     * @return $this
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getComments(): ?int
    {
        return $this->comments;
    }

    /**
     * @param int $comments
     * @return $this
     */
    public function setComments(int $comments): static
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    /**
     * @param \DateTimeInterface $created_at
     * @return $this
     */
    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    /**
     * @param \DateTimeInterface $updated_at
     * @return $this
     */
    public function setUpdatedAt(\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deleted_at;
    }

    /**
     * @param \DateTimeInterface|null $deleted_at
     * @return $this
     */
    public function setDeletedAt(?\DateTimeInterface $deleted_at): static
    {
        $this->deleted_at = $deleted_at;

        return $this;
    }

    /**
     * @return Collection<int, EntryAttachment>
     */
    public function getEntryAttachments(): Collection
    {
        return $this->entryAttachments;
    }

    public function addEntryAttachment(EntryAttachment $entryAttachment): static
    {
        if (!$this->entryAttachments->contains($entryAttachment)) {
            $this->entryAttachments->add($entryAttachment);
            $entryAttachment->setEntry($this);
        }

        return $this;
    }

    public function removeEntryAttachment(EntryAttachment $entryAttachment): static
    {
        if ($this->entryAttachments->removeElement($entryAttachment)) {
            // set the owning side to null (unless already changed)
            if ($entryAttachment->getEntry() === $this) {
                $entryAttachment->setEntry(null);
            }
        }

        return $this;
    }

    /**
     * @return EntryDetails|null
     */
    public function getEntryDetails(): ?EntryDetails
    {
        return $this->entryDetails;
    }

    public function setEntryDetails(?EntryDetails $entryDetails): static
    {
        // unset the owning side of the relation if necessary
        if ($entryDetails === null && $this->entryDetails !== null) {
            $this->entryDetails->setEntry(null);
        }

        // set the owning side of the relation if necessary
        if ($entryDetails !== null && $entryDetails->getEntry() !== $this) {
            $entryDetails->setEntry($this);
        }

        $this->entryDetails = $entryDetails;

        return $this;
    }

    /**
     * @return Collection<int, EntryCategory>
     */
    public function getEntryCategories(): Collection
    {
        return $this->entryCategories;
    }

    public function addEntryCategory(EntryCategory $entryCategory): static
    {
        if (!$this->entryCategories->contains($entryCategory)) {
            $this->entryCategories->add($entryCategory);
            $entryCategory->setEntry($this);
        }

        return $this;
    }

    public function removeEntryCategory(EntryCategory $entryCategory): static
    {
        if ($this->entryCategories->removeElement($entryCategory)) {
            // set the owning side to null (unless already changed)
            if ($entryCategory->getEntry() === $this) {
                $entryCategory->setEntry(null);
            }
        }

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getLockedTo(): ?\DateTimeInterface
    {
        return $this->locked_to;
    }

    /**
     * @param \DateTimeInterface|null $locked_to
     * @return $this
     */
    public function setLockedTo(?\DateTimeInterface $locked_to): static
    {
        $this->locked_to = $locked_to;

        return $this;
    }
}
