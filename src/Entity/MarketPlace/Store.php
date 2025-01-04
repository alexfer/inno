<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Inno\Entity\Attach;
use Inno\Entity\User;
use Inno\Repository\MarketPlace\StoreRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: StoreRepository::class)]
#[UniqueEntity(fields: ['slug'], message: 'slug.unique')]
class Store
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stores')]
    private ?User $owner = null;

    #[ORM\Column(length: 512)]
    private ?string $name = null;

    #[ORM\Column(length: 3)]
    private ?string $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 512, unique: true, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $currency = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $tax = null;

    #[ORM\OneToMany(targetEntity: StoreProduct::class, mappedBy: 'store')]
    private Collection $products;

    #[ORM\OneToMany(targetEntity: StoreBrand::class, mappedBy: 'store')]
    #[ORM\OrderBy(['name' => 'asc'])]
    private Collection $storeBrands;

    #[ORM\OneToMany(targetEntity: StoreSupplier::class, mappedBy: 'store')]
    #[ORM\OrderBy(['name' => 'asc'])]
    private Collection $storeSuppliers;

    #[ORM\OneToMany(targetEntity: StoreManufacturer::class, mappedBy: 'store')]
    #[ORM\OrderBy(['name' => 'asc'])]
    private Collection $storeManufacturers;

    #[ORM\OneToOne(inversedBy: 'store', cascade: ['persist', 'remove'])]
    private ?Attach $attach = null;

    #[ORM\OneToMany(targetEntity: StoreOrders::class, mappedBy: 'store')]
    private Collection $storeOrders;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $messages = [];

    #[ORM\OneToMany(targetEntity: StorePaymentGatewayStore::class, mappedBy: 'store', cascade: ['persist', 'remove'])]
    private Collection $storePaymentGatewayStores;

    #[ORM\OneToMany(targetEntity: StoreWishlist::class, mappedBy: 'store')]
    private Collection $storeWishlists;

    /**
     * @var Collection<int, StoreCoupon>
     */
    #[ORM\OneToMany(targetEntity: StoreCoupon::class, mappedBy: 'store')]
    private Collection $storeCoupons;

    /**
     * @var Collection<int, StoreMessage>
     */
    #[ORM\OneToMany(targetEntity: StoreMessage::class, mappedBy: 'store')]
    private Collection $storeMessages;

    /**
     * @var Collection<int, StoreSocial>
     */
    #[ORM\OneToMany(targetEntity: StoreSocial::class, mappedBy: 'store', cascade: ['persist', 'remove'])]
    private Collection $storeSocials;

    /**
     * @var Collection<int, StoreOperation>
     */
    #[ORM\OneToMany(targetEntity: StoreOperation::class, mappedBy: 'store')]
    private Collection $storeOperations;

    #[ORM\OneToOne(mappedBy: 'store', cascade: ['remove'])]
    private ?StoreOptions $storeOptions = null;

    /**
     * @var Collection<int, StoreCarrierStore>
     */
    #[ORM\OneToMany(targetEntity: StoreCarrierStore::class, mappedBy: 'store', cascade: ['persist', 'remove'])]
    private Collection $storeCarrierStores;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cc = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTime $created_at;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deleted_at = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $locked_to = null;

    public function __construct()
    {
        $this->created_at = new DateTime();
        $this->products = new ArrayCollection();
        $this->storeBrands = new ArrayCollection();
        $this->storeSuppliers = new ArrayCollection();
        $this->storeManufacturers = new ArrayCollection();
        $this->storeOrders = new ArrayCollection();
        $this->messages = ["email"];
        $this->storePaymentGatewayStores = new ArrayCollection();
        $this->storeWishlists = new ArrayCollection();
        $this->storeCoupons = new ArrayCollection();
        $this->storeMessages = new ArrayCollection();
        $this->storeSocials = new ArrayCollection();
        $this->storeOperations = new ArrayCollection();
        $this->storeCarrierStores = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isPrivate(): bool
    {
        return true;
    }

    /**
     * @return User|null
     */
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    /**
     * @param User|null $owner
     * @return $this
     */
    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

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
     * @param string|null $name
     * @return $this
     */
    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @param null|string $address
     * @return $this
     */
    public function setAddress(?string $address): static
    {
        $this->address = $address;

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
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     * @return $this
     */
    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    /**
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
     * @return Collection<int, StoreProduct>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    /**
     * @param StoreProduct $product
     * @return $this
     */
    public function addProduct(StoreProduct $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setStore($this);
        }

        return $this;
    }

    /**
     * @param StoreProduct $product
     * @return $this
     */
    public function removeProduct(StoreProduct $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getStore() === $this) {
                $product->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return DateTime|null
     */

    public function getCreatedAt(): ?DateTime
    {
        return $this->created_at;
    }

    /**
     * @param DateTime $created_at
     * @return $this
     */
    public function setCreatedAt(DateTime $created_at): static
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
     * @return Collection<int, StoreBrand>
     */
    public function getStoreBrands(): Collection
    {
        return $this->storeBrands;
    }

    /**
     * @param StoreBrand $storeBrand
     * @return $this
     */
    public function addStoreBrand(StoreBrand $storeBrand): static
    {
        if (!$this->storeBrands->contains($storeBrand)) {
            $this->storeBrands->add($storeBrand);
            $storeBrand->setStore($this);
        }

        return $this;
    }

    /**
     * @param StoreBrand $storeBrand
     * @return $this
     */
    public function removeStoreBrand(StoreBrand $storeBrand): static
    {
        if ($this->storeBrands->removeElement($storeBrand)) {
            // set the owning side to null (unless already changed)
            if ($storeBrand->getStore() === $this) {
                $storeBrand->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StoreSupplier>
     */
    public function getStoreSuppliers(): Collection
    {
        return $this->storeSuppliers;
    }

    /**
     * @param StoreSupplier $storeSupplier
     * @return $this
     */
    public function addStoreSupplier(StoreSupplier $storeSupplier): static
    {
        if (!$this->storeSuppliers->contains($storeSupplier)) {
            $this->storeSuppliers->add($storeSupplier);
            $storeSupplier->setStore($this);
        }

        return $this;
    }

    /**
     * @param StoreSupplier $storeSupplier
     * @return $this
     */
    public function removeStoreSupplier(StoreSupplier $storeSupplier): static
    {
        if ($this->storeSuppliers->removeElement($storeSupplier)) {
            // set the owning side to null (unless already changed)
            if ($storeSupplier->getStore() === $this) {
                $storeSupplier->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StoreManufacturer>
     */
    public function getStoreManufacturers(): Collection
    {
        return $this->storeManufacturers;
    }

    /**
     * @param StoreManufacturer $storeManufacturer
     * @return $this
     */
    public function addStoreManufacturer(StoreManufacturer $storeManufacturer): static
    {
        if (!$this->storeManufacturers->contains($storeManufacturer)) {
            $this->storeManufacturers->add($storeManufacturer);
            $storeManufacturer->setStore($this);
        }

        return $this;
    }

    /**
     * @param StoreManufacturer $storeManufacturer
     * @return $this
     */
    public function removeStoreManufacturer(StoreManufacturer $storeManufacturer): static
    {
        if ($this->storeManufacturers->removeElement($storeManufacturer)) {
            // set the owning side to null (unless already changed)
            if ($storeManufacturer->getStore() === $this) {
                $storeManufacturer->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return Attach|null
     */
    public function getAttach(): ?Attach
    {
        return $this->attach;
    }

    /**
     * @param Attach|null $attach
     * @return $this
     */
    public function setAttach(?Attach $attach): static
    {
        $this->attach = $attach;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string|null $currency
     * @return $this
     */
    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return Collection<int, StoreOrders>
     */
    public function getStoreOrders(): Collection
    {
        return $this->storeOrders;
    }

    /**
     * @param StoreOrders $storeOrder
     * @return $this
     */
    public function addStoreOrder(StoreOrders $storeOrder): static
    {
        if (!$this->storeOrders->contains($storeOrder)) {
            $this->storeOrders->add($storeOrder);
            $storeOrder->setStore($this);
        }

        return $this;
    }

    /**
     * @param StoreOrders $storeOrder
     * @return $this
     */
    public function removeStoreOrder(StoreOrders $storeOrder): static
    {
        if ($this->storeOrders->removeElement($storeOrder)) {
            // set the owning side to null (unless already changed)
            if ($storeOrder->getStore() === $this) {
                $storeOrder->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param array $messages
     * @return $this
     */
    public function setMessages(array $messages): static
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * @param string|null $website
     * @return $this
     */
    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     * @return $this
     */
    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return Collection<int, StorePaymentGatewayStore>
     */
    public function getStorePaymentGatewayStores(): Collection
    {
        return $this->storePaymentGatewayStores;
    }

    /**
     * @param StorePaymentGatewayStore $storePaymentGatewayStore
     * @return $this
     */
    public function addStorePaymentGatewayStore(StorePaymentGatewayStore $storePaymentGatewayStore): static
    {
        if (!$this->storePaymentGatewayStores->contains($storePaymentGatewayStore)) {
            $this->storePaymentGatewayStores->add($storePaymentGatewayStore);
            $storePaymentGatewayStore->setStore($this);
        }

        return $this;
    }

    /**
     * @param StorePaymentGatewayStore $storePaymentGatewayStore
     * @return $this
     */
    public function removeStorePaymentGatewayStore(StorePaymentGatewayStore $storePaymentGatewayStore): static
    {
        if ($this->storePaymentGatewayStores->removeElement($storePaymentGatewayStore)) {
            // set the owning side to null (unless already changed)
            if ($storePaymentGatewayStore->getStore() === $this) {
                $storePaymentGatewayStore->setStore(null);
            }
        }

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
            $storeWishlist->setStore($this);
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
            if ($storeWishlist->getStore() === $this) {
                $storeWishlist->setStore(null);
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

    public function addStoreCoupon(StoreCoupon $storeCoupon): static
    {
        if (!$this->storeCoupons->contains($storeCoupon)) {
            $this->storeCoupons->add($storeCoupon);
            $storeCoupon->setStore($this);
        }

        return $this;
    }

    public function removeStoreCoupon(StoreCoupon $storeCoupon): static
    {
        if ($this->storeCoupons->removeElement($storeCoupon)) {
            // set the owning side to null (unless already changed)
            if ($storeCoupon->getStore() === $this) {
                $storeCoupon->setStore(null);
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
    public function addStoreMessage(StoreMessage $storeMessages): static
    {
        if (!$this->storeMessages->contains($storeMessages)) {
            $this->storeMessages->add($storeMessages);
            $storeMessages->setStore($this);
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
            if ($storeMessage->getStore() === $this) {
                $storeMessage->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StoreSocial>
     */
    public function getStoreSocials(): Collection
    {
        return $this->storeSocials;
    }

    /**
     * @param StoreSocial $storeSocial
     * @return $this
     */
    public function addStoreSocial(StoreSocial $storeSocial): static
    {
        if (!$this->storeSocials->contains($storeSocial)) {
            $this->storeSocials->add($storeSocial);
            $storeSocial->setStore($this);
        }

        return $this;
    }

    /**
     * @param StoreSocial $storeSocial
     * @return $this
     */
    public function removeStoreSocial(StoreSocial $storeSocial): static
    {
        if ($this->storeSocials->removeElement($storeSocial)) {
            // set the owning side to null (unless already changed)
            if ($storeSocial->getStore() === $this) {
                $storeSocial->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTax(): ?string
    {
        return $this->tax;
    }

    /**
     * @param string|null $tax
     * @return $this
     */
    public function setTax(?string $tax): static
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getCc(): ?string
    {
        return $this->cc;
    }

    /**
     * @param string|null $cc
     * @return $this
     */
    public function setCc(?string $cc): static
    {
        $this->cc = $cc;

        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getLockedTo(): ?\DateTimeInterface
    {
        return $this->locked_to;
    }

    /**
     * @param DateTimeInterface|null $locked_to
     * @return $this
     */
    public function setLockedTo(?\DateTimeInterface $locked_to): static
    {
        $this->locked_to = $locked_to;

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
     * @param string|null $country
     * @return $this
     */
    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Collection<int, StoreOperation>
     */
    public function getStoreOperations(): Collection
    {
        return $this->storeOperations;
    }

    public function addStoreOperation(StoreOperation $storeOperation): static
    {
        if (!$this->storeOperations->contains($storeOperation)) {
            $this->storeOperations->add($storeOperation);
            $storeOperation->setStore($this);
        }

        return $this;
    }

    public function removeStoreOperation(StoreOperation $storeOperation): static
    {
        if ($this->storeOperations->removeElement($storeOperation)) {
            // set the owning side to null (unless already changed)
            if ($storeOperation->getStore() === $this) {
                $storeOperation->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return StoreOptions|null
     */
    public function getStoreOptions(): ?StoreOptions
    {
        return $this->storeOptions;
    }

    /**
     * @param StoreOptions|null $storeOptions
     * @return $this
     */
    public function setStoreOptions(?StoreOptions $storeOptions): static
    {
        // unset the owning side of the relation if necessary
        if ($storeOptions === null && $this->storeOptions !== null) {
            $this->storeOptions->setStore(null);
        }

        // set the owning side of the relation if necessary
        if ($storeOptions !== null && $storeOptions->getStore() !== $this) {
            $storeOptions->setStore($this);
        }

        $this->storeOptions = $storeOptions;

        return $this;
    }

    /**
     * @return Collection<int, StoreCarrierStore>
     */
    public function getStoreCarrierStores(): Collection
    {
        return $this->storeCarrierStores;
    }

    /**
     * @param StoreCarrierStore $storeCarrierStore
     * @return $this
     */
    public function addStoreCarrierStore(StoreCarrierStore $storeCarrierStore): static
    {
        if (!$this->storeCarrierStores->contains($storeCarrierStore)) {
            $this->storeCarrierStores->add($storeCarrierStore);
            $storeCarrierStore->setStore($this);
        }

        return $this;
    }

    /**
     * @param StoreCarrierStore $storeCarrierStore
     * @return $this
     */
    public function removeStoreCarrierStore(StoreCarrierStore $storeCarrierStore): static
    {
        if ($this->storeCarrierStores->removeElement($storeCarrierStore)) {
            // set the owning side to null (unless already changed)
            if ($storeCarrierStore->getStore() === $this) {
                $storeCarrierStore->setStore(null);
            }
        }

        return $this;
    }
}
