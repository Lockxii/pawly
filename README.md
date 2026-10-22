# Pawly

> Projet d’examen Symfony : application de gestion d’un refuge animalier.

## Stack

- PHP 8.4.1 ou supérieur et Composer 2.
- Symfony 8.1, Doctrine ORM et Twig.
- MySQL 8.4 et Symfony CLI.
- PHPUnit, avec l’extension `pdo_sqlite` pour les tests.

## Installation

### 1 - Récupérer le projet

```bash
git clone --branch develop https://github.com/Lockxii/pawly.git
cd pawly
symfony composer install
```

### 2 - Configurer la base

Le serveur MySQL doit être lancé et l’extension PHP `pdo_mysql` activée. Adapter le port à votre installation (3307 sur le poste de développement).
Créer `.env.local` avec vos identifiants de connexion :

```dotenv
APP_SECRET=REMPLACER_PAR_UNE_VALEUR_ALEATOIRE
DATABASE_URL="mysql://UTILISATEUR:MOT_DE_PASSE@127.0.0.1:3307/pawly?serverVersion=8.4.3&charset=utf8mb4"
```

Générer la valeur de `APP_SECRET` avec :

```bash
symfony php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

```bash
symfony console doctrine:database:create --if-not-exists
```

Appliquer les migrations livrées avec le projet :

```bash
symfony console doctrine:migrations:migrate
```

Après une modification des entités, générer une nouvelle migration avec `symfony console make:migration` et la versionner. `.env.local` reste local et ne doit pas être commité.

### 3 - Charger les données de démonstration

```bash
symfony console doctrine:fixtures:load
```

Cette commande vide la base avant de charger les fixtures : 6 chiens, 3 demandes d’adoption et les comptes suivants.

| Email | Rôle |
| --- | --- |
| admin@example.com | ROLE_ADMIN |
| membre@example.com | ROLE_EMPLOYEE |
| user@example.com | ROLE_USER |

Mot de passe de démonstration : `password123`. Les mots de passe sont stockés hachés.

## Lancer le projet

```bash
symfony serve -d
```

Ouvrir l’adresse affichée par Symfony CLI.

- `/` : animaux disponibles à l’adoption.
- `/dogs/{id}` : fiche détaillée d’un animal.
- `/dogs/creator` : création d’un animal, réservée aux membres et administrateurs.
- `/dogs/modify/{id}` : modification des informations, du statut et de la photo.
- `/animals` : liste de tous les animaux, réservée aux membres et administrateurs.
- `/login`, `/register` : connexion et inscription. La déconnexion se fait depuis le menu.
- `/dogs/{id}/adopt` : demande d’adoption accessible aux visiteurs, sans compte obligatoire.
- `/adoptions` : suivi des demandes et décisions, réservé aux membres et administrateurs.
- `/admin/users` : modification des utilisateurs et de leurs rôles, réservée aux administrateurs.
- `/health-check` : contrôle de fonctionnement.

Les photographies sont facultatives, enregistrées dans `public/uploads` et limitées à 5 Mo (JPEG, PNG, WebP ou GIF).

La recherche porte sur le nom, la race et l’espèce. L’accueil affiche uniquement les animaux disponibles ; les membres peuvent aussi filtrer tous les animaux par statut depuis `/animals`.

Une demande est en attente, acceptée ou refusée. L’acceptation place l’animal en cours d’adoption ; un membre peut ensuite le marquer adopté depuis sa fiche. Une demande traitée ne peut plus être modifiée.

## Tests

```bash
symfony php bin/phpunit
symfony php bin/phpunit --testsuite Unit
symfony php bin/phpunit --testsuite Functional
```

Les tests fonctionnels utilisent SQLite en mémoire et ne modifient pas la base MySQL.
Sous Windows, si `pdo_sqlite` est installée mais désactivée :

```bash
symfony php -d extension=pdo_sqlite bin/phpunit
```

## Organisation

Les fonctionnalités sont développées sur les branches `feature/*`, puis intégrées à `develop` par pull request. La version validée est ensuite intégrée à `main`.

## Droits d’accès

Les visiteurs consultent les animaux et déposent des demandes. Les membres gèrent les animaux et traitent les demandes. Les administrateurs disposent aussi de la gestion des utilisateurs. Les formulaires sont protégés par un jeton CSRF et l’inscription attribue uniquement le rôle utilisateur.
