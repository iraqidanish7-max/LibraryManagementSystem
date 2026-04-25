<?php include("header.php"); ?>

<!-- ================= BIG CARD 1 ================= -->
<section class="soft-card">

    <div class="hero-content">
        <h1>Library Management System</h1>
        <p>
            A modern academic platform that helps manage library resources,
            student participation, and book sharing in a simple and organized way.
        </p>

        <div class="hero-actions">
            <a href="student/student_login.php" class="hero-btn borrow">Borrow a Book</a>
            <a href="student/student_login.php" class="hero-btn donate">Donate a Book</a>
        </div>

        <!-- MINI CARDS (INSIDE SAME CARD) -->
        <div class="hero-mini-cards">
            <div class="mini-card">
                <h4>Smart Book Management</h4>
                <p>Maintain digital records of books and circulation.</p>
            </div>

            <div class="mini-card">
                <h4>Student Participation</h4>
                <p>Students can request or donate books under supervision.</p>
            </div>

            <div class="mini-card">
                <h4>Admin Supervision</h4>
                <p>All actions are tracked and approved by librarian.</p>
            </div>
        </div>
    </div>

</section>
<!-- ================= BIG CARD 1 ENDS ================= -->


<!-- ================= BIG CARD 2 ================= -->
<section class="soft-card soft-card-alt">

    <div class="dual-section">

        <!-- LEFT -->
        <div class="dual-left">
            <div class="carousel-card">
                <h2>Designed for Academic Simplicity</h2>

                <div id="libraryCircleCarousel"
                     class="carousel slide"
                     data-bs-ride="carousel"
                     data-bs-interval="3000">

                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img src="assets/images/library1.jpg" alt="">
                        </div>
                        <div class="carousel-item">
                            <img src="assets/images/library2.avif" alt="">
                        </div>
                        <div class="carousel-item">
                            <img src="assets/images/library3.avif" alt="">
                        </div>
                        <div class="carousel-item">
                            <img src="assets/images/library4.avif" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="dual-right">
            <div class="mini-card">
                <h4>Structured Access</h4>
                <p>Clear separation between student actions and admin approvals.</p>
            </div>

            <div class="mini-card">
                <h4>Student-Friendly</h4>
                <p>Minimal steps with clear feedback.</p>
            </div>

            <div class="mini-card">
                <h4>Academic Control</h4>
                <p>Every transaction is tracked and verified.</p>
            </div>
        </div>

    </div>

</section>
<!-- ================= BIG CARD 2 ENDS ================= -->


<section class="closing-strip">
    <p>
        This system is designed to simplify library operations while encouraging
        responsible student involvement in academic resource sharing.
    </p>
</section>

<?php include("footer.php"); ?>