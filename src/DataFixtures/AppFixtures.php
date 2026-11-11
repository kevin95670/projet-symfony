<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use App\Entity\Jeu;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const NB_USERS = 30;

    private const PRIX_POSSIBLES = [4.99, 9.99, 14.99, 19.99, 24.99, 29.99, 34.99, 39.99, 44.99, 49.99, 54.99, 59.99, 69.99];

    // Seuls les jeux qui ont une image dans images.json
    private const JEUX = [
        'Animal Crossing: New Horizons',
        "Assassin's Creed Valhalla",
        'Among Us',
        'Balatro',
        'Battlefield 2042',
        'Baldur\'s Gate 3',
        'Borderlands 3',
        'Celeste',
        'Cuphead',
        'Cyberpunk 2077',
        'Dead Cells',
        'Doom Eternal',
        'Dying Light 2',
        'Elden Ring',
        'Fallout 4',
        'Final Fantasy VII Rebirth',
        'Fire Emblem: Three Houses',
        'Fortnite',
        'Forza Horizon 5',
        'Ghost of Tsushima',
        'God of War Ragnarök',
        'Gran Turismo 7',
        'Grand Theft Auto V',
        'Hades',
        'Halo Infinite',
        'Hi-Fi Rush',
        'Hogwarts Legacy',
        'Hollow Knight',
        'Horizon Forbidden West',
        'Kirby et le Monde Oublié',
        'Pokémon Légendes Arceus',
        'Super Mario Odyssey',
        'Metroid Dread',
        'Minecraft',
        'Mario Kart 8 Deluxe',
        'Call of Duty: Modern Warfare',
        'Need for Speed Unbound',
        'Ori and the Will of the Wisps',
        'Persona 5 Royal',
        'Pokémon Écarlate',
        'Red Dead Redemption 2',
        'Rocket League',
        'The Elder Scrolls V: Skyrim',
        'Super Smash Bros. Ultimate',
        'Sea of Thieves',
        'Spider-Man 2',
        'Splatoon 3',
        'Stardew Valley',
        'Starfield',
        'Titanfall 2',
        'Valorant',
        'Vampire Survivors',
        'Watch Dogs: Legion',
        'The Witcher 3: Wild Hunt',
        'Xenoblade Chronicles 3',
        'The Legend of Zelda: Breath of the Wild',
    ];

    public function __construct(
        private UserPasswordHasherInterface $hasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');
        $slugger = new AsciiSlugger();
        // Images des jeux, saisies une fois dans images.json (titre => URL)
        $images = json_decode(file_get_contents(__DIR__ . '/images.json'), true) ?? [];

        $categories = ['Action', 'Aventure', 'RPG', 'Simulation', 'Stratégie', 'Sport', 'Course', 'Puzzle', 'Horreur', 'Musique', 'Combat', 'Plateforme', 'Tir', 'MMORPG', 'Survie', 'Sandbox', 'Réalité virtuelle', 'Jeux de société', 'Jeux de cartes', 'Jeux de rôle', 'Monde ouvert'];

        $categoriesArray = [];
        foreach ($categories as $nom) {
            $categorie = new Categorie();
            $categorie->setNom($nom);
            $categorie->setSlug($slugger->slug($nom)->lower()->toString());
            $categoriesArray[] = $categorie;
            $manager->persist($categorie);
        }

        $users = [];
        for ($i = 0; $i < self::NB_USERS; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->email());
            $user->setPassword($this->hasher->hashPassword($user, 'password'));
            $user->setNom($faker->lastName());
            $user->setPrenom($faker->firstName());
            $user->setPseudo($faker->unique()->userName());
            $user->setDateNaissance($faker->dateTimeBetween('-50 years', '-18 years'));
            $user->setTelephone($faker->phoneNumber());
            $user->setDateCreation($faker->dateTimeBetween('-5 years', 'now'));
            $manager->persist($user);
            $users[] = $user;
        }

        foreach (self::JEUX as $titre) {
            $jeu = new Jeu();
            $jeu->setTitre($titre);
            $jeu->setDescription($faker->text(200));
            $jeu->setPrix($faker->randomElement(self::PRIX_POSSIBLES));
            $jeu->setSlug($slugger->slug($titre)->lower()->toString());
            $jeu->setDateCreation($faker->dateTimeBetween('-8 years', 'now'));
            $jeu->setPlateforme($faker->randomElement(\App\Enum\Plateforme::cases()));
            $jeu->setPegi($faker->randomElement(\App\Enum\Pegi::cases()));
            $jeu->setStatut($faker->randomElement(\App\Enum\JeuStatut::cases()));
            $jeu->setEtat($faker->randomElement(\App\Enum\Etat::cases()));
            $jeu->setVendeur($faker->randomElement($users));
            $jeu->setImage($images[$titre] ?? null);

            $categoriesDuJeu = $faker->randomElements($categoriesArray, 3);
            foreach ($categoriesDuJeu as $categorie) {
                $jeu->addCategory($categorie);
            }

            $manager->persist($jeu);
        }

        $manager->flush();
    }
}