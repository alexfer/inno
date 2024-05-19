<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;

#[AsCommand('app:functions:import', 'Import functions into database')]
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


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(
        InputInterface  $input,
        OutputInterface $output,
    ): int
    {
        $io = new SymfonyStyle($input, $output);
        $directory = $this->root . '/database/functions';

        $finder = new Finder();
        $files = $finder->files()->in($directory);

        foreach ($files as $file) {
            try {
                $this->connection->prepare($file->getContents())->executeStatement();
                $io->text('Importing ' . $file->getFilename());
            } catch (Exception $e) {
                $io->error('Error: ' . $e->getMessage());
            }

        }

        $io->success(sprintf('Imported functions into database %s.', count($files)));
        return Command::SUCCESS;
    }
}