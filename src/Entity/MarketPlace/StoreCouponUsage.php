<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreCouponUsageRepository;

#[ORM\Entity(repositoryClass: StoreCouponUsageRepository::class)]
class StoreCouponUsage
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $relation = null;

    #[ORM\ManyToOne(inversedBy: 'storeCouponUsages')]
    private ?StoreCoupon $coupon = null;

    #[ORM\ManyToOne(inversedBy: 'storeCouponUsages')]
    private ?StoreCustomer $customer = null;

    #[ORM\ManyToOne(inversedBy: 'storeCouponUsages')]
    private ?StoreCouponCode $coupon_code = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $used_at;


    public function __construct()
    {
        $this->used_at = new \DateTimeImmutable();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return StoreCoupon|null
     */
    public function getCoupon(): ?StoreCoupon
    {
        return $this->coupon;
    }

    /**
     * @param StoreCoupon|null $coupon
     * @return $this
     */
    public function setCoupon(?StoreCoupon $coupon): static
    {
        $this->coupon = $coupon;

        return $this;
    }

    /**
     * @return StoreCustomer|null
     */
    public function getCustomer(): ?StoreCustomer
    {
        return $this->customer;
    }

    /**
     * @param StoreCustomer|null $customer
     * @return $this
     */
    public function setCustomer(?StoreCustomer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getUsedAt(): ?\DateTimeImmutable
    {
        return $this->used_at;
    }

    /**
     * @param \DateTimeImmutable $used_at
     * @return $this
     */
    public function setUsedAt(\DateTimeImmutable $used_at): static
    {
        $this->used_at = $used_at;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getRelation(): ?int
    {
        return $this->relation;
    }

    /**
     * @param int $relation
     * @return $this
     */
    public function setRelation(int $relation): static
    {
        $this->relation = $relation;

        return $this;
    }

    /**
     * @return StoreCouponCode|null
     */
    public function getCouponCode(): ?StoreCouponCode
    {
        return $this->coupon_code;
    }

    /**
     * @param StoreCouponCode|null $coupon_code
     * @return $this
     */
    public function setCouponCode(?StoreCouponCode $coupon_code): static
    {
        $this->coupon_code = $coupon_code;

        return $this;
    }
}
