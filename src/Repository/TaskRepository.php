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

    /**
     * Récupère les tâches terminées triées par priorité (Élevée → Moyenne → Faible)
     * puis par titre (A → Z).
     *
     * @return Task[]
     */
    public function findCompletedHistorySorted(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.completed = :completed')
            ->setParameter('completed', true)

            // CASE pour ordonner la priorité selon notre logique métier
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

    //    /**
    //     * @return Task[] Returns an array of Task objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Task
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

}
