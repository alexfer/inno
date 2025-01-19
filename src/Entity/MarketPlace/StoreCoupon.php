<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreCouponRepository;

#[ORM\Entity(repositoryClass: StoreCouponRepository::class)]
class StoreCoupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'storeCoupons')]
    private ?Store $store = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?int $discount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $event = null;

    /**
     * @var Collection<int, StoreProduct>
     */
    #[ORM\ManyToMany(targetEntity: StoreProduct::class, inversedBy: 'storeCoupons')]
    private Collection $product;

    /**
     * @var Collection<int, StoreCouponCode>
     */
    #[ORM\OneToMany(targetEntity: StoreCouponCode::class, mappedBy: 'coupon')]
    private Collection $storeCouponCodes;

    /**
     * @var Collection<int, StoreCouponUsage>
     */
    #[ORM\OneToMany(targetEntity: StoreCouponUsage::class, mappedBy: 'coupon')]
    private Collection $storeCouponUsages;

    #[ORM\Column]
    private ?int $available = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $max_usage = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column]
    private ?int $order_limit = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $promotion_text = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $started_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expired_at = null;

    const string COUPON_ORDER = 'order';

    const string COUPON_PRODUCT = 'product';

    const string COUPON_SHIPMENT = 'shipment';

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->product = new ArrayCollection();
        $this->storeCouponCodes = new ArrayCollection();
        $this->storeCouponUsages = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getDiscount(): ?int
    {
        return $this->discount;
    }

    /**
     * @param int|null $discount
     * @return $this
     */
    public function setDiscount(?int $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPrice(): ?string
    {
        return $this->price;
    }

    /**
     * @param string|null $price
     * @return $this
     */
    public function setPrice(?string $price): static
    {
        $this->price = number_format((float)$price, 2, '.', '');;

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
     * @return \DateTimeImmutable|null
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    /**
     * @param \DateTimeImmutable $created_at
     * @return $this
     */
    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->started_at;
    }

    /**
     * @param \DateTimeImmutable|null $started_at
     * @return $this
     */
    public function setStartedAt(?\DateTimeImmutable $started_at): static
    {
        $this->started_at = $started_at;

        return $this;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getExpiredAt(): ?\DateTimeImmutable
    {
        return $this->expired_at;
    }

    /**
     * @param \DateTimeImmutable|null $expired_at
     * @return $this
     */
    public function setExpiredAt(?\DateTimeImmutable $expired_at): static
    {
        $this->expired_at = $expired_at;

        return $this;
    }

    /**
     * @return Collection<int, StoreProduct>
     */
    public function getProduct(): Collection
    {
        return $this->product;
    }

    /**
     * @param StoreProduct $product
     * @return $this
     */
    public function addProduct(StoreProduct $product): static
    {
        if (!$this->product->contains($product)) {
            $this->product->add($product);
        }

        return $this;
    }

    /**
     * @param StoreProduct $product
     * @return $this
     */
    public function removeProduct(StoreProduct $product): static
    {
        $this->product->removeElement($product);

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
     * @return int|null
     */
    public function getEvent(): ?int
    {
        return $this->event;
    }

    /**
     * @param int|null $event
     * @return $this
     */
    public function setEvent(?int $event): static
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getMaxUsage(): ?int
    {
        return $this->max_usage;
    }

    /**
     * @param int $max_usage
     * @return $this
     */
    public function setMaxUsage(int $max_usage): static
    {
        $this->max_usage = $max_usage;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getOrderLimit(): ?int
    {
        return $this->order_limit;
    }

    /**
     * @param int $order_limit
     * @return $this
     */
    public function setOrderLimit(int $order_limit): static
    {
        $this->order_limit = $order_limit;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPromotionText(): ?string
    {
        return $this->promotion_text;
    }

    /**
     * @param string|null $promotion_text
     * @return $this
     */
    public function setPromotionText(?string $promotion_text): static
    {
        $this->promotion_text = $promotion_text;

        return $this;
    }

    /**
     * @return Collection<int, StoreCouponCode>
     */
    public function getStoreCouponCodes(): Collection
    {
        return $this->storeCouponCodes;
    }

    /**
     * @param StoreCouponCode $storeCouponCode
     * @return $this
     */
    public function addStoreCouponCode(StoreCouponCode $storeCouponCode): static
    {
        if (!$this->storeCouponCodes->contains($storeCouponCode)) {
            $this->storeCouponCodes->add($storeCouponCode);
            $storeCouponCode->setCoupon($this);
        }

        return $this;
    }

    /**
     * @param StoreCouponCode $storeCouponCode
     * @return $this
     */
    public function removeStoreCouponCode(StoreCouponCode $storeCouponCode): static
    {
        if ($this->storeCouponCodes->removeElement($storeCouponCode)) {
            // set the owning side to null (unless already changed)
            if ($storeCouponCode->getCoupon() === $this) {
                $storeCouponCode->setCoupon(null);
            }
        }

        return $this;
    }

    /**
     * @return int|null
     */
    public function getAvailable(): ?int
    {
        return $this->available;
    }

    /**
     * @param int $available
     * @return $this
     */
    public function setAvailable(int $available): static
    {
        $this->available = $available;

        return $this;
    }

    /**
     * @return Collection<int, StoreCouponUsage>
     */
    public function getStoreCouponUsages(): Collection
    {
        return $this->storeCouponUsages;
    }

    /**
     * @param StoreCouponUsage $storeCouponUsage
     * @return $this
     */
    public function addStoreCouponUsage(StoreCouponUsage $storeCouponUsage): static
    {
        if (!$this->storeCouponUsages->contains($storeCouponUsage)) {
            $this->storeCouponUsages->add($storeCouponUsage);
            $storeCouponUsage->setCoupon($this);
        }

        return $this;
    }

    /**
     * @param StoreCouponUsage $storeCouponUsage
     * @return $this
     */
    public function removeStoreCouponUsage(StoreCouponUsage $storeCouponUsage): static
    {
        if ($this->storeCouponUsages->removeElement($storeCouponUsage)) {
            // set the owning side to null (unless already changed)
            if ($storeCouponUsage->getCoupon() === $this) {
                $storeCouponUsage->setCoupon(null);
            }
        }

        return $this;
    }

}
