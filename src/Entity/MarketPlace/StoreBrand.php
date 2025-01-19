<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreBrandRepository;

#[ORM\Entity(repositoryClass: StoreBrandRepository::class)]
class StoreBrand
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'storeBrands')]
    private ?Store $store = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $url = null;

    #[ORM\OneToMany(targetEntity: StoreProductBrand::class, mappedBy: 'brand')]
    private Collection $storeProductBrands;

    public function __construct()
    {
        $this->storeProductBrands = new ArrayCollection();
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
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     * @return $this
     */
    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return Collection<int, StoreProductBrand>
     */
    public function getStoreProductBrands(): Collection
    {
        return $this->storeProductBrands;
    }

    /**
     * @param StoreProductBrand $storeProductBrand
     * @return $this
     */
    public function addStoreProductBrand(StoreProductBrand $storeProductBrand): static
    {
        if (!$this->storeProductBrands->contains($storeProductBrand)) {
            $this->storeProductBrands->add($storeProductBrand);
            $storeProductBrand->setBrand($this);
        }

        return $this;
    }

    /**
     * @param StoreProductBrand $storeProductBrand
     * @return $this
     */
    public function removeStoreProductBrand(StoreProductBrand $storeProductBrand): static
    {
        if ($this->storeProductBrands->removeElement($storeProductBrand)) {
            // set the owning side to null (unless already changed)
            if ($storeProductBrand->getBrand() === $this) {
                $storeProductBrand->setBrand(null);
            }
        }

        return $this;
    }
}
