<?php
session_start();
require_once("../config/db.php");

/* ===== AUTH PROTECTION ===== */
if (!isset($_SESSION["student_id"])) {
    header("Location: student_login.php");
    exit;
}

$studentId = $_SESSION["student_id"];
$successMsg = "";
$errorMsg   = "";

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $book_name      = trim($_POST["book_name"]);
    $author         = trim($_POST["author"]);
    $description    = trim($_POST["description"]);
    $category       = trim($_POST["category"]);
    $book_condition = trim($_POST["book_condition"]);
    $image_url      = trim($_POST["image_url"]);

    if ($book_name === "" || $author === "" || $category === "") {
        $errorMsg = "Please fill all required fields.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO donation_requests
            (student_id, book_name, author, description, image_url, category, quantity, source, book_condition, status)
            VALUES (?, ?, ?, ?, ?, ?, 1, 'Donation', ?, 'Pending')
        ");

        $stmt->bind_param(
            "issssss",
            $studentId,
            $book_name,
            $author,
            $description,
            $image_url,
            $category,
            $book_condition
        );

        if ($stmt->execute()) {
            $successMsg = "Donation request submitted successfully!";
        } else {
            $errorMsg = "Something went wrong. Please try again.";
        }
    }
}
?>

<?php include("../header.php"); ?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/donate.css">

<div class="dashboard-wrapper">

    <div class="dash-card donate-card">

        <h2 class="donate-title">🎁 Donate a Book</h2>
        <p class="donate-sub">
            Share your book with other students. <br>
            <strong>Admin approval required.</strong>
        </p>

        <form method="POST" class="donate-form">

            <div class="donate-grid">

                <!-- LEFT COLUMN -->
                <div class="form-group">
                    <label>Book Name *</label>
                    <input type="text" name="book_name" required placeholder="Enter book name">
                </div>

                <div class="form-group">
                    <label>Author *</label>
                    <input type="text" name="author" required placeholder="Enter author name">
                </div>

                <div class="form-group">
                    <label>Category *</label>
                    <input type="text" name="category" required placeholder="e.g. Programming, Database">
                </div>

                <div class="form-group">
                    <label>Book Condition</label>
                    <select name="book_condition">
                        <option value="">Select condition</option>
                        <option value="New">New</option>
                        <option value="Good">Good</option>
                        <option value="Used">Used</option>
                        <option value="Old">Old</option>
                    </select>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Short description (optional)"></textarea>
                </div>

                <div class="form-group full">
                    <label>Image URL</label>
                    <input type="text" name="image_url" placeholder="Paste image URL (optional)">
                </div>

            </div>

            <div class="donate-actions">
                <button type="submit" class="btn-primary">Submit Donation</button>
                <a href="student_dashboard.php" class="btn-secondary">Cancel</a>
            </div>

        </form>

    </div>

</div>

<?php include("../footer.php"); ?>

<!-- ===== SWEETALERT ===== -->
<?php if ($successMsg): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
    icon: 'success',
    title: 'Thank You!',
    text: '<?= $successMsg ?> Waiting for admin approval.',
    timer: 2500,
    showConfirmButton: false
}).then(() => {
    window.location.href = "student_dashboard.php";
});
</script>
<?php endif; ?>

<?php if ($errorMsg): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
    icon: 'error',
    title: 'Error',
    text: '<?= $errorMsg ?>'
});
</script>
<?php endif; ?>