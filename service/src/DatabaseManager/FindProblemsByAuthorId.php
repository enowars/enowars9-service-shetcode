<?php

namespace App\DatabaseManager;

use Doctrine\ORM\EntityManagerInterface;

readonly class FindProblemsByAuthorId
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function execute(?int $authorId): array
    {
        $sql = "SELECT p.* FROM problems p WHERE p.is_published = true";
        if ($authorId) {
            $sql .= " AND p.author_id = " . $authorId;
        }

        return $this->entityManager
            ->getConnection()
            ->prepare($sql)
            ->executeQuery()
            ->fetchAllAssociative();
    }
}