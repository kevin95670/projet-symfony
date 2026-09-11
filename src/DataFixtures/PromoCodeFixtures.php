<?php

namespace App\DataFixtures;

use App\Entity\PromoCode;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class PromoCodeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $codes = ['PROMO10', 'PROMO20', 'NOEL15', 'SUMMER25'];
        $pourcentages = [10, 15, 20, 25];

        foreach ($codes as $key => $code) {
            $promo = new PromoCode();
            $promo->setCode($code);
            $promo->setPourcentage($pourcentages[$key]);
            $promo->setDateExpiration(
                \DateTime::createFromFormat('Y-m-d', $faker->dateTimeBetween('+1 days', '+1 year')->format('Y-m-d'))
            );
            $promo->setActif(true);

            $manager->persist($promo);
        }

        $manager->flush();
    }
}