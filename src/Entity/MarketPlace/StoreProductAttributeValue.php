<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreProductAttributeValueRepository;

#[ORM\Entity(repositoryClass: StoreProductAttributeValueRepository::class)]
class StoreProductAttributeValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'storeProductAttributeValues')]
    private ?StoreProductAttribute $attribute = null;

    #[ORM\Column(length: 255)]
    private ?string $value = null;

    #[ORM\Column]
    private array $extra = [];

    #[ORM\Column]
    private ?int $in_use = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function __construct()
    {
        $this->in_use = 1;
    }

    /**
     * @return StoreProductAttribute|null
     */
    public function getAttribute(): ?StoreProductAttribute
    {
        return $this->attribute;
    }

    /**
     * @param StoreProductAttribute|null $attribute
     * @return $this
     */
    public function setAttribute(?StoreProductAttribute $attribute): static
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * @param array $extra
     * @return $this
     */
    public function setExtra(array $extra): static
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getInUse(): ?int
    {
        return $this->in_use;
    }

    /**
     * @param int $in_use
     * @return $this
     */
    public function setInUse(int $in_use): static
    {
        $this->in_use = $in_use;

        return $this;
    }
}
