<?php declare(strict_types=1);

namespace Inno\Service\MarketPlace\Dashboard\Operation;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\Enum\EnumOperation;
use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreOperation;
use Inno\Service\MarketPlace\Dashboard\Operation\FactoryHandler\XlsxFactory;
use Inno\Service\MarketPlace\Dashboard\Operation\FactoryHandler\XmlFactory;
use Inno\Service\MarketPlace\Dashboard\Operation\Interface\HandleFactoryInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class FactoryHandler implements HandleFactoryInterface
{
    protected array $options;

    protected Store $store;

    /**
     * @param EntityManagerInterface $manager
     * @param Filesystem $filesystem
     * @param ParameterBagInterface $params
     * @param CacheManager $cacheManager
     */
    public function __construct(
        protected readonly EntityManagerInterface $manager,
        protected readonly Filesystem             $filesystem,
        protected readonly ParameterBagInterface  $params,
        protected readonly CacheManager           $cacheManager,
    )
    {

    }

    /**
     * @param string $file
     * @param string $class
     * @param int $revision
     * @return void
     */
    protected function xml(string $file, string $class, int $revision): void
    {
        $collection = $this->instance($class);
        $option = $this->options['option'];

        $xml = new XmlFactory('products');
        $xml->cacheManager = $this->cacheManager;
        $xml->params = $this->params;

        $xml = $xml->build($collection, $option);

        $this->store($revision, $this->options['format'], $file, $xml);
    }

    /**
     * @param string $file
     * @param string $class
     * @param int $revision
     * @return void
     */
    protected function xlsx(string $file, string $class, int $revision): void
    {
        $collection = $this->instance($class);
        $option = $this->options['option'];

        $xlsx = new XlsxFactory();
        $xlsx->cacheManager = $this->cacheManager;
        $xlsx->params = $this->params;
        $xlsx->build($revision, $collection, $option);
        $this->save($revision, $this->options['format']);
    }


    /**
     * @param int $revision
     * @param string $format
     * @return void
     */
    private function save(int $revision, string $format): void
    {
        $operation = new StoreOperation();
        $operation->setFormat(EnumOperation::from($format))
            ->setRevision((string)$revision)
            ->setStore($this->store);
        $this->manager->persist($operation);
        $this->manager->flush();
    }

    /**
     * @param int $revision
     * @param string $format
     * @param string $file
     * @param string $data
     * @return void
     */
    private function store(int $revision, string $format, string $file, string $data): void
    {
        $this->save($revision, $format);
        $this->filesystem->dumpFile($file, $data);

    }

    /**
     * @param string $class
     * @return array|object
     */
    private function instance(string $class): array|object
    {
        return $this->manager->getRepository($class)->findBy(['store' => $this->store]);
    }
}