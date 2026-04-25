<?php
session_start();
require_once("../config/db.php");

/* ===== ADMIN AUTH ===== */
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

/* ===== CACHE PREVENTION ===== */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");


/* Active Students */
$studentsRes = $conn->query("
    SELECT COUNT(*) AS total 
    FROM students 
    WHERE status = 'active'
");
$activeStudents = $studentsRes->fetch_assoc()['total'];

/* Total Books (variety count) */
$booksRes = $conn->query("
    SELECT COUNT(*) AS total 
    FROM books
");
$totalBooks = $booksRes->fetch_assoc()['total'];

/* ===============================
   BORROW REQUESTS SUMMARY (ADMIN)
================================ */

$borrowStats = [
    'total'     => 0,
    'pending'   => 0,
    'approved'  => 0,
    'rejected'  => 0,
    'returned'  => 0
];

$br = $conn->query("
    SELECT status, COUNT(*) AS cnt
    FROM issue_requests
    GROUP BY status
");

while ($r = $br->fetch_assoc()) {

    $count  = (int)$r['cnt'];
    $status = strtolower($r['status']);

    // total = all requests ever
    $borrowStats['total'] += $count;

    // map statuses safely
    if ($status === 'pending') {
        $borrowStats['pending'] = $count;
    } elseif ($status === 'approved') {
        $borrowStats['approved'] = $count;
    } elseif ($status === 'rejected') {
        $borrowStats['rejected'] = $count;
    } elseif ($status === 'returned') {
        $borrowStats['returned'] = $count;
    }
}
/* Donation Requests Summary */
$donStats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

$dr = $conn->query("
    SELECT status, COUNT(*) AS cnt
    FROM donation_requests
    GROUP BY status
");

while ($r = $dr->fetch_assoc()) {
    $donStats['total'] += $r['cnt'];
    $status = strtolower($r['status']);
    if (isset($donStats[$status])) {
        $donStats[$status] = $r['cnt'];
    }
}
/* ===============================
   MANAGE STUDENTS – ACTION LOGIC
================================ */

if (isset($_GET['student_action'], $_GET['student_id'])) {
    $studentId = (int) $_GET['student_id'];
    $action = $_GET['student_action'];

    if ($action === 'activate') {
        $stmt = $conn->prepare("UPDATE students SET status='active' WHERE id=?");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
    }

    if ($action === 'deactivate') {
        $stmt = $conn->prepare("UPDATE students SET status='blocked' WHERE id=?");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
    }

    // Prevent repeat action on refresh
    header("Location: admin_dashboard.php");
    exit;
}

/* ===============================
   FETCH STUDENTS
================================ */

$studentsRes = $conn->query("
    SELECT id, name, roll_no, phone, email, status
    FROM students
    ORDER BY created_at DESC
");
/* ===== FETCH ACTIVE BORROW REQUESTS ===== */
$borrowReqStmt = $conn->prepare("
    SELECT 
        ir.id,
        ir.status,
        ir.request_date,
        ir.issued_at,

        s.name   AS student_name,
        s.email  AS student_email,
        s.phone  AS student_phone,

        b.book_name,
        b.category,
        b.image_url

    FROM issue_requests ir
    JOIN students s ON s.id = ir.student_id
    JOIN books b ON b.id = ir.book_id

    WHERE ir.status IN ('Pending', 'Approved')
    ORDER BY ir.request_date DESC
");
$borrowReqStmt->execute();
$borrowRequests = $borrowReqStmt->get_result();

/* ===== FETCH DONATION REQUESTS (ADMIN) ===== */
$donReqStmt = $conn->prepare("
    SELECT
        dr.id,
        dr.book_name,
        dr.author,
        dr.category,
        dr.quantity,
        dr.source,
        dr.book_condition,
        dr.image_url,
        dr.status,
        dr.request_date,

        s.name  AS student_name,
        s.email AS student_email,
        s.phone AS student_phone

    FROM donation_requests dr
    JOIN students s ON s.id = dr.student_id
    WHERE dr.status IN ('Pending', 'Approved')
    ORDER BY dr.request_date DESC
");
$donReqStmt->execute();
$donationRequests = $donReqStmt->get_result();
$historyStmt = $conn->query("
    SELECT * FROM (
        -- BORROW HISTORY (COMPLETED ONLY)
        SELECT
            'Borrow' AS type,
            s.name AS student_name,
            s.email AS student_email,
            b.book_name,
            b.category,
            ir.request_date,
            ir.status,
            ir.issued_at,
            ir.returned_at
        FROM issue_requests ir
        JOIN students s ON s.id = ir.student_id
        JOIN books b ON b.id = ir.book_id
        WHERE ir.status IN ('Returned', 'Rejected')

        UNION ALL

        -- DONATION HISTORY (COMPLETED ONLY)
        SELECT
            'Donation' AS type,
            s.name,
            s.email,
            dr.book_name,
            dr.category,
            dr.request_date,
            dr.status,
            NULL AS issued_at,
            NULL AS returned_at
        FROM donation_requests dr
        JOIN students s ON s.id = dr.student_id
        WHERE dr.status IN ('Approved', 'Rejected')
    ) history
    ORDER BY request_date DESC
");

$historyResults = $historyStmt;

$availableBooks = $conn->query("
    SELECT 
        book_name,
        author,
        category,
        quantity,
        source
    FROM books
    ORDER BY book_name ASC
");
?>

<?php include("../header.php"); ?>
<link rel="stylesheet" href="../assets/css/dashboard.css">

<div class="dashboard-wrapper admin-page">

    <!-- ================= TOP SECTION ================= -->
    <div class="dashboard-top">

        <!-- LEFT : SYSTEM OVERVIEW (70%) -->
        <div class="dash-card large-card">
            <h2 class="welcome-title">System Overview</h2>
            <p class="welcome-sub">Library statistics at a glance</p>

            <div class="action-grid">

                <div class="action-card stat-card dark">
                    <h4>👥 Active Students</h4>
                    <p class="stat-number"><?= $activeStudents ?></p>
                </div>

                <div class="action-card stat-card dark">
                    <h4>📚 Total Books</h4>
                    <p class="stat-number"><?= $totalBooks ?></p>
                </div>

                <div class="action-card stat-card dark">
                    <h4>📘 Borrow Requests</h4>
                    <p>Total: <?= $borrowStats['total'] ?></p>
                    <p>Pending: <?= $borrowStats['pending'] ?></p>
                    <p>Approved: <?= $borrowStats['approved'] ?></p>
                    <p>Rejected: <?= $borrowStats['rejected'] ?></p>
                </div>

                <div class="action-card stat-card dark">
                    <h4>🎁 Donation Requests</h4>
                    <p>Total: <?= $donStats['total'] ?></p>
                    <p>Pending: <?= $donStats['pending'] ?></p>
                    <p>Approved: <?= $donStats['approved'] ?></p>
                    <p>Rejected: <?= $donStats['rejected'] ?></p>
                </div>

            </div>
        </div>

        <!-- RIGHT : GRAPH PLACEHOLDER (30%) -->
        <div class="admin-right-center">
    <h4>Statistics Overview</h4>
    <p style="opacity:0.85; font-size:0.9rem;">
        Graphical insights will appear here.
    </p>

    <div class="chart-placeholder">
        <canvas id="requestsChart"></canvas>
    </div>
</div>
        </div>
        <!-- ===============================
     MANAGE STUDENTS SECTION
================================ -->

<div class="dash-card full-width-card">

    <h3 class="section-title">👥 Manage Students</h3>
    <p class="section-sub">Activate or deactivate student accounts</p>

    <div class="table-wrapper">
        <table class="admin-table">

            <thead>
                <tr>
                    <th>Name</th>
                    <th>Roll No</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($studentsRes->num_rows > 0): ?>
                <?php while ($s = $studentsRes->fetch_assoc()): ?>
                    <?php $isActive = ($s['status'] === 'active'); ?>
                    <tr>

                        <td><?= htmlspecialchars($s['name']) ?></td>
                        <td><?= htmlspecialchars($s['roll_no']) ?></td>
                        <td><?= htmlspecialchars($s['phone']) ?></td>
                        <td><?= htmlspecialchars($s['email']) ?></td>

                        <td>
                            <span class="status-badge <?= $isActive ? 'active' : 'blocked' ?>">
                                <?= $isActive ? 'Active' : 'Deactivated' ?>
                            </span>
                        </td>

                        <td class="action-cell">

                            <!-- ACTIVATE -->
                            <a
                                href="admin_dashboard.php?student_action=activate&student_id=<?= $s['id'] ?>"
                                class="btn-pill btn-activate <?= $isActive ? 'disabled' : '' ?>"
                            >
                                Activate
                            </a>

                            <!-- DEACTIVATE -->
                            <a
                                href="admin_dashboard.php?student_action=deactivate&student_id=<?= $s['id'] ?>"
                                class="btn-pill btn-deactivate <?= !$isActive ? 'disabled' : '' ?>"
                            >
                                Deactivate
                            </a>

                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="empty-text">No students found.</td>
                </tr>
            <?php endif; ?>
            </tbody>

        </table>
    </div>

</div>


<!-- ================= BORROW REQUEST MANAGEMENT ================= -->
<div class="dash-card history-card">

    <h4>📘 Manage Borrow Requests</h4>

    <?php if ($borrowRequests->num_rows > 0): ?>
        <table class="admin-table">

            <thead>
                <tr>
                    <th>Student</th>
                    <th>Book</th>
                    <th>Requested</th>
                    <th>Status</th>
                    <th>Countdown</th>
                    <th>Returned?</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php while ($row = $borrowRequests->fetch_assoc()): ?>
                <?php
                    // 🔑 normalize status ONCE
                    $status = strtolower($row['status']);
                ?>

                <tr>

                    <!-- Student -->
                    <td>
                        <strong><?= htmlspecialchars($row['student_name']) ?></strong><br>
                        <small><?= htmlspecialchars($row['student_email']) ?></small><br>
                        <small><?= htmlspecialchars($row['student_phone']) ?></small>
                    </td>

                    <!-- Book -->
                    <td>
                        <strong><?= htmlspecialchars($row['book_name']) ?></strong><br>
                        <small><?= htmlspecialchars($row['category']) ?></small>
                    </td>

                    <!-- Requested Date -->
                    <td>
                        <?= date("d M Y", strtotime($row['request_date'])) ?>
                    </td>

                    <!-- Status -->
                    <td>
                        <span class="status <?= $status ?>">
                            <?= ucfirst($status) ?>
                        </span>
                    </td>

                    <!-- Countdown -->
                    <td>
                        <?php if ($status === 'approved' && $row['issued_at']): ?>
                            <?php
                                $daysLeft = 15 - (new DateTime($row['issued_at']))->diff(new DateTime())->days;
                            ?>
                            <span class="<?= $daysLeft <= 0 ? 'danger-text' : '' ?>">
                                <?= $daysLeft > 0 ? "$daysLeft days" : "Overdue" ?>
                            </span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>

                    <!-- Returned Column -->
                    <td>
                        <?php if ($status === 'returned'): ?>
                            <span class="success-text">
                                Returned on <?= date("d M Y", strtotime($row['returned_at'])) ?>
                            </span>
                        <?php elseif ($status === 'approved'): ?>
                            Not returned yet
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>

                    <!-- Action (ADMIN ONLY APPROVE / REJECT) -->
                    <td>
                        <?php if ($status === 'pending'): ?>
                            <form method="POST" action="/library_management_system/admin/borrow_action.php" style="display:flex; gap:8px;">
                                <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn-small success">
                                    Approve
                                </button>
                                <button type="submit" name="action" value="reject" class="btn-small danger">
                                    Reject
                                </button>
                            </form>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>

                </tr>

            <?php endwhile; ?>
            </tbody>

        </table>
    <?php else: ?>
        <p class="empty-text">No borrow requests found.</p>
    <?php endif; ?>

</div>
<!-- ================= DONATION REQUEST MANAGEMENT ================= -->
<div class="dash-card history-card">

    <h4>🎁 Manage Donation Requests</h4>

    <?php if ($donationRequests->num_rows > 0): ?>
        <table class="admin-table">

            <thead>
                <tr>
                    <th>Donor</th>
                    <th>Book</th>
                    <th>Condition</th>
                    <th>Quantity</th>
                    <th>Requested</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php while ($row = $donationRequests->fetch_assoc()): ?>
                <?php $status = strtolower($row['status']); ?>

                <tr>

                    <!-- Donor -->
                    <td>
                        <strong><?= htmlspecialchars($row['student_name']) ?></strong><br>
                        <small><?= htmlspecialchars($row['student_email']) ?></small><br>
                        <small><?= htmlspecialchars($row['student_phone']) ?></small>
                    </td>

                    <!-- Book -->
                    <td>
                        <strong><?= htmlspecialchars($row['book_name']) ?></strong><br>
                        <small><?= htmlspecialchars($row['author']) ?></small><br>
                        <small><?= htmlspecialchars($row['category']) ?></small>
                    </td>

                    <!-- Condition -->
                    <td><?= htmlspecialchars($row['book_condition']) ?></td>

                    <!-- Quantity -->
                    <td><?= (int)$row['quantity'] ?></td>

                    <!-- Requested Date -->
                    <td><?= date("d M Y", strtotime($row['request_date'])) ?></td>

                    <!-- Status -->
                    <td>
                        <span class="status <?= $status ?>">
                            <?= ucfirst($status) ?>
                        </span>
                    </td>

                    <!-- Action -->
                    <td>
                        <?php if ($status === 'pending'): ?>
                            <form method="POST" action="donation_action.php" style="display:flex; gap:8px;">
                                <input type="hidden" name="donation_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn-small success">
                                    Approve
                                </button>
                                <button type="submit" name="action" value="reject" class="btn-small danger">
                                    Reject
                                </button>
                            </form>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>

                </tr>
            <?php endwhile; ?>
            </tbody>

        </table>
    <?php else: ?>
        <p class="empty-text">No donation requests available.</p>
    <?php endif; ?>

</div>


<div class="dash-card history-card full-width-card">

    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h4>📜 System Transaction History</h4>

        <a href="export_history_csv.php" class="btn-pill btn-secondary">
            ⬇️ Download CSV
        </a>
    </div>

    <?php if ($historyResults->num_rows > 0): ?>
        <table class="admin-table">

            <thead>
                <tr>
                    <th>Type</th>
                    <th>Student</th>
                    <th>Book</th>
                    <th>Category</th>
                    <th>Requested</th>
                    <th>Status</th>
                    <th>Issued</th>
                    <th>Completed On</th>
                </tr>
            </thead>

            <tbody>
            <?php while ($row = $historyResults->fetch_assoc()): ?>
                <tr>

                    <td><strong><?= $row['type'] ?></strong></td>

                    <td>
                        <?= htmlspecialchars($row['student_name']) ?><br>
                        <small><?= htmlspecialchars($row['student_email']) ?></small>
                    </td>

                    <td><?= htmlspecialchars($row['book_name']) ?></td>

                    <td><?= htmlspecialchars($row['category']) ?></td>

                    <td><?= date("d M Y", strtotime($row['request_date'])) ?></td>

                    <td>
                        <span class="status <?= strtolower($row['status']) ?>">
                            <?= $row['status'] ?>
                        </span>
                    </td>

                    <td>
                        <?= $row['issued_at']
                            ? date("d M Y", strtotime($row['issued_at']))
                            : '—' ?>
                    </td>

                    <td>
    <?php if ($row['type'] === 'Borrow'): ?>
        <?= $row['returned_at']
            ? date("d M Y", strtotime($row['returned_at']))
            : '—' ?>
    <?php else: ?>
        <?= date("d M Y", strtotime($row['request_date'])) ?>
    <?php endif; ?>
</td>
                </tr>
            <?php endwhile; ?>
            </tbody>

        </table>
    <?php else: ?>
        <p class="empty-text">No completed transactions yet.</p>
    <?php endif; ?>

</div>
<!-- ===============================
     AVAILABLE BOOKS (STOCK OVERVIEW)
================================ -->

<div class="dash-card full-width-card">

    <h3 class="section-title">📚 Available Books</h3>
    <p class="section-sub">Live stock overview of library books</p>

    <?php if ($availableBooks->num_rows > 0): ?>
        <div class="table-wrapper">
            <table class="admin-table">

                <thead>
                    <tr>
                        <th>Book Name</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Source</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($b = $availableBooks->fetch_assoc()): ?>
                    <?php
                        $qty = (int)$b['quantity'];
                        $isAvailable = $qty > 0;
                    ?>
                    <tr>

                        <td><?= htmlspecialchars($b['book_name']) ?></td>
                        <td><?= htmlspecialchars($b['author']) ?></td>
                        <td><?= htmlspecialchars($b['category']) ?></td>

                        <td>
                            <strong><?= $qty ?></strong>
                        </td>

                        <td>
                            <span class="status-badge <?= $isAvailable ? 'active' : 'blocked' ?>">
                                <?= $isAvailable ? 'Available' : 'Out of Stock' ?>
                            </span>
                        </td>

                        <td>
                            <?= ucfirst(htmlspecialchars($b['source'] ?? 'manual')) ?>
                        </td>

                    </tr>
                <?php endwhile; ?>
                </tbody>

            </table>
        </div>
    <?php else: ?>
        <p class="empty-text">No books found in library.</p>
    <?php endif; ?>

</div>

</div> 
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('requestsChart');

new Chart(ctx, {
    type: 'pie',   // ✅ REAL PIE
    data: {
        labels: [
            'Borrow Pending',
            'Borrow Approved',
            'Borrow Rejected',
            'Donation Pending',
            'Donation Approved',
            'Donation Rejected'
        ],
        datasets: [{
            data: [
                <?= $borrowStats['pending'] ?>,
                <?= $borrowStats['approved'] ?>,
                <?= $borrowStats['rejected'] ?>,
                <?= $donStats['pending'] ?>,
                <?= $donStats['approved'] ?>,
                <?= $donStats['rejected'] ?>
            ],
            backgroundColor: [
                '#f59e0b',
                '#10b981',
                '#ef4444',
                '#f97316',
                '#22c55e',
                '#dc2626'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // 🔥 allows full height
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 12,
                    boxWidth: 12,
                    font: {
                        size: 12
                    }
                }
            }
        }
    }
});
</script>

<?php include("../footer.php"); ?>