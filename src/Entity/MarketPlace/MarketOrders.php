<?php

namespace App\Entity\MarketPlace;

use App\Repository\MarketPlace\MarketOrdersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MarketOrdersRepository::class)]
class MarketOrders
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'marketOrders')]
    private ?Market $market = null;

    #[ORM\Column(length: 50)]
    private ?string $number = null;

    #[ORM\Column]
    private ?float $total = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completed_at = null;

    #[ORM\OneToOne(mappedBy: 'orders', cascade: ['persist', 'remove'])]
    private ?MarketInvoice $marketInvoice = null;

    #[ORM\OneToMany(mappedBy: 'orders', targetEntity: MarketOrdersProduct::class)]
    private Collection $marketOrdersProducts;

    public function __construct()
    {
        $this->marketOrdersProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMarket(): ?Market
    {
        return $this->market;
    }

    public function setMarket(?Market $market): static
    {
        $this->market = $market;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completed_at;
    }

    public function setCompletedAt(?\DateTimeInterface $completed_at): static
    {
        $this->completed_at = $completed_at;

        return $this;
    }

    public function getMarketInvoice(): ?MarketInvoice
    {
        return $this->marketInvoice;
    }

    public function setMarketInvoice(?MarketInvoice $marketInvoice): static
    {
        // unset the owning side of the relation if necessary
        if ($marketInvoice === null && $this->marketInvoice !== null) {
            $this->marketInvoice->setOrders(null);
        }

        // set the owning side of the relation if necessary
        if ($marketInvoice !== null && $marketInvoice->getOrders() !== $this) {
            $marketInvoice->setOrders($this);
        }

        $this->marketInvoice = $marketInvoice;

        return $this;
    }

    /**
     * @return Collection<int, MarketOrdersProduct>
     */
    public function getMarketOrdersProducts(): Collection
    {
        return $this->marketOrdersProducts;
    }

    public function addMarketOrdersProduct(MarketOrdersProduct $marketOrdersProduct): static
    {
        if (!$this->marketOrdersProducts->contains($marketOrdersProduct)) {
            $this->marketOrdersProducts->add($marketOrdersProduct);
            $marketOrdersProduct->setOrders($this);
        }

        return $this;
    }

    public function removeMarketOrdersProduct(MarketOrdersProduct $marketOrdersProduct): static
    {
        if ($this->marketOrdersProducts->removeElement($marketOrdersProduct)) {
            // set the owning side to null (unless already changed)
            if ($marketOrdersProduct->getOrders() === $this) {
                $marketOrdersProduct->setOrders(null);
            }
        }

        return $this;
    }
}
