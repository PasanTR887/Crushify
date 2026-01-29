<?php 
require_once "../includes/auth_guard.php";
require_once "../config/db.php";

$userId = $_SESSION["user_id"];

//mark all as read 
$conn->query("
    UPDATE notifications
    SET is_read=1
    WHERE user_id=$userId
");

//fetch notifications
$res = $conn->query("
    SELECT * FROM notifications
    WHERE user_id=$userId
    ORDER BY created_at DESC
");
?>
<!doctype html>
<html>
<head>
  <title>Notifications - Crushify</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<?php include "../includes/navbar.php"; ?>

<div class="container py-4">
  <h3 class="mb-4">Notifications 🔔</h3>

  <?php if ($res->num_rows === 0): ?>
    <div class="alert alert-info">No notifications yet 😊</div>
  <?php endif; ?>

  <ul class="list-group">
    <?php while ($n = $res->fetch_assoc()): ?>
      <li class="list-group-item">
        <?= htmlspecialchars($n['content']) ?>
        <div class="text-muted small">
          <?= $n['created_at'] ?>
        </div>
      </li>
    <?php endwhile; ?>
  </ul>
</div>
</body>
</html>
