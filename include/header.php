<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="header">
  <img class="logo" src="assets/logo.png" alt="logo of the site" /> 
  <div class="search-form" action="#" method="get">
    <input type="text" class="searchBar" placeholder="Search MythiFox..." name="q">
    <i class="fas fa-search"></i>
  </div>

  <?php if (isset($_SESSION['user'])): ?>
          <span class="user-icon">
            <a href="profile.php"><img src="assets/user.png" alt="user profile" /> </a>
            <p class="name"><?php echo htmlspecialchars($_SESSION['user']); ?></p>
          </span>
        
    <div class="btnLogin">
      <form action="./authentication/logout.php" method="POST">
        <button type="submit" class="logout">Logout</button>
      </form>
    </div>
  <?php else: ?>
    <div class="btnLogin">
      <button class="btnLogin-popup">Connect</button>
    </div>
  <?php endif; ?>
</header>
