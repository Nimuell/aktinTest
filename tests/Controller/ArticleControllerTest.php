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

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Vyčistíme databázi
        $this->entityManager->createQuery('DELETE FROM App\Entity\Article')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    public function testReaderCannotCreateArticle(): void
    {
        // create reader
        $user = new User();
        $user->setEmail('reader@test.com')
            ->setName('Reader')
            ->setRole(UserRole::READER)
            ->setPassword($this->passwordHasher->hashPassword($user, 'password'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // login
        $this->client->request(
            'POST',
            '/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'reader@test.com',
                'password' => 'password'
            ])
        );

        $response = $this->client->getResponse();
        $token = json_decode($response->getContent(), true)['token'];

        // create article
        $this->client->request(
            'POST',
            '/articles',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'title' => 'Test Article',
                'content' => 'Test Content'
            ])
        );

        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
    }

    public function testAuthorCanCreateAndEditOwnArticle(): void
    {
        // create author
        $user = new User();
        $user->setEmail('author@test.com')
            ->setName('Author')
            ->setRole(UserRole::AUTHOR)
            ->setPassword($this->passwordHasher->hashPassword($user, 'password'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // login
        $this->client->request(
            'POST',
            '/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'author@test.com',
                'password' => 'password'
            ])
        );

        $response = $this->client->getResponse();
        $token = json_decode($response->getContent(), true)['token'];

        // create article
        $this->client->request(
            'POST',
            '/articles',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode([
                'title' => 'Test Article',
                'content' => 'Test Content'
            ])
        );

        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $articleData = json_decode($this->client->getResponse()->getContent(), true);

        // modify article
        $this->client->request(
            'PUT',
            '/articles/' . $articleData['id'],
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
        $this->assertEquals('Updated Title', json_decode($this->client->getResponse()->getContent(), true)['title']);
    }

    public function testAuthorCannotEditOthersArticle(): void
    {
        // create second author
        $author1 = new User();
        $author1->setEmail('author1@test.com')
            ->setName('Author 1')
            ->setRole(UserRole::AUTHOR)
            ->setPassword($this->passwordHasher->hashPassword($author1, 'password'));

        $author2 = new User();
        $author2->setEmail('author2@test.com')
            ->setName('Author 2')
            ->setRole(UserRole::AUTHOR)
            ->setPassword($this->passwordHasher->hashPassword($author2, 'password'));

        $this->entityManager->persist($author1);
        $this->entityManager->persist($author2);

        // create article for author1
        $article = new Article();
        $article->setTitle('Test Article')
            ->setContent('Test Content')
            ->setAuthor($author1);

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        // login as author2
        $this->client->request(
            'POST',
            '/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'author2@test.com',
                'password' => 'password'
            ])
        );

        $response = $this->client->getResponse();
        $token = json_decode($response->getContent(), true)['token'];

        // modify author1 article
        $this->client->request(
            'PUT',
            '/articles/' . $article->getId(),
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
    }
}
