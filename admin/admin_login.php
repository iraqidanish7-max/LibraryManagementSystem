<?php
session_start();
require_once("../config/db.php");

$error = "";

/* ===== If already logged in as admin ===== */
if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") {
    header("Location: admin_dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (empty($username) || empty($password)) {
        $error = "Please fill all fields.";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, username, password
             FROM admin
             WHERE username = ?
             LIMIT 1"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            if (password_verify($password, $admin["password"])) {

                // ✅ LOGIN SUCCESS
                $_SESSION["admin_id"]   = $admin["id"];
                $_SESSION["admin_name"] = $admin["username"];
                $_SESSION["role"]       = "admin";

                header("Location: admin_dashboard.php");
                exit;

            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<?php include("../header.php"); ?>

<div class="auth-wrapper">

    <div class="auth-card">

        <h2>Admin Login</h2>
        <p class="auth-sub">Authorized access only</p>

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

            <label>Username</label>
            <input
                type="text"
                name="username"
                required
                
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

    </div>

</div>

<?php include("../footer.php"); ?>

<script>
function togglePassword() {
    const input = document.getElementById("password");
    input.type = input.type === "password" ? "text" : "password";
}
</script>