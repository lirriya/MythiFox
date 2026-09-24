<?php
require_once __DIR__ . '/session_boot.php';
?>
<!-- Section de navigation principale du site -->
<div class="navigation">
  <ul>
    <div class="big-link">
      <li>
        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-8 9 8"/><path d="M5 10v11h14V10"/></svg>
        <a href="index.php" class="nav-link">Home</a>
      </li>
    </div>
    <div class="big-link">
      <li>
        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
        <a href="#" class="nav-link">Liked</a>
      </li>
    </div>
    <div class="big-link">
      <li>
        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <a href="#" class="nav-link">Following</a>
      </li>
    </div>
    <div class="big-link">
      <li>
        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5"/></svg>
        <span id="arrow" class="tree">Category</span>
        <svg class="icon icon-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
      </li>
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
