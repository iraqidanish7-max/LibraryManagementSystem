<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION["student_id"])) {
    header("Location: student_login.php");
    exit;
}

$studentId = $_SESSION["student_id"];

/* Fetch books */
$books = $conn->query("SELECT * FROM books ORDER BY created_at DESC");

/* Fetch already requested books */
$requestedBooks = [];
$reqStmt = $conn->prepare("
    SELECT book_id FROM issue_requests WHERE student_id = ?
");
$reqStmt->bind_param("i", $studentId);
$reqStmt->execute();
$res = $reqStmt->get_result();
while ($r = $res->fetch_assoc()) {
    $requestedBooks[] = $r["book_id"];
}
?>

<?php include("../header.php"); ?>
<link rel="stylesheet" href="../assets/css/available_books.css">
<div class="books-wrapper">
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-error">
        <?= $_SESSION['flash_error'] ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">
        <?= $_SESSION['flash_success'] ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

    <h2 class="page-title">Available Books</h2>
    <p class="page-sub">Browse books from the library and request instantly</p>

    <?php if (!empty($_SESSION["flash_success"])): ?>
        <div class="flash-success">
            <?= htmlspecialchars($_SESSION["flash_success"]) ?>
        </div>
        <?php unset($_SESSION["flash_success"]); ?>
    <?php endif; ?>

    <div class="books-grid">

        <?php while ($book = $books->fetch_assoc()): 
            $isRequested = in_array($book["id"], $requestedBooks);
            $qty = (int)$book["quantity"];
        ?>

        <div class="book-card">

            <img src="<?= htmlspecialchars($book["image_url"]) ?>" alt="Book">

            <div class="book-body">

                <h4><?= htmlspecialchars($book["book_name"]) ?></h4>
                <p class="author"><?= htmlspecialchars($book["author"]) ?></p>

                <p class="description">
                    <?= htmlspecialchars(substr($book["description"], 0, 90)) ?>...
                </p>

                <p class="category">
                    Category: <span><?= htmlspecialchars($book["category"]) ?></span>
                </p>

                <div class="pill-row">
                    <span class="pill <?= $book["source"] === 'college' ? 'pill-blue' : 'pill-green' ?>">
                        <?= ucfirst($book["source"]) ?>
                    </span>

                    <?php if ($qty >= 3): ?>
                        <span class="pill pill-green"><?= $qty ?> available</span>
                    <?php elseif ($qty == 2): ?>
                        <span class="pill pill-yellow">2 left</span>
                    <?php elseif ($qty == 1): ?>
                        <span class="pill pill-red">1 left</span>
                    <?php else: ?>
                        <span class="pill pill-gray">Out of stock</span>
                    <?php endif; ?>
                </div>

                <?php if ($qty == 0): ?>
                    <button class="btn-disabled" disabled>Out of Stock</button>
                <?php elseif ($isRequested): ?>
                    <button class="
                    btn-danger" disabled>Already Requested</button>
                <?php else: ?>
                    <form method="post" action="request_book.php">
                        <input type="hidden" name="book_id" value="<?= $book["id"] ?>">
                        <button type="submit" class="btn-primary">Request Book</button>
                    </form>
                <?php endif; ?>

            </div>
        </div>

        <?php endwhile; ?>

    </div>
</div>
<script>
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(el => {
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 300);
    });
}, 3000);
</script>
<?php include("../footer.php"); ?>