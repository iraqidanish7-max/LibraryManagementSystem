<?php
session_start();
require_once("../config/db.php");

/* ===== AUTH PROTECTION ===== */
if (!isset($_SESSION["student_id"])) {
    header("Location: student_login.php");
    exit;
}

/* ===== CACHE PREVENTION ===== */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$studentId   = $_SESSION["student_id"];
$studentName = $_SESSION["student_name"];

/* ===== BORROW SUMMARY ===== */
$borrowSummary = [
    'total'    => 0,
    'pending'  => 0,
    'approved' => 0,
    'returned' => 0,
    'rejected' => 0
];

$bs = $conn->prepare("
    SELECT status, COUNT(*) as cnt
    FROM issue_requests
    WHERE student_id = ?
    GROUP BY status
");
$bs->bind_param("i", $studentId);
$bs->execute();
$bsRes = $bs->get_result();

while ($r = $bsRes->fetch_assoc()) {
    $count = (int)$r['cnt'];
    $status = strtolower($r['status']);

    $borrowSummary['total'] += $count;

    if ($status === 'pending') {
        $borrowSummary['pending'] = $count;
    } elseif ($status === 'approved') {
        $borrowSummary['approved'] = $count;
    } elseif ($status === 'returned') {
        $borrowSummary['returned'] = $count;
    } elseif ($status === 'rejected') {
        $borrowSummary['rejected'] = $count;
    }
}
/* ===== DONATION SUMMARY ===== */
$donSummary = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0
];

