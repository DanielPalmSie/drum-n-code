<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Создание пользователей
        $user1 = new User();
        $user1->setEmail('user@user.com');
        $user1->setPassword($this->passwordHasher->hashPassword($user1, 'test'));
        $user1->setRoles([]);
        $manager->persist($user1);

        $user2 = new User();
        $user2->setEmail('test@test.com');
        $user2->setPassword($this->passwordHasher->hashPassword($user2, 'test'));
        $user2->setRoles([]);
        $manager->persist($user2);

        $manager->flush();

        // Создание задач
        for ($i = 0; $i < 7; $i++) {
            $task = new Task();
            $task->setTitle('Task Title ' . $i);
            $task->setDescription('Task Description ' . $i);

            $statusOptions = ['todo', 'done'];
            $randomStatus = $statusOptions[rand(0, 1)];
            $task->setStatus($randomStatus); // 0 или 1
            $task->setPriority(rand(1, 5));

            if ($i < 3) {
                $task->setUser($user1);
            } else {
                $task->setUser($user2);
            }

            if ($i % 2 == 1) {
                $task->setCompletedAt(new \DateTime());
            }

            $manager->persist($task);
        }

        $manager->flush();
    }
}
