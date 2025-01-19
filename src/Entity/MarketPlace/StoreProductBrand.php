<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreProductBrandRepository;

#[ORM\Entity(repositoryClass: StoreProductBrandRepository::class)]
class StoreProductBrand
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'storeProductBrand', cascade: ['persist', 'remove'])]
    private ?StoreProduct $product = null;

    #[ORM\ManyToOne(inversedBy: 'storeProductBrands')]
    private ?StoreBrand $brand = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return StoreBrand|null
     */
    public function getBrand(): ?StoreBrand
    {
        return $this->brand;
    }

    /**
     * @param StoreBrand|null $brand
     * @return $this
     */
    public function setBrand(?StoreBrand $brand): static
    {
        $this->brand = $brand;

        return $this;
    }
}
