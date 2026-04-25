<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION["student_id"])) {
    header("Location: student_login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: available_books.php");
    exit;
}

$studentId = (int)$_SESSION["student_id"];
$bookId    = (int)$_POST["book_id"];

/* ======================================================
   🔒 BORROW LIMIT CHECK (MAX 2 ACTIVE REQUESTS)
   Counts: Pending + Approved (not returned)
   ====================================================== */
$limitStmt = $conn->prepare("
    SELECT COUNT(*) AS active_count
    FROM issue_requests
    WHERE student_id = ?
      AND status IN ('Pending', 'Approved')
");
$limitStmt->bind_param("i", $studentId);
$limitStmt->execute();
$limitRes = $limitStmt->get_result()->fetch_assoc();

if ($limitRes['active_count'] >= 2) {
    $_SESSION["flash_error"] =
        "⚠️ You can request only 2 books at a time. Please wait for approval or return a book.";
    header("Location: available_books.php");
    exit;
}

/* ======================================================
   CHECK STOCK
   ====================================================== */
$stmt = $conn->prepare("SELECT quantity FROM books WHERE id = ?");
$stmt->bind_param("i", $bookId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0 || $res->fetch_assoc()["quantity"] <= 0) {
    $_SESSION["flash_error"] = "❌ Book is currently not available.";
    header("Location: available_books.php");
    exit;
}

/* ======================================================
   CHECK DUPLICATE REQUEST (same book)
   ====================================================== */
$check = $conn->prepare("
    SELECT id 
    FROM issue_requests 
    WHERE student_id = ? 
      AND book_id = ?
      AND status IN ('Pending', 'Approved')
");
$check->bind_param("ii", $studentId, $bookId);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    $_SESSION["flash_error"] = "⚠️ You have already requested this book.";
    header("Location: available_books.php");
    exit;
}

/* ======================================================
   INSERT REQUEST
   ====================================================== */
$insert = $conn->prepare("
    INSERT INTO issue_requests
    (student_id, book_id, status, issued_at, due_at, returned_at, request_date)
    VALUES (?, ?, 'Pending', NULL, NULL, NULL, NOW())
");
$insert->bind_param("ii", $studentId, $bookId);
$insert->execute();

$_SESSION["flash_success"] = "📘 Book request submitted successfully!";
header("Location: available_books.php");
exit;