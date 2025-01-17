<?php declare(strict_types=1);

namespace Inno\Entity;

use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreProductAttach;
use Inno\Repository\AttachRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttachRepository::class)]
class Attach
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    private string $name;

    #[ORM\Column(type: Types::STRING)]
    private string $mime;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $size;

    #[ORM\OneToMany(targetEntity: EntryAttachment::class, mappedBy: 'attach')]
    private Collection $entryAttachments;

    #[ORM\ManyToOne(inversedBy: 'attach')]
    private ?UserDetails $userDetails = null;

    #[ORM\OneToOne(mappedBy: 'attach', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: StoreProductAttach::class, mappedBy: 'attach')]
    private Collection $storeProductAttaches;

    #[ORM\OneToOne(mappedBy: 'attach', cascade: ['persist', 'remove'])]
    private ?Store $store = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $path = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected DateTime $created_at;

    public function __construct()
    {
        $this->created_at = new DateTime();
        $this->size = 0;
        $this->entryAttachments = new ArrayCollection();
        $this->storeProductAttaches = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMime(): ?string
    {
        return $this->mime;
    }

    /**
     * @param string $mime
     * @return $this
     */
    public function setMime(string $mime): self
    {
        $this->mime = $mime;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @param int $size
     * @return $this
     */
    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return Collection<int, EntryAttachment>
     */
    public function getEntryAttachments(): Collection
    {
        return $this->entryAttachments;
    }

    /**
     * @param EntryAttachment $entryAttachment
     * @return $this
     */
    public function addEntryAttachment(EntryAttachment $entryAttachment): static
    {
        if (!$this->entryAttachments->contains($entryAttachment)) {
            $this->entryAttachments->add($entryAttachment);
            $entryAttachment->setAttach($this);
        }

        return $this;
    }

    /**
     * @param EntryAttachment $entryAttachment
     * @return $this
     */
    public function removeEntryAttachment(EntryAttachment $entryAttachment): static
    {
        if ($this->entryAttachments->removeElement($entryAttachment)) {
            // set the owning side to null (unless already changed)
            if ($entryAttachment->getAttach() === $this) {
                $entryAttachment->setAttach(null);
            }
        }

        return $this;
    }

    /**
     * @return UserDetails|null
     */
    public function getUserDetails(): ?UserDetails
    {
        return $this->userDetails;
    }

    /**
     * @param UserDetails|null $userDetails
     * @return $this
     */
    public function setUserDetails(?UserDetails $userDetails): static
    {
        $this->userDetails = $userDetails;

        return $this;
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
    public function setUser(?User $user): static
    {
        // unset the owning side of the relation if necessary
        if ($user === null && $this->user !== null) {
            $this->user->setAttach(null);
        }

        // set the owning side of the relation if necessary
        if ($user !== null && $user->getAttach() !== $this) {
            $user->setAttach($this);
        }

        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, StoreProductAttach>
     */
    public function getStoreProductAttaches(): Collection
    {
        return $this->storeProductAttaches;
    }

    /**
     * @param StoreProductAttach $storeProductAttach
     * @return $this
     */
    public function addStoreProductAttach(StoreProductAttach $storeProductAttach): static
    {
        if (!$this->storeProductAttaches->contains($storeProductAttach)) {
            $this->storeProductAttaches->add($storeProductAttach);
            $storeProductAttach->setAttach($this);
        }

        return $this;
    }

    /**
     * @param StoreProductAttach $storeProductAttach
     * @return $this
     */
    public function removeStoreProductAttach(StoreProductAttach $storeProductAttach): static
    {
        if ($this->storeProductAttaches->removeElement($storeProductAttach)) {
            // set the owning side to null (unless already changed)
            if ($storeProductAttach->getAttach() === $this) {
                $storeProductAttach->setAttach(null);
            }
        }

        return $this;
    }

    /**
     * @return Store|null
     */
    public function getStore(): ?Store
    {
        return $this->store;
    }

    /**
     * @param Store|null $store
     * @return $this
     */
    public function setStore(?Store $store): static
    {
        // unset the owning side of the relation if necessary
        if ($store === null && $this->store !== null) {
            $this->store->setAttach(null);
        }

        // set the owning side of the relation if necessary
        if ($store !== null && $store->getAttach() !== $this) {
            $store->setAttach($this);
        }

        $this->store = $store;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @param string|null $path
     * @return $this
     */
    public function setPath(?string $path): static
    {
        $this->path = $path;

        return $this;
    }
}
