# Pharmacie Oganla — Lots 1 à 4 — révision interfaces et fonctionnalités

PHP natif, structure MVC légère, PDO SQLite en développement et PDO MySQL en production. Administration harmonisée, espace client clair, routage local corrigé.

## Installation locale dans VS Code

Prérequis : PHP 8.2+ en ligne de commande, PDO et pdo_sqlite. Pour l'import Excel : zip et SimpleXML. mbstring est recommandé.

Ubuntu/Debian : installer `php-cli php-sqlite3 php-mbstring php-xml php-zip` avec le gestionnaire de paquets. Sous Windows : localiser `php.ini` avec `php --ini`, activer les extensions `pdo_sqlite`, `sqlite3`, `mbstring`, `zip` et vérifier `php -m`. Si aucun php.ini n'est chargé, copier php.ini-development vers php.ini dans le dossier PHP. Le dossier PHP doit être dans PATH.

Dans le terminal, à la racine du projet :

```sh
php -v
php -m
php bin/init.php
php bin/admin.php admin@pharmacie-olg.com
php -S 127.0.0.1:8000 -t public router.php
```

Choisir un mot de passe local unique de 12 à 72 octets à l'invite (la saisie peut être visible dans le terminal). Aucun compte ou mot de passe par défaut. Ne pas utiliser le mot de passe de production.

- Site public : http://127.0.0.1:8000/
- Administration : http://127.0.0.1:8000/admin
- Connexion client : http://127.0.0.1:8000/connexion
- Inscription : http://127.0.0.1:8000/inscription

La base est créée sous `storage/database/olg.sqlite`, hors du document root. Le schéma est initialisé sans données métier, et aucune garde n’est publiée par défaut. Relancer init ne supprime pas les données. Les anciennes URLs PHP ne sont plus des routes publiques.

Réinitialisation administrateur via terminal autorisé : `php bin/admin.php admin@pharmacie-olg.com reset`. La récupération par email n'est pas encore implémentée ; l'ancienne procédure dangereuse est supprimée. Les sessions admin sont invalidées après changement de mot de passe.

## Import

CSV UTF-8, séparateur virgule ou point-virgule, ou XLSX première feuille. Colonnes dans l’ordre : nom, quantite (stock physique), prix (facultatif). Première ligne en-tête au choix. Aperçu obligatoire, confirmation dans les 15 minutes, 5 000 lignes maximum, 2 Mio. Les lignes invalides ou doublons sont ignorées et comptées. Le prix vide conserve le prix existant. L’import remplace le stock physique et conserve le statut des produits existants. Le bouton + Stock incrémente le disponible sans modifier les autres champs. L’ajout d’un produit existant est refusé.

## Tests

`php tests/unit.php` — tests des helpers et CSV. `php tests/integration.php` — tests PDO SQLite sur une base mémoire. Recette manuelle : connexion admin, ajout, modification, import CSV, recherche publique, désactivation, suppression avec confirmation, déconnexion, inscription client, connexion, espace client. Vérifier que GET sur la suppression renvoie 405 et qu'un POST sans jeton CSRF renvoie 403 après connexion admin. Vérifier que /app/Core/Database.php, /config/database.php et /storage/database/olg.sqlite ne sont pas accessibles.

État de validation : lint PHP, tests unitaires et schéma SQLite vérifié séparément. Les tests d'intégration PHP et MySQL et la recette navigateur restent à exécuter sur un poste équipé des pilotes PDO. Aucune mise en production ni push effectué.

## Production MySQL (phase 3, préparation seulement)

Variables d'environnement du serveur : APP_ENV=production, DB_DRIVER=mysql, DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD. La base doit exister. `database/migrations/mysql.sql` définit le schéma neuf ; il ne s'agit pas d'une migration vérifiée sur la base existante. Ne pas l'appliquer à la production avant sauvegarde et comparaison avec un export de schéma réel. `php bin/init.php` initialise une base de test neuve si l'utilisateur MySQL a les droits nécessaires. Le serveur doit avoir `public/` pour document root, Apache mod_rewrite et AllowOverride appropriés, et HTTPS valide. Les autres dossiers doivent être privés. Si l'hébergeur ne permet pas cette configuration, déplacer la partie privée hors de la racine publique et adapter APP_ROOT avant déploiement. Ne jamais publier la racine entière. Le CSP transitoire autorise les scripts/styles inline existants ; le durcissement par nonce/externalisation reste à faire. Les variables d'environnement ne sont pas chargées automatiquement depuis un fichier .env.

## Lots 3 et 4 — Réservations et gardes

Relancer `php bin/init.php` après installation : les nouvelles tables sont ajoutées sans supprimer les données des lots 1 et 2. Les anciennes réservations historiques restent dans patient_requests ; elles ne sont pas assimilées aux nouvelles réservations ni affichées dans le nouveau workflow. Leur rapprochement éventuel avec la production relève du lot 5.

Réservation : compte client requis, 1 à 100 unités par demande, téléphone, référence aléatoire. Aucun paiement en ligne. Le stock disponible est déduit dès la demande. Confirmation par l’officine, retrait au comptoir, refus ou annulation avec restitution des unités. Expiration 24 h après création (y compris après confirmation), traitée lors des requêtes et via `php bin/expire.php` à planifier en production. Le pharmacien vérifie les conditions de délivrance, notamment les ordonnances si nécessaires. Les clients consultent leurs propres réservations dans /mon-compte. L’administration est accessible sous /admin/reservations.

Inventaire : la colonne quantite représente le stock disponible, hors unités réservées. Le formulaire de modification et l’import attendent le stock physique total (y compris les unités réservées) ; une quantité inférieure aux unités bloquées est refusée. L’action + Stock incrémente le disponible. La suppression d’un médicament lié à une réservation est interdite par la clé étrangère. Pour éviter une correction sur un inventaire ancien, vérifier les retraits avant de synchroniser le fichier de stock.

Gardes : créer une pharmacie une seule fois, programmer des périodes distinctes, annuler une période si besoin. L’historique conserve les gardes annulées et terminées ; publication automatique des périodes en cours. Les dates sont en heure du Bénin. `php bin/init.php` migre les anciennes gardes vers les nouvelles tables, sans effacer les anciennes données. Les anciens drapeaux OLG ne sont plus utilisés.

Utilisateurs : /admin/utilisateurs permet de créer uniquement des comptes administrateurs et de consulter les comptes clients. Les clients créent eux-mêmes leur compte depuis /inscription. Tous les administrateurs existants ont les mêmes droits complets, y compris la création d’autres comptes administrateurs. Aucun nouveau rôle intermédiaire n’est introduit. Ne confier cet accès qu’au personnel autorisé.

Tests complémentaires : `php tests/phase2.php`. Tester les demandes concurrentes, les transitions, les expirations, l’import avec réservations, les accès clients distincts, la garde future/en cours/terminée/masquée et le rendu mobile. Le pilote PDO SQLite manque dans l’environnement de génération ; la recette PHP avec base et MySQL reste à faire. Ne pas déployer avant cette recette. Les anciennes tables de comptes sont conservées pour compatibilité.

## Mise à jour depuis les lots 1 à 4

Arrêter PHP et sauvegarder storage/database/olg.sqlite avant remplacement des fichiers. Conserver .git et storage. Relancer php bin/init.php, puis le serveur. Les comptes existants sont conservés.
