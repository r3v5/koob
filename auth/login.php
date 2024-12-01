<?php
session_start();
$isAuthenticated = isset($_SESSION['username']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = 'localhost';
    $db = 'bookdb';
    $user = 'root';
    $pass = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $errors = [];

    if (empty($username) || empty($password)) {
        $errors[] = "Both username and password are required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT Password FROM Users WHERE Username = :username");
        $stmt->execute(['username' => $username]);
        $storedPassword = $stmt->fetchColumn();

        if ($storedPassword === $password) {
            $_SESSION['username'] = $username;
            $_SESSION['login_time'] = time();
            $_SESSION['timeout'] = 900;
            header("Location: ../books/search_book.php");
            exit;
        } else {
            $errors[] = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - koob</title>
    <link rel="stylesheet" href="../styles/index.css">
</head>
<body>
    <nav>
        <ul class="navbar">
            <li><a href="../index.php">Home</a></li>
            <?php if ($isAuthenticated): ?>
                <li><a href="../books/reserve_book.php">Reserve a Book</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="registration.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <header>
        <div class="container">
            <h1>Login to <span class="highlight">koob</span></h1>
            <p>Access your account to reserve books!</p>
        </div>
    </header>

    <main>
        <form class="login-form" method="POST">
            <?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
                <div class="error">
                    <p>Your session has expired. Please log in again.</p>
                </div>
            <?php endif; ?>
            <?php
            if (!empty($errors)) {
                echo "<div class='error'>";
                foreach ($errors as $error) {
                    echo "<p>$error</p>";
                }
                echo "</div>";
            }
            ?>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" maxlength="32" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" minlength="6" maxlength="6" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Login</button>
                <a href="../index.php" class="btn btn-outline">Home</a>
            </div>
        </form>
    </main>
    
    <footer>
        <p>&copy; 2024 <span class="highlight">koob</span>. Created by Ian Miller (D23124620)</p>
    </footer>
</body>
</html>
