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
                $success = "Account created! Please login." ;
            }else {
                $error = "Someting went wrong. Try again.";
            }
        }
    }
}

$newUserId = $conn->insert_id;

//notify existing users
$notify = $conn->query("
  INSERT INTO notifications (user_id, type, content)
  SELECT id, 'new_user', 'A new user just joined Crushify 💕'
  FROM users
  WHERE id != $newUserId
  ");
?>
<!doctype html>
<html>
<head>
  <title>Crushify - Register</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:520px;">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h3 class="mb-3">Create Account</h3>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input class="form-control" name="full_name" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input class="form-control" type="password" name="password" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Confirm Password</label>
          <input class="form-control" type="password" name="confirm_password" required>
        </div>
        <button class="btn btn-primary w-100">Sign up</button>
      </form>

      <div class="mt-3 text-center">
        Already have an account? <a href="login.php">Login</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>