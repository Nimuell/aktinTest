<?php

namespace App\Tests\Controller;

use App\Entity\Article;
use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ArticleControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;
    private $testArticle;
    private $reader;
    private $author;
    private $admin;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Vyčistíme databázi
        $this->entityManager->createQuery('DELETE FROM App\Entity\Article')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();

        // Vytvoříme testovací uživatele
        $this->reader = $this->createUser('reader@test.com', 'Reader', UserRole::READER);
        $this->author = $this->createUser('author@test.com', 'Author', UserRole::AUTHOR);
        $this->admin = $this->createUser('admin@test.com', 'Admin', UserRole::ADMIN);

        // Vytvoříme testovací článek
        $this->testArticle = new Article();
        $this->testArticle->setTitle('Test Article')
            ->setContent('Test Content')
            ->setAuthor($this->author);

        $this->entityManager->persist($this->testArticle);
        $this->entityManager->flush();
    }

    private function createUser(string $email, string $name, UserRole $role): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setName($name)
            ->setRole($role)
            ->setPassword($this->passwordHasher->hashPassword($user, 'password'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function getToken(string $email): string
    {
        $this->client->request(
            'POST',
            '/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => 'password'
            ])
        );

        $response = $this->client->getResponse();
        return json_decode($response->getContent(), true)['token'];
    }

    public function testReaderCanReadArticles(): void
    {
        $token = $this->getToken('reader@test.com');

        // Test GET /api/articles
        $this->client->request(
            'GET',
            '/api/articles',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Test GET /api/articles/{id}
        $this->client->request(
            'GET',
            sprintf('/api/articles/%d', $this->testArticle->getId()),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testReaderCannotModifyArticles(): void
    {
        $token = $this->getToken('reader@test.com');

        // Test POST /api/articles
        $this->client->request(
            'POST',
            '/api/articles',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'title' => 'New Article',
                'content' => 'Content'
            ])
        );
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        // Test PUT /api/articles/{id}
        $this->client->request(
            'PUT',
            sprintf('/api/articles/%d', $this->testArticle->getId()),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'title' => 'Updated Title'
            ])
        );
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        // Test DELETE /api/articles/{id}
        $this->client->request(
            'DELETE',
            sprintf('/api/articles/%d', $this->testArticle->getId()),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
    }

    public function testAuthorCanManageOwnArticles(): void
    {
        $token = $this->getToken('author@test.com');

        // Test POST /api/articles
        $this->client->request(
            'POST',
            '/api/articles',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'title' => 'New Article',
                'content' => 'Content'
            ])
        );
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());

        // Test PUT /api/articles/{id}
        $this->client->request(
            'PUT',
            sprintf('/api/articles/%d', $this->testArticle->getId()),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'title' => 'Updated Title'
            ])
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Test DELETE /api/articles/{id}
        $this->client->request(
            'DELETE',
            sprintf('/api/articles/%d', $this->testArticle->getId()),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );
        $this->assertEquals(204, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCanManageAllArticles(): void
    {
        $token = $this->getToken('admin@test.com');

        // Test POST /api/articles
        $this->client->request(
            'POST',
            '/api/articles',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'title' => 'Admin Article',
                'content' => 'Content'
            ])
        );
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());

        // Test PUT /api/articles/{id}
        $this->client->request(
            'PUT',
            sprintf('/api/articles/%d', $this->testArticle->getId()),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'title' => 'Admin Updated'
            ])
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Test DELETE /api/articles/{id}
        $this->client->request(
            'DELETE',
            sprintf('/api/articles/%d', $this->testArticle->getId()),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );
        $this->assertEquals(204, $this->client->getResponse()->getStatusCode());
    }
} 