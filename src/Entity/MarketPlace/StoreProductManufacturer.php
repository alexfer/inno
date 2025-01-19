<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreProductManufacturerRepository;

#[ORM\Entity(repositoryClass: StoreProductManufacturerRepository::class)]
class StoreProductManufacturer
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'storeProductManufacturer', cascade: ['persist', 'remove'])]
    private ?StoreProduct $product = null;

    #[ORM\ManyToOne(inversedBy: 'storeProductManufacturers')]
    private ?StoreManufacturer $manufacturer = null;

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
     * @return StoreManufacturer|null
     */
    public function getManufacturer(): ?StoreManufacturer
    {
        return $this->manufacturer;
    }

    /**
     * @param StoreManufacturer|null $manufacturer
     * @return $this
     */
    public function setManufacturer(?StoreManufacturer $manufacturer): static
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }
}
