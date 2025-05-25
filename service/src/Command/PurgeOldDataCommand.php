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
    description: 'Purges all data older than 10 minutes from the database and filesystem',
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
        $cutoffTime = $timestamp->getTimestamp();

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

        $submissionsDeleted = $this->cleanupSubmissions($io, $cutoffTime);
        
        $io->success(sprintf('Successfully purged %d database rows and %d submission directories older than 10 minutes', $totalDeleted, $submissionsDeleted));

        return Command::SUCCESS;
    }

    private function cleanupSubmissions(SymfonyStyle $io, int $cutoffTime): int
    {
        $submissionsRoot = getcwd() . '/public/submissions';
        $deletedCount = 0;

        if (!is_dir($submissionsRoot)) {
            $io->text('Submissions directory does not exist, skipping cleanup');
            return 0;
        }

        try {
            $userDirs = scandir($submissionsRoot);
            if ($userDirs === false) {
                $io->error('Could not read submissions directory');
                return 0;
            }

            foreach ($userDirs as $userDir) {
                if ($userDir === '.' || $userDir === '..') {
                    continue;
                }

                $userPath = $submissionsRoot . '/' . $userDir;
                if (!is_dir($userPath)) {
                    continue;
                }

                $problemDirs = scandir($userPath);
                if ($problemDirs === false) {
                    continue;
                }

                foreach ($problemDirs as $problemDir) {
                    if ($problemDir === '.' || $problemDir === '..') {
                        continue;
                    }

                    $problemPath = $userPath . '/' . $problemDir;
                    if (!is_dir($problemPath)) {
                        continue;
                    }

                    if ($this->isDirectoryOlderThan($problemPath, $cutoffTime)) {
                        if ($this->removeDirectory($problemPath)) {
                            $deletedCount++;
                            $io->text(sprintf('Deleted submission directory: %s/%s', $userDir, $problemDir));
                        }
                    }
                }

                if ($this->isDirectoryEmpty($userPath)) {
                    if (rmdir($userPath)) {
                        $io->text(sprintf('Deleted empty user directory: %s', $userDir));
                    }
                }
            }
        } catch (\Exception $e) {
            $io->error(sprintf('Error cleaning up submissions: %s', $e->getMessage()));
        }

        return $deletedCount;
    }

    private function isDirectoryOlderThan(string $dirPath, int $cutoffTime): bool
    {
        if (!is_dir($dirPath)) {
            return false;
        }

        $files = scandir($dirPath);
        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $dirPath . '/' . $file;
            if (is_file($filePath)) {
                $mtime = filemtime($filePath);
                if ($mtime !== false && $mtime > $cutoffTime) {
                    return false;
                }
            }
        }

        return true;
    }

    private function removeDirectory(string $dirPath): bool
    {
        if (!is_dir($dirPath)) {
            return false;
        }

        $files = scandir($dirPath);
        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $dirPath . '/' . $file;
            if (is_dir($filePath)) {
                if (!$this->removeDirectory($filePath)) {
                    return false;
                }
            } else {
                if (!unlink($filePath)) {
                    return false;
                }
            }
        }

        return rmdir($dirPath);
    }

    private function isDirectoryEmpty(string $dirPath): bool
    {
        if (!is_dir($dirPath)) {
            return false;
        }

        $files = scandir($dirPath);
        if ($files === false) {
            return false;
        }

        return count($files) <= 2;
    }
} 