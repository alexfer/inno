<?php declare(strict_types=1);

namespace Inno\Service\MarketPlace\Dashboard\Configuration;

use Inno\Entity\MarketPlace\StoreCarrier;
use Inno\Entity\MarketPlace\StorePaymentGateway;
use Inno\Entity\MarketPlace\StorePaymentGatewayStore;
use Inno\Service\MarketPlace\Dashboard\Configuration\Interface\ConfigurationServiceInterface;

class ConfigurationService extends HandleService implements ConfigurationServiceInterface
{

    /**
     * @var string
     */
    private string $target;

    /**
     * @param string|null $target
     * @return $this
     */
    public function take(?string $target = null): self
    {
        $this->target = $target;
        return $this;
    }

    /**
     * @param array|null $criteria
     * @param array|null $order
     * @return array
     */
    public function collection(?array $criteria = [], ?array $order = ['id' => 'DESC']): array
    {
        return [
            'carriers' => $this->manager->getRepository(StoreCarrier::class)->findBy($criteria, $order),
            'payment_gateways' => $this->manager->getRepository(StorePaymentGateway::class)->findBy($criteria, $order),
        ];
    }


    /**
     * @param int $id
     * @return bool
     */
    public function remove(int $id): bool
    {
        if ($this->target == 'carrier') {
            $target = $this->get($id, StoreCarrier::class);
            $this->removeAttach($target);
            $this->manager->remove($target);
        }

        if ($this->target == 'payment_gateway') {
            $target = $this->get($id, StorePaymentGateway::class);

            if ($this->manager->getRepository(StorePaymentGatewayStore::class)->findOneBy(['gateway' => $target])) {
                return false;
            }
            $this->removeAttach($target);
            $this->manager->remove($target);
        }

        $this->manager->flush();
        return true;
    }

    /**
     * @param array $data
     * @param array|null $media
     * @return StoreCarrier|StorePaymentGateway|null
     */
    public function create(array $data, ?array $media = null): null|StoreCarrier|StorePaymentGateway
    {
        if ($this->target == 'carrier') {
            return $this->createCarrier($data, $media);
        }

        if ($this->target == 'payment_gateway') {
            return $this->createPaymentGateway($data, $media);
        }
        return null;
    }

    /**
     * @param int $id
     * @param array $data
     * @param array|null $media
     * @return StoreCarrier|StorePaymentGateway|null
     */
    public function update(int $id, array $data, ?array $media = null): null|StoreCarrier|StorePaymentGateway
    {
        if ($this->target == 'carrier') {
            $carrier = $this->get($id, StoreCarrier::class);
            return $this->updateCarrier($data, $carrier, $media);
        }

        if ($this->target == 'payment_gateway') {
            $paymentGateway = $this->get($id, StorePaymentGateway::class);
            return $this->updatePaymentGateway($data, $paymentGateway, $media);
        }
        return null;
    }

    /**
     * @param int $id
     * @param string $class
     * @return array|null
     */
    public function find(int $id, string $class = StoreCarrier::class | StorePaymentGateway::class): ?array
    {
        return $this->manager->getRepository($class)->fetch($id);
    }
}