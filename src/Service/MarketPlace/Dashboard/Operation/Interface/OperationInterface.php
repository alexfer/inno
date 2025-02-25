<?php declare(strict_types=1);

namespace Inno\Service\MarketPlace\Dashboard\Operation\Interface;

use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreOperation;

interface OperationInterface
{
    /**
     * @param string $class
     * @param array $options
     * @param Store $store
     * @return bool
     */
    public function export(string $class, array $options, Store $store): bool;

    /**
     * @param string $class
     * @param array $options
     * @param Store $store
     * @return bool
     */
    public function import(string $class, array $options, Store $store): bool;

    /**
     * @param Store $store
     * @param bool $imports
     * @param int $offset
     * @param int $limit
     * @return object|array
     */
    public function fetch(Store $store, bool $imports = false, int $offset = 0, int $limit = 25): object|array;

    /**
     * @param Store $store
     * @param int $id
     * @return StoreOperation
     */
    public function find(Store $store, int $id): StoreOperation;

    /**
     * @param string|null $format
     * @return string
     */
    public function storage(?string $format = null): string;

    /**
     * @return array
     */
    public function metadata(): array;

    /**
     * @param string $file
     * @param StoreOperation $operation
     * @return void
     */
    public function prune(string $file, StoreOperation $operation): void;

    /**
     * @param string $dir
     * @param StoreOperation $operation
     * @return void
     */
    public function compose(string $dir, StoreOperation $operation): void;
}