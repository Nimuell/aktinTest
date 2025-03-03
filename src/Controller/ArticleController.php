<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Article;
use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/articles')]
class ArticleController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'article_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $articles = $this->entityManager->getRepository(Article::class)->findAll();
        $data = array_map(fn(Article $article) => [
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'author' => [
                'id' => $article->getAuthor()->getId(),
                'name' => $article->getAuthor()->getName()
            ],
            'createdAt' => $article->getCreatedAt()->format('c'),
            'updatedAt' => $article->getUpdatedAt()->format('c')
        ], $articles);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'article_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $article = $this->entityManager->getRepository(Article::class)->find($id);
        
        if (!$article) {
            return $this->json(['message' => 'Article not found'], 404);
        }

        return $this->json([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'author' => [
                'id' => $article->getAuthor()->getId(),
                'name' => $article->getAuthor()->getName()
            ],
            'createdAt' => $article->getCreatedAt()->format('c'),
            'updatedAt' => $article->getUpdatedAt()->format('c')
        ]);
    }

    #[Route('', name: 'article_create', methods: ['POST'])]
    #[IsGranted('ROLE_AUTHOR')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title'], $data['content'])) {
            return $this->json(['message' => 'Missing required fields'], 400);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isGranted('ROLE_AUTHOR') && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['message' => 'Access denied'], 403);
        }

        $article = new Article();
        $article->setTitle($data['title'])
            ->setContent($data['content'])
            ->setAuthor($user);

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        return $this->json([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'author' => [
                'id' => $article->getAuthor()->getId(),
                'name' => $article->getAuthor()->getName()
            ],
            'createdAt' => $article->getCreatedAt()->format('c'),
            'updatedAt' => $article->getUpdatedAt()->format('c')
        ], 201);
    }

    #[Route('/{id}', name: 'article_update', methods: ['PUT'])]
    #[IsGranted('ROLE_AUTHOR')]
    public function update(int $id, Request $request): JsonResponse
    {
        $article = $this->entityManager->getRepository(Article::class)->find($id);
        
        if (!$article) {
            return $this->json(['message' => 'Article not found'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->isGranted('ROLE_ADMIN') && ($article->getAuthor()->getId() !== $user->getId() || !$this->isGranted('ROLE_AUTHOR'))) {
            return $this->json(['message' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $article->setTitle($data['title']);
        }
        if (isset($data['content'])) {
            $article->setContent($data['content']);
        }

        $this->entityManager->flush();

        return $this->json([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'author' => [
                'id' => $article->getAuthor()->getId(),
                'name' => $article->getAuthor()->getName()
            ],
            'createdAt' => $article->getCreatedAt()->format('c'),
            'updatedAt' => $article->getUpdatedAt()->format('c')
        ]);
    }

    #[Route('/{id}', name: 'article_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_AUTHOR')]
    public function delete(int $id): JsonResponse
    {
        $article = $this->entityManager->getRepository(Article::class)->find($id);
        
        if (!$article) {
            return $this->json(['message' => 'Article not found'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->isGranted('ROLE_ADMIN') && ($article->getAuthor()->getId() !== $user->getId() || !$this->isGranted('ROLE_AUTHOR'))) {
            return $this->json(['message' => 'Access denied'], 403);
        }

        $this->entityManager->remove($article);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }
} 