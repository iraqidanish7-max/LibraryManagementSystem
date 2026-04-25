<?php
session_start();
require_once("../config/db.php");

/* ===== STUDENT AUTH ===== */
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: student_dashboard.php");
    exit;
}

$requestId = (int)($_POST['request_id'] ?? 0);
$studentId = (int)$_SESSION['student_id'];

if ($requestId <= 0) {
    header("Location: student_dashboard.php");
    exit;
}

/* ===== FETCH REQUEST (SECURITY CHECK) ===== */
$stmt = $conn->prepare("
    SELECT ir.status, ir.book_id
    FROM issue_requests ir
    WHERE ir.id = ? AND ir.student_id = ?
");
$stmt->bind_param("ii", $requestId, $studentId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: student_dashboard.php");
    exit;
}

$row    = $res->fetch_assoc();
$status = $row['status'];
$bookId = $row['book_id'];

/* ===== ONLY APPROVED CAN BE RETURNED ===== */
if ($status !== 'Approved') {
    header("Location: student_dashboard.php");
    exit;
}

/* ===== MARK AS RETURNED ===== */
$stmt = $conn->prepare("
    UPDATE issue_requests
    SET status = 'Returned', returned_at = NOW()
    WHERE id = ?
");
$stmt->bind_param("i", $requestId);
$stmt->execute();

/* ===== INCREASE BOOK STOCK ===== */
$stmt = $conn->prepare("
    UPDATE books
    SET quantity = quantity + 1
    WHERE id = ?
");
$stmt->bind_param("i", $bookId);
$stmt->execute();

header("Location: student_dashboard.php");
exit;