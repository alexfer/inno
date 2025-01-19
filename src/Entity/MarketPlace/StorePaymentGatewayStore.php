<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StorePaymentGatewayStoreRepository;

#[ORM\Entity(repositoryClass: StorePaymentGatewayStoreRepository::class)]
class StorePaymentGatewayStore
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'storePaymentGatewayStores')]
    private ?StorePaymentGateway $gateway = null;

    #[ORM\ManyToOne(inversedBy: 'storePaymentGatewayStores')]
    private ?Store $store = null;

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
     * @return StorePaymentGateway|null
     */
    public function getGateway(): ?StorePaymentGateway
    {
        return $this->gateway;
    }

    /**
     * @param StorePaymentGateway|null $gateway
     * @return $this
     */
    public function setGateway(?StorePaymentGateway $gateway): static
    {
        $this->gateway = $gateway;

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
