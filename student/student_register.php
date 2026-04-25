<?php
include("../header.php");
require_once("../config/db.php");

$success = false;
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name     = trim($_POST['name']);
    $roll_no  = trim($_POST['roll_no']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Passwords do not match";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare(
            "INSERT INTO students (name, roll_no, email, phone, password)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssss", $name, $roll_no, $email, $phone, $hashedPassword);

        if ($stmt->execute()) {
           header("Location: student_login.php?registered=1&email=" . urlencode($email));
exit;
        } else {
            $error = "Email already exists or registration failed.";
        }
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card">

        <h2>Student Registration</h2>
        <p class="auth-sub">Create your library account</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">

            <label>Full Name</label>
            <input type="text" name="name" required>

            <label>Roll No</label>
            <input type="text" name="roll_no" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Phone</label>
            <input type="text" name="phone" required>

            <label>Password</label>
            <div class="password-box">
                <input type="password" name="password" id="password" required>
                <span onclick="togglePassword('password')">👁</span>
            </div>

            <label>Confirm Password</label>
            <div class="password-box">
                <input type="password" name="confirm_password" id="confirm_password" required>
                <span onclick="togglePassword('confirm_password')">👁</span>
            </div>

            <button type="submit" class="auth-btn">Register</button>

        </form>

        <p class="auth-footer">
            Already registered?
            <a href="student_login.php">Login</a>
        </p>

    </div>
</div>

<script>
function togglePassword(id) {
    const field = document.getElementById(id);
    field.type = field.type === "password" ? "text" : "password";
}
</script>

<?php include("../footer.php"); ?>