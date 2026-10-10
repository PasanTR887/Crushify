<?php
require_once "../config/db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$error = "";
$success = "" ;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if ($full_name === "" || $email === "" || $password === "") {
        $error = "All fields are required." ;
    }elseif ($password !== $confirm) {
        $error = "Passwords do not match." ;
    }elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters." ; 
    }else {
        $stmt = $conn -> prepare ("SELECT id FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $error = "Email already registered.";
        }else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt2 = $conn->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (?,?,?)");
            $stmt2->bind_param("sss", $full_name,$email,$hash);

            if ($stmt2->execute()) {
                $newUserId = $conn->insert_id;
                $conn->query("
                  INSERT INTO notifications (user_id, type, content)
                  SELECT id, 'new_user', 'A new user just joined Crushify'
                  FROM users
                  WHERE id != $newUserId
                ");
                $success = "Account created! Please login." ;
            }else {
                $error = "Someting went wrong. Try again.";
            }
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Crushify - Register</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/Crushify/assets/css/style.css">
</head>
<body>

<div class="auth-wrap">

  <!-- Left branding panel -->
  <div class="auth-left">
    <div class="brand-badge"><span aria-hidden="true">&hearts;</span> <strong>Crushify</strong></div>
    <div class="brand">Crushify</div>
    <div class="slogan">Join today - meet meaningful matches effortlessly, with a romantic and modern experience.</div>
  </div>

  <!-- Right form panel -->
  <div class="auth-right">
    <div class="auth-card">

      <div class="auth-title">Create Account</div>
      <div class="auth-sub">Let's start your story.</div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label" for="registerName">Full Name</label>
          <input id="registerName" class="form-control" name="full_name" maxlength="120" autocomplete="name" placeholder="Your name" required>
        </div>

        <div class="mb-3">
          <label class="form-label" for="registerEmail">Email</label>
          <input id="registerEmail" class="form-control" type="email" name="email" autocomplete="email" placeholder="you@example.com" required>
        </div>

        <div class="mb-3">
          <label class="form-label" for="registerPassword">Password</label>
          <input id="registerPassword" class="form-control" type="password" name="password" minlength="6" autocomplete="new-password" placeholder="Create a password" required>
        </div>

        <div class="mb-3">
          <label class="form-label" for="confirmPassword">Confirm Password</label>
          <input id="confirmPassword" class="form-control" type="password" name="confirm_password" minlength="6" autocomplete="new-password" placeholder="Repeat password" required>
        </div>

        <button class="btn btn-crushify w-100" type="submit">Create Account</button>
      </form>

      <div class="auth-footer text-center">
        Already have an account? <a class="auth-link" href="login.php">Login</a>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
