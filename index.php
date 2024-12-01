<?php
session_start();
$isAuthenticated = isset($_SESSION['username']);
$username = $isAuthenticated ? htmlspecialchars($_SESSION['username']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="koob is an advanced Book Reservation System. Login or register to get started!">
    <title>Welcome to koob</title>
    <link rel="stylesheet" href="styles/index.css">
</head>
<body>
    <nav>
        <ul class="navbar">
            <li><a href="index.php">koob</a></li>
            <?php if ($isAuthenticated): ?>
                <li><a href="books/search_book.php">Search</a></li>
                <li><a href="books/reserve_book.php">Reserve</a></li>
                <li><a href="auth/logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="auth/login.php">Login</a></li>
                <li><a href="auth/registration.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <header>
        <div class="container">
            <h1><span class="highlight">koob</span></h1>
            <p>Book Reservation System built with PHP, MySQL and Apache Web Server  </p>
        </div>
    </header>
    <main>
        <div class="cta">
        <?php if (!$isAuthenticated): ?>
            <p>Login or Register to get started!</p>
        <?php else: ?>
            <p>Hi, <?= htmlspecialchars($username); ?>! Enjoy searching and reserving a book.</p>
        <?php endif; ?>
            <div class="links">
                <?php if (!$isAuthenticated): ?>
                    <a href="auth/login.php" class="btn">Login</a>
                    <a href="auth/registration.php" class="btn">Register</a>
                <?php else: ?>
                    <a href="books/search_book.php" class="btn">Search a Book</a>
                    <a href="books/reserve_book.php" class="btn">Reserve a Book</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <footer>
        <p>&copy; 2024 <span class="highlight">koob</span>. Created by Ian Miller (D23124620)</p>
    </footer>
</body>
</html>
