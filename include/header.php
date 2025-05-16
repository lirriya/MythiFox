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
