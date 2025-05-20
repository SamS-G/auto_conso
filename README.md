## Consommation des véhicules jusqu'à 2009

Permet de consulter les données de consommation en carburant et la classe énergétique d'environ 8500 modèles du parc automobile mondial.
Recherche multi critères (Marque, type d'énergie, classe énergétique, type de transmission).
Possibilité de supprimer une entrée de la base.

**Stack :**
- PHP 7.4
- Composer 1.10.27
- PhpQuery 0.9.7
- Bootstrap 5
- JQuery 3.7.1

**Déploiement de l'application "dockerisée" :**
- 
- Docker 28.1.1
- Docker Compose 2.35.1

1. À la racine du projet exécuter : `docker compose up --build` 
2. Exécuter `php composer.phar install` pour installer les dépendances
3. Créer les tables et les peupler : `localhost:8080/seed`
4. Accéder à l'application : `localhost:8080`
