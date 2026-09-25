
# Documentation du Projet MythiFox

## Structure Générale du Projet

Ce projet est un site web de type réseau social permettant aux utilisateurs de s’inscrire, se connecter, publier du contenu avec des images, et consulter les profils. L'organisation du projet suit une structure claire en dossiers et fichiers selon leur rôle.

---

## Arborescence des Dossiers et Fichiers

### 1. `assets/`

Ce dossier contient les fichiers statiques utilisés sur l’ensemble du site.

* `script.js` : Code JavaScript pour l’interactivité.
* `styles.css` : Feuille de style principale du site.
* Images :

  * `logo.png` : Logo du site.
  * `camera.png` : Icône pour les actions liées à l’image (ex : ajout).
  * `category.png` : Icône représentant les catégories.
  * `close.png` : Icône pour fermer une fenêtre ou un élément.
  * `default.png` : Image de profil par défaut.
  * `down.png` : Icône flèche vers le bas (déroulant, navigation).
  * `follow.png` : Icône pour l’abonnement (follow/unfollow).
  * `heart.png` : Icône pour les mentions "j’aime".
  * `home.png` : Icône de la page d’accueil.
  * `user.png` : Icône représentant un utilisateur ou un profil.

---

### 2. `authentication/`

Ce dossier contient les fichiers liés à l’authentification des utilisateurs.

* `login.php` : Code de connexion avec traitement des identifiants.
* `logout.php` : Déconnexion de l’utilisateur et destruction de la session.
* `register.php` : Code d’inscription pour créer un nouveau compte.

---

### 3. `include/`

Contient les fichiers PHP partagés ou dédiés à des actions spécifiques.

* `header.php` : En-tête commun du site (balises HTML de base, navigation).
* `sidebar.php` : Sidebar de navigation.
* `makePost.php` : Traitement de l’envoi d’un nouveau post.
* `delete_post.php` : Suppression d’un post existant.
* `update_post.php` : Modification d’un post.
* `edit_profile.php` : Modification du profil utilisateur (username, bio, photo).

---

### 4. `uploads/`

Ce dossier stocke les images téléchargées par les utilisateurs, que ce soit dans leurs publications ou comme image de profil.

---

### 5. Fichiers principaux

Ces fichiers constituent les pages principales accessibles via le navigateur.

* `index.php` : Page d’accueil affichant les publications.
* `posts.php` : Liste des publications par catégorie.
* `profile.php` : Affichage du profil d’un utilisateur (connecté ou sélectionné).

---

## Schéma de Base de Données

### Table `users` :

* `id` (clé primaire)
* `username` (unique)
* `pwd` (mot de passe haché)
* `email` (adresse unique, optionnelle pour les anciens comptes)
* `email_verified_at` (date de vérification, optionnelle)
* `bio`
* `profile_pic` (par défaut : `assets/default.jpg`)
* `created_at`

### Table `posts` :

* `id` (clé primaire)
* `user_id` (clé étrangère → `users.id`)
* `content`
* `image`
* `created_at`
* `category` (ENUM : `'music'`, `'art'`, `'quotes'`, `'games'`, `'anime'`, `'coding'`)

---

## Flux de Données

1. L'utilisateur s’inscrit via `authentication/register.php` (création dans la table `users`, normalisation de l'adresse e-mail et envoi d'un lien de vérification).
2. Il peut ouvrir le lien reçu dans Mailpit via `authentication/verify_email.php`; le jeton est haché en base et utilisable une seule fois.
3. Il se connecte via `login.php` (création de la session).
4. Sur `index.php`, les publications sont affichées (jointure entre `users` et `posts`).
5. L’utilisateur peut publier un contenu via un formulaire traité par `makePost.php`. L’image est enregistrée dans `uploads/` et la publication dans la table `posts`.
6. Les publications peuvent être modifiées via `update_post.php` ou supprimées via `delete_post.php`.
7. Le profil est modifiable via `edit_profile.php`.

## MISE À JOUR 02/06/2025


### Ajout du système de "like" dynamique avec animation

#### Modifications apportées :

* **`script.js`**  
  Ajout d’un script JavaScript pour gérer les likes sur les posts avec :
  * Animation lors du clic sur le bouton "like"
  * Mise à jour du compteur de likes via AJAX
  * Message contextuel si l'utilisateur a déjà liké le post

* **`include/like_post.php`**  
  Nouveau fichier côté serveur qui :
  * Vérifie si l'utilisateur est connecté
  * Empêche les likes multiples depuis une même session
  * Incrémente les likes dans la base de données
  * Retourne la réponse au format `x-www-form-urlencoded` avec le nombre de likes mis à jour

* **Base de données (`posts` table)**  
  Ajout de la colonne suivante :
  ```sql
  ALTER TABLE posts ADD COLUMN likes INT DEFAULT 0;
