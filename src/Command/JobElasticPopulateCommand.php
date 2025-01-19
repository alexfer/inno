<?php declare(strict_types=1);

namespace Inno\Command;

use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Inno\Command\Elastic\ElasticServiceInterface;
use Inno\Entity\MarketPlace\StoreProduct;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:populate:indices:job',
    description: 'Populate indices into elasticsearch using cron',
    aliases: ['app:elasticsearch:indexing:job'],
    hidden: false,
)]
class JobElasticPopulateCommand extends Command
{
    /**
     * @var array
     */
    private array $options;


    /**
     * @param EntityManagerInterface $manager
     * @param ElasticServiceInterface $elastic
     * @param ParameterBagInterface $params
     */
    public function __construct(
        private readonly EntityManagerInterface  $manager,
        private readonly ElasticServiceInterface $elastic,
        ParameterBagInterface                    $params
    )
    {
        $this->options['dsn'] = $params->get('app.elastic.dsn');
        $this->options['index'] = $params->get('app.elastic.index');
        parent::__construct();
    }

    /**
     * @return Client
     * @throws AuthenticationException
     */
    private function client(): Client
    {
        return ClientBuilder::create()->setHosts([$this->options['dsn']])->build();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws AuthenticationException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $client = $this->client();
        $index = $this->options['index'];

        $this->elastic->reset($client, $index);
        $io->writeln(strtoupper('deleted'));
        $result = $this->elastic->create($client, $index);
        $io->writeln($result);

        $queryBuilder = $this->manager
            ->getRepository(StoreProduct::class)
            ->createQueryBuilder('p');

        $this->elastic->build($queryBuilder, $io, $client, $index);

        return Command::SUCCESS;

    }
}