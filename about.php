<?php
session_start();
?>
<?php include("header.php"); ?>

<link rel="stylesheet" href="assets/css/dashboard.css">

<div class="dashboard-wrapper">

    <!-- ================= PAGE HEADING ================= -->
    <div class="page-heading">
        <h1>About the Library Management System</h1>
        <p>
            A centralized platform built to manage library operations
            with clarity, control, and accountability.
        </p>
    </div>

    <!-- ================= TOP ABOUT GRID ================= -->
    <div class="about-grid">

        <div class="about-card dark">
            <h3>What We Do</h3>
            <p>
                We provide a structured system to manage book borrowing,
                returns, and donations in a controlled library environment.
            </p>
        </div>

        <div class="about-card dark">
            <h3>How It Helps</h3>
            <p>
                Students can request, track, and return books easily,
                while administrators manage approvals and inventory
                from a single dashboard.
            </p>
        </div>

        <div class="about-card dark">
            <h3>Who Uses It</h3>
            <p>
                Registered students use the system for borrowing and
                donations, while librarians and administrators oversee
                all transactions and records.
            </p>
        </div>

        <div class="about-card dark">
            <h3>Why It Matters</h3>
            <p>
                The system removes manual tracking, reduces errors,
                and ensures transparency across every library action.
            </p>
        </div>

    </div>

    <!-- ================= FULL WIDTH CARD ================= -->
    <div class="about-card dark full-width secondary">

        <h3>How the System Operates</h3>

        <p>
            Every request in the system follows a clear lifecycle.
            A student places a request, the administrator reviews it,
            and once approved, the book is issued for a fixed period.
            Returns and rejections are logged automatically to maintain
            a complete operational history.
        </p>

        <p>
            This approach ensures that no transaction is lost,
            accountability is maintained, and the library inventory
            remains accurate at all times.
        </p>

    </div>

</div>

<?php include("footer.php"); ?>