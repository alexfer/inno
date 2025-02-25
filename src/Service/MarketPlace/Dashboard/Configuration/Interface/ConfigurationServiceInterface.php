<?php declare(strict_types=1);

namespace Inno\Service\MarketPlace\Dashboard\Configuration\Interface;

use Inno\Entity\MarketPlace\StoreCarrier;
use Inno\Entity\MarketPlace\StorePaymentGateway;

interface ConfigurationServiceInterface
{
    /**
     * @param string|null $target
     * @return self
     */
    public function take(?string $target = null): self;

    /**
     * @param int $id
     * @param string $class
     * @return array|null
     */
    public function find(int $id, string $class = StoreCarrier::class | StorePaymentGateway::class): ?array;

    /**
     * @param array|null $criteria
     * @param array|null $order
     * @return array
     */
    public function collection(?array $criteria = [], ?array $order = []): array;

    /**
     * @param int $id
     * @return bool
     */
    public function remove(int $id): bool;

    /**
     * @param array $data
     * @param array|null $media
     * @return StoreCarrier|StorePaymentGateway|null
     */
    public function create(array $data, ?array $media = null): null|StoreCarrier|StorePaymentGateway;

    /**
     * @param int $id
     * @param array $data
     * @param array|null $media
     * @return StoreCarrier|StorePaymentGateway|null
     */
    public function update(int $id, array $data, ?array $media = null): null|StoreCarrier|StorePaymentGateway;
}