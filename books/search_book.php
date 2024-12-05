<?php
session_start();
$isAuthenticated = isset($_SESSION['username']);
$username = $isAuthenticated ? $_SESSION['username'] : null;

$host = 'localhost';
$db = 'bookdb';
$user = 'root';
$pass = '';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$successMessage = $_SESSION['success_message'] ?? "";
$errorMessage = $_SESSION['error_message'] ?? "";
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle reservation confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reserve']) && $isAuthenticated) {
    $isbn = trim($_POST['isbn']);
    try {
        $stmt = $pdo->prepare("SELECT Reserved FROM Books WHERE ISBN = :isbn");
        $stmt->execute([':isbn' => $isbn]);
        $reserved = $stmt->fetchColumn();

        if ($reserved === 'N') {
            $stmt = $pdo->prepare("INSERT INTO Reservations (ISBN, Username, ReservedDate) VALUES (:isbn, :username, CURDATE())");
            $stmt->execute([':isbn' => $isbn, ':username' => $username]);

            $stmt = $pdo->prepare("UPDATE Books SET Reserved = 'Y' WHERE ISBN = :isbn");
            $stmt->execute([':isbn' => $isbn]);

            $_SESSION['success_message'] = "The book has been reserved successfully!";
        } else {
            $_SESSION['error_message'] = "The book is already reserved.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Failed to reserve the book: " . $e->getMessage();
    }
    header("Location: search_book.php");
    exit;
}

// Handle search
$searchResults = [];
$title = trim($_GET['title'] ?? '');
$author = trim($_GET['author'] ?? '');
$category = trim($_GET['category'] ?? '');

$resultsPerPage = 5;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $resultsPerPage;

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

$query .= " LIMIT $resultsPerPage OFFSET $offset";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error in SQL Query: " . $e->getMessage());
}

$totalQuery = "SELECT COUNT(*) FROM Books b LEFT JOIN Categories c ON b.CategoryID = c.CategoryID WHERE 1=1";
if ($title) {
    $totalQuery .= " AND b.BookTitle LIKE :title";
}
if ($author) {
    $totalQuery .= " AND b.Author LIKE :author";
}
if ($category) {
    $totalQuery .= " AND c.CategoryDescription = :category";
}

try {
    $totalStmt = $pdo->prepare($totalQuery);
    $totalStmt->execute($params);
    $totalResults = $totalStmt->fetchColumn();
} catch (PDOException $e) {
    die("Error counting total results: " . $e->getMessage());
}

$totalPages = ceil($totalResults / $resultsPerPage);

// Handle modal data
$reserveISBN = $_POST['isbn'] ?? null;
$reserveTitle = $_POST['bookTitle'] ?? null;
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
                <input type="text" name="title" placeholder="Search by Title" value="<?= htmlspecialchars($title) ?>">
                <input type="text" name="author" placeholder="Search by Author" value="<?= htmlspecialchars($author) ?>">
                <select name="category">
                    <option value="">-- Select Category --</option>
                    <?php
                    $categories = $pdo->query("SELECT CategoryDescription FROM Categories")->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($categories as $cat) {
                        $selected = ($category === $cat) ? 'selected' : '';
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
                                        <input type="hidden" name="bookTitle" value="<?= htmlspecialchars($book['BookTitle']) ?>">
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
        
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>">Previous</a>
                <?php endif; ?>

                <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page])) ?>" 
                       class="<?= $page == $currentPage ? 'active' : '' ?>">
                       <?= $page ?>
                    </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>">Next</a>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <p>No books found matching your search criteria.</p>
        <?php endif; ?>

        <?php if ($reserveISBN && $reserveTitle): ?>
            <div class="modal">
                <form method="POST">
                    <h2 class="prompt-text">Confirm Reservation</h2>
                    <p class="prompt-text">Do you want to reserve the book <strong><?= htmlspecialchars($reserveTitle) ?></strong> (ISBN: <?= htmlspecialchars($reserveISBN) ?>)?</p>
                    <input type="hidden" name="isbn" value="<?= htmlspecialchars($reserveISBN) ?>">
                    <button type="submit" name="confirm_reserve" class="btn btn-primary">Yes</button>
                    <a href="search_book.php" class="btn btn-danger">No</a>
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