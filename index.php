<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>MythiFox</title>
    <link rel="stylesheet" href="./assets/styles.css">
    <link rel="icon" href="./favicon.ico" type="image/x-icon">
    
  </head>
  <body>
    
  <?php include 'include/header.php'; ?>
  <?php include 'include/sidebar.php'; ?>  

  <?php if (isset($_SESSION['user'])): ?>
    <?php include 'include/makePost.php'; ?>
    <?php endif; ?>


    <div class="wrapper" >  	
      <span class="close">
        <img class="close" src="assets/close.png" alt="close" />
      </span>
      <input type="checkbox" id="chk" aria-hidden="true">

        <div class="signup">
          <form method="POST" action="./authentication/register.php">
            <label for="chk" aria-hidden="true">Sign up</label>
            <input type="text" name="username" class="signupPH" placeholder="Username" required>
            <input type="password" name="password" class="signupPH" placeholder="Password" required>
            <button class="btnPopup1">Sign up</button>
          </form>
        </div>

        <div class="login">
          <form method="POST" action="./authentication/login.php">
            <label for="chk" aria-hidden="true">Login</label>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required="">

            <button class='btnPopup2'>Login</button>
          </form>
        </div>
    </div>


    <script src="./assets/script.js"></script>
  </body>
</html>