<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const USER_RAYMUNDO = 'user-raymundo';
    public const USER_CARLOS = 'user-carlos';
    public const USER_ARMANDO = 'user-armando';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Usuario 1: Raymundo Salazar
        $raymundo = new User();
        $raymundo
            ->setClerkUserId('user_36nPERXSExq2TITjdFGDSey8e4e')
            ->setEmail('hello@raymundosalazar.dev')
            ->setName('Raymundo')
            ->setLastName('Salazar')
            ->setRoles(['ROLE_USER']);

        $raymundo->setPassword(
            $this->passwordHasher->hashPassword($raymundo, 'password123')
        );

        $manager->persist($raymundo);
        $this->addReference(self::USER_RAYMUNDO, $raymundo);

        // Usuario 2: Carlos Mendoza
        $carlos = new User();
        $carlos
            ->setClerkUserId('user_36np26Z9ac0zZwPIwEZf9m6fcU9')
            ->setEmail('carlos.mendoza@example.com')
            ->setName('Carlos')
            ->setLastName('Mendoza')
            ->setRoles(['ROLE_USER']);

        $carlos->setPassword(
            $this->passwordHasher->hashPassword($carlos, 'password123')
        );

        $manager->persist($carlos);
        $this->addReference(self::USER_CARLOS, $carlos);

        // Usuario 3: Armando Salazar
        $armando = new User();
        $armando
            ->setClerkUserId('user_36npDZuBTgXPYwd9z51KHdtmiNa')
            ->setEmail('armando.salazar@example.com')
            ->setName('Armando')
            ->setLastName('Salazar')
            ->setRoles(['ROLE_USER']);

        $armando->setPassword(
            $this->passwordHasher->hashPassword($armando, 'password123')
        );

        $manager->persist($armando);
        $this->addReference(self::USER_ARMANDO, $armando);

        $manager->flush();
    }
}
