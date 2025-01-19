<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreCarrierStoreRepository;

#[ORM\Entity(repositoryClass: StoreCarrierStoreRepository::class)]
class StoreCarrierStore
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'storeCarrierStores')]
    private ?Store $store = null;

    #[ORM\ManyToOne(inversedBy: 'storeCarrierStores')]
    private ?StoreCarrier $carrier = null;

    #[ORM\Column(nullable: true)]
    private ?bool $active = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return StoreCarrier|null
     */
    public function getCarrier(): ?StoreCarrier
    {
        return $this->carrier;
    }

    /**
     * @param StoreCarrier|null $carrier
     * @return $this
     */
    public function setCarrier(?StoreCarrier $carrier): static
    {
        $this->carrier = $carrier;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isActive(): ?bool
    {
        return $this->active;
    }

    /**
     * @param bool|null $active
     * @return $this
     */
    public function setActive(?bool $active): static
    {
        $this->active = $active;

        return $this;
    }
}
