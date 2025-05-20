## 🚗 Consommation des véhicules jusqu'à 2009

Permet de consulter les données de consommation en carburant et la classe énergétique d'environ 8500 modèles du parc automobile mondial.  
Recherche multi critères (Marque, type d'énergie, classe énergétique, type de transmission).  
Possibilité de supprimer une entrée de la base.

---

**🧱 Stack technique :**
- PHP 7.4
- Composer 1.10.27
- PhpQuery 0.9.7
- Bootstrap 5
- JQuery 3.7.1

**🚀 Déploiement de l'application Dockerisée :**
- Docker 28.1.1
- Docker Compose 2.35.1

---

### 📦 Installation et utilisation

1. Installer les dépendances PHP :

   ```bash
   php composer.phar install

2. Lancer les conteneurs Docker (depuis la racine du projet) :

   ```bash
   docker compose up --build
   
3. Créer les tables et les remplir avec les données d'exemple :

      ```bash
    http://localhost:8080/seed

4. Accéder à l'application :

      ```bash
    http://localhost:8080

---

## 🧪 Lancer les tests unitaires

Les tests sont écrits avec [PHPUnit 9](https://phpunit.de/).

Dans le conteneur Docker PHP, exécuter la commande suivante depuis la racine du projet :

 ```bash
   docker exec -it auto_conso-php-1 php vendor/phpunit/phpunit/phpunit ./tests

