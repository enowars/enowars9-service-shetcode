<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:purge-old-data',
    description: 'Purges all data older than 10 minutes from the database',
)]
class PurgeOldDataCommand extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Purging data older than 10 minutes');

        $timestamp = new \DateTime('-10 minutes');
        $formattedTimestamp = $timestamp->format('Y-m-d H:i:s');

        $tables = [
            'problems',
            'private_problems',
            'private_access',
            'users',
            'feedback'
        ];

        $totalDeleted = 0;

        foreach ($tables as $table) {
            $timestampColumn = 'created_at';

            if ($table === 'feedback') {
                $timestampColumn = 'created_at';
            }

            if ($table === 'users') {
                $sql = "DELETE FROM $table WHERE $timestampColumn < :timestamp AND is_admin = FALSE";
            } else {
                $sql = "DELETE FROM $table WHERE $timestampColumn < :timestamp";
            }

            try {
                $stmt = $this->connection->prepare($sql);
                $stmt->bindValue('timestamp', $formattedTimestamp);
                $result = $stmt->executeStatement();
                $totalDeleted += $result;
                
                $io->text(sprintf('Deleted %d rows from %s', $result, $table));
            } catch (\Exception $e) {
                $io->error(sprintf('Error deleting from %s: %s', $table, $e->getMessage()));
            }
        }

        $io->success(sprintf('Successfully purged %d rows older than 10 minutes', $totalDeleted));

        return Command::SUCCESS;
    }
} 