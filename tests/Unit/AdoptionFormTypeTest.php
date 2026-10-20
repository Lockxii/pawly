<?php

namespace App\Tests\Unit;

use App\Entity\Adoption;
use App\Enum\AdoptionStatus;
use App\Form\AdoptionFormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class AdoptionFormTypeTest extends FormIntegrationTestCase
{
    protected function getExtensions(): array
    {
        return [new ValidatorExtension(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator())];
    }

    public function testValidContactDetailsCreatePendingRequest(): void
    {
        $adoption = new Adoption();
        $form = $this->factory->create(AdoptionFormType::class, $adoption);
        $form->submit(['name' => 'Alice', 'email' => 'Alice@example.com', 'message' => 'Je souhaite rencontrer Rex.']);

        self::assertTrue($form->isValid());
        self::assertSame('alice@example.com', $adoption->getEmail());
        self::assertSame(AdoptionStatus::Pending, $adoption->getStatus());
    }

    public function testInvalidEmailAndEmptyMessageAreRejected(): void
    {
        $form = $this->factory->create(AdoptionFormType::class, new Adoption());
        $form->submit(['name' => 'Alice', 'email' => 'invalide', 'message' => '']);

        self::assertFalse($form->isValid());
        self::assertGreaterThan(0, $form->get('email')->getErrors()->count());
        self::assertGreaterThan(0, $form->get('message')->getErrors()->count());
    }

    public function testVisitorCannotSetRequestStatus(): void
    {
        $adoption = new Adoption();
        $form = $this->factory->create(AdoptionFormType::class, $adoption);
        $form->submit(['name' => 'Alice', 'email' => 'alice@example.com', 'message' => 'Bonjour', 'status' => 'Acceptée']);

        self::assertFalse($form->isValid());
        self::assertSame(AdoptionStatus::Pending, $adoption->getStatus());
    }
}
