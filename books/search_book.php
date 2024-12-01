<?php
session_start();
$isAuthenticated = isset($_SESSION['username']);
$username = $isAuthenticated ? $_SESSION['username'] : null;

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

$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAuthenticated) {
    $isbn = trim($_POST['isbn']);

    $stmt = $pdo->prepare("SELECT Reserved FROM Books WHERE ISBN = :isbn");
    $stmt->execute([':isbn' => $isbn]);
    $reserved = $stmt->fetchColumn();

    if ($reserved === 'N') {
        $stmt = $pdo->prepare("INSERT INTO Reservations (ISBN, Username, ReservedDate) VALUES (:isbn, :username, CURDATE())");
        $stmt->execute([':isbn' => $isbn, ':username' => $username]);

        $stmt = $pdo->prepare("UPDATE Books SET Reserved = 'Y' WHERE ISBN = :isbn");
        $stmt->execute([':isbn' => $isbn]);

        $successMessage = "The book has been reserved successfully!";
    } else {
        $errorMessage = "The book is already reserved.";
    }
}

$searchResults = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $title = trim($_GET['title'] ?? '');
    $author = trim($_GET['author'] ?? '');
    $category = trim($_GET['category'] ?? '');

    $query = "SELECT b.ISBN, b.BookTitle, b.Author, b.Edition, b.Year, b.Reserved, c.CategoryDescription
              FROM Books b
              LEFT JOIN Categories c ON b.CategoryID = c.CategoryID
              WHERE 1=1";

    $params = [];
    if ($title) {
        $query .= " AND b.BookTitle LIKE :title";
        $params[':title'] = "%$title%";
    }
    if ($author) {
        $query .= " AND b.Author LIKE :author";
        $params[':author'] = "%$author%";
    }
    if ($category) {
        $query .= " AND c.CategoryDescription = :category";
        $params[':category'] = $category;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search for Books - koob</title>
    <link rel="stylesheet" href="../styles/index.css">
    <link rel="stylesheet" href="../styles/search_book.css">
</head>
<body>
    <nav>
        <ul class="navbar">
        <li><a href="../index.php">koob</a></li>
            <?php if ($isAuthenticated): ?>
                <li><a href="search_book.php">Search</a></li>
                <li><a href="reservations.php">Reservations</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="../auth/login.php">Login</a></li>
                <li><a href="../auth/registration.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main>
        <div class="search-bar">
            <form method="GET">
                <input type="text" name="title" placeholder="Search by Title" value="<?= htmlspecialchars($_GET['title'] ?? '') ?>">
                <input type="text" name="author" placeholder="Search by Author" value="<?= htmlspecialchars($_GET['author'] ?? '') ?>">
                <select name="category">
                    <option value="">-- Select Category --</option>
                    <?php
                    $categories = $pdo->query("SELECT CategoryDescription FROM Categories")->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($categories as $cat) {
                        $selected = (isset($_GET['category']) && $_GET['category'] === $cat) ? 'selected' : '';
                        echo "<option value=\"$cat\" $selected>$cat</option>";
                    }
                    ?>
                </select>
                <button type="submit">Search</button>
            </form>
        </div>

        <?php if (!empty($successMessage)): ?>
            <div class="message-container">
                <p class="success"><?= htmlspecialchars($successMessage) ?></p>
                <a href="reservations.php" class="btn btn-primary">View My Reservations</a>
            </div>
        <?php endif; ?>
        <?php if (!empty($errorMessage)): ?>
            <p class="error"><?= htmlspecialchars($errorMessage) ?></p>
        <?php endif; ?>

        <?php if (!empty($searchResults)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ISBN</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Edition</th>
                        <th>Year</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($searchResults as $book): ?>
                        <tr>
                            <td><?= htmlspecialchars($book['ISBN']) ?></td>
                            <td><?= htmlspecialchars($book['BookTitle']) ?></td>
                            <td><?= htmlspecialchars($book['Author']) ?></td>
                            <td><?= htmlspecialchars($book['Edition']) ?></td>
                            <td><?= htmlspecialchars($book['Year']) ?></td>
                            <td><?= htmlspecialchars($book['CategoryDescription'] ?? 'No Category') ?></td>
                            <td class="<?= $book['Reserved'] === 'Y' ? 'reserved' : '' ?>">
                                <?= $book['Reserved'] === 'Y' ? 'Reserved' : 'Available' ?>
                            </td>
                            <td>
                                <?php if ($book['Reserved'] === 'N' && $isAuthenticated): ?>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="isbn" value="<?= htmlspecialchars($book['ISBN']) ?>">
                                        <button type="submit" class="btn-reserve">Reserve</button>
                                    </form>
                                <?php elseif ($book['Reserved'] === 'Y'): ?>
                                    <span class="reserved">Not Available</span>
                                <?php else: ?>
                                    <span>Please login to reserve</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET'): ?>
            <p>No books found matching your search criteria.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2024 <span class="highlight">koob</span>. Created by Ian Miller (D23124620)</p>
    </footer>
</body>
</html>
