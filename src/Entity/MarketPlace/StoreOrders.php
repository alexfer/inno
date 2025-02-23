<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Inno\Entity\MarketPlace\Enum\EnumStoreOrderStatus;
use Inno\Repository\MarketPlace\StoreOrdersRepository;

#[ORM\Entity(repositoryClass: StoreOrdersRepository::class)]
#[ORM\Index(name: 'session_index', columns: ['session'])]
#[ORM\Index(name: 'order_number_idx', columns: ['number'])]
class StoreOrders
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'storeOrders')]
    private ?Store $store = null;

    #[ORM\Column(length: 50)]
    private ?string $number = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $total = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $tax = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $session = null;

    #[ORM\Column(length: 100, enumType: EnumStoreOrderStatus::class)]
    private ?EnumStoreOrderStatus $status;

    #[ORM\OneToOne(mappedBy: 'orders', cascade: ['persist', 'remove'])]
    private ?StoreInvoice $storeInvoice = null;

    #[ORM\OneToMany(targetEntity: StoreOrdersProduct::class, mappedBy: 'orders')]
    private Collection $storeOrdersProducts;

    #[ORM\OneToMany(targetEntity: StoreCustomerOrders::class, mappedBy: 'orders')]
    private Collection $storeCustomerOrders;

    /**
     * @var Collection<int, StoreMessage>
     */
    #[ORM\OneToMany(targetEntity: StoreMessage::class, mappedBy: 'orders')]
    private Collection $storeMessages;

    #[ORM\Column]
    private ?DateTimeImmutable $created_at;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $completed_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cancelled_at = null;

    public function __construct()
    {
        $this->created_at = new DateTimeImmutable();
        $this->storeOrdersProducts = new ArrayCollection();
        $this->status = EnumStoreOrderStatus::Processing;
        $this->storeCustomerOrders = new ArrayCollection();
        $this->storeMessages = new ArrayCollection();
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
     * @return string|null
     */
    public function getNumber(): ?string
    {
        return $this->number;
    }

    /**
     * @param string $number
     * @return $this
     */
    public function setNumber(string $number): static
    {
        $this->number = $number;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTotal(): ?string
    {
        return $this->total;
    }

    /**
     * @param string $total
     * @return $this
     */
    public function setTotal(string $total): static
    {
        $this->total = $total;
        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->created_at;
    }

    /**
     * @param DateTimeImmutable $created_at
     * @return $this
     */
    public function setCreatedAt(DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getCompletedAt(): ?DateTimeInterface
    {
        return $this->completed_at;
    }

    /**
     * @param DateTimeInterface|null $completed_at
     * @return $this
     */
    public function setCompletedAt(?DateTimeInterface $completed_at): static
    {
        $this->completed_at = $completed_at;

        return $this;
    }

    /**
     * @return StoreInvoice|null
     */
    public function getStoreInvoice(): ?StoreInvoice
    {
        return $this->storeInvoice;
    }

    /**
     * @param StoreInvoice|null $storeInvoice
     * @return $this
     */
    public function setStoreInvoice(?StoreInvoice $storeInvoice): static
    {
        // unset the owning side of the relation if necessary
        if ($storeInvoice === null && $this->storeInvoice !== null) {
            $this->storeInvoice->setOrders(null);
        }

        // set the owning side of the relation if necessary
        if ($storeInvoice !== null && $storeInvoice->getOrders() !== $this) {
            $storeInvoice->setOrders($this);
        }

        $this->storeInvoice = $storeInvoice;

        return $this;
    }

    /**
     * @return Collection<int, StoreOrdersProduct>
     */
    public function getStoreOrdersProducts(): Collection
    {
        return $this->storeOrdersProducts;
    }

    /**
     * @param StoreOrdersProduct $storeOrdersProduct
     * @return $this
     */
    public function addStoreOrdersProduct(StoreOrdersProduct $storeOrdersProduct): static
    {
        if (!$this->storeOrdersProducts->contains($storeOrdersProduct)) {
            $this->storeOrdersProducts->add($storeOrdersProduct);
            $storeOrdersProduct->setOrders($this);
        }

        return $this;
    }

    /**
     * @param StoreOrdersProduct $storeOrdersProduct
     * @return $this
     */
    public function removeStoreOrdersProduct(StoreOrdersProduct $storeOrdersProduct): static
    {
        if ($this->storeOrdersProducts->removeElement($storeOrdersProduct)) {
            // set the owning side to null (unless already changed)
            if ($storeOrdersProduct->getOrders() === $this) {
                $storeOrdersProduct->setOrders(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSession(): ?string
    {
        return $this->session;
    }

    /**
     * @param string|null $session
     * @return $this
     */
    public function setSession(?string $session): static
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @return EnumStoreOrderStatus|null
     */
    public function getStatus(): ?EnumStoreOrderStatus
    {
        return $this->status;
    }

    /**
     * @param EnumStoreOrderStatus|null $status
     * @return $this
     */
    public function setStatus(?EnumStoreOrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, StoreCustomerOrders>
     */
    public function getStoreCustomerOrders(): Collection
    {
        return $this->storeCustomerOrders;
    }

    /**
     * @param StoreCustomerOrders $storeCustomerOrder
     * @return $this
     */
    public function addStoreCustomerOrder(StoreCustomerOrders $storeCustomerOrder): static
    {
        if (!$this->storeCustomerOrders->contains($storeCustomerOrder)) {
            $this->storeCustomerOrders->add($storeCustomerOrder);
            $storeCustomerOrder->setOrders($this);
        }

        return $this;
    }

    /**
     * @param StoreCustomerOrders $storeCustomerOrder
     * @return $this
     */
    public function removeStoreCustomerOrder(StoreCustomerOrders $storeCustomerOrder): static
    {
        if ($this->storeCustomerOrders->removeElement($storeCustomerOrder)) {
            // set the owning side to null (unless already changed)
            if ($storeCustomerOrder->getOrders() === $this) {
                $storeCustomerOrder->setOrders(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StoreMessage>
     */
    public function getStoreMessages(): Collection
    {
        return $this->storeMessages;
    }

    /**
     * @param StoreMessage $storeMessage
     * @return $this
     */
    public function addStoreMessage(StoreMessage $storeMessage): static
    {
        if (!$this->storeMessages->contains($storeMessage)) {
            $this->storeMessages->add($storeMessage);
            $storeMessage->setOrders($this);
        }

        return $this;
    }

    /**
     * @param StoreMessage $storeMessage
     * @return $this
     */
    public function removeStoreMessage(StoreMessage $storeMessage): static
    {
        if ($this->storeMessages->removeElement($storeMessage)) {
            // set the owning side to null (unless already changed)
            if ($storeMessage->getOrders() === $this) {
                $storeMessage->setOrders(null);
            }
        }

        return $this;
    }

    public function getTax(): ?string
    {
        return $this->tax;
    }

    public function setTax(?string $tax): static
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getCancelledAt(): ?\DateTimeInterface
    {
        return $this->cancelled_at;
    }

    /**
     * @param DateTimeInterface|null $cancelled_at
     * @return $this
     */
    public function setCancelledAt(?\DateTimeInterface $cancelled_at): static
    {
        $this->cancelled_at = $cancelled_at;

        return $this;
    }
}
