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

// Handle removal confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_remove'])) {
    $isbn = trim($_POST['isbn']);
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

// Handle modal data
$removeISBN = $_POST['isbn'] ?? null;
$removeTitle = $_POST['bookTitle'] ?? null;
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
                                    <input type="hidden" name="isbn" value="<?= htmlspecialchars($reservation['ISBN']) ?>">
                                    <input type="hidden" name="bookTitle" value="<?= htmlspecialchars($reservation['BookTitle']) ?>">
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

        <?php if ($removeISBN && $removeTitle): ?>
            <div class="modal">
                <form method="POST">
                    <h2 class="prompt-text">Confirm Removal</h2>
                    <p class="prompt-text">Do you want to remove the reservation for the book <strong><?= htmlspecialchars($removeTitle) ?></strong> (ISBN: <?= htmlspecialchars($removeISBN) ?>)?</p>
                    <input type="hidden" name="isbn" value="<?= htmlspecialchars($removeISBN) ?>">
                    <button type="submit" name="confirm_remove" class="btn btn-primary">Yes</button>
                    <a href="reservations.php" class="btn btn-danger">No</a>
                </form>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2024 <span class="highlight">koob</span>. Created by Ian Miller (D23124620)</p>
    </footer>

    <style>
        .prompt-text {
            color: black;
        }

        .modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            z-index: 1000;
            width: 90%;
            max-width: 500px;
        }

        .modal h2 {
            margin-top: 0;
        }

        .modal p {
            margin: 10px 0;
        }

        .modal .btn {
            display: inline-block;
            margin: 5px;
            padding: 10px 15px;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .modal .btn-primary {
            background-color: #00e676;
            color: #fff;
        }

        .modal .btn-danger {
            background-color: #ff5252;
            color: #fff;
        }

        .modal .btn-primary:hover {
            background-color: #00c853;
        }

        .modal .btn-danger:hover {
            background-color: #ff1744;
        }

        .modal a {
            text-decoration: none;
        }
    </style>
</body>
</html>