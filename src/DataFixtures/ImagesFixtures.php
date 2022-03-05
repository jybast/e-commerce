<?php

namespace App\DataFixtures;

use Faker;

use App\Entity\Images;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImagesFixtures extends Fixture implements DependentFixtureInterface
{

    /**
     * Constructeur slugs
     */
    public function __construct(private SluggerInterface $slugger)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($img = 1; $img <= 100; $img++) {
            $image = new Images();
            $image->setName($faker->image(null, 640, 480));
            // On va chercher la référence du produit
            $product = $this->getReference('prod-' . rand(1, 10));
            // on set le produit
            $image->setProducts($product);

            $manager->persist($image);
        }

        $manager->flush();
    }

    /**
     * Fonction requise par l'implementation de DependantFixtureInterface
     */
    public function getDependencies(): array
    {
        // renvoie un tableau des fixtures qui doivent être exécutées avant ImagesFixtures
        return [
            ProductsFixtures::class
        ];
    }
}
