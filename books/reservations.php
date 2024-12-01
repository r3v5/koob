<?php
session_start();
$isAuthenticated = isset($_SESSION['username']);
$username = $isAuthenticated ? $_SESSION['username'] : null;

if (!$isAuthenticated) {
    header("Location: ../auth/login.php");
    exit;
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_isbn'])) {
    $isbn = trim($_POST['remove_isbn']);
    $stmt = $pdo->prepare("DELETE FROM Reservations WHERE ISBN = :isbn AND Username = :username");
    $stmt->execute([':isbn' => $isbn, ':username' => $username]);

    $stmt = $pdo->prepare("UPDATE Books SET Reserved = 'N' WHERE ISBN = :isbn");
    $stmt->execute([':isbn' => $isbn]);

    $_SESSION['success_message'] = "Reservation for the book has been successfully removed.";

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$successMessage = "";
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$reservations = [];
$stmt = $pdo->prepare("
    SELECT r.ISBN, b.BookTitle, b.Author, b.Edition, b.Year, c.CategoryDescription, r.ReservedDate
    FROM Reservations r
    INNER JOIN Books b ON r.ISBN = b.ISBN
    LEFT JOIN Categories c ON b.CategoryID = c.CategoryID
    WHERE r.Username = :username
");
$stmt->execute([':username' => $username]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - koob</title>
    <link rel="stylesheet" href="../styles/index.css">
    <link rel="stylesheet" href="../styles/reservations.css">
</head>
<body>
    <nav>
        <ul class="navbar">
            <li><a href="../index.php">koob</a></li>
            <li><a href="search_book.php">Search</a></li>
            <li><a href="reservations.php">Reservations</a></li>
            <li><a href="../auth/logout.php">Logout</a></li>
        </ul>
    </nav>

    <main class="reservations-wrapper">
        <h1><?= htmlspecialchars($username) ?>'s reservations</h1>

        <?php if (!empty($successMessage)): ?>
            <p class="success"><?= htmlspecialchars($successMessage) ?></p>
        <?php endif; ?>

        <?php if (!empty($reservations)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ISBN</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Edition</th>
                        <th>Year</th>
                        <th>Category</th>
                        <th>Reserved Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?= htmlspecialchars($reservation['ISBN']) ?></td>
                            <td><?= htmlspecialchars($reservation['BookTitle']) ?></td>
                            <td><?= htmlspecialchars($reservation['Author']) ?></td>
                            <td><?= htmlspecialchars($reservation['Edition']) ?></td>
                            <td><?= htmlspecialchars($reservation['Year']) ?></td>
                            <td><?= htmlspecialchars($reservation['CategoryDescription'] ?? 'No Category') ?></td>
                            <td><?= htmlspecialchars($reservation['ReservedDate']) ?></td>
                            <td>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="remove_isbn" value="<?= htmlspecialchars($reservation['ISBN']) ?>">
                                    <button type="submit" class="btn btn-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You have not reserved any books yet.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2024 <span class="highlight">koob</span>. Created by Ian Miller (D23124620)</p>
    </footer>
</body>
</html>