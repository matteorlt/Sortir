# 🚀 Sorties Entre Collègues - Application Web Symfony

Une application web en **PHP Symfony** permettant d’organiser facilement des sorties entre collègues.  
Chaque utilisateur peut créer des événements, s’inscrire à ceux de ses collègues, et discuter dans un espace dédié.

---

## 📋 Fonctionnalités principales

- **Création d’événements** : date, lieu, description, nombre de participants.
- **Inscription aux sorties** et gestion des places disponibles.
- **Messagerie intégrée** pour échanger entre participants.
- **Filtre & recherche** des sorties par date, lieu ou mot-clé.
- **Espace personnel** pour consulter et gérer ses sorties.
- **Système d’authentification sécurisé** (inscription / connexion).

---

## 🛠️ Technologies utilisées

- **Backend** : [Symfony](https://symfony.com/) (PHP 8+)
- **Base de données** : MySQL / MariaDB
- **Frontend** : Twig + HTML/CSS + JavaScript
- **Gestion des dépendances** : Composer
- **Serveur local** : Symfony CLI ou XAMPP / MAMP
- **Autres** :
    - Doctrine ORM
    - Bootstrap (design responsive)
    - API Platform (si besoin d’API future)

---

## 📦 Installation

### 1️⃣ Cloner le projet
```bash
git clone https://github.com/votre-utilisateur/sorties-collegues.git
cd sorties-collegues
2️⃣ Installer les dépendances PHP
bash
Copier
Modifier
composer install
3️⃣ Configurer l'environnement
Copiez le fichier .env et adaptez les variables de connexion à votre base de données :

bash
Copier
Modifier
cp .env .env.local
Exemple de configuration :

ini
Copier
Modifier
DATABASE_URL="mysql://root:motdepasse@127.0.0.1:3306/sorties_collegues"
4️⃣ Créer la base de données et charger les fixtures
bash
Copier
Modifier
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
5️⃣ Lancer le serveur Symfony
bash
Copier
Modifier
symfony server:start
L’application sera accessible sur http://localhost:8000.

📂 Structure du projet
csharp
Copier
Modifier
.
├── assets/             # Fichiers front-end (JS, CSS)
├── config/             # Configuration Symfony
├── public/             # Point d'entrée (index.php)
├── src/                # Code source PHP
│   ├── Controller/     # Contrôleurs
│   ├── Entity/         # Entités Doctrine
│   ├── Form/           # Formulaires Symfony
│   └── Repository/     # Requêtes BDD
├── templates/          # Vues Twig
├── migrations/         # Migrations BDD
└── README.md           # Documentation du projet
👤 Utilisateurs par défaut (fixtures)
Email : admin@example.com | Mot de passe : admin123 (rôle admin)

Email : user@example.com | Mot de passe : user123 (utilisateur simple)

```
📌 Améliorations possibles
Notifications par e-mail lors d’une inscription.

Intégration d’un système de points / badges.

Version mobile PWA.

Connexion via Google / Microsoft.

📜 Licence
Ce projet est sous licence MIT. Vous pouvez le réutiliser librement.