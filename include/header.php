<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="header">
  <!-- Logo du site affiché en haut -->
  <img class="logo" src="assets/logo.png" alt="logo of the site" /> 
  <!-- Barre de recherche (formulaire) -->
  <div class="search-form" action="#" method="get">
    <!-- Champ texte pour entrer la recherche -->
    <input type="text" class="searchBar" placeholder="Search MythiFox..." name="q">
    <!-- Icône de recherche -->
    <i class="fas fa-search"></i>
  </div>

  <?php if (isset($_SESSION['user'])): ?>
          <!-- Si utilisateur connecté, afficher icône utilisateur et nom -->
          <span class="user-icon">
            <!-- Lien vers le profil utilisateur -->
            <a href="profile.php"><img src="assets/user.png" alt="user profile" /> </a>
            <!-- Affiche le nom d'utilisateur échappé pour la sécurité -->
            <p class="name"><?php echo htmlspecialchars($_SESSION['user']); ?></p>
          </span>
    
    <!-- Bouton pour se déconnecter -->
    <div class="btnLogin">
      <form action="./authentication/logout.php" method="POST">
        <button type="submit" class="logout">Logout</button>
      </form>
    </div>
  <?php else: ?>
    <!-- Afficher bouton de connexion si pas connecté -->
    <div class="btnLogin">
      <button class="btnLogin-popup">Connect</button>
    </div>
  <?php endif; ?>
</header>
