<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Inno\Entity\Attach;
use Inno\Repository\MarketPlace\StoreCarrierRepository;

#[ORM\Entity(repositoryClass: StoreCarrierRepository::class)]
class StoreCarrier
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private string $slug;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Attach $attach = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link_url = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_enabled = null;

    /**
     * @var Collection<int, StoreCarrierStore>
     */
    #[ORM\OneToMany(targetEntity: StoreCarrierStore::class, mappedBy: 'carrier')]
    private Collection $storeCarrierStores;

    /**
     * @var Collection<int, StoreInvoice>
     */
    #[ORM\OneToMany(targetEntity: StoreInvoice::class, mappedBy: 'carrier')]
    private Collection $storeInvoices;

    public function __construct()
    {
        $this->storeCarrierStores = new ArrayCollection();
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
    public function getSlug(): ?string
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

    /**
     * @return string|null
     */
    public function getLinkUrl(): ?string
    {
        return $this->link_url;
    }

    /**
     * @param string|null $link_url
     * @return $this
     */
    public function setLinkUrl(?string $link_url): static
    {
        $this->link_url = $link_url;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isEnabled(): ?bool
    {
        return $this->is_enabled;
    }

    /**
     * @param bool|null $is_enabled
     * @return $this
     */
    public function setEnabled(?bool $is_enabled): static
    {
        $this->is_enabled = $is_enabled;

        return $this;
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
     * @return Collection<int, StoreCarrierStore>
     */
    public function getStoreCarrierStores(): Collection
    {
        return $this->storeCarrierStores;
    }

    /**
     * @param StoreCarrierStore $storeCarrierStore
     * @return $this
     */
    public function addStoreCarrierStore(StoreCarrierStore $storeCarrierStore): static
    {
        if (!$this->storeCarrierStores->contains($storeCarrierStore)) {
            $this->storeCarrierStores->add($storeCarrierStore);
            $storeCarrierStore->setCarrier($this);
        }

        return $this;
    }

    /**
     * @param StoreCarrierStore $storeCarrierStore
     * @return $this
     */
    public function removeStoreCarrierStore(StoreCarrierStore $storeCarrierStore): static
    {
        if ($this->storeCarrierStores->removeElement($storeCarrierStore)) {
            // set the owning side to null (unless already changed)
            if ($storeCarrierStore->getCarrier() === $this) {
                $storeCarrierStore->setCarrier(null);
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

    /**
     * @param StoreInvoice $storeInvoice
     * @return $this
     */
    public function addStoreInvoice(StoreInvoice $storeInvoice): static
    {
        if (!$this->storeInvoices->contains($storeInvoice)) {
            $this->storeInvoices->add($storeInvoice);
            $storeInvoice->setCarrier($this);
        }

        return $this;
    }

    /**
     * @param StoreInvoice $storeInvoice
     * @return $this
     */
    public function removeStoreInvoice(StoreInvoice $storeInvoice): static
    {
        if ($this->storeInvoices->removeElement($storeInvoice)) {
            // set the owning side to null (unless already changed)
            if ($storeInvoice->getCarrier() === $this) {
                $storeInvoice->setCarrier(null);
            }
        }

        return $this;
    }
}
