<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Inno\Entity\User;
use Inno\Repository\MarketPlace\StoreMessageRepository;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StoreMessageRepository::class)]
class StoreMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'storeMessages')]
    private ?self $parent = null;

    #[ORM\Column(type: 'uuid')]
    private ?Uuid $identity = null;

    #[ORM\Column(nullable: true)]
    private ?bool $read = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $storeMessages;

    #[ORM\ManyToOne(inversedBy: 'storeMessages')]
    private ?Store $store = null;

    #[ORM\ManyToOne(inversedBy: 'storeMessages')]
    private ?StoreCustomer $customer = null;

    #[ORM\ManyToOne(inversedBy: 'storeMessages')]
    private ?StoreProduct $product = null;
    #[ORM\ManyToOne(inversedBy: 'storeMessages')]
    private ?StoreOrders $orders = null;

    #[ORM\ManyToOne(inversedBy: 'storeMessages')]
    private ?User $owner = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $priority;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deleted_at = null;

    const string PRIORITY_HIGH = 'high';

    const string PRIORITY_MEDIUM = 'medium';

    const string PRIORITY_LOW = 'low';

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->priority = self::PRIORITY_LOW;
        $this->storeMessages = new ArrayCollection();
        $this->identity = !$this->identity ? Uuid::v4() : $this->identity;
        $this->read = false;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
        $this->store = $store;

        return $this;
    }

    /**
     * @return StoreCustomer|null
     */
    public function getCustomer(): ?StoreCustomer
    {
        return $this->customer;
    }

    /**
     * @param StoreCustomer|null $customer
     * @return $this
     */
    public function setCustomer(?StoreCustomer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return StoreProduct|null
     */
    public function getProduct(): ?StoreProduct
    {
        return $this->product;
    }

    /**
     * @param StoreProduct|null $product
     * @return $this
     */
    public function setProduct(?StoreProduct $product): static
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): static
    {
        $this->message = $message;

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
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    /**
     * @param \DateTimeImmutable|null $updated_at
     * @return $this
     */
    public function setUpdatedAt(?\DateTimeImmutable $updated_at): static
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
     * @return string|null
     */
    public function getPriority(): ?string
    {
        return $this->priority;
    }

    /**
     * @param string|null $priority
     * @return $this
     */
    public function setPriority(?string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return self|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @param StoreMessage|null $parent
     * @return $this
     */
    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getStoreMessages(): Collection
    {
        return $this->storeMessages;
    }

    /**
     * @param StoreMessage $storeMessage
     * @return $this
     */
    public function addStoreMessage(self $storeMessage): static
    {
        if (!$this->storeMessages->contains($storeMessage)) {
            $this->storeMessages->add($storeMessage);
            $storeMessage->setParent($this);
        }

        return $this;
    }

    /**
     * @param StoreMessage $storeMessage
     * @return $this
     */
    public function removeStoreMessage(self $storeMessage): static
    {
        if ($this->storeMessages->removeElement($storeMessage)) {
            // set the owning side to null (unless already changed)
            if ($storeMessage->getParent() === $this) {
                $storeMessage->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return StoreOrders|null
     */
    public function getOrders(): ?StoreOrders
    {
        return $this->orders;
    }

    /**
     * @param StoreOrders|null $orders
     * @return $this
     */
    public function setOrders(?StoreOrders $orders): static
    {
        $this->orders = $orders;

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

    /**
     * @return Uuid|null
     */
    public function getIdentity(): ?Uuid
    {
        return $this->identity;
    }

    /**
     * @param Uuid $identity
     * @return $this
     */
    public function setIdentity(Uuid $identity): static
    {
        $this->identity = $identity;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isRead(): ?bool
    {
        return $this->read;
    }

    /**
     * @param bool|null $read
     * @return $this
     */
    public function setRead(?bool $read): static
    {
        $this->read = $read;

        return $this;
    }
}
