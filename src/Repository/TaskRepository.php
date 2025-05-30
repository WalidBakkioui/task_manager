<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findCompletedHistorySorted(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.completed = :completed')
            ->setParameter('completed', true)
            ->orderBy(
                "CASE 
                    WHEN t.priority = 'élevée' THEN 0 
                    WHEN t.priority = 'moyenne' THEN 1 
                    ELSE 2 
                END",
                'ASC'
            )
            ->addOrderBy('t.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}