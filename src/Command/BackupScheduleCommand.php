<?php declare(strict_types=1);

namespace Inno\Command;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\Store;
use Inno\Entity\MarketPlace\StoreProduct;
use Inno\Service\MarketPlace\Dashboard\Operation\Interface\OperationInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:backup_schedule', description: 'Store products in XML format')]
class BackupScheduleCommand extends Command
{
    private array $options = [
        "name" => "name",
        "description" => "description",
        "slug" => "slug",
        "quantity" => "quantity",
        "cost" => "cost",
        "short_name" => "short_name",
        "pckg_discount" => "pckg_discount",
        "sku" => "sku",
        "pckg_quantity" => "pckg_quantity",
        "fee" => "fee",
        "created_at" => "created_at",
        "price" => "price",
        "supplier" => "supplier",
        "brand" => "brand",
        "manufacturer" => "manufacturer",
        "category" => "category",
        "image" => "image",
        "discount" => "discount",
    ];

    /**
     * @param EntityManagerInterface $manager
     * @param OperationInterface $operation
     */
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly OperationInterface     $operation
    )
    {
        parent::__construct();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stores = $this->manager->getRepository(Store::class)->findAll();

        foreach ($stores as $store) {
            if ($store->getStoreOptions()->getBackupSchedule() === 1) {
                $this->operation->export(StoreProduct::class, [
                    'format' => 'xml',
                    'option' => $this->options,
                ], $store);
            }
        }

        return Command::SUCCESS;
    }
}
