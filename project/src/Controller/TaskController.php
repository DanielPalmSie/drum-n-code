<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Services\TaskService;
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

    private $taskService;

    public function __construct(
        TaskRepository $taskRepository,
        EntityManagerInterface $entityManager,
        TaskService $taskService
    ) {
        $this->taskRepository = $taskRepository;
        $this->entityManager = $entityManager;
        $this->taskService = $taskService;
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

        $tasks = $this->taskRepository->findByStatusAndTitle($statusString, $searchTerm, $sortBy, $sortOrder, $priorityRange);

        $taskList = [];
        foreach ($tasks as $task) {
            $taskList[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'status' => $task->getStatus(),
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
        $task = $this->taskService->createTask($data, $user);

        return $this->json($task);
    }

    #[Route('/api/task/{id}', name: 'edit_task', methods: ['POST'])]
    public function editTask(Task $task, Request $request, TokenStorageInterface $tokenStorage): Response
    {
        $token = $tokenStorage->getToken();
        $user = $token->getUser();
        $data = $request->toArray();

        try {
            $task = $this->taskService->editTask($task, $data, $user);
            return $this->json($task);
        } catch (\LogicException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
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
    public function markAsCompleted(Task $task, TaskService $taskService): Response
    {
        try {
            $taskService->markAsCompleted($task);
            return $this->json(['message' => 'Task marked complete']);
        } catch (\LogicException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
    }

    #[Route('/api/task/{id}', name: 'delete_task', methods: ['DELETE'])]
    public function deleteTask(Task $task, TokenStorageInterface $tokenStorage, TaskService $taskService): Response
    {
        $token = $tokenStorage->getToken();
        $user = $token->getUser();

        $result = $taskService->deleteTask($task, $user);

        $messageKey = isset($result['error']) ? 'error' : 'message';

        return $this->json([$messageKey => $result[$messageKey]], $result['status']);
    }
}