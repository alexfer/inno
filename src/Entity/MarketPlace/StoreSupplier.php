<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreSupplierRepository;

#[ORM\Entity(repositoryClass: StoreSupplierRepository::class)]
class StoreSupplier
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'storeSuppliers')]
    private ?Store $store = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 3)]
    private ?string $country = null;

    #[ORM\OneToMany(targetEntity: StoreProductSupplier::class, mappedBy: 'supplier')]
    private Collection $storeProductSuppliers;

    public function __construct()
    {
        $this->storeProductSuppliers = new ArrayCollection();
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
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return $this
     */
    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Collection<int, StoreProductSupplier>
     */
    public function getStoreProductSuppliers(): Collection
    {
        return $this->storeProductSuppliers;
    }

    /**
     * @param StoreProductSupplier $storeProductSupplier
     * @return $this
     */
    public function addStoreProductSupplier(StoreProductSupplier $storeProductSupplier): static
    {
        if (!$this->storeProductSuppliers->contains($storeProductSupplier)) {
            $this->storeProductSuppliers->add($storeProductSupplier);
            $storeProductSupplier->setSupplier($this);
        }

        return $this;
    }

    /**
     * @param StoreProductSupplier $storeProductSupplier
     * @return $this
     */
    public function removeStoreProductSupplier(StoreProductSupplier $storeProductSupplier): static
    {
        if ($this->storeProductSuppliers->removeElement($storeProductSupplier)) {
            // set the owning side to null (unless already changed)
            if ($storeProductSupplier->getSupplier() === $this) {
                $storeProductSupplier->setSupplier(null);
            }
        }

        return $this;
    }
}
