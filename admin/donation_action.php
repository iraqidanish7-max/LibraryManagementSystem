<?php
session_start();
require_once("../config/db.php");

/* ===== ADMIN AUTH ===== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php");
    exit;
}

$donationId = (int)($_POST['donation_id'] ?? 0);
$action     = $_POST['action'] ?? '';

if ($donationId <= 0) {
    header("Location: admin_dashboard.php");
    exit;
}

/* ===== FETCH DONATION ===== */
$stmt = $conn->prepare("
    SELECT *
    FROM donation_requests
    WHERE id = ?
");
$stmt->bind_param("i", $donationId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: admin_dashboard.php");
    exit;
}

$row = $res->fetch_assoc();

/* ===== APPROVE ===== */
if ($action === 'approve' && $row['status'] === 'Pending') {

    // Insert into books table
    $stmt = $conn->prepare("
        INSERT INTO books
        (book_name, author, description, image_url, category, quantity, source)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssssis",
        $row['book_name'],
        $row['author'],
        $row['description'],
        $row['image_url'],
        $row['category'],
        $row['quantity'],
        $row['source']
    );

    $stmt->execute();

    // Update donation status
    $conn->query("
        UPDATE donation_requests
        SET status = 'Approved'
        WHERE id = $donationId
    ");
}

/* ===== REJECT ===== */
elseif ($action === 'reject' && $row['status'] === 'Pending') {

    $conn->query("
        UPDATE donation_requests
        SET status = 'Rejected'
        WHERE id = $donationId
    ");
}

header("Location: admin_dashboard.php");
exit;