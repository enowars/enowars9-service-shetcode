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

    public function execute(?string $authorId): array
    {
        $sql = "SELECT p.title as title, p.difficulty as difficulty, p.is_published as is_published, p.id as id, p.description as description FROM problems p WHERE p.is_published = true";
        $params = [];
        
        if ($authorId) {
            $sql .= " AND p.author_id = :author_id";
            $params['author_id'] = $authorId;
        }

        $cacheKey = 'problems_query_' . md5($sql . serialize($params));

        return $this->userCache->get($cacheKey, function (CacheItemInterface $item) use ($sql, $params) {
            $item->expiresAfter(5);

            $stmt = $this->entityManager
                ->getConnection()
                ->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            return $stmt->executeQuery()->fetchAllAssociative();
        });
    }
}