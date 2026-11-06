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

    private const JEUX = [
        'The Legend of Zelda: Breath of the Wild',
        'Super Mario Odyssey',
        'Grand Theft Auto V',
        'Call of Duty: Modern Warfare',
        'FIFA 21',
        "Assassin's Creed Valhalla",
        'Cyberpunk 2077',
        'Among Us',
        'Elden Ring',
        'The Witcher 3: Wild Hunt',
        'Red Dead Redemption 2',
        'God of War Ragnarök',
        'Spider-Man 2',
        'Horizon Forbidden West',
        'Ghost of Tsushima',
        'Hogwarts Legacy',
        'Baldur\'s Gate 3',
        'Final Fantasy VII Rebirth',
        'Persona 5 Royal',
        'Metroid Dread',
        'Animal Crossing: New Horizons',
        'Mario Kart 8 Deluxe',
        'Super Smash Bros. Ultimate',
        'Splatoon 3',
        'Pokémon Écarlate',
        'Pokémon Légendes Arceus',
        'Kirby et le Monde Oublié',
        'Xenoblade Chronicles 3',
        'Fire Emblem: Three Houses',
        'Starfield',
        'The Elder Scrolls V: Skyrim',
        'Fallout 4',
        'Doom Eternal',
        'Halo Infinite',
        'Forza Horizon 5',
        'Gran Turismo 7',
        'Need for Speed Unbound',
        'Rocket League',
        'Fortnite',
        'Valorant',
        'Minecraft',
        'Stardew Valley',
        'Hades',
        'Celeste',
        'Hollow Knight',
        'Dead Cells',
        'Hi-Fi Rush',
        'Resident Evil 4 Remake',
        'Resident Evil Village',
        'Dead Space',
        'Alan Wake 2',
        'Lies of P',
        'Sekiro: Shadows Die Twice',
        'Dark Souls III',
        'Bloodborne',
        'Death Stranding',
        'Mortal Kombat 1',
        'Tekken 8',
        'Street Fighter 6',
        'Ratchet & Clank: Rift Apart',
        'Sackboy: A Big Adventure',
        'Uncharted 4: A Thief\'s End',
        'The Last of Us Part II',
        'Days Gone',
        'Civilization VI',
        'Cities: Skylines',
        'Microsoft Flight Simulator',
        'Assetto Corsa Competizione',
        'EA Sports FC 24',
        'NBA 2K24',
        'Madden NFL 24',
        'Monster Hunter Rise',
        'Monster Hunter World',
        'Destiny 2',
        'Overwatch 2',
        'League of Legends',
        'Dota 2',
        'Phasmophobia',
        'Lethal Company',
        'Vampire Survivors',
        'Balatro',
        'Cuphead',
        'Ori and the Will of the Wisps',
        'Sea of Thieves',
        'Battlefield 2042',
        'Titanfall 2',
        'Borderlands 3',
        'Dying Light 2',
        'Watch Dogs: Legion',
        'Far Cry 6',
        'Prince of Persia: The Sands of Time',
        'Mass Effect Legendary Edition',
        'Dragon Age: Inquisition',
        'Dragon Quest XI S',
        'Octopath Traveler',
        'Trials Rising',
        'Just Dance 2024',
        'Ring Fit Adventure',
        'Mario Party Superstars',
        'Tetris Effect: Connected',
        'Rayman Legends',
        'It Takes Two',
        'Unravel Two',
        'Untitled Goose Game',
        'Cult of the Lamb',
        'Slay the Spire',
        'Jeu de société : Catan',
        'Jeu de cartes : Magic Arena Pack',
        'Jeu de rôle : Pathfinder Core Rulebook',
        'Puzzle : The Witness',
        'Simulation : Planet Coaster',
        'Stratégie : Age of Empires IV',
        'Course : F1 23',
        'Sport : Rematch',
        'Musique : Rock Band 4',
        'Réalité virtuelle : Beat Saber',
        'Horreur : Layers of Fear',
        'Survie : The Forest',
        'Sandbox : Terraria',
        'Monde ouvert : Ghost Recon Wildlands',
        'MMORPG : Final Fantasy XIV',
        'MMORPG : The Elder Scrolls Online',
        'Tir : Apex Legends',
        'Combat : Street Fighter V',
        'Plateforme : Crash Bandicoot N. Sane Trilogy',
    ];

    public function __construct(
        private UserPasswordHasherInterface $hasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');
        $slugger = new AsciiSlugger();

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
            $jeu->setImage(null);

            $categoriesDuJeu = $faker->randomElements($categoriesArray, 3);
            foreach ($categoriesDuJeu as $categorie) {
                $jeu->addCategory($categorie);
            }

            $manager->persist($jeu);
        }

        $manager->flush();
    }
}