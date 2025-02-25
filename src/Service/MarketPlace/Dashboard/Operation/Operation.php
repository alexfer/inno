<?php declare(strict_types=1);

namespace Inno\Service\MarketPlace\Dashboard\Operation;

use Inno\Entity\MarketPlace\Enum\EnumOperation;
use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreOperation;
use Inno\Entity\MarketPlace\StoreProduct;
use Inno\Service\MarketPlace\Dashboard\Operation\Interface\OperationInterface;

class Operation extends FactoryHandler implements OperationInterface
{

    /**
     * @var array|string[]
     */
    private array $formats = ['xlsx', 'xml'];

    /**
     * @param string $class
     * @param array $options
     * @param Store $store
     * @return bool
     */
    public function export(string $class, array $options, Store $store): bool
    {
        $this->options = $options;
        $this->store = $store;
        return $this->product($class);
    }

    /**
     * @param string $class
     * @param array $options
     * @param Store $store
     * @return bool
     */
    public function import(string $class, array $options, Store $store): bool
    {
        $this->options = $options;
        return false;
    }

    /**
     * @param string $class
     * @return bool
     */
    private function product(string $class): bool
    {
        $revision = time();
        $format = $this->options['format'];
        $file = sprintf('%s/%d.%s', $this->storage(), $revision, $format);

        if (!in_array($format, $this->formats)) {
            throw new \RuntimeException(sprintf('Unknown operation format "%s"', $format));
        }

        $method = EnumOperation::from($format)->value;
        $this->$method($file, $class, $revision);

        return $this->filesystem->exists($file);
    }

    /**
     * @param string|null $format
     * @return string
     */
    public function storage(?string $format = null): string
    {
        $storage = $this->params->get('private_storage');
        $format = $format ?? $this->options['format'];

        if (!$this->filesystem->exists($format)) {
            $this->filesystem->mkdir($format);
        }

        return sprintf('%s/%s', $storage, $format);
    }

    /**
     * @param Store $store
     * @param bool $imports
     * @param int $offset
     * @param int $limit
     * @return object|array
     */
    public function fetch(Store $store, bool $imports = false, int $offset = 0, int $limit = 20): object|array
    {
        return $this->manager->getRepository(StoreOperation::class)
            ->fetch($store, $imports, $limit, $offset);
    }

    /**
     * @param Store $store
     * @param int $id
     * @return StoreOperation
     */
    public function find(Store $store, int $id): StoreOperation
    {
        return $this->manager->getRepository(StoreOperation::class)->findOneBy(['id' => $id, 'store' => $store]);
    }

    /**
     * @param string $file
     * @param StoreOperation $operation
     * @return void
     */
    public function prune(string $file, StoreOperation $operation): void
    {
        $this->filesystem->remove($file);
        $this->manager->remove($operation);
        $this->manager->flush();
    }

    /**
     * @return array
     */
    public function metadata(): array
    {
        $class = $this->manager->getClassMetadata(StoreProduct::class);

        $exclude = ['id', 'updated_at', 'deleted_at'];
        $include = ['price', 'supplier', 'brand', 'manufacturer', 'category', 'image', 'discount'];
        $fields = [];

        if (!empty($class->discriminatorColumn)) {
            $fields[] = $class->discriminatorColumn['name'];
        }

        $fields = array_merge($class->getColumnNames(), $fields);

        foreach ($fields as $index => $field) {
            if ($class->isInheritedField($field)) {
                unset($fields[$index]);
            }
        }
        $fields = array_diff($fields, $exclude);
        return array_merge($fields, $include);
    }

    public function compose(string $dir, StoreOperation $operation): void
    {

    }
}