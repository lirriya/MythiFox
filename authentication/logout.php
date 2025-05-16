<?php
    session_start();
    session_destroy(); // Destroy the session
    header("Location: ../index.php"); // Redirect to the main page
    exit(); // Always exit after header redirect
    ?>