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
        $priorityRange = $request->query->get('priority'); // Диапазон приоритетов, например, "1-3"
        $searchTerm = $request->query->get('search'); // Поисковый запрос по полю title
        $sortBy = $request->query->get('sortBy'); // Поле для сортировки
        $sortOrder = $request->query->get('sortOrder'); // Порядок сортировки

        $tasks = $this->taskRepository->findByStatusAndTitle($statusString, $searchTerm, $sortBy, $sortOrder);

        if ($priorityRange) {
            list($minPriority, $maxPriority) = explode('-', $priorityRange);
            $minPriority = max(1, (int)$minPriority); // Минимальное значение не менее 1
            $maxPriority = min(5, (int)$maxPriority); // Максимальное значение не более 5

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
                'completed' => $task->getCompletedAt()->format('Y-m-d H:i:s'),
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
        $statusString = strtolower($data['status']); // Приводим к нижнему регистру
        $priority = (int)$data['priority'];
        $completedAt = new \DateTime($data['completed']);

        // Преобразование строки статуса в числовое значение
        $statusValue = ($statusString === 'done') ? 'done' : 'todo';

        // Создание новой задачи
        $task = new Task();
        $task->setTitle($title);
        $task->setStatus($statusValue);
        $task->setPriority($priority);
        $task->setCompletedAt($completedAt);
        $task->setUser($user);

        // Сохранение задачи в базе данных
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // Возвращение успешного ответа
        return $this->json($task);
    }

    #[Route('/api/task/{id}', name: 'edit_task', methods: ['POST'])]
    public function editTask(int $id, Request $request, TokenStorageInterface $tokenStorage): Response
    {
        $token = $tokenStorage->getToken();
        $user = $token->getUser();

        $data = $request->toArray();

        $title = $data['title'];
        $statusString = strtolower($data['status']); // Приводим к нижнему регистру
        $priority = (int)$data['priority'];
        $completedAt = new \DateTime($data['completed']);

        // Преобразование строки статуса в числовое значение
        $statusValue = ($statusString === 'done') ? 'done' : 'todo';

        $task = $this->taskRepository->find($id);
        $task->setTitle($title);
        $task->setStatus($statusValue);
        $task->setPriority($priority);
        $task->setCompletedAt($completedAt);
        $task->setUser($user);

        // Сохранение задачи в базе данных
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // Возвращение успешного ответа
        return $this->json($task);
    }
}