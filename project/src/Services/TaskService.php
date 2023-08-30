<?php

namespace App\Services;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class TaskService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createTask(array $data, $user): Task
    {
        $title = $data['title'];
        $statusString = strtolower($data['status']);
        $priority = (int)$data['priority'];
        $completedAt = new \DateTime($data['completed']);

        $statusValue = ($statusString === 'done') ? 'done' : 'todo';

        $task = new Task();
        $task->setTitle($title);
        $task->setStatus($statusValue);
        $task->setPriority($priority);
        $task->setCompletedAt($completedAt);
        $task->setUser($user);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    public function editTask(Task $task, array $data, $user): Task
    {
        if ($task->getUser() !== $user) {
            throw new \LogicException('You do not have permission to edit this task.');
        }

        $title = $data['title'];
        $statusString = strtolower($data['status']);
        $priority = (int)$data['priority'];
        $completedAt = new \DateTime($data['completed']);
        $statusValue = ($statusString === 'done') ? 'done' : 'todo';

        $task->setTitle($title);
        $task->setStatus($statusValue);
        $task->setPriority($priority);
        $task->setCompletedAt($completedAt);
        $task->setUser($user);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    public function markAsCompleted(Task $task): void
    {
        foreach ($task->getTasks() as $subTask) {
            if ($subTask->getCompletedAt() === null) {
                throw new \LogicException('You cannot complete a task which has subtasks that are not completed.');
            }
        }

        $task->setCompletedAt(new \DateTime());
        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    public function deleteTask(Task $task, User $user): array {


        if ($task->getUser()->getId() !== $user->getId()) {
            return ['error' => "you do not have permission to delete other people's tasks", 'status' => 403];
        }

        if (!is_null($task->getCompletedAt())) {
            return ['error' => 'You cannot delete a task that has been completed.', 'status' => 403];
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return ['message' => 'task was successfully deleted', 'status' => 200];
    }
}