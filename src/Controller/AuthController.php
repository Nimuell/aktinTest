<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/auth/register', name: 'auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['name'], $data['role'])) {
            return $this->json(['message' => 'Missing required fields'], 400);
        }

        try {
            $role = UserRole::from($data['role']);
        } catch (\ValueError $e) {
            return $this->json(['message' => 'Invalid role'], 400);
        }

        $user = new User();
        $user->setEmail($data['email'])
            ->setName($data['name'])
            ->setRole($role)
            ->setPassword(
                $this->passwordHasher->hashPassword($user, $data['password'])
            );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'User registered successfully'], 201);
    }

    #[Route('/auth/login', name: 'auth_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        return $this->json([
            'user' => [
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'role' => $user->getRole()->value,
            ],
        ]);
    }
} 