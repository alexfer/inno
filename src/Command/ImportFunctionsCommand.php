<?php declare(strict_types=1);

namespace Inno\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;

#[AsCommand('app:functions:import', 'Import functions into database. To import single function you may pass file name as argument.')]
class ImportFunctionsCommand extends Command
{
    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @var string|mixed
     */
    private string $root;

    /**
     * @param EntityManagerInterface $em
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(
        EntityManagerInterface $em,
        ParameterBagInterface  $parameterBag,
    )
    {
        $params = $parameterBag->all();
        $this->root = $params['kernel.project_dir'];
        $this->connection = $em->getConnection();

        parent::__construct();

    }

    protected function configure(): void
    {
        $this->addArgument('file-name', InputArgument::OPTIONAL, 'Import single file');
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(
        InputInterface  $input,
        OutputInterface $output,
    ): int
    {
        $io = new SymfonyStyle($input, $output);
        $directory = $this->root . '/database/functions';

        $fileName = $input->getArgument('file-name');

        if ($fileName) {
            $file = $directory . '/' . $fileName;
            if (file_exists($file)) {
                try {
                    $this->connection->beginTransaction();
                    $this->connection->prepare(file_get_contents($file))->executeStatement();
                    $this->connection->commit();
                } catch (Exception $e) {
                    $this->connection->rollBack();
                    $io->error('Error: ' . $e->getMessage() . sprintf('. Import function "%s" failure.', $fileName));
                    return Command::FAILURE;
                }
                $io->success(sprintf('Import function "%s" success.', $fileName));
                return Command::SUCCESS;
            } else {
                $io->error(sprintf('File "%s" not found.', $file));
                return Command::FAILURE;
            }
        }


        $finder = new Finder();
        $files = $finder->files()->in($directory);

        foreach ($files as $file) {
            try {
                $this->connection->beginTransaction();
                $this->connection->prepare($file->getContents())->executeStatement();
                $this->connection->commit();
                $io->text('Importing ' . $file->getFilename());
            } catch (Exception $e) {
                $io->error('Error: ' . $e->getMessage() . '. File: ' . $file->getFilename());
                $this->connection->rollback();
            }

        }

        $io->success(sprintf('Imported functions into database %s.', count($files)));
        return Command::SUCCESS;
    }
}