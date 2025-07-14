<?php

namespace App\DatabaseManager;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

readonly class FindProblemsByAuthorId
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface $userCache
    ) {
    }

    public function execute(?string $authorUsername): array
    {
        try {
        $sql = "SELECT p.title as title, p.difficulty as difficulty, p.is_published as is_published, p.id as id, p.description as description FROM problems p JOIN users u ON p.author_id = u.id WHERE p.is_published = true";
        if ($authorUsername) {
            $sql .= " AND u.username = '" . $authorUsername . "'";
        }
        
        $preparedStatement = $this->entityManager->getConnection()->prepare($sql);
            $result = $preparedStatement->executeQuery();
            return $result->fetchAllAssociative();
        } catch (\Exception $e) {
            return [];
        }
    }
}