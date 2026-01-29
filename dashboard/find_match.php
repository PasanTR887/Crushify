<?php
require_once "../includes/auth_guard.php";
require_once "../config/db.php";

$currentUser = $_SESSION["user_id"];

$sql = "
SELECT * FROM users
WHERE id != ?
AND id NOT IN (
    SELECT to_user FROM likes WHERE from_user = ?
)
LIMIT 10
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $currentUser, $currentUser);
$stmt->execute();
$users = $stmt->get_result();
?>
<!doctype html>
<html>
<head>
  <title>Find Match - Crushify</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<?php include "../includes/navbar.php"; ?>

<div class="container py-4">
  <h3 class="mb-4">Find Your Match 💗</h3>

  <div class="row g-4">
    <?php while ($u = $users->fetch_assoc()): ?>
      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <img src="../assets/uploads/<?= htmlspecialchars($u['profile_pic']) ?>"
               class="card-img-top"
               onerror="this.src='../assets/img/default.png'">

          <div class="card-body">
            <h5><?= htmlspecialchars($u['full_name']) ?></h5>
            <p class="text-muted"><?= htmlspecialchars($u['location'] ?? 'Unknown') ?></p>
            <p><?= htmlspecialchars($u['bio'] ?? 'No bio yet.') ?></p>
          </div>

          <div class="card-footer d-flex justify-content-between">
            <form method="post" action="match_action.php">
              <input type="hidden" name="to_user" value="<?= $u['id'] ?>">
              <input type="hidden" name="action" value="pass">
              <button class="btn btn-outline-danger">❌ Pass</button>
            </form>

            <form method="post" action="match_action.php">
              <input type="hidden" name="to_user" value="<?= $u['id'] ?>">
              <input type="hidden" name="action" value="like">
              <button class="btn btn-outline-success">❤️ Like</button>
            </form>
          </div>
        </div>
      </div>
    <?php endwhile; ?>

    <?php if ($users->num_rows === 0): ?>
      <div class="col-12">
        <div class="alert alert-info">
          No more users to show right now 😌
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>