<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreProductRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StoreProductRepository::class)]
#[UniqueEntity(fields: ['slug'], message: 'slug.unique', errorPath: 'slug')]
class StoreProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(length: 512)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 512, unique: true, nullable: true)]
    #[Assert\Valid]
    private ?string $slug = null;

    #[ORM\Column]
    private ?int $quantity;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $cost;

    #[ORM\Column(length: 80)]
    private ?string $short_name = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $pckg_discount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sku = null;

    #[ORM\Column(nullable: true)]
    private ?int $pckg_quantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $fee;

    #[ORM\OneToMany(targetEntity: StoreCategoryProduct::class, mappedBy: 'product')]
    private Collection $storeCategoryProducts;

    #[ORM\OneToMany(targetEntity: StoreProductAttach::class, mappedBy: 'product')]
    private Collection $storeProductAttaches;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Store $store = null;

    #[ORM\OneToOne(mappedBy: 'product', cascade: ['persist', 'remove'])]
    private ?StoreProductBrand $storeProductBrand = null;

    #[ORM\OneToOne(mappedBy: 'product', cascade: ['persist', 'remove'])]
    private ?StoreProductSupplier $storeProductSupplier = null;

    #[ORM\OneToOne(mappedBy: 'product', cascade: ['persist', 'remove'])]
    private ?StoreProductManufacturer $storeProductManufacturer = null;

    #[ORM\OneToMany(targetEntity: StoreOrdersProduct::class, mappedBy: 'product')]
    private Collection $storeOrdersProducts;

    #[ORM\OneToMany(targetEntity: StoreProductAttribute::class, mappedBy: 'product')]
    private Collection $storeProductAttributes;

    #[ORM\OneToMany(targetEntity: StoreWishlist::class, mappedBy: 'product')]
    private Collection $storeWishlists;

    /**
     * @var Collection<int, StoreCoupon>
     */
    #[ORM\ManyToMany(targetEntity: StoreCoupon::class, mappedBy: 'product')]
    private Collection $storeCoupons;

    /**
     * @var Collection<int, StoreMessage>
     */
    #[ORM\OneToMany(targetEntity: StoreMessage::class, mappedBy: 'product')]
    private Collection $storeMessages;

    #[ORM\OneToOne(mappedBy: 'product', cascade: ['persist', 'remove'])]
    private ?StoreProductDiscount $storeProductDiscount = null;

    #[ORM\Column]
    private ?DateTime $created_at;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updated_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deleted_at = null;

    public function __construct()
    {
        $this->created_at = new DateTime();
        $this->storeCategoryProducts = new ArrayCollection();
        $this->storeProductAttaches = new ArrayCollection();
        $this->storeOrdersProducts = new ArrayCollection();
        $this->storeProductAttributes = new ArrayCollection();
        $this->storeWishlists = new ArrayCollection();
        $this->storeCoupons = new ArrayCollection();
        $this->storeMessages = new ArrayCollection();
    }

    /**
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }

    /**
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     *
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     *
     * @param string $slug
     * @return static
     */
    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     *
     * @return int|null
     */
    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    /**
     * @param int|null $quantity
     * @return $this
     */
    public function setQuantity(?int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getCost(): string
    {
        return $this->cost;
    }

    /**
     * @param string $cost
     * @return $this
     */
    public function setCost(string $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     *
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->created_at;
    }

    /**
     *
     * @param DateTime $created_at
     * @return static
     */
    public function setCreatedAt(DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     *
     * @return DateTimeInterface|null
     */
    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updated_at;
    }

    /**
     *
     * @param DateTimeInterface|null $updated_at
     * @return static
     */
    public function setUpdatedAt(?DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     *
     * @return DateTimeInterface|null
     */
    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deleted_at;
    }

    /**
     *
     * @param DateTimeInterface|null $deleted_at
     * @return static
     */
    public function setDeletedAt(?DateTimeInterface $deleted_at): static
    {
        $this->deleted_at = $deleted_at;

        return $this;
    }

    /**
     * @return Collection<int, StoreCategoryProduct>
     */
    public function getStoreCategoryProducts(): Collection
    {
        return $this->storeCategoryProducts;
    }

    /**
     * @param StoreCategoryProduct $storeCategoryProduct
     * @return $this
     */
    public function addStoreCategoryProduct(StoreCategoryProduct $storeCategoryProduct): static
    {
        if (!$this->storeCategoryProducts->contains($storeCategoryProduct)) {
            $this->storeCategoryProducts->add($storeCategoryProduct);
            $storeCategoryProduct->setProduct($this);
        }

        return $this;
    }

    /**
     *
     * @param StoreCategoryProduct $storeCategoryProduct
     * @return static
     */
    public function removeStoreCategoryProduct(StoreCategoryProduct $storeCategoryProduct): static
    {
        if ($this->storeCategoryProducts->removeElement($storeCategoryProduct)) {
            // set the owning side to null (unless already changed)
            if ($storeCategoryProduct->getProduct() === $this) {
                $storeCategoryProduct->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StoreProductAttach>
     */
    public function getStoreProductAttaches(): Collection
    {
        return $this->storeProductAttaches;
    }

    /**
     * @param StoreProductAttach $storeProductAttach
     * @return $this
     */
    public function addStoreProductAttach(StoreProductAttach $storeProductAttach): static
    {
        if (!$this->storeProductAttaches->contains($storeProductAttach)) {
            $this->storeProductAttaches->add($storeProductAttach);
            $storeProductAttach->setProduct($this);
        }

        return $this;
    }

    /**
     * @param StoreProductAttach $storeProductAttach
     * @return $this
     */
    public function removeStoreProductAttach(StoreProductAttach $storeProductAttach): static
    {
        if ($this->storeProductAttaches->removeElement($storeProductAttach)) {
            // set the owning side to null (unless already changed)
            if ($storeProductAttach->getProduct() === $this) {
                $storeProductAttach->setProduct(null);
            }
        }

        return $this;
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
     * @return StoreProductBrand|null
     */
    public function getStoreProductBrand(): ?StoreProductBrand
    {
        return $this->storeProductBrand;
    }

    /**
     * @param StoreProductBrand|null $storeProductBrand
     * @return $this
     */
    public function setStoreProductBrand(?StoreProductBrand $storeProductBrand): static
    {
        // unset the owning side of the relation if necessary
        if ($storeProductBrand === null && $this->storeProductBrand !== null) {
            $this->storeProductBrand->setProduct(null);
        }

        // set the owning side of the relation if necessary
        if ($storeProductBrand !== null && $storeProductBrand->getProduct() !== $this) {
            $storeProductBrand->setProduct($this);
        }

        $this->storeProductBrand = $storeProductBrand;

        return $this;
    }

    /**
     * @return StoreProductSupplier|null
     */
    public function getStoreProductSupplier(): ?StoreProductSupplier
    {
        return $this->storeProductSupplier;
    }

    /**
     * @param StoreProductSupplier|null $storeProductSupplier
     * @return $this
     */
    public function setStoreProductSupplier(?StoreProductSupplier $storeProductSupplier): static
    {
        // unset the owning side of the relation if necessary
        if ($storeProductSupplier === null && $this->storeProductSupplier !== null) {
            $this->storeProductSupplier->setProduct(null);
        }

        // set the owning side of the relation if necessary
        if ($storeProductSupplier !== null && $storeProductSupplier->getProduct() !== $this) {
            $storeProductSupplier->setProduct($this);
        }

        $this->storeProductSupplier = $storeProductSupplier;

        return $this;
    }

    /**
     * @return StoreProductManufacturer|null
     */
    public function getStoreProductManufacturer(): ?StoreProductManufacturer
    {
        return $this->storeProductManufacturer;
    }

    /**
     * @param StoreProductManufacturer|null $storeProductManufacturer
     * @return $this
     */
    public function setStoreProductManufacturer(?StoreProductManufacturer $storeProductManufacturer): static
    {
        // unset the owning side of the relation if necessary
        if ($storeProductManufacturer === null && $this->storeProductManufacturer !== null) {
            $this->storeProductManufacturer->setProduct(null);
        }

        // set the owning side of the relation if necessary
        if ($storeProductManufacturer !== null && $storeProductManufacturer->getProduct() !== $this) {
            $storeProductManufacturer->setProduct($this);
        }

        $this->storeProductManufacturer = $storeProductManufacturer;

        return $this;
    }

    /**
     * @return Collection<int, StoreOrdersProduct>
     */
    public function getStoreOrdersProducts(): Collection
    {
        return $this->storeOrdersProducts;
    }

    /**
     * @param StoreOrdersProduct $storeOrdersProduct
     * @return $this
     */
    public function addStoreOrdersProduct(StoreOrdersProduct $storeOrdersProduct): static
    {
        if (!$this->storeOrdersProducts->contains($storeOrdersProduct)) {
            $this->storeOrdersProducts->add($storeOrdersProduct);
            $storeOrdersProduct->setProduct($this);
        }

        return $this;
    }

    /**
     * @param StoreOrdersProduct $storeOrdersProduct
     * @return $this
     */
    public function removeStoreOrdersProduct(StoreOrdersProduct $storeOrdersProduct): static
    {
        if ($this->storeOrdersProducts->removeElement($storeOrdersProduct)) {
            // set the owning side to null (unless already changed)
            if ($storeOrdersProduct->getProduct() === $this) {
                $storeOrdersProduct->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getShortName(): ?string
    {
        return $this->short_name;
    }

    /**
     * @param string|null $short_name
     * @return $this
     */
    public function setShortName(?string $short_name): static
    {
        $this->short_name = $short_name;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPckgDiscount(): ?int
    {
        return $this->pckg_discount;
    }

    /**
     * @param int|null $pckg_discount
     * @return $this
     */
    public function setPckgDiscount(?int $pckg_discount): static
    {
        $this->pckg_discount = $pckg_discount;

        return $this;
    }

    /**
     * @return Collection<int, StoreProductAttribute>
     */
    public function getStoreProductAttributes(): Collection
    {
        return $this->storeProductAttributes;
    }

    public function addStoreProductAttribute(StoreProductAttribute $storeProductAttribute): static
    {
        if (!$this->storeProductAttributes->contains($storeProductAttribute)) {
            $this->storeProductAttributes->add($storeProductAttribute);
            $storeProductAttribute->setProduct($this);
        }

        return $this;
    }

    /**
     * @param StoreProductAttribute $storeProductAttribute
     * @return $this
     */
    public function removeStoreProductAttribute(StoreProductAttribute $storeProductAttribute): static
    {
        if ($this->storeProductAttributes->removeElement($storeProductAttribute)) {
            // set the owning side to null (unless already changed)
            if ($storeProductAttribute->getProduct() === $this) {
                $storeProductAttribute->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSku(): ?string
    {
        return $this->sku;
    }

    /**
     * @param string|null $sku
     * @return $this
     */
    public function setSku(?string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPckgQuantity(): ?int
    {
        return $this->pckg_quantity;
    }

    /**
     * @param int|null $pckg_quantity
     * @return $this
     */
    public function setPckgQuantity(?int $pckg_quantity): static
    {
        $this->pckg_quantity = $pckg_quantity;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFee(): ?string
    {
        return $this->fee;
    }

    /**
     * @param string|null $fee
     * @return $this
     */
    public function setFee(?string $fee): static
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * @return Collection<int, StoreWishlist>
     */
    public function getStoreWishlists(): Collection
    {
        return $this->storeWishlists;
    }

    /**
     * @param StoreWishlist $storeWishlist
     * @return $this
     */
    public function addStoreWishlist(StoreWishlist $storeWishlist): static
    {
        if (!$this->storeWishlists->contains($storeWishlist)) {
            $this->storeWishlists->add($storeWishlist);
            $storeWishlist->setProduct($this);
        }

        return $this;
    }

    /**
     * @param StoreWishlist $storeWishlist
     * @return $this
     */
    public function removeStoreWishlist(StoreWishlist $storeWishlist): static
    {
        if ($this->storeWishlists->removeElement($storeWishlist)) {
            // set the owning side to null (unless already changed)
            if ($storeWishlist->getProduct() === $this) {
                $storeWishlist->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StoreCoupon>
     */
    public function getStoreCoupons(): Collection
    {
        return $this->storeCoupons;
    }

    /**
     * @param StoreCoupon $storeCoupon
     * @return $this
     */
    public function addStoreCoupon(StoreCoupon $storeCoupon): static
    {
        if (!$this->storeCoupons->contains($storeCoupon)) {
            $this->storeCoupons->add($storeCoupon);
            $storeCoupon->addProduct($this);
        }

        return $this;
    }

    /**
     * @param StoreCoupon $storeCoupon
     * @return $this
     */
    public function removeStoreCoupon(StoreCoupon $storeCoupon): static
    {
        if ($this->storeCoupons->removeElement($storeCoupon)) {
            $storeCoupon->removeProduct($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, StoreMessage>
     */
    public function getStoreMessages(): Collection
    {
        return $this->storeMessages;
    }

    /**
     * @param StoreMessage $storeMessage
     * @return $this
     */
    public function addStoreMessage(StoreMessage $storeMessage): static
    {
        if (!$this->storeMessages->contains($storeMessage)) {
            $this->storeMessages->add($storeMessage);
            $storeMessage->setProduct($this);
        }

        return $this;
    }

    /**
     * @param StoreMessage $storeMessage
     * @return $this
     */
    public function removeStoreMessage(StoreMessage $storeMessage): static
    {
        if ($this->storeMessages->removeElement($storeMessage)) {
            // set the owning side to null (unless already changed)
            if ($storeMessage->getProduct() === $this) {
                $storeMessage->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return StoreProductDiscount|null
     */
    public function getStoreProductDiscount(): ?StoreProductDiscount
    {
        return $this->storeProductDiscount;
    }

    /**
     * @param StoreProductDiscount|null $storeProductDiscount
     * @return $this
     */
    public function setStoreProductDiscount(?StoreProductDiscount $storeProductDiscount): static
    {
        // unset the owning side of the relation if necessary
        if ($storeProductDiscount === null && $this->storeProductDiscount !== null) {
            $this->storeProductDiscount->setProduct(null);
        }

        // set the owning side of the relation if necessary
        if ($storeProductDiscount !== null && $storeProductDiscount->getProduct() !== $this) {
            $storeProductDiscount->setProduct($this);
        }

        $this->storeProductDiscount = $storeProductDiscount;

        return $this;
    }
}
