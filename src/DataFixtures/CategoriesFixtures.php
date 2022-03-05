<?php

namespace App\DataFixtures;

use App\Entity\Categories;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoriesFixtures extends Fixture
{

    // permet de compter les éléments pour les références
    private $counter = 1;

    // Constructeur pour injection du slugger
    public function __construct(private SluggerInterface $slugger)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // catégorie parent
        $parent = $this->createCategorie('Informatique', null, $manager);
        // Sous-catégories
        $this->createCategorie('Ordinateurs portables', $parent, $manager);
        $this->createCategorie('Ecrans', $parent, $manager);
        $this->createCategorie('Souris', $parent, $manager);

        // catégorie parent
        $parent1 = $this->createCategorie('Mode', null, $manager);
        // Sous-catégories
        $this->createCategorie('Homme', $parent1, $manager);
        $this->createCategorie('Femme', $parent1, $manager);
        $this->createCategorie('Enfant', $parent1, $manager);

        $manager->flush();
    }

    public function createCategorie(string $name, Categories $parent = null, ObjectManager $manager)
    {
        $categorie = new Categories();
        $categorie->setName($name);
        $categorie->setSlug($this->slugger->slug($categorie->getName())->lower());
        $categorie->setParent($parent);
        $manager->persist($categorie);

        // mise en mémoire de la référence
        $this->addReference('cat-' . $this->counter, $categorie);
        // incrémente le compteur
        $this->counter++;

        return $categorie;
    }
}
