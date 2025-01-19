<?php declare(strict_types=1);

namespace Inno\Entity\MarketPlace;

use Doctrine\ORM\Mapping as ORM;
use Inno\Repository\MarketPlace\StoreCustomerOrdersRepository;

#[ORM\Entity(repositoryClass: StoreCustomerOrdersRepository::class)]
class StoreCustomerOrders
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'storeCustomerOrders')]
    private ?StoreCustomer $customer = null;

    #[ORM\ManyToOne(inversedBy: 'storeCustomerOrders')]
    private ?StoreOrders $orders = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return StoreOrders|null
     */
    public function getOrders(): ?StoreOrders
    {
        return $this->orders;
    }

    /**
     * @param StoreOrders|null $orders
     * @return $this
     */
    public function setOrders(?StoreOrders $orders): static
    {
        $this->orders = $orders;

        return $this;
    }
}
