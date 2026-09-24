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
    <div class="search-field">
      <svg class="icon icon-search" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" class="searchBar" placeholder="Search MythiFox..." name="q">
    </div>
  </div>

  <?php if (isset($_SESSION['user'])): ?>
          <!-- Si utilisateur connecté, afficher icône utilisateur et nom -->
          <span class="user-icon">
            <a href="profile.php" class="user-link">
              <svg class="icon icon-user" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <span class="name"><?php echo htmlspecialchars($_SESSION['user']); ?></span>
            </a>
          </span>
    
    <!-- Bouton pour se déconnecter -->
    <div class="btnLogin">
      <form action="./authentication/logout.php" method="POST">
        <button type="submit" class="btn btn-ghost">Logout</button>
      </form>
    </div>
  <?php else: ?>
    <!-- Afficher bouton de connexion si pas connecté -->
    <div class="btnLogin">
      <button id="login-popup-btn" class="btn btn-signin">Connect</button>
    </div>
  <?php endif; ?>
</header>
