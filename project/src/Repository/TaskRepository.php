<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 *
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array $criteria, array $orderBy = null)
 * @method Task[]    findAll()
 * @method Task[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findByStatusAndTitle(
        string $status = null,
        ?string $searchTerm = null,
        ?string $sortBy = null, // Сортировка по умолчанию
        string $sortOrder = null // Порядок сортировки по умолчанию
    ): array {
        $queryBuilder = $this->createQueryBuilder('task');

        if ($status !== null && $status !== 'all') {
            $statusValue = ($status === 'done') ? Task::DONE : Task::TODO;
            $queryBuilder->andWhere('task.status = :status')
                ->setParameter('status', $statusValue);
        }

        if ($searchTerm !== null) {
            $queryBuilder->andWhere('LOWER(task.title) LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%');
        }

        // Добавляем сортировку
        switch ($sortBy) {
            case 'createdAt':
                $queryBuilder->orderBy('task.createdAt', $sortOrder);
                break;
            case 'completedAt':
                $queryBuilder->orderBy('task.completedAt', $sortOrder);
                break;
            case 'priority':
                $queryBuilder->orderBy('task.priority', $sortOrder);
                break;
            default:
                $queryBuilder->orderBy('task.id', $sortOrder);
                break;
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
