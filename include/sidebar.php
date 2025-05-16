<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="navigation">
  <ul>
    <div class="big-link">
      <li>
        <img src="assets/home.png" alt="home page" />
        <a href="index.php" class="nav-link">Home</a>
      </li>
    </div>
    <div class="big-link">
      <li>
        <img src="assets/heart.png" alt="liked posts" />
        <a href="#" class="nav-link">Liked</a>
      </li>
    </div>
    <div class="big-link">
      <li>
        <img src="assets/follow.png" alt="following people" />
        <a href="#" class="nav-link">Following</a>
      </li>
    </div>
    <div class="big-link">
      <li>
        <img src="assets/category.png" alt="categories" />
        <span id="arrow" class="tree">Category</span>
        <img id="down" src="assets/down.png" alt="down more" />
      </li>
      <ul class="dropdown"> 
        <li><a href="#" class="nav-link">Music</a></li>
        <li><a href="#" class="nav-link">Drawings</a></li>
        <li><a href="#" class="nav-link">Quotes</a></li>
        <li><a href="#" class="nav-link">Games</a></li>
        <li><a href="#" class="nav-link">Anime</a></li>
        <li><a href="#" class="nav-link">Coding</a></li>
      </ul>
    </div>
  </ul>
</div>
