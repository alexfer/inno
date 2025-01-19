<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Inno\Entity\User;
use Inno\Repository\MarketPlace\StoreCustomerRepository;

#[ORM\Entity(repositoryClass: StoreCustomerRepository::class)]
#[ORM\Index(name: 'social_id_idx', columns: ['social_id'])]
class StoreCustomer
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $first_name = null;

    #[ORM\Column(length: 255)]
    private ?string $last_name = null;

    #[ORM\Column(length: 100)]
    private ?string $phone = null;

    #[ORM\Column(length: 5)]
    private ?string $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?User $member = null;

    #[ORM\OneToMany(targetEntity: StoreCustomerOrders::class, mappedBy: 'customer')]
    private Collection $storeCustomerOrders;

    #[ORM\OneToOne(mappedBy: 'customer', cascade: ['persist', 'remove'])]
    private ?StoreAddress $storeAddress = null;

    #[ORM\OneToMany(targetEntity: StoreWishlist::class, mappedBy: 'customer')]
    private Collection $storeWishlists;

    /**
     * @var Collection<int, StoreCouponUsage>
     */
    #[ORM\OneToMany(targetEntity: StoreCouponUsage::class, mappedBy: 'customer')]
    private Collection $storeCouponUsages;

    /**
     * @var Collection<int, StoreMessage>
     */
    #[ORM\OneToMany(targetEntity: StoreMessage::class, mappedBy: 'customer')]
    private Collection $storeMessages;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $social_id = null;

    #[ORM\Column]
    private ?DateTimeImmutable $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updated_at = null;

    public function __construct()
    {
        $this->created_at = new DateTimeImmutable();
        $this->storeCustomerOrders = new ArrayCollection();
        $this->storeWishlists = new ArrayCollection();
        $this->storeCouponUsages = new ArrayCollection();
        $this->storeMessages = new ArrayCollection();
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
    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    /**
     * @param string $first_name
     * @return $this
     */
    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    /**
     * @param string $last_name
     * @return $this
     */
    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     * @return $this
     */
    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getMember(): ?User
    {
        return $this->member;
    }

    /**
     * @param User|null $member
     * @return $this
     */
    public function setMember(?User $member): static
    {
        $this->member = $member;

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
    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updated_at;
    }

    /**
     * @param DateTimeInterface|null $updated_at
     * @return $this
     */
    public function setUpdatedAt(?DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * @return Collection<int, StoreCustomerOrders>
     */
    public function getStoreCustomerOrders(): Collection
    {
        return $this->storeCustomerOrders;
    }

    /**
     * @param StoreCustomerOrders $storeCustomerOrder
     * @return $this
     */
    public function addStoreCustomerOrder(StoreCustomerOrders $storeCustomerOrder): static
    {
        if (!$this->storeCustomerOrders->contains($storeCustomerOrder)) {
            $this->storeCustomerOrders->add($storeCustomerOrder);
            $storeCustomerOrder->setCustomer($this);
        }

        return $this;
    }

    /**
     * @param StoreCustomerOrders $storeCustomerOrder
     * @return $this
     */
    public function removeStoreCustomerOrders(StoreCustomerOrders $storeCustomerOrder): static
    {
        if ($this->storeCustomerOrders->removeElement($storeCustomerOrder)) {
            // set the owning side to null (unless already changed)
            if ($storeCustomerOrder->getCustomer() === $this) {
                $storeCustomerOrder->setCustomer(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return $this
     */
    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     * @return $this
     */
    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return StoreAddress|null
     */
    public function getStoreAddress(): ?StoreAddress
    {
        return $this->storeAddress;
    }

    /**
     * @param StoreAddress|null $storeAddress
     * @return $this
     */
    public function setStoreAddress(?StoreAddress $storeAddress): static
    {
        // unset the owning side of the relation if necessary
        if ($storeAddress === null && $this->storeAddress !== null) {
            $this->storeAddress->setCustomer(null);
        }

        // set the owning side of the relation if necessary
        if ($storeAddress !== null && $storeAddress->getCustomer() !== $this) {
            $storeAddress->setCustomer($this);
        }

        $this->storeAddress = $storeAddress;

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
            $storeWishlist->setCustomer($this);
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
            if ($storeWishlist->getCustomer() === $this) {
                $storeWishlist->setCustomer(null);
            }
        }

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
            $storeCouponUsage->setCustomer($this);
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
            if ($storeCouponUsage->getCustomer() === $this) {
                $storeCouponUsage->setCustomer(null);
            }
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
            $storeMessage->setCustomer($this);
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
            if ($storeMessage->getCustomer() === $this) {
                $storeMessage->setCustomer(null);
            }
        }

        return $this;
    }

    public function getSocialId(): ?string
    {
        return $this->social_id;
    }

    public function setSocialId(?string $social_id): static
    {
        $this->social_id = $social_id;

        return $this;
    }
}
