<?php

namespace App\Repository;

use App\Entity\Title;
use App\Enum\TitleType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Title>
 */
class TitleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Title::class);
    }

    /**
     * @return Title[] newest first; a null filter is not applied
     */
    public function findByFilters(?TitleType $type, ?bool $watched, ?string $search): array
    {
        $queryBuilder = $this->createQueryBuilder('title')
            ->orderBy('title.id', 'DESC');

        // Values always go in through setParameter(), never into the query text, which prevents SQL injection.
        if (null !== $type) {
            $queryBuilder->andWhere('title.type = :type')->setParameter('type', $type);
        }
        if (null !== $watched) {
            $queryBuilder->andWhere('title.watched = :watched')->setParameter('watched', $watched);
        }
        if (null !== $search && '' !== trim($search)) {
            // LOWER() on both sides makes the search case-insensitive on every database.
            $queryBuilder->andWhere('LOWER(title.name) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower(trim($search)).'%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function save(Title $title): void
    {
        // persist() only starts tracking the object; flush() is what actually runs the SQL.
        $this->getEntityManager()->persist($title);
        $this->getEntityManager()->flush();
    }

    public function remove(Title $title): void
    {
        $this->getEntityManager()->remove($title);
        $this->getEntityManager()->flush();
    }
}
