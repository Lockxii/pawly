<?php

namespace App\Tests\Functional;

use App\DataFixtures\AppFixtures;
use App\Entity\Adoption;
use App\Entity\Dogs;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ContainerRepositoryFactory;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ApplicationFlowTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManager $manager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $container = static::getContainer();
        $config = ORMSetup::createAttributeMetadataConfig([dirname(__DIR__, 2).'/src/Entity'], true);
        $config->enableNativeLazyObjects(true);
        $config->setRepositoryFactory(new ContainerRepositoryFactory($container));
        $this->manager = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]), $config);
        (new SchemaTool($this->manager))->createSchema([
            $this->manager->getClassMetadata(User::class),
            $this->manager->getClassMetadata(Dogs::class),
            $this->manager->getClassMetadata(Adoption::class),
        ]);
        $container->set('doctrine.orm.default_entity_manager', $this->manager);
        $container->get(AppFixtures::class)->load($this->manager);
        $this->manager->clear();
    }

    protected function tearDown(): void
    {
        $this->manager->close();
        $this->manager->getConnection()->close();
        parent::tearDown();
    }

    public function testRegistrationLoginAndLogout(): void
    {
        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();
        $this->client->submitForm('Créer mon compte', [
            'user[username]' => 'Alice', 'user[email]' => 'alice@test.fr',
            'user[password][first]' => 'password123', 'user[password][second]' => 'password123',
        ]);
        self::assertResponseRedirects('/login');
        $user = $this->manager->getRepository(User::class)->findOneBy(['email' => 'alice@test.fr']);
        self::assertSame(['ROLE_USER'], array_values($user->getRoles()));
        self::assertTrue(static::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'password123'));

        $this->login('alice@test.fr');
        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorTextContains('nav', 'Déconnexion');
        self::assertSelectorNotExists('a[href="/admin/users"]');
        $this->client->submitForm('Déconnexion');
        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorExists('a[href="/login"]');
    }

    public function testDuplicateEmailAndInvalidLoginCsrfAreRejected(): void
    {
        $this->client->request('GET', '/register');
        $this->client->submitForm('Créer mon compte', [
            'user[username]' => 'Double', 'user[email]' => 'admin@example.com',
            'user[password][first]' => 'password123', 'user[password][second]' => 'password123',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.animal-form', 'Cette adresse e-mail est déjà utilisée.');
        self::assertSame(3, $this->manager->getConnection()->fetchOne('SELECT COUNT(*) FROM users'));

        $this->client->request('POST', '/login', ['_username' => 'admin@example.com', '_password' => 'password123', '_csrf_token' => 'invalide']);
        self::assertResponseRedirects('/login');
        $this->client->request('GET', '/admin/users');
        self::assertResponseRedirects('/login');
    }

    public function testVisitorSubmitsAndMemberAcceptsAdoption(): void
    {
        $this->client->request('GET', '/adoptions');
        self::assertResponseRedirects('/login');
        $this->client->request('GET', '/dogs/1/adopt');
        self::assertResponseIsSuccessful();
        $this->client->submitForm('Envoyer ma demande', [
            'adoption_form[name]' => 'Visiteur <script>',
            'adoption_form[email]' => 'visiteur@test.fr',
            'adoption_form[message]' => 'Je souhaite accueillir Rex.',
        ]);
        self::assertResponseRedirects('/dogs/1');
        self::assertSame(4, $this->manager->getConnection()->fetchOne('SELECT COUNT(*) FROM Adoption'));

        $this->login('user@example.com');
        $this->client->request('GET', '/adoptions');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/animals');
        self::assertResponseStatusCodeSame(403);
        $this->login('membre@example.com');
        $this->client->request('GET', '/animals');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(6, '.animal-card');
        $this->client->request('GET', '/adoptions');
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('a[href="/admin/users"]');
        $this->client->request('GET', '/adoptions/4');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Visiteur &lt;script&gt;', $this->client->getResponse()->getContent());
        $this->client->submitForm('Valider la décision', ['adoption_decision[status]' => 'Acceptée']);
        self::assertResponseRedirects('/adoptions');
        self::assertSame('En Attente', $this->manager->getConnection()->fetchOne('SELECT status FROM Dogs WHERE id = 1'));
        self::assertSame('Acceptée', $this->manager->getConnection()->fetchOne('SELECT status FROM Adoption WHERE id = 4'));

        $this->client->request('GET', '/adoptions/1');
        $this->client->submitForm('Valider la décision', ['adoption_decision[status]' => 'Acceptée']);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.animal-form', 'Cet animal n’est plus disponible');
        self::assertSame('En attente', $this->manager->getConnection()->fetchOne('SELECT status FROM Adoption WHERE id = 1'));
    }

    public function testMemberCanRefuseAndInvalidDecisionIsRejected(): void
    {
        $this->login('membre@example.com');
        $this->client->request('GET', '/adoptions/1');
        $this->client->submitForm('Valider la décision');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.animal-form', 'Choisissez une décision.');
        $this->client->submitForm('Valider la décision', ['adoption_decision[status]' => 'Refusée']);
        self::assertResponseRedirects('/adoptions');
        self::assertSame('Refusée', $this->manager->getConnection()->fetchOne('SELECT status FROM Adoption WHERE id = 1'));
        self::assertSame('Disponible', $this->manager->getConnection()->fetchOne('SELECT status FROM Dogs WHERE id = 1'));
        $this->client->request('GET', '/adoptions/1');
        self::assertSelectorTextContains('.form-card', 'Cette demande a déjà été traitée.');
        self::assertSelectorNotExists('select');
    }

    public function testOnlyAdminCanEditUsers(): void
    {
        $this->login('membre@example.com');
        $this->client->request('GET', '/admin/users');
        self::assertResponseStatusCodeSame(403);
        $this->login('admin@example.com');
        $this->client->request('GET', '/admin/users');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'user@example.com');
        $crawler = $this->client->request('GET', '/admin/users/3');
        $this->client->request('POST', '/admin/users/3', ['admin_user' => [
            'username' => 'user', 'email' => 'user@example.com', 'roles' => ['ROLE_EMPLOYEE'],
            '_token' => $crawler->filter('input[name="admin_user[_token]"]')->attr('value'),
        ]]);
        self::assertResponseRedirects('/admin/users');
        self::assertStringContainsString('ROLE_EMPLOYEE', $this->manager->getConnection()->fetchOne('SELECT roles FROM users WHERE id = 3'));

        $crawler = $this->client->request('GET', '/admin/users/1');
        $this->client->request('POST', '/admin/users/1', ['admin_user' => [
            'username' => 'admin', 'email' => 'admin@example.com', 'roles' => ['ROLE_USER'],
            '_token' => $crawler->filter('input[name="admin_user[_token]"]')->attr('value'),
        ]]);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.animal-form', 'Vous ne pouvez pas retirer vos propres droits');
        self::assertStringContainsString('ROLE_ADMIN', $this->manager->getConnection()->fetchOne('SELECT roles FROM users WHERE id = 1'));
    }

    public function testRegistrationDoesNotAllowRoleInjectionOrInvalidCsrf(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $data = [
            'username' => 'Intrus', 'email' => 'intrus@test.fr',
            'password' => ['first' => 'password123', 'second' => 'password123'],
            '_token' => $crawler->filter('input[name="user[_token]"]')->attr('value'),
            'roles' => ['ROLE_ADMIN'],
        ];
        $this->client->request('POST', '/register', ['user' => $data]);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.animal-form ul li');
        self::assertSame(3, $this->manager->getConnection()->fetchOne('SELECT COUNT(*) FROM users'));

        unset($data['roles']);
        $data['_token'] = 'invalide';
        $this->client->request('POST', '/register', ['user' => $data]);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.animal-form ul li');
        self::assertSame(3, $this->manager->getConnection()->fetchOne('SELECT COUNT(*) FROM users'));
    }

    public function testAdminCannotInjectUnknownRole(): void
    {
        $this->login('admin@example.com');
        $crawler = $this->client->request('GET', '/admin/users/3');
        $this->client->request('POST', '/admin/users/3', ['admin_user' => [
            'username' => 'user', 'email' => 'user@example.com', 'roles' => ['ROLE_SUPER_ADMIN'],
            '_token' => $crawler->filter('input[name="admin_user[_token]"]')->attr('value'),
        ]]);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.animal-form ul li');
        self::assertStringNotContainsString('ROLE_SUPER_ADMIN', $this->manager->getConnection()->fetchOne('SELECT roles FROM users WHERE id = 3'));
    }

    public function testBlankRegistrationFieldsShowValidationErrors(): void
    {
        $this->client->request('GET', '/register');
        $this->client->submitForm('Créer mon compte', [
            'user[username]' => '', 'user[email]' => '',
            'user[password][first]' => '', 'user[password][second]' => '',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.animal-form ul li');
        self::assertSame(3, $this->manager->getConnection()->fetchOne('SELECT COUNT(*) FROM users'));
    }

    private function login(string $email): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Se connecter', ['_username' => $email, '_password' => 'password123']);
    }

    public function testPublicSearchOnlyReturnsAvailableAnimals(): void
    {
        $this->client->request('GET', '/');
        $this->client->submitForm('Rechercher', ['dog_search[q]' => 'Labrador'], 'GET');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, '.animal-card');
        self::assertSelectorTextContains('.animal-card', 'Luna');
        $this->client->submitForm('Rechercher', ['dog_search[q]' => 'Nala'], 'GET');
        self::assertSelectorCount(0, '.animal-card');

        $this->client->request('GET', '/', ['dog_search' => ['q' => '', 'status' => 'Adopte']]);
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(0, '.animal-card');
        self::assertSelectorExists('.search-form ul li');
    }

    public function testMemberCanCombineSearchAndStatusFilter(): void
    {
        $this->login('membre@example.com');
        $this->client->request('GET', '/animals');
        $this->client->submitForm('Rechercher', ['dog_search[q]' => 'Bella', 'dog_search[status]' => 'Adopte'], 'GET');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, '.animal-card');
        self::assertSelectorTextContains('.animal-card', 'Bella');
        $this->client->submitForm('Rechercher', ['dog_search[q]' => 'Bella', 'dog_search[status]' => 'Disponible'], 'GET');
        self::assertSelectorCount(0, '.animal-card');

        $this->client->request('GET', '/animals', ['dog_search' => ['q' => '', 'status' => 'inconnu']]);
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(0, '.animal-card');
        self::assertSelectorExists('.search-form ul li');
    }

    public function testLoginWorksWithPreviousStatelessCsrfSession(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $session = $this->client->getRequest()->getSession();
        $session->set('csrf-token', 2 | (2 << 8));
        $session->save();
        $token = $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $this->client->request('POST', '/login', [
            '_username' => 'admin@example.com',
            '_password' => 'password123',
            '_csrf_token' => $token,
        ]);
        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorExists('a[href="/admin/users"]');
    }
}