$ds = $conn->prepare("
    SELECT status, COUNT(*) as cnt
    FROM donation_requests
    WHERE student_id = ?
    GROUP BY status
");
$ds->bind_param("i", $studentId);
$ds->execute();
$dsRes = $ds->get_result();

while ($r = $dsRes->fetch_assoc()) {
    $donSummary['total'] += $r['cnt'];
    if ($r['status'] === 'Pending') $donSummary['pending'] = $r['cnt'];
    if ($r['status'] === 'Approved') $donSummary['approved'] = $r['cnt'];
}

/* ===== FETCH ISSUE REQUESTS (FULL DATA) ===== */

$issueStmt = $conn->prepare("
    SELECT 
        ir.id AS request_id,   -- ✅ VERY IMPORTANT
        b.book_name,
        b.author,
        b.category,
        b.source,
        b.image_url,
        ir.status,
        ir.request_date,
        ir.issued_at
    FROM issue_requests ir
    JOIN books b ON b.id = ir.book_id
    WHERE ir.student_id = ?
    ORDER BY ir.request_date DESC
");

$issueStmt->bind_param("i", $studentId);
$issueStmt->execute();
$issueResults = $issueStmt->get_result();

/* ===== FETCH DONATION REQUESTS ===== */
$donStmt = $conn->prepare("
    SELECT book_name, status, request_date
    FROM donation_requests
    WHERE student_id = ?
    ORDER BY request_date DESC
");
$donStmt->bind_param("i", $studentId);
$donStmt->execute();
$donationResults = $donStmt->get_result();
/* ===== FETCH RETURNED BOOKS ===== */
$returnStmt = $conn->prepare("
    SELECT
        b.book_name,
        b.author,
        b.category,
        b.image_url,
        ir.request_date,
        ir.issued_at,
        ir.returned_at
    FROM issue_requests ir
    JOIN books b ON b.id = ir.book_id
    WHERE ir.student_id = ?
      AND ir.status = 'Returned'
    ORDER BY ir.returned_at DESC
");
$returnStmt->bind_param("i", $studentId);
$returnStmt->execute();
$returnResults = $returnStmt->get_result();
?>

<?php include("../header.php"); ?>
<link rel="stylesheet" href="../assets/css/dashboard.css">

<div class="dashboard-wrapper">

    <!-- ================= TOP SECTION ================= -->
    <div class="dashboard-top">

        <!-- LEFT MAIN CARD -->
        <div class="dash-card large-card">
            <h2 class="welcome-title">Welcome, <?= htmlspecialchars($studentName) ?> 👋</h2>
            <p class="welcome-sub">Manage your library activity from one place</p>

            <div class="action-grid">

                <div class="action-card">
                    <h4>📚 Available Books</h4>
                    <p>Browse and request books instantly</p>
                    <a href="available_books.php" class="btn-primary">Explore Books</a>
                </div>

                <div class="action-card">
                    <h4>🎁 Donate a Book</h4>
                    <p>Share books with other students</p>
                    <a href="donate_book.php" class="btn-secondary">Donate Now</a>
                </div>

                <div class="action-card stat-card">
    <h4>📘 Borrow Requests</h4>

    <p>Total: <?= $borrowSummary['total'] ?></p>
    <p>Pending: <?= $borrowSummary['pending'] ?></p>
    <p>Approved: <?= $borrowSummary['approved'] ?></p>
    <p>Returned: <?= $borrowSummary['returned'] ?></p>
    <p>Rejected: <?= $borrowSummary['rejected'] ?></p>
</div>
                <div class="action-card stat-card">
                    <h4>🎁 Donation Requests</h4>
                    <p>Total: <?= $donSummary['total'] ?></p>
                    <p>Pending: <?= $donSummary['pending'] ?></p>
                    <p>Approved: <?= $donSummary['approved'] ?></p>
                </div>

            </div>
        </div>

        <!-- RIGHT INFO CARD -->
        <div class="dash-card info-card">
            <h4>How Library Works</h4>
            <ul>
                <li>Browse available books</li>
                <li>Request books in one click</li>
                <li>Only 2 books can be requested simultaneously</li>
                <li>Admin approves requests</li>
                <li>Admin can reject request</li>
                <li>15-day issue period</li>
                <li>Admin can deactivate account if fail to comply rules</li>
                <li>students can donate books</li>

            </ul>
        </div>

    </div>
<!-- ================= BOTTOM SECTION ================= -->
<div class="dashboard-bottom">

    <!-- ================= LEFT (70%) : MY ACTIVE REQUESTS ================= -->
    <div>

        <h4 style="margin-bottom:16px;">📘 My Active Requests</h4>

        <?php
        $hasActive = false;
        mysqli_data_seek($issueResults, 0);
        ?>

        <?php while ($row = $issueResults->fetch_assoc()): ?>
            <?php
                $status = strtolower($row['status']);
            ?>

            <?php if ($status === 'pending' || $status === 'approved'): ?>
                <?php $hasActive = true; ?>

                <div class="history-item rich">

                    <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="Book">

                    <div class="history-content">

                        <h5><?= htmlspecialchars($row['book_name']) ?></h5>

                        <div class="history-meta-line">
                            <?= htmlspecialchars($row['author']) ?> · <?= htmlspecialchars($row['category']) ?>
                        </div>

                        <!-- STATUS -->
                        <span class="status <?= $status ?>">
                            <?= strtoupper($status) ?>
                        </span>

                        <!-- REQUEST DATE -->
                        <small class="history-date">
                            Requested on <?= date("d M Y", strtotime($row['request_date'])) ?>
                        </small>

                        <!-- COUNTDOWN (ONLY APPROVED) -->
                        <?php if ($status === 'approved' && !empty($row['issued_at'])): ?>
                            <?php
                                $daysLeft = 15 - (new DateTime($row['issued_at']))->diff(new DateTime())->days;
                                $dangerClass = ($daysLeft <= 0) ? 'danger' : '';
                            ?>
                            <div class="countdown <?= $dangerClass ?>">
                                <?= $daysLeft > 0
                                    ? "⏳ $daysLeft days remaining"
                                    : "⚠️ Due date passed" ?>
                            </div>

                            <!-- RETURN BUTTON -->
                            <form method="POST" action="return_book.php" style="margin-top:10px;">
                                <input type="hidden" name="request_id" value="<?= (int)$row['request_id'] ?>">
                                <button type="submit" class="btn-small secondary">
                                    Return Book
                                </button>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>

            <?php endif; ?>
        <?php endwhile; ?>

        <?php if (!$hasActive): ?>
            <p class="empty-text">No active borrow requests.</p>
        <?php endif; ?>

    </div>

    <!-- ================= RIGHT (30%) : MY DONATION REQUESTS ================= -->
    <div class="dash-card history-card">
        <h4>🎁 My Donation Requests</h4>

        <?php if ($donationResults->num_rows > 0): ?>
            <?php while ($row = $donationResults->fetch_assoc()): ?>
                <div class="donation-history-item">
                    <strong><?= htmlspecialchars($row['book_name']) ?></strong>

                    <span class="status <?= strtolower($row['status']) ?>">
                        <?= htmlspecialchars($row['status']) ?>
                    </span>

                    <small>
                        Requested on <?= date("d M Y", strtotime($row['request_date'])) ?>
                    </small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty-text">No donation requests yet.</p>
        <?php endif; ?>
    </div>

</div>

<!-- ================= MY HISTORY (FULL WIDTH BELOW) ================= -->
<div class="dash-card returned-history-card">

    <h4>📚 My History</h4>

    <?php
    $hasHistory = false;
    mysqli_data_seek($issueResults, 0);
    ?>

    <?php while ($row = $issueResults->fetch_assoc()): ?>
        <?php
            $status = strtolower($row['status']);
        ?>

        <?php if ($status === 'returned' || $status === 'rejected'): ?>
            <?php $hasHistory = true; ?>

            <div class="returned-item">

                <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="Book">

                <div class="returned-content">

                    <h5><?= htmlspecialchars($row['book_name']) ?></h5>

                    <div class="returned-meta">
                        <?= htmlspecialchars($row['author']) ?> · <?= htmlspecialchars($row['category']) ?>
                    </div>

                    <?php if ($status === 'returned'): ?>
                        <div class="returned-dates">
                            Issued on <?= date("d M Y", strtotime($row['issued_at'])) ?> ·
                            Returned on <?= date("d M Y", strtotime($row['returned_at'])) ?>
                        </div>
                        <span class="status returned">Returned</span>
                    <?php else: ?>
                        <small class="history-date">
                            Requested on <?= date("d M Y", strtotime($row['request_date'])) ?>
                        </small>
                        <span class="status rejected">Rejected</span>
                    <?php endif; ?>

                </div>
            </div>

        <?php endif; ?>
    <?php endwhile; ?>

    <?php if (!$hasHistory): ?>
        <p class="empty-text">No history yet.</p>
    <?php endif; ?>

</div>
</div>
<?php include("../footer.php"); ?>