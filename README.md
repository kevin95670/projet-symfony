# Replay

Replay est un site de vente de jeux vidéo d'occasion entre particuliers. Vous pouvez mettre en vente vos jeux, en acheter, les ajouter à votre liste de souhaits, laisser un avis après réception et poser des questions au vendeur.

> Replay est un site fictif, réalisé dans le cadre d'un projet personnel et à des fins de démonstration. Les informations légales (société, SIRET, etc.) sont inventées.

## Fonctionnalités

- Mise en vente d'un jeu, avec photo, prix, plateforme, état et PEGI
- Recherche et filtres (catégorie, plateforme, état, prix, PEGI)
- Panier et achat via Stripe (en mode test)
- Liste de souhaits
- Alertes lorsque le prix d'un jeu baisse ou monte, ou lorsqu'il revient en stock
- Avis après réception d'une commande
- Questions posées au vendeur sur chaque annonce
- Signalement d'une annonce, avec suspension par l'administrateur
- Tableau de bord pour suivre vos ventes

## Technologies

- PHP 8.3 et Symfony 7.4
- Doctrine ORM et migrations
- Twig et Tailwind CSS
- Stripe (mode test uniquement)
- Docker pour l'environnement local
- PHPUnit pour les tests

## Installation en local

Il vous faut Docker et Docker Compose.

```bash
git clone <url-du-depot> replay
cd replay
cp .env .env.local
```

Dans `.env.local`, renseignez au minimum la base de données et les clés Stripe de test (`STRIPE_SECRET_KEY` et `STRIPE_WEBHOOK_SECRET`).

Lancez ensuite les conteneurs et préparez la base :

```bash
docker compose up -d
docker compose exec php php bin/console doctrine:migrations:migrate
docker compose exec php php bin/console importmap:install
```

Le site est alors accessible à l'adresse définie dans votre configuration Docker.

## Tests

Pour lancer les tests de paiement :

```bash
docker compose exec php php bin/phpunit
```

Les fichiers de test se trouvent dans le dossier `tests/`. Les variables Stripe utilisées pour les tests sont dans `.env.test`, avec des valeurs fictives.

## Mise en production

Chaque envoi sur la branche `master` déclenche un déploiement automatique avec GitHub Actions. Le code est transféré sur l'hébergement, les migrations sont appliquées et le cache est reconstruit. Les clés et les accès au serveur sont stockés dans les secrets du dépôt, jamais dans le code.

## Licence

Projet personnel, à des fins de démonstration. Aucune utilisation commerciale.

# Replay

Replay est un site de vente de jeux vidéo d'occasion entre particuliers. Vous pouvez mettre en vente vos jeux, en acheter, les ajouter à votre liste de souhaits, laisser un avis après réception et poser des questions au vendeur.

> Replay est un site fictif, réalisé dans le cadre d'un projet personnel et à des fins de démonstration. Les informations légales (société, SIRET, etc.) sont inventées.

## Fonctionnalités

- Mise en vente d'un jeu, avec photo, prix, plateforme, état et PEGI
- Recherche et filtres (catégorie, plateforme, état, prix, PEGI)
- Panier et achat via Stripe (en mode test)
- Liste de souhaits
- Alertes lorsque le prix d'un jeu baisse ou monte, ou lorsqu'il revient en stock
- Avis après réception d'une commande
- Questions posées au vendeur sur chaque annonce
- Signalement d'une annonce, avec suspension par l'administrateur
- Tableau de bord pour suivre vos ventes

## Technologies

- PHP 8.3 et Symfony 7.4
- Doctrine ORM et migrations
- Twig et Tailwind CSS
- Stripe (mode test uniquement)
- Docker pour l'environnement local
- PHPUnit pour les tests

## Installation en local

Il vous faut Docker et Docker Compose.

```bash
git clone <url-du-depot> replay
cd replay
cp .env .env.local
```

Dans `.env.local`, renseignez au minimum la base de données et les clés Stripe de test (`STRIPE_SECRET_KEY` et `STRIPE_WEBHOOK_SECRET`).

Lancez ensuite les conteneurs et préparez la base :

```bash
docker compose up -d
docker compose exec php php bin/console doctrine:migrations:migrate
docker compose exec php php bin/console importmap:install
```

Le site est alors accessible à l'adresse définie dans votre configuration Docker.

## Tests

Pour lancer les tests de paiement :

```bash
docker compose exec php php bin/phpunit
```

Les fichiers de test se trouvent dans le dossier `tests/`. Les variables Stripe utilisées pour les tests sont dans `.env.test`, avec des valeurs fictives.

## Mise en production

Chaque envoi sur la branche `master` déclenche un déploiement automatique avec GitHub Actions. Le code est transféré sur l'hébergement, les migrations sont appliquées et le cache est reconstruit. Les clés et les accès au serveur sont stockés dans les secrets du dépôt, jamais dans le code.

## Licence

Projet personnel, à des fins de démonstration. Aucune utilisation commerciale.

