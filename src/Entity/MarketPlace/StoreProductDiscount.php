<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreProductDiscountRepository;

#[ORM\Entity(repositoryClass: StoreProductDiscountRepository::class)]
class StoreProductDiscount
{
    const string UNIT_PERCENTAGE = 'percentage';

    const string UNIT_STOCK = 'stock';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'storeProductDiscount', cascade: ['persist', 'remove'])]
    private ?StoreProduct $product = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $value = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at;

    public function __construct()
    {
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?StoreProduct
    {
        return $this->product;
    }

    public function setProduct(?StoreProduct $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }
}
