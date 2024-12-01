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
    $confirm_password = $_POST['confirm_password'];
    $firstname = trim($_POST['firstname']);
    $surname = trim($_POST['surname']);
    $address1 = trim($_POST['address1']);
    $address2 = trim($_POST['address2']);
    $city = trim($_POST['city']);
    $telephone = trim($_POST['telephone']);
    $mobile = trim($_POST['mobile']);
    
    $errors = [];
    
    if (empty($username) || empty($password) || empty($confirm_password) || empty($firstname) || empty($surname) || empty($address1) || empty($address2) || empty($city) || empty($telephone) || empty($mobile)) {
        $errors[] = "All fields are required.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($password) !== 6) {
        $errors[] = "Password must be exactly 6 characters.";
    }
    if (!preg_match('/^\d{10}$/', $telephone)) {
        $errors[] = "Telephone must be 10 numeric characters.";
    }
    if (!preg_match('/^\d{10}$/', $mobile)) {
        $errors[] = "Mobile must be 10 numeric characters.";
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE Username = :username");
    $stmt->execute(['username' => $username]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Username already exists. Please choose a different username.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO Users (Username, Password, FirstName, Surname, AddressLine1, AddressLine2, City, Telephone, Mobile) 
                VALUES (:username, :password, :firstname, :surname, :address1, :address2, :city, :telephone, :mobile)");

            $stmt->execute([
                'username' => $username,
                'password' => $password,
                'firstname' => $firstname,
                'surname' => $surname,
                'address1' => $address1,
                'address2' => $address2,
                'city' => $city,
                'telephone' => $telephone,
                'mobile' => $mobile
            ]);

            $successMessage = "Registration successful! <a href='login.php'>Click here to login</a>.";
        } catch (PDOException $e) {
            $errors[] = "An error occurred: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - koob</title>
    <link rel="stylesheet" href="../styles/index.css">
</head>
<body>
    <nav>
        <ul class="navbar">
            <li><a href="../index.php">Home</a></li>
            <?php if ($isAuthenticated): ?>
                <li><a href="../books/reserve.php">Reserve a Book</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="registration.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <header>
        <div class="container">
            <h1>Register at <span class="highlight">koob</span></h1>
            <p>Get access to book reservations!</p>
        </div>
    </header>

    <main>
        <form class="registration-form" method="POST">
            <?php
            if (!empty($errors)) {
                echo "<div class='error'>";
                foreach ($errors as $error) {
                    echo "<p>$error</p>";
                }
                echo "</div>";
            }
            if (!empty($successMessage)) {
                echo "<div class='success'>$successMessage</div>";
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
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6" maxlength="6" required>
            </div>
            <div class="form-group">
                <label for="firstname">First Name:</label>
                <input type="text" id="firstname" name="firstname" maxlength="32" required>
            </div>
            <div class="form-group">
                <label for="surname">Surname:</label>
                <input type="text" id="surname" name="surname" maxlength="32" required>
            </div>
            <div class="form-group">
                <label for="address1">Address Line 1:</label>
                <input type="text" id="address1" name="address1" maxlength="64" required>
            </div>
            <div class="form-group">
                <label for="address2">Address Line 2:</label>
                <input type="text" id="address2" name="address2" maxlength="64" required>
            </div>
            <div class="form-group">
                <label for="city">City:</label>
                <input type="text" id="city" name="city" maxlength="32" required>
            </div>
            <div class="form-group">
                <label for="telephone">Telephone:</label>
                <input type="text" id="telephone" name="telephone" pattern="\d{10}" maxlength="10" required>
            </div>
            <div class="form-group">
                <label for="mobile">Mobile:</label>
                <input type="text" id="mobile" name="mobile" pattern="\d{10}" maxlength="10" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Register</button>
                <a href="../index.php" class="btn btn-outline">Home</a>
            </div>
        </form>
    </main>

    <footer>
        <p>&copy; 2024 <span class="highlight">koob</span>. Created by Ian Miller (D23124620)</p>
    </footer>
</body>
</html>
