<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreCouponCodeRepository;

#[ORM\Entity(repositoryClass: StoreCouponCodeRepository::class)]
class StoreCouponCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'storeCouponCodes')]
    private ?StoreCoupon $coupon = null;

    #[ORM\Column(length: 50)]
    private ?string $code = null;

    /**
     * @var Collection<int, StoreCouponUsage>
     */
    #[ORM\OneToMany(targetEntity: StoreCouponUsage::class, mappedBy: 'coupon_code')]
    private Collection $storeCouponUsages;

    public function __construct()
    {
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
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return Collection<int, StoreCouponUsage>
     */
    public function getStoreCouponUsages(): Collection
    {
        return $this->storeCouponUsages;
    }

    public function addStoreCouponUsage(StoreCouponUsage $storeCouponUsage): static
    {
        if (!$this->storeCouponUsages->contains($storeCouponUsage)) {
            $this->storeCouponUsages->add($storeCouponUsage);
            $storeCouponUsage->setCouponCode($this);
        }

        return $this;
    }

    public function removeStoreCouponUsage(StoreCouponUsage $storeCouponUsage): static
    {
        if ($this->storeCouponUsages->removeElement($storeCouponUsage)) {
            // set the owning side to null (unless already changed)
            if ($storeCouponUsage->getCouponCode() === $this) {
                $storeCouponUsage->setCouponCode(null);
            }
        }

        return $this;
    }
}