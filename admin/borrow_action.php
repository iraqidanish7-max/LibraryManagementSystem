<?php
session_start();
require_once("../config/db.php");

/* ===== ADMIN AUTH ===== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

/* ===== ONLY POST ALLOWED ===== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$action    = $_POST['action'] ?? '';

if ($requestId <= 0 || empty($action)) {
    die("Missing data");
}

/* ===== FETCH REQUEST ===== */
$stmt = $conn->prepare("
    SELECT ir.status, ir.book_id, b.quantity
    FROM issue_requests ir
    JOIN books b ON b.id = ir.book_id
    WHERE ir.id = ?
");
$stmt->bind_param("i", $requestId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("Request not found");
}

$row    = $res->fetch_assoc();
$status = strtolower($row['status']);   // 🔥 normalize once
$bookId = (int)$row['book_id'];
$qty    = (int)$row['quantity'];

/* ===== APPROVE ===== */
if ($action === 'approve' && $status === 'pending') {

    if ($qty <= 0) {
        header("Location: admin_dashboard.php?error=nostock");
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE issue_requests
        SET status = 'Approved', issued_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();

    $stmt = $conn->prepare("
        UPDATE books
        SET quantity = quantity - 1
        WHERE id = ?
    ");
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
}

/* ===== REJECT ===== */
elseif ($action === 'reject' && $status === 'pending') {

    $stmt = $conn->prepare("
        UPDATE issue_requests
        SET status = 'Rejected'
        WHERE id = ?
    ");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
}

header("Location: admin_dashboard.php");
exit;