<?php
session_start();
require_once("../config/db.php");

$error = "";
$success = "";

/* ===== Show success message after registration ===== */
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = "Registration successful. Please login.";
}

// Auto-fill email after registration
$prefillEmail = $_GET['email'] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $error = "Please fill all fields.";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, email, password, status 
             FROM students 
             WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $student = $result->fetch_assoc();

            if ($student["status"] === "blocked") {
                $error = "Your account has been blocked. Contact admin.";
            } elseif (password_verify($password, $student["password"])) {

                // LOGIN SUCCESS
                $_SESSION["student_id"]    = $student["id"];
                $_SESSION["student_name"]  = $student["name"];
                $_SESSION["student_email"] = $student["email"];
                $_SESSION["role"]          = "student";

                header("Location: student_dashboard.php");
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<?php include("../header.php"); ?>

<div class="auth-wrapper">

    <div class="auth-card">

        <h2>Student Login</h2>
        <p class="auth-sub">Access your library account</p>

        <!-- ✅ SUCCESS MESSAGE -->
        <?php if (!empty($success)): ?>
            <div style="
                background:#ecfdf5;
                color:#065f46;
                border-left:5px solid #0f766e;
                padding:12px 14px;
                border-radius:8px;
                margin-bottom:14px;
                font-size:0.9rem;
            ">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- ❌ ERROR MESSAGE -->
        <?php if (!empty($error)): ?>
            <div style="
                background:#fef2f2;
                color:#b91c1c;
                border-left:5px solid #b91c1c;
                padding:12px 14px;
                border-radius:8px;
                margin-bottom:14px;
                font-size:0.9rem;
            ">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label>Email</label>
            <input
                type="email"
                name="email"
                required
                value="<?= htmlspecialchars($prefillEmail) ?>"
            >

            <label>Password</label>
            <div class="password-box">
                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                >
                <span onclick="togglePassword()">👁</span>
            </div>

            <button type="submit" class="auth-btn">
                Login
            </button>
        </form>

        <div class="auth-footer">
            Don’t have an account?
            <a href="student_register.php">Register here</a>
        </div>

    </div>

</div>

<?php include("../footer.php"); ?>

<script>
function togglePassword() {
    const input = document.getElementById("password");
    input.type = input.type === "password" ? "text" : "password";
}
</script>