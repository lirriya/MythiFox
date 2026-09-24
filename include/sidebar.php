<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!-- Section de navigation principale du site -->
<div class="navigation">
  <ul>
    <!-- Lien principal : page d'accueil -->
    <div class="big-link">
      <li>
        <!-- Icône maison pour représenter "Home" -->
        <img src="assets/home.png" alt="home page" />
        <a href="index.php" class="nav-link">Home</a>
      </li>
    </div>
    <!-- Lien principal : posts aimés -->
    <div class="big-link">
      <li>
        <!-- Icône cœur pour représenter les publications aimées -->
        <img src="assets/heart.png" alt="liked posts" />
        <a href="#" class="nav-link">Liked</a>
      </li>
    </div>
    <!-- Lien principal : personnes suivies -->
    <div class="big-link">
      <li>
        <!-- Icône pour représenter les personnes suivies -->
        <img src="assets/follow.png" alt="following people" />
        <a href="#" class="nav-link">Following</a>
      </li>
    </div>
    <!-- Lien principal : catégories avec menu déroulant -->
    <div class="big-link">
      <li>
        <!-- Icône catégorie et flèche pour indiquer un sous-menu -->
        <img src="assets/category.png" alt="categories" />
        <span id="arrow" class="tree">Category</span>
        <img id="down" src="assets/down.png" alt="down more" />
      </li>
      <!-- Sous-menu : liste des catégories -->
      <ul class="dropdown"> 
        <li><a href="posts.php?category=music" class="nav-link">Music</a></li>
        <li><a href="posts.php?category=art" class="nav-link">Art</a></li>
        <li><a href="posts.php?category=quotes" class="nav-link">Quotes</a></li>
        <li><a href="posts.php?category=games" class="nav-link">Games</a></li>
        <li><a href="posts.php?category=anime" class="nav-link">Anime</a></li>
        <li><a href="posts.php?category=coding" class="nav-link">Coding</a></li>
      </ul>
    </div>
  </ul>
</div>
