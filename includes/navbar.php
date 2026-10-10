<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "../config/db.php";

$current = basename($_SERVER['PHP_SELF']);
$userId = (int) ($_SESSION['user_id'] ?? 0);
$userPhoto = "default.jpg";

// Notification count
$notifCount = 0;
if ($userId > 0) {
  $profileQuery = $conn->prepare("SELECT profile_pic FROM users WHERE id = ? LIMIT 1");
  $profileQuery->bind_param("i", $userId);
  $profileQuery->execute();
  $profile = $profileQuery->get_result()->fetch_assoc();
  if ($profile && $profile["profile_pic"]) {
    $userPhoto = basename($profile["profile_pic"]);
  }

  $q = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE user_id=? AND is_read=0
  ");
  $q->bind_param("i", $userId);
  $q->execute();
  $notifCount = (int) $q->get_result()->fetch_assoc()['total'];
}
?>

<nav class="navbar navbar-expand-lg cx-nav sticky-top" aria-label="Main navigation">
  <div class="container">

    <a class="navbar-brand cx-brand-text" href="/Crushify/dashboard/index.php">
      <span class="brand-c">C</span>rushify
      <span class="brand-heart" aria-hidden="true">&hearts;</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#cxNav" aria-controls="cxNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="cxNav">
      <ul class="navbar-nav me-auto gap-1">
        <li class="nav-item">
          <a class="nav-link <?= $current === 'index.php' ? 'active' : '' ?>" href="/Crushify/dashboard/index.php" <?= $current === 'index.php' ? 'aria-current="page"' : '' ?>>Home</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= $current === 'find_match.php' || $current === 'view_profile.php' ? 'active' : '' ?>" href="/Crushify/dashboard/find_match.php" <?= $current === 'find_match.php' ? 'aria-current="page"' : '' ?>>Discover</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= $current === 'inbox.php' ? 'active' : '' ?>" href="/Crushify/dashboard/inbox.php" <?= $current === 'inbox.php' ? 'aria-current="page"' : '' ?>>Inbox</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= $current === 'notifications.php' ? 'active' : '' ?>" href="/Crushify/dashboard/notifications.php" <?= $current === 'notifications.php' ? 'aria-current="page"' : '' ?>>
            Updates
            <?php if ($notifCount > 0): ?>
              <span class="badge badge-cx" aria-label="<?= $notifCount ?> unread notifications"><?= $notifCount > 99 ? '99+' : $notifCount ?></span>
            <?php endif; ?>
          </a>
        </li>
      </ul>

      <div class="dropdown">
        <button class="btn cx-account dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <span class="cx-account-photo">
            <img src="/Crushify/assets/uploads/<?= rawurlencode($userPhoto) ?>" alt="">
          </span>
          <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Account') ?></span>
        </button>

        <ul class="dropdown-menu dropdown-menu-end cx-account-menu">
          <li>
            <a class="dropdown-item" href="/Crushify/dashboard/profile.php">Edit profile</a>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item text-danger" href="/Crushify/auth/logout.php">Sign out</a>
          </li>
        </ul>
      </div>

    </div>
  </div>
</nav>

