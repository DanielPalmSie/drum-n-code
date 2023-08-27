<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Enum\Status;
use App\Entity\Enum\TaskStatus;
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

    #[Route('/api/task-list', name: 'empty', methods: ['GET'])]
    public function index(TaskRepository $taskRepository, Request $request): Response
    {
        $statusString = $request->query->get('status');

        $tasks = [];
        if ($statusString) {
            $statusString = strtolower($statusString);
            if ($statusString === 'all') {
                $tasks = $taskRepository->findAll();
            } elseif ($statusString === 'todo' || $statusString === 'done') {
                $statusValue = ($statusString === 'done') ? Task::DONE : Task::TODO;
                $tasks = $taskRepository->findByStatus($statusValue);
            }
        } else {
            $tasks = $taskRepository->findAll();
        }

        $taskList = [];
        foreach ($tasks as $task) {
            $taskList[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'status' => $task->getStatus(), // Получаем строковое значение статуса
                'priority' => $task->getPriority(),
                'created' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
                'completed' => $task->getCompletedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($taskList);
    }

    #[Route('/api/task', name: 'task', methods: ['POST'])]
    public function createTask(Request $request, TokenStorageInterface $tokenStorage): Response
    {
        $token = $tokenStorage->getToken();
        $user = $token->getUser();

        $data = $request->toArray();

        $title = $data['title'];
        $statusString = strtolower($data['status']); // Приводим к нижнему регистру
        $priority = $data['priority'];
        $completedAt = new \DateTime($data['completed']);

        // Преобразование строки статуса в числовое значение
        $statusValue = ($statusString === 'done') ? Status::Done->getValue() : Status::Todo->getValue();

        // Создание новой задачи
        $task = new Task();
        $task->setTitle($title);
        $task->setStatus(Status::fromValue($statusValue));
        $task->setPriority($priority);
        $task->setCompletedAt($completedAt);
        $task->setUser($user);

        // Сохранение задачи в базе данных
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // Возвращение успешного ответа
        return $this->json(['message' => 'Task created successfully']);
    }

    #[Route('api/task/status', name: 'status', methods: ['GET'])]
    public function getTasksByStatus(TaskRepository $taskRepository, Request $request): Response
    {
        $statusString = $request->query->get('status'); // Получаем строковое значение статуса

        if ($statusString) {
            // Приводим к нижнему регистру и проверяем значение статуса
            $statusString = strtolower($statusString);
            if ($statusString === 'todo' || $statusString === 'done') {
                $status = ($statusString === 'done') ? Status::Done : Status::Todo;
                $tasks = $taskRepository->findByStatus($status);

                $taskList = [];
                foreach ($tasks as $task) {
                    $taskList[] = [
                        'id' => $task->getId(),
                        'title' => $task->getTitle(),
                        'status' => $task->getStatus(),
                        'priority' => $task->getPriority(),
                        'created' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
                        'completed' => $task->getCompletedAt()->format('Y-m-d H:i:s'),
                    ];
                }

                // Возвращаем задачи в виде JSON-ответа
                return $this->json($taskList);
            }
        }

        // Некорректное значение статуса или отсутствие параметра "status"
        return $this->json(['message' => 'Invalid or missing status parameter'], Response::HTTP_BAD_REQUEST);
    }
}