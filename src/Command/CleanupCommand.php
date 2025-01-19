<?php declare(strict_types=1);

namespace Inno\Command;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\MarketPlace\StoreOperation;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:cleanup',
    description: 'Clean old files(.xml, .csv) from storage',
)]
class CleanupCommand extends Command
{
    const int SCHEDULED = 7 * 24 * 3600;

    /**
     * @param EntityManagerInterface $manager
     * @param Filesystem $filesystem
     * @param ParameterBagInterface $params
     */
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly Filesystem             $filesystem,
        private readonly ParameterBagInterface  $params,
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
        $io = new SymfonyStyle($input, $output);

        $operations = $this->manager
            ->getRepository(StoreOperation::class)
            ->createQueryBuilder('so')
            ->select(['so.id', 'so.format', 'so.revision'])
            ->where('so.revision >= :schedule')
            ->setParameter('schedule', self::SCHEDULED)
            ->getQuery()->getArrayResult();

        foreach ($operations as $operation) {
            $this->remove($operation['format']->value, $operation['revision']);
        }

        $io->success('An old files cleaned successfully.');

        return Command::SUCCESS;
    }

    /**
     * @param string $format
     * @param string $file
     * @return void
     */
    private function remove(string $format, string $file): void
    {
        $storage = $this->params->get('private_storage');
        $path = sprintf('%s/%s/%s.%s', $storage, $format, $file, $format);
        $this->filesystem->remove($path);
    }
}
