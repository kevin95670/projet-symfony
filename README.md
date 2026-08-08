# 🎮 Marketplace de jeux vidéo

Projet personnel d'apprentissage : une marketplace C2C permettant aux utilisateurs d'acheter et de vendre des jeux vidéo d'occasion.

> Projet fictif à but pédagogique — aucune transaction réelle.

---

## 🎯 Objectifs d'apprentissage

- Maîtriser **Symfony** (Doctrine, Security, formulaires, services)
- Mettre en place un environnement de développement **Docker** complet
- Écrire des **tests automatisés** avec PHPUnit
- Construire un pipeline **CI/CD** avec GitHub Actions
- Déployer sur un environnement de **pré-production**
- Découvrir **Tailwind CSS**

---

## 🛠️ Stack technique

| Domaine | Technologie |
|---|---|
| Backend | Symfony |
| Templates | Twig |
| CSS | Tailwind CSS (via TailwindBundle) |
| JavaScript | jQuery + AJAX |
| Base de données | MariaDB |
| Environnement local | Docker / Docker Compose |
| Tests | PHPUnit |
| CI/CD | GitHub Actions |
| Pré-production | Railway |

---

## 🚀 Installation

### Prérequis

- Docker et Docker Compose installés

### Démarrage

```bash
# Cloner le projet
git clone <url-du-repo>
cd <nom-du-projet>

# Créer le fichier d'environnement
cp .env.example .env
# Renseigner DB_ROOT_PASSWORD dans .env

# Lancer les conteneurs
docker compose up --build
```

### Accès

| Service | URL |
|---|---|
| Application | http://localhost:8080 |
| phpMyAdmin | http://localhost:8082 |
| MariaDB | localhost:3306 |

### Commandes utiles

```bash
# Entrer dans le conteneur PHP
docker compose exec php bash

# Installer les dépendances
docker compose exec php composer install

# Lancer les migrations
docker compose exec php php bin/console doctrine:migrations:migrate

# Charger les fixtures (données de test)
docker compose exec php php bin/console doctrine:fixtures:load

# Compiler Tailwind en mode watch
docker compose exec php php bin/console tailwind:build --watch

# Lancer les tests
docker compose exec php php bin/phpunit
```

---

## 📦 Fonctionnalités

### Phase 1 — Boutique

- [ ] Page d'accueil avec slider des nouveautés
- [ ] Listing des jeux (cards, tri par prix, pagination)
- [ ] Fiche produit détaillée (note moyenne, avis)
- [ ] Barre de recherche
- [ ] Inscription / connexion
- [ ] Panier (session si non connecté, base si connecté, avec fusion à la connexion)
- [ ] Passage de commande
- [ ] Restriction d'accès à certaines pages si non connecté

### Phase 2 — Marketplace

- [ ] Formulaire de mise en vente d'un jeu
- [ ] Page « Mes ventes » (statut : en vente / vendu)
- [ ] Page « Mes achats » (historique des commandes)
- [ ] Page « Ma wishlist »
- [ ] Système d'avis sur les jeux

### Phase 3 — Communauté

- [ ] Forum avec catégories
- [ ] Modération des annonces (back-office admin)

---

## 🗂️ Modèle de données

```
User ──< Jeu (vendeur)
User ──< Avis >── Jeu
User ──< PanierItem >── Jeu
User ──< Commande ──< LigneCommande >── Jeu
User ──< Wishlist >── Jeu
Jeu >── Categorie
```

**Note :** l'entité `Jeu` porte un champ `statut` (`en_vente`, `vendu`, `retire`) afin de préparer l'ajout de la modération en phase 3 sans migration structurelle lourde.

---

## 📋 Feuille de route technique

### Mise en place

1. ✅ Environnement Docker (PHP + Apache + MariaDB + phpMyAdmin)
2. ✅ Installation Symfony + page de test
3. ⬜ Initialisation Git + repo GitHub
4. ⬜ Environnement de pré-production sur Railway
5. ⬜ Pipeline CI/CD (déploiement automatique)
6. ⬜ Développement des fonctionnalités
7. ⬜ Tests PHPUnit au fil du développement
8. ⬜ Enrichissement du CI/CD (tests avant déploiement)

### Ordre de développement Symfony

1. Entités et relations Doctrine
2. Migrations
3. Fixtures (jeu de données de test)
4. Authentification (préalable au panier)
5. Controllers et templates
6. Formulaires
7. Logique du panier
8. Tests

---

## 🧪 Tests

Le projet vise une couverture sur deux niveaux :

- **Tests unitaires** — logique métier isolée (calcul du total du panier, fusion des paniers session/base, règles de validation des avis)
- **Tests fonctionnels** — parcours utilisateur complet (restriction d'accès, ajout au panier, passage de commande)

---

## 📄 Licence

Projet personnel à but pédagogique.
