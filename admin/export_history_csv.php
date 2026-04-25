<?php
session_start();
require_once("../config/db.php");

/* ===== ADMIN AUTH ===== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

/* ===== CSV HEADERS ===== */
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=library_history.csv');

/* ===== OPEN OUTPUT ===== */
$output = fopen('php://output', 'w');

/* ===== CSV COLUMN HEADERS ===== */
fputcsv($output, [
    'Type',
    'Student Name',
    'Student Email',
    'Book Name',
    'Category',
    'Status',
    'Requested On',
    'Issued On',
    'Returned / Completed On'
]);

/* ===== HISTORY QUERY (FIXED) ===== */
$query = "
    SELECT * FROM (
        -- BORROW HISTORY
        SELECT
            'Borrow' AS type,
            s.name AS student_name,
            s.email AS student_email,
            b.book_name,
            b.category,
            ir.status,
            ir.request_date,
            ir.issued_at,
            ir.returned_at
        FROM issue_requests ir
        JOIN students s ON s.id = ir.student_id
        JOIN books b ON b.id = ir.book_id
        WHERE ir.status IN ('Returned', 'Rejected')

        UNION ALL

        -- DONATION HISTORY
        SELECT
            'Donation' AS type,
            s.name,
            s.email,
            dr.book_name,
            dr.category,
            dr.status,
            dr.request_date,
            NULL AS issued_at,
            NULL AS returned_at
        FROM donation_requests dr
        JOIN students s ON s.id = dr.student_id
        WHERE dr.status IN ('Approved', 'Rejected')
    ) history
    ORDER BY request_date DESC
";

$result = $conn->query($query);

/* ===== WRITE ROWS ===== */
while ($row = $result->fetch_assoc()) {

    fputcsv($output, [
        $row['type'],
        $row['student_name'],
        $row['student_email'],
        $row['book_name'],
        $row['category'],
        $row['status'],
        date("d-m-Y", strtotime($row['request_date'])),
        $row['issued_at'] ? date("d-m-Y", strtotime($row['issued_at'])) : '-',
        $row['returned_at']
            ? date("d-m-Y", strtotime($row['returned_at']))
            : '-'
    ]);
}

fclose($output);
exit;