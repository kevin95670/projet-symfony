<?php

namespace App\DataFixtures;
use App\Entity\Categorie;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    public function __construct(
        private UserPasswordHasherInterface $hasher,
    ) {}

    public function load(ObjectManager $manager): void
    {

        $faker = \Faker\Factory::create('fr_FR');

        $categories = ['Action', 'Aventure', 'RPG', 'Simulation', 'Stratégie', 'Sport', 'Course', 'Puzzle', 'Horreur', 'Musique','Combat', 'Plateforme', 'Tir', 'MMORPG', 'Survie', 'Sandbox', 'Réalité virtuelle', 'Jeux de société', 'Jeux de cartes', 'Jeux de rôle','Monde ouvert'];

        $slugger = new AsciiSlugger();

        $categories_array = [];

        foreach ($categories as $nom) {
            $categorie = new Categorie();
            $categorie->setNom($nom);
            $categorie->setSlug($slugger->slug($nom)->lower()->toString());
            $categories_array[] = $categorie;
            $manager->persist($categorie);
        }

        $users = [];

        for($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->email());
            $user->setPassword($this->hasher->hashPassword($user, 'password'));
            $user->setNom($faker->lastName());
            $user->setPrenom($faker->firstName());
            $user->setPseudo($faker->unique()->userName());
            $user->setDateNaissance($faker->dateTimeBetween('-50 years', '-5 years'));
            $user->setTelephone($faker->phoneNumber());
            $manager->persist($user);
            $users[] = $user;
        }

        $jeux = [
            ['titre' => 'The Legend of Zelda: Breath of the Wild', 'description' => $faker->paragraph(3),'prix' => 59.99, 'date_creation' => new \DateTime('2017-03-03'), 'plateforme' => 'Nintendo Switch', 'pegi' => $faker->randomElement(\App\Enum\Pegi::cases()), 'statut' => $faker->randomElement(\App\Enum\JeuStatut::cases()) , 'etat' => $faker->randomElement(\App\Enum\Etat::cases()),'vendeur_id' => $faker->randomElement($users),'image' => 'https://upload.wikimedia.org/wikipedia/en/0/0b/The_Legend_of_Zelda_Breath_of_the_Wild.jpg'],
            ['titre' => 'Super Mario Odyssey', 'description' => $faker->paragraph(3),'prix' => 49.99, 'date_creation' => new \DateTime('2017-10-27'), 'plateforme' => 'Nintendo Switch', 'pegi' => $faker->randomElement(\App\Enum\Pegi::cases()), 'statut' => $faker->randomElement(\App\Enum\JeuStatut::cases()), 'etat' => $faker->randomElement(\App\Enum\Etat::cases()),'vendeur_id' => $faker->randomElement($users), 'image' => 'https://upload.wikimedia.org/wikipedia/en/8/8d/Super_Mario_Odyssey.jpg'],
            ['titre' => 'Grand Theft Auto V', 'description' => $faker->paragraph(3),'prix' => 29.99, 'date_creation' => new \DateTime('2013-09-17'), 'plateforme' => 'PlayStation 4', 'pegi' => $faker->randomElement(\App\Enum\Pegi::cases()), 'statut' => $faker->randomElement(\App\Enum\JeuStatut::cases()), 'etat' => $faker->randomElement(\App\Enum\Etat::cases()),'vendeur_id' => $faker->randomElement($users), 'image' => 'https://upload.wikimedia.org/wikipedia/en/a/a5/Grand_Theft_Auto_V.png'],
            ['titre' => 'Call of Duty: Modern Warfare', 'description' => $faker->paragraph(3),'prix' => 59.99, 'date_creation' => new \DateTime('2019-10-25'), 'plateforme' => 'PlayStation 4', 'pegi' => $faker->randomElement(\App\Enum\Pegi::cases()), 'statut' => $faker->randomElement(\App\Enum\JeuStatut::cases()), 'etat' => $faker->randomElement(\App\Enum\Etat::cases()),'vendeur_id' => $faker->randomElement($users),'image' => 'https://upload.wikimedia.org/wikipedia/en/7/7f/Call_of_Duty_Modern_Warfare_cover_art.jpg'],
            ['titre' => 'FIFA 21', 'description' => $faker->paragraph(3),'prix' => 49.99, 'date_creation' => new \DateTime('2020-10-09'), 'plateforme' => 'PlayStation 4', 'pegi' => $faker->randomElement(\App\Enum\Pegi::cases()), 'statut' => $faker->randomElement(\App\Enum\JeuStatut::cases()), 'etat' => $faker->randomElement(\App\Enum\Etat::cases()),'vendeur_id' => $faker->randomElement($users)],
            ['titre' => 'Assassin\'s Creed Valhalla', 'description' => $faker->paragraph(3),'prix' => 59.99, 'date_creation' => new \DateTime('2020-11-10'), 'plateforme' => 'PlayStation 4', 'pegi' => $faker->randomElement(\App\Enum\Pegi::cases()), 'statut' => $faker->randomElement(\App\Enum\JeuStatut::cases()), 'etat' => $faker->randomElement(\App\Enum\Etat::cases()),'vendeur_id' => $faker->randomElement($users),'image' => 'https://upload.wikimedia.org/wikipedia/en/5/5e/Assassin%27s_Creed_Valhalla_cover_art.jpg'],
            ['titre' => 'Cyberpunk 2077', 'description' => $faker->paragraph(3),'prix' => 59.99, 'date_creation' => new \DateTime('2020-12-10'), 'plateforme' => 'PC', 'pegi' => $faker->randomElement(\App\Enum\Pegi::cases()), 'statut' => $faker->randomElement(\App\Enum\JeuStatut::cases()), 'etat' => $faker->randomElement(\App\Enum\Etat::cases()),'vendeur_id' => $faker->randomElement($users),'image' => 'https://upload.wikimedia.org/wikipedia/en/9/9f/Cyberpunk_2077_box_art.jpg'],
            ['titre' => 'Among Us', 'description' => $faker->paragraph(3),'prix' => 4.99, 'date_creation' => new \DateTime('2018-06-15'), 'plateforme' => 'PC', 'pegi' => $faker->randomElement(\App\Enum\Pegi::cases()), 'statut' => $faker->randomElement(\App\Enum\JeuStatut::cases()), 'etat' => $faker->randomElement(\App\Enum\Etat::cases()),'vendeur_id' => $faker->randomElement($users)],
        ];

        foreach ($jeux as $jeuData) {
            $jeu = new \App\Entity\Jeu();
            $jeu->setTitre($jeuData['titre']);
            $jeu->setDescription($jeuData['description']);
            $jeu->setPrix($jeuData['prix']);
            $jeu->setDateCreation($jeuData['date_creation']);
            $jeu->setPlateforme($jeuData['plateforme']);
            $jeu->setPegi($jeuData['pegi']);
            $jeu->setStatut($jeuData['statut']);
            $jeu->setEtat($jeuData['etat']);
            $jeu->setVendeur($jeuData['vendeur_id']);
            $jeu->setImage($jeuData['image'] ?? null);
            shuffle($categories_array);
            foreach (array_slice($categories_array, 0, 3) as $categorie) {
                $jeu->addCategory($categorie);
            }
            $manager->persist($jeu);
        }

        $manager->flush();
    }
}
