<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/db.php"; 


//Show noti count on navbar
$countQ = $conn->prepare("
  SELECT COUNT(*) AS total
  FROM notifications
  WHERE user_id=? AND is_read=0
");
$countQ->bind_param("i", $_SESSION['user_id']);
$countQ->execute();
$notifCount = $countQ->get_result()->fetch_assoc()['total'];

?>
<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/Crushify/dashboard/index.php">Crushify</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="/Crushify/dashboard/index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="/Crushify/dashboard/find_match.php">Find Match</a></li>
        <li class="nav-item"><a class="nav-link" href="/Crushify/dashboard/inbox.php">Inbox</a></li>
        <li class="nav-item"><a class="nav-link" href="/Crushify/dashboard/notifications.php">
          Notifications
          <?php if($notifCount > 0) : ?>
            <span class="badge bg-danger"><?= $notifCount ?> </span>
          <?php endif; ?>
          </a>
        </li>
      </ul>

      <div class="dropdown">
        <a class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" href="#">
          <?= htmlspecialchars($_SESSION["user_name"] ?? "Account") ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="/Crushify/dashboard/profile.php">Profile</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="/Crushify/auth/logout.php">Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>