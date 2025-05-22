<?php

namespace App\DatabaseManager;

use Doctrine\ORM\EntityManagerInterface;

readonly class FindProblemsByAuthorId
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function execute(?string $authorId): array
    {
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
        
        $queryBuilder
            ->select('p.*')
            ->from('problems', 'p')
            ->where('p.is_published = true');
            
        if ($authorId) {
            $queryBuilder->andWhere('p.author_id = :authorId')
                ->setParameter('authorId', $authorId);
        }

        return $queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();
    }
}