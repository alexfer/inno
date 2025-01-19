<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreProductAttributeRepository;

#[ORM\Entity(repositoryClass: StoreProductAttributeRepository::class)]
class StoreProductAttribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $in_front = null;

    #[ORM\OneToMany(targetEntity: StoreProductAttributeValue::class, mappedBy: 'attribute')]
    private Collection $storeProductAttributeValues;

    #[ORM\ManyToOne(inversedBy: 'storeProductAttributes')]
    private ?StoreProduct $product = null;

    #[ORM\Column]
    private ?DateTimeImmutable $created_at;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deleted_at = null;

    public function __construct()
    {
        $this->created_at = new DateTimeImmutable();
        $this->storeProductAttributeValues = new ArrayCollection();
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
     * @return int|null
     */
    public function getInFront(): ?int
    {
        return $this->in_front;
    }

    /**
     * @param int $in_front
     * @return $this
     */
    public function setInFront(int $in_front): static
    {
        $this->in_front = $in_front;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->created_at;
    }

    /**
     * @param DateTimeImmutable $created_at
     * @return $this
     */
    public function setCreatedAt(DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

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
     * @return Collection<int, StoreProductAttributeValue>
     */
    public function getStoreProductAttributeValues(): Collection
    {
        return $this->storeProductAttributeValues;
    }

    /**
     * @param StoreProductAttributeValue $storeProductAttributeValue
     * @return $this
     */
    public function addStoreProductAttributeValue(StoreProductAttributeValue $storeProductAttributeValue): static
    {
        if (!$this->storeProductAttributeValues->contains($storeProductAttributeValue)) {
            $this->storeProductAttributeValues->add($storeProductAttributeValue);
            $storeProductAttributeValue->setAttribute($this);
        }

        return $this;
    }

    /**
     * @param StoreProductAttributeValue $storeProductAttributeValue
     * @return $this
     */
    public function removeStoreProductAttributeValue(StoreProductAttributeValue $storeProductAttributeValue): static
    {
        if ($this->storeProductAttributeValues->removeElement($storeProductAttributeValue)) {
            // set the owning side to null (unless already changed)
            if ($storeProductAttributeValue->getAttribute() === $this) {
                $storeProductAttributeValue->setAttribute(null);
            }
        }

        return $this;
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
}
