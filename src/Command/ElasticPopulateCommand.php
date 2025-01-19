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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:populate:indices',
    description: 'Populate indices into elasticsearch',
    aliases: ['app:elasticsearch:indexing'],
    hidden: false,
)]
class ElasticPopulateCommand extends Command
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
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('reset', null, InputOption::VALUE_NONE, 'Delete an index')
            ->addOption('create', null, InputOption::VALUE_NONE, 'Create a new index')
            ->addOption('populate', null, InputOption::VALUE_NONE, 'Populate indices');
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

        $helper = $this->getHelper('question');
        $reset = $input->getOption('reset');
        $create = $input->getOption('create');
        $populate = $input->getOption('populate');

        if ($reset) {
            $question = new ConfirmationQuestion('Are you sure?(y|n)', false,
                '/^(y|j)/i');
            if ($helper->ask($input, $output, $question) == 'y') {

                $this->elastic->reset($client, $index);
                $io->success(strtoupper('deleted'));
                return Command::SUCCESS;
            }
        }

        if ($create) {
            $question = new ConfirmationQuestion('Are you sure?(y|n)', false,
                '/^(y|j)/i');
            if ($helper->ask($input, $output, $question) == 'y') {
                $result = $this->elastic->create($client, $index);
                $io->success($result);
                return Command::SUCCESS;
            }
        }

        if ($populate) {

            $queryBuilder = $this->manager
                ->getRepository(StoreProduct::class)
                ->createQueryBuilder('p');

            $this->elastic->build($queryBuilder, $io, $client, $index);

            return Command::SUCCESS;
        }
        $io->error('Invalid command. Try --help');
        return Command::INVALID;
    }
}