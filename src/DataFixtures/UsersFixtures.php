<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Faker;

class UsersFixtures extends Fixture
{

    /**
     * Constructeur pour hashage et slugs
     */
    public function __construct(private UserPasswordHasherInterface $passwwordEncoder, private SluggerInterface $slugger)
    {
    }

    /**
     * Fixtures
     */
    public function load(ObjectManager $manager): void
    {
        // Administrateur
        $admin = new User();
        $admin->setEmail('admin@demo.fr');
        $admin->setFirstname('Benoît');
        $admin->setlastname('Lepape');
        $admin->setAddress('10, rue des moines');
        $admin->setZipcode('45000');
        $admin->setCity('Orléans');
        $admin->setPassword(
            $this->passwwordEncoder->hashPassword($admin, 'admin')
        );
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        // Utilisateurs avec Faker
        $faker = Faker\Factory::create('fr_FR');
        // boucle pour 5 users
        for ($usr = 1; $usr <= 5; $usr++) {
            $user = new User();
            $user->setEmail($faker->email);
            $user->setFirstname($faker->firstname);
            $user->setlastname($faker->lastname);
            $user->setAddress($faker->streetAddress);
            $user->setZipcode(str_replace(' ', '', $faker->postcode));
            $user->setCity($faker->city);
            $user->setPassword(
                $this->passwwordEncoder->hashPassword($user, 'secret')
            );
            $user->setRoles(['ROLE_USER']);
            $manager->persist($user);
        }
        $manager->flush();
    }
}
