<?php
require_once "../includes/auth_guard.php";
require_once "../config/db.php";

$userId = (int) $_SESSION["user_id"];
$markRead = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$markRead->bind_param("i", $userId);
$markRead->execute();

$query = $conn->prepare("
  SELECT type, content, is_read, created_at
  FROM notifications
  WHERE user_id = ?
  ORDER BY created_at DESC, id DESC
  LIMIT 100
");
$query->bind_param("i", $userId);
$query->execute();
$notifications = $query->get_result();

$notificationIcons = [
  "match" => "&hearts;",
  "message" => "&#9993;",
  "new_user" => "&#10022;",
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Notifications - Crushify</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/Crushify/assets/css/style.css">
</head>
<body class="bg-soft">
<?php include "../includes/navbar.php"; ?>

<main class="container py-4 container-narrow">
  <header class="notification-heading">
    <div>
      <span class="dashboard-eyebrow">A LITTLE SOMETHING FOR YOU</span>
      <h1>Your updates</h1>
    </div>
    <a class="btn btn-cx-outline" href="find_match.php">Discover people</a>
  </header>

  <?php if ($notifications->num_rows === 0): ?>
    <section class="cx-card dashboard-empty">
      <div class="dashboard-empty-icon" aria-hidden="true">&hearts;</div>
      <h2>No updates just yet</h2>
      <p>When something happens on Crushify, you'll find it here.</p>
    </section>
  <?php else: ?>
    <section class="cx-card" aria-label="Notifications">
      <ul class="list-group list-group-flush">
        <?php while ($notification = $notifications->fetch_assoc()): ?>
          <?php
            $createdAt = strtotime($notification["created_at"]);
            $formattedDate = $createdAt ? date("M j, Y \\a\\t g:i A", $createdAt) : $notification["created_at"];
          ?>
          <li class="list-group-item notification-item <?= $notification["is_read"] ? "" : "unread" ?>">
            <span class="notification-icon" aria-hidden="true"><?= $notificationIcons[$notification["type"]] ?? "&hearts;" ?></span>
            <div class="notification-content">
              <div class="fw-semibold"><?= htmlspecialchars($notification["content"]) ?></div>
              <time datetime="<?= htmlspecialchars(date("c", $createdAt ?: time())) ?>"><?= htmlspecialchars($formattedDate) ?></time>
            </div>
          </li>
        <?php endwhile; ?>
      </ul>
    </section>
  <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
