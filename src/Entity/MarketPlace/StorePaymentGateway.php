<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Inno\Entity\Attach;
use Inno\Repository\MarketPlace\StorePaymentGatewayRepository;

#[ORM\Entity(repositoryClass: StorePaymentGatewayRepository::class)]
class StorePaymentGateway
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $summary = null;

    #[ORM\Column]
    private bool $active;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Attach $attach = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 100)]
    private ?string $handler_text = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deleted_at = null;

    #[ORM\OneToMany(targetEntity: StorePaymentGatewayStore::class, mappedBy: 'gateway')]
    private Collection $storePaymentGatewayStores;

    #[ORM\OneToMany(targetEntity: StoreInvoice::class, mappedBy: 'payment_gateway')]
    private Collection $storeInvoices;

    public function __construct()
    {
        $this->storePaymentGatewayStores = new ArrayCollection();
        $this->storeInvoices = new ArrayCollection();
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
     * @return string|null
     */
    public function getSummary(): ?string
    {
        return $this->summary;
    }

    /**
     * @param string $summary
     * @return $this
     */
    public function setSummary(string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return $this
     */
    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     * @return $this
     */
    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return string
     */
    public function getHandlerText(): string
    {
        return $this->handler_text;
    }

    public function setHandlerText(?string $handler_text): static
    {
        $this->handler_text = $handler_text;

        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deleted_at;
    }

    /**
     * @param DateTimeInterface|null $deleted_at
     * @return $this
     */
    public function setDeletedAt(?DateTimeInterface $deleted_at): static
    {
        $this->deleted_at = $deleted_at;

        return $this;
    }

    /**
     * @return Collection<int, StorePaymentGatewayStore>
     */
    public function getStorePaymentGatewayStores(): Collection
    {
        return $this->storePaymentGatewayStores;
    }

    /**
     * @param StorePaymentGatewayStore $storePaymentGatewayStore
     * @return $this
     */
    public function addStorePaymentGatewayStore(StorePaymentGatewayStore $storePaymentGatewayStore): static
    {
        if (!$this->storePaymentGatewayStores->contains($storePaymentGatewayStore)) {
            $this->storePaymentGatewayStores->add($storePaymentGatewayStore);
            $storePaymentGatewayStore->setGateway($this);
        }

        return $this;
    }

    /**
     * @param StorePaymentGatewayStore $storePaymentGatewayStore
     * @return $this
     */
    public function removeStorePaymentGatewayStore(StorePaymentGatewayStore $storePaymentGatewayStore): static
    {
        if ($this->storePaymentGatewayStores->removeElement($storePaymentGatewayStore)) {
            // set the owning side to null (unless already changed)
            if ($storePaymentGatewayStore->getGateway() === $this) {
                $storePaymentGatewayStore->setGateway(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StoreInvoice>
     */
    public function getStoreInvoices(): Collection
    {
        return $this->storeInvoices;
    }

    public function addStoreInvoice(StoreInvoice $storeInvoice): static
    {
        if (!$this->storeInvoices->contains($storeInvoice)) {
            $this->storeInvoices->add($storeInvoice);
            $storeInvoice->setPaymentGateway($this);
        }

        return $this;
    }

    public function removeStoreInvoice(StoreInvoice $storeInvoice): static
    {
        if ($this->storeInvoices->removeElement($storeInvoice)) {
            // set the owning side to null (unless already changed)
            if ($storeInvoice->getPaymentGateway() === $this) {
                $storeInvoice->setPaymentGateway(null);
            }
        }

        return $this;
    }

    /**
     * @return Attach|null
     */
    public function getAttach(): ?Attach
    {
        return $this->attach;
    }

    /**
     * @param Attach|null $attach
     * @return $this
     */
    public function setAttach(?Attach $attach): static
    {
        $this->attach = $attach;

        return $this;
    }
}
