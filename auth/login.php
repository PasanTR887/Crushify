<?php
require_once "../config/db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare("SELECT id, full_name, password_hash FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows === 1){
        $user = $res->fetch_assoc();
        if(password_verify($password, $user["password_hash"])){
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["full_name"];
            header("Location:/Crushify/dashboard/index.php");
            exit();
        }
    }
    $error = "Invalid email or password.";
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Crushify - Login</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/Crushify/assets/css/style.css">
</head>
<body>

<div class="auth-wrap">

  <!-- Left branding panel -->
  <div class="auth-left">
    <div class="brand-badge"><span aria-hidden="true">&hearts;</span> <strong>Crushify</strong></div>
    <div class="brand">Crushify</div>
    <div class="slogan">Your Match, Your Story - a safe space to meet, chat, and discover genuine connections.</div>
  </div>

  <!-- Right form panel -->
  <div class="auth-right">
    <div class="auth-card">

      <div class="auth-title">Welcome Back</div>
      <div class="auth-sub">Sign in to continue your dating journey.</div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label" for="loginEmail">Email</label>
          <input id="loginEmail" class="form-control" type="email" name="email" autocomplete="email" placeholder="you@example.com" required>
        </div>

        <div class="mb-3">
          <label class="form-label" for="loginPassword">Password</label>
          <input id="loginPassword" class="form-control" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
        </div>

        <button class="btn btn-crushify w-100" type="submit">Sign In</button>
      </form>

      <div class="auth-footer text-center">
        New here? <a class="auth-link" href="register.php">Create account</a>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
