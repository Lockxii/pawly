<?php

namespace App\Tests\Unit;

use App\Entity\Dogs;
use App\Form\DogFormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class DogFormTypeTest extends FormIntegrationTestCase
{
    protected function getExtensions(): array
    {
        return [new ValidatorExtension(Validation::createValidator())];
    }

    public function testValidDataUpdatesDogWithoutPhoto(): void
    {
        $dog = new Dogs();
        $form = $this->factory->create(DogFormType::class, $dog, ['csrf_protection' => false]);
        $form->submit($this->validData());

        self::assertTrue($form->isValid());
        self::assertSame('Rex', $dog->getNom());
        self::assertSame(3, $dog->getAge());
        self::assertSame('Disponible', $dog->getStatus());
        self::assertNull($dog->getCoverImage());
    }

    public function testNegativeAgeIsRejected(): void
    {
        $data = $this->validData();
        $data['age'] = '-1';
        $form = $this->factory->create(DogFormType::class, new Dogs(), ['csrf_protection' => false]);
        $form->submit($data);

        self::assertFalse($form->isValid());
        self::assertGreaterThan(0, $form->get('age')->getErrors()->count());
    }

    public function testUnknownStatusIsRejected(): void
    {
        $data = $this->validData();
        $data['status'] = 'inconnu';
        $form = $this->factory->create(DogFormType::class, new Dogs(), ['csrf_protection' => false]);
        $form->submit($data);

        self::assertFalse($form->isValid());
        self::assertGreaterThan(0, $form->get('status')->getErrors()->count());
    }

    public function testMissingNameIsRejected(): void
    {
        $data = $this->validData();
        $data['Nom'] = '';
        $form = $this->factory->create(DogFormType::class, new Dogs(), ['csrf_protection' => false]);
        $form->submit($data);

        self::assertFalse($form->isValid());
        self::assertGreaterThan(0, $form->get('Nom')->getErrors()->count());
    }

    private function validData(): array
    {
        return [
            'Nom' => 'Rex',
            'espece' => 'Chien',
            'race' => 'Croise',
            'age' => '3',
            'description' => 'Un chien calme.',
            'status' => 'Disponible',
        ];
    }
}
