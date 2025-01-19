<?php declare(strict_types=1);

namespace Inno\Command;

use DateTime;
use Doctrine\ORM\{NonUniqueResultException, NoResultException,};
use Inno\Repository\ResetPasswordRequestRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:requesttoken:cleanup', 'Deletes request tokens from database')]
class RequestTokenCleanupCommand extends Command
{

    final const string PERFORM = 'perform';

    /**
     *
     * @param ResetPasswordRequestRepository $repository
     */
    public function __construct(
        private readonly ResetPasswordRequestRepository $repository,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(self::PERFORM, null, InputOption::VALUE_NONE, 'Execute');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    protected function execute(
        InputInterface  $input,
        OutputInterface $output,
    ): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new DateTime();

        $count = 0;
        $perform = false;

        if ($input->getOption(self::PERFORM)) {
            $io->note('performing...');
            $result = $this->repository
                ->createQueryBuilder('rt')
                ->delete()
                ->where('rt.expiresAt < :expiresAt')
                ->setParameter('expiresAt', $now->format('Y-m-d H:i'));

            $count = $result->getQuery()->getSingleScalarResult();
            $perform = true;
        }

        if (!$perform) {
            $io->warning(sprintf('Invalid options. Use [--%s] option', self::PERFORM));
            return Command::INVALID;
        }

        $io->success(sprintf('Deleted %d old request tokens.', $count));
        return Command::SUCCESS;
    }
}
