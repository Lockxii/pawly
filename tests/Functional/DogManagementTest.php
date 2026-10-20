<?php

namespace App\Tests\Functional;

use App\Entity\Dogs;
use App\Service\FileUploaderService;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\String\Slugger\AsciiSlugger;

class DogManagementTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManager $manager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $config = ORMSetup::createAttributeMetadataConfig([dirname(__DIR__, 2).'/src/Entity'], true);
        $config->enableNativeLazyObjects(true);
        $this->manager = new EntityManager(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            $config,
        );
        (new SchemaTool($this->manager))->createSchema([$this->manager->getClassMetadata(Dogs::class)]);
        static::getContainer()->set('doctrine.orm.default_entity_manager', $this->manager);
    }

    protected function tearDown(): void
    {
        $this->manager->close();
        $this->manager->getConnection()->close();
        parent::tearDown();
    }

    public function testVisitorCannotCreate(): void
    {
        $this->client->request('GET', '/dogs/creator');
        self::assertResponseRedirects('/login');
    }

    public function testUserCannotCreate(): void
    {
        $this->login('ROLE_USER');
        $this->client->request('GET', '/dogs/creator');
        self::assertResponseStatusCodeSame(403);
    }

    public function testEmployeeCanCreateAndModify(): void
    {
        $this->login('ROLE_EMPLOYEE');
        $this->client->request('GET', '/dogs/creator');
        self::assertResponseIsSuccessful();
        $this->client->submitForm('Ajouter l’animal', [
            'dog_form[Nom]' => 'Rex',
            'dog_form[espece]' => 'Chien',
            'dog_form[race]' => 'Croise',
            'dog_form[age]' => '3',
            'dog_form[description]' => 'Un chien calme.',
            'dog_form[status]' => 'Disponible',
        ]);
        self::assertResponseRedirects('/dogs/1');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.detail-content h1', 'Rex');
        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href="/dogs/1"]');
        $dog = $this->manager->find(Dogs::class, 1);
        self::assertSame('Rex', $dog->getNom());

        $dog->setCoverImage('photo-existante.jpg');
        $this->manager->flush();
        $this->client->request('GET', '/dogs/modify/1');
        self::assertResponseIsSuccessful();
        $this->client->submitForm('Enregistrer les modifications', [
            'dog_form[Nom]' => 'Rex modifie',
            'dog_form[status]' => 'Adopte',
        ]);
        self::assertResponseRedirects('/dogs/1');
        $this->manager->clear();
        $dog = $this->manager->find(Dogs::class, 1);
        self::assertSame('Rex modifie', $dog->getNom());
        self::assertSame('Adopte', $dog->getStatus());
        self::assertSame('photo-existante.jpg', $dog->getCoverImage());

        $this->client->request('GET', '/dogs/modify/999');
        self::assertResponseStatusCodeSame(404);
    }

    public function testInvalidDataAndCsrfAreRejected(): void
    {
        $this->login('ROLE_EMPLOYEE');
        $this->client->request('GET', '/dogs/creator');
        $this->client->submitForm('Ajouter l’animal', [
            'dog_form[Nom]' => '',
            'dog_form[age]' => '-1',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.animal-form ul li');
        self::assertSame(0, $this->manager->getConnection()->fetchOne('SELECT COUNT(*) FROM Dogs'));

        $this->client->request('POST', '/dogs/creator', ['dog_form' => [
            'Nom' => 'Rex', 'espece' => 'Chien', 'race' => 'Croise', 'age' => 3,
            'description' => 'Calme', 'status' => 'Disponible', '_token' => 'invalide',
        ]]);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.animal-form ul li');
        self::assertSame(0, $this->manager->getConnection()->fetchOne('SELECT COUNT(*) FROM Dogs'));
    }

    public function testPhotosAreValidatedAndDoNotOverwriteEachOther(): void
    {
        $this->login('ROLE_EMPLOYEE');
        $directory = dirname(__DIR__, 2).'/var/dog-upload-test-'.bin2hex(random_bytes(4));
        mkdir($directory);
        static::getContainer()->set(FileUploaderService::class, new FileUploaderService($directory, new AsciiSlugger()));

        try {
            $filenames = [];
            foreach (['not an image', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7')] as $image) {
                $crawler = $this->client->request('GET', '/dogs/creator');
                $source = $directory.'/fixture.gif';
                file_put_contents($source, $image);
                $this->client->request('POST', '/dogs/creator', ['dog_form' => [
                    'Nom' => 'Rex', 'espece' => 'Chien', 'race' => 'Croise', 'age' => 3,
                    'description' => 'Calme', 'status' => 'Disponible',
                    '_token' => $crawler->filter('input[name="dog_form[_token]"]')->attr('value'),
                ]], ['dog_form' => ['coverImage' => new UploadedFile($source, 'photo.gif', null, null, true)]]);

                if ($image === 'not an image') {
                    self::assertResponseIsSuccessful();
                    self::assertSelectorExists('.animal-form ul li');
                    self::assertSame(0, $this->manager->getConnection()->fetchOne('SELECT COUNT(*) FROM Dogs'));
                } else {
                    self::assertResponseRedirects('/dogs/'.(count($filenames) + 1));
                    $dog = $this->manager->find(Dogs::class, count($filenames) + 1);
                    $filenames[] = $dog->getCoverImage();
                    self::assertFileExists($directory.'/'.$dog->getCoverImage());
                }
            }

            self::assertNotSame($filenames[0], $filenames[1]);
            self::assertFileExists($directory.'/'.$filenames[0]);
        } finally {
            foreach (glob($directory.'/*') as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }

    private function login(string $role): void
    {
        $provider = new InMemoryUserProvider(['test' => ['roles' => [$role]]]);
        static::getContainer()->set('security.user.provider.concrete.user_providers', $provider);
        $this->client->loginUser($provider->loadUserByIdentifier('test'));
    }
}
