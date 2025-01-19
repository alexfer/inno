<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreProductSupplierRepository;

#[ORM\Entity(repositoryClass: StoreProductSupplierRepository::class)]
class StoreProductSupplier
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'storeProductSupplier', cascade: ['persist', 'remove'])]
    private ?StoreProduct $product = null;

    #[ORM\ManyToOne(inversedBy: 'storeProductSuppliers')]
    private ?StoreSupplier $supplier = null;

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
     * @return StoreSupplier|null
     */
    public function getSupplier(): ?StoreSupplier
    {
        return $this->supplier;
    }

    /**
     * @param StoreSupplier|null $supplier
     * @return $this
     */
    public function setSupplier(?StoreSupplier $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }
}
