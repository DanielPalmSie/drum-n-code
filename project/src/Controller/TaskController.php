<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TaskController extends AbstractController
{
    private $taskRepository;

    private $entityManager;

    public function __construct(TaskRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $this->taskRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('create/task', name: 'open_task', methods: ['GET'])]
    public function openCreatePage(): Response
    {
        return $this->render('create.html.twig');
    }

    #[Route('edit/task/{taskId}', name: 'open_edit_task', methods: ['GET'])]
    public function openEditPage(int $taskId): Response
    {
        return $this->render('edit.html.twig', [
            'taskId' => $taskId,
        ]);
    }

    #[Route('/api/task-list', name: 'empty', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $statusString = $request->query->get('status');
        $priorityRange = $request->query->get('priority');
        $searchTerm = $request->query->get('search');
        $sortBy = $request->query->get('sortBy');
        $sortOrder = $request->query->get('sortOrder');

        $tasks = $this->taskRepository->findByStatusAndTitle($statusString, $searchTerm, $sortBy, $sortOrder);

        if ($priorityRange) {
            list($minPriority, $maxPriority) = explode('-', $priorityRange);
            $minPriority = max(1, (int)$minPriority);
            $maxPriority = min(5, (int)$maxPriority);

            $tasks = array_filter($tasks, function ($task) use ($minPriority, $maxPriority) {
                return $task->getPriority() >= $minPriority && $task->getPriority() <= $maxPriority;
            });
        }

        $taskList = [];
        foreach ($tasks as $task) {
            $taskList[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'status' => $task->getStatus(), // Получаем строковое значение статуса
                'priority' => $task->getPriority(),
                'created' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
                'completed' => $task->getCompletedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($taskList);
    }

    #[Route('/api/task', name: 'create_task', methods: ['POST'])]
    public function createTask(Request $request, TokenStorageInterface $tokenStorage): Response
    {
        $token = $tokenStorage->getToken();
        $user = $token->getUser();

        $data = $request->toArray();

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

        return $this->json($task);
    }

    #[Route('/api/task/{id}', name: 'edit_task', methods: ['POST'])]
    public function editTask(Task $task, Request $request, TokenStorageInterface $tokenStorage): Response
    {
        $token = $tokenStorage->getToken();
        $user = $token->getUser();

        if ($task->getUser() !== $user) {
            return $this->json(['error' => 'You do not have permission to edit this task.'], 403);
        }

        $data = $request->toArray();

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

        return $this->json($task);
    }

    #[Route('/api/task/{id}', name: 'get_task', methods: ['GET'])]
    public function getTask(Task $task)
    {
        $responseData = [
            'title' => $task->getTitle(),
            'status' => $task->getStatus(),
            'priority' => $task->getPriority(),
            'completed' => $task->getCompletedAt(),
        ];

        return $this->json(['task' => $responseData]);
    }

    #[Route('/api/task/{id}', name: 'mark_task', methods: ['PATCH'])]
    public function markAsCompleted(Task $task)
    {
        foreach ($task->getTasks() as $subTask) {
            if ($subTask->getCompletedAt() === null) {
                return $this->json(['error' => 'You cannot complete a task which has subtasks that are not completed.'], 403);
            }
        }

        $task->setCompletedAt(new \DateTime());
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->json(['error' => 'Task marked complete']);
    }

    #[Route('/api/task/{id}', name: 'delete_task', methods: ['DELETE'])]
    public function deleteTask(Task $task, TokenStorageInterface $tokenStorage)
    {
        $token = $tokenStorage->getToken();
        $user = $token->getUser();

        if ($task->getUser() !== $user) {
            return $this->json(['error' => "you do not have permission to delete other people's tasks"], 403);
        }

        if (!is_null($task->getCompletedAt())) {
            return $this->json(['error' => 'You cannot delete a task that has been completed.'], 403);
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return $this->json(['message' => 'task was successfully deleted']);
    }
}