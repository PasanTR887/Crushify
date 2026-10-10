<?php
require_once "../includes/auth_guard.php";
require_once "../config/db.php";

$userId = (int) $_SESSION["user_id"];

$profileQuery = $conn->prepare("
  SELECT full_name, profile_pic, bio, gender, looking_for, dob, location, occupation
  FROM users
  WHERE id = ?
  LIMIT 1
");
$profileQuery->bind_param("i", $userId);
$profileQuery->execute();
$profile = $profileQuery->get_result()->fetch_assoc();

if (!$profile) {
  header("Location: ../auth/logout.php");
  exit();
}

$matchQuery = $conn->prepare("SELECT COUNT(*) AS total FROM matches WHERE user1 = ? OR user2 = ?");
$matchQuery->bind_param("ii", $userId, $userId);
$matchQuery->execute();
$matchCount = (int) $matchQuery->get_result()->fetch_assoc()["total"];

$conversationQuery = $conn->prepare("SELECT COUNT(*) AS total FROM conversations WHERE user1 = ? OR user2 = ?");
$conversationQuery->bind_param("ii", $userId, $userId);
$conversationQuery->execute();
$conversationCount = (int) $conversationQuery->get_result()->fetch_assoc()["total"];

$notificationQuery = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
$notificationQuery->bind_param("i", $userId);
$notificationQuery->execute();
$unreadCount = (int) $notificationQuery->get_result()->fetch_assoc()["total"];

$photoQuery = $conn->prepare("SELECT COUNT(*) AS total FROM user_photos WHERE user_id = ?");
$photoQuery->bind_param("i", $userId);
$photoQuery->execute();
$photoCount = (int) $photoQuery->get_result()->fetch_assoc()["total"];

$completionFields = [
  !empty($profile["bio"]),
  !empty($profile["gender"]),
  !empty($profile["looking_for"]),
  !empty($profile["dob"]),
  !empty($profile["location"]),
  !empty($profile["occupation"]),
  !empty($profile["profile_pic"]) && $profile["profile_pic"] !== "default.jpg",
  $photoCount > 0,
];
$profileCompletion = (int) round(count(array_filter($completionFields)) / count($completionFields) * 100);

$suggestionQuery = $conn->prepare("
  SELECT u.id, u.full_name, u.profile_pic, u.location, u.dob, u.occupation
  FROM users u
  WHERE u.id <> ?
    AND NOT EXISTS (
      SELECT 1 FROM likes l
      WHERE l.from_user = ? AND l.to_user = u.id
    )
  ORDER BY u.created_at DESC
  LIMIT 3
");
$suggestionQuery->bind_param("ii", $userId, $userId);
$suggestionQuery->execute();
$suggestions = $suggestionQuery->get_result();

$firstName = explode(" ", trim($profile["full_name"]))[0];
$profilePhoto = basename($profile["profile_pic"] ?: "default.jpg");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Home - Crushify</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/Crushify/assets/css/style.css">
</head>
<body class="bg-soft">
<?php include "../includes/navbar.php"; ?>

<main class="container py-4 home-shell dashboard-home">
  <section class="dashboard-welcome">
    <div class="dashboard-welcome-copy">
      <span class="dashboard-eyebrow"><span aria-hidden="true">&#10022;</span> YOUR NEXT CHAPTER STARTS HERE</span>
      <h1>Good to see you,<br><?= htmlspecialchars($firstName) ?> <span aria-hidden="true">&hearts;</span></h1>
      <p>Make room for a connection that feels like you.</p>
      <div class="dashboard-welcome-actions">
        <a class="btn btn-crushify" href="find_match.php">Discover people <span aria-hidden="true">&rarr;</span></a>
        <a class="dashboard-welcome-secondary" href="profile.php">Make your profile shine</a>
      </div>
    </div>
    <div class="dashboard-welcome-photo">
      <img src="../assets/uploads/<?= rawurlencode($profilePhoto) ?>" alt="Your profile photo" onerror="this.src='../assets/uploads/default.jpg';">
    </div>
    <div class="dashboard-welcome-decoration" aria-hidden="true">&hearts;</div>
    <span class="dashboard-welcome-tag"><span aria-hidden="true">&#9679;</span> YOUR STORY, YOUR WAY</span>
  </section>

  <section class="dashboard-stats" aria-label="Your activity">
    <a class="dashboard-stat dashboard-stat-matches" href="find_match.php">
      <span class="dashboard-stat-icon" aria-hidden="true">&hearts;</span>
      <span class="dashboard-stat-value"><?= $matchCount ?></span>
      <span class="dashboard-stat-label">Your matches</span>
      <span class="dashboard-stat-arrow" aria-hidden="true">&nearr;</span>
    </a>
    <a class="dashboard-stat dashboard-stat-conversations" href="inbox.php">
      <span class="dashboard-stat-icon" aria-hidden="true">&#9993;</span>
      <span class="dashboard-stat-value"><?= $conversationCount ?></span>
      <span class="dashboard-stat-label">Conversations</span>
      <span class="dashboard-stat-arrow" aria-hidden="true">&nearr;</span>
    </a>
    <a class="dashboard-stat dashboard-stat-updates" href="notifications.php">
      <span class="dashboard-stat-icon" aria-hidden="true">&#10022;</span>
      <span class="dashboard-stat-value"><?= $unreadCount ?></span>
      <span class="dashboard-stat-label">New updates</span>
      <span class="dashboard-stat-arrow" aria-hidden="true">&nearr;</span>
    </a>
  </section>

  <section class="dashboard-content-grid">
    <div class="dashboard-main-column">
      <div class="dashboard-section-heading">
        <div>
          <span class="dashboard-eyebrow">CURATED FOR YOU</span>
          <h2>People you might like</h2>
        </div>
        <a class="dashboard-text-link" href="find_match.php">See everyone <span aria-hidden="true">&rarr;</span></a>
      </div>

      <?php if ($suggestions->num_rows === 0): ?>
        <div class="dashboard-empty cx-card">
          <div class="dashboard-empty-icon" aria-hidden="true">&hearts;</div>
          <h3>You're all caught up</h3>
          <p>New faces will show up here as more people join Crushify.</p>
          <a class="btn btn-cx-outline" href="find_match.php">Explore matches</a>
        </div>
      <?php else: ?>
        <div class="dashboard-suggestions">
          <?php while ($person = $suggestions->fetch_assoc()): ?>
            <?php
              $personPhoto = basename($person["profile_pic"] ?: "default.jpg");
              $personAge = null;
              if (!empty($person["dob"])) {
                try {
                  $personAge = (new DateTime())->diff(new DateTime($person["dob"]))->y;
                } catch (Exception $exception) {
                  $personAge = null;
                }
              }
            ?>
            <a class="dashboard-person-card" href="view_profile.php?id=<?= (int) $person["id"] ?>">
              <img src="../assets/uploads/<?= rawurlencode($personPhoto) ?>" alt="Profile photo of <?= htmlspecialchars($person["full_name"]) ?>" loading="lazy" onerror="this.src='../assets/uploads/default.jpg';">
              <span class="dashboard-person-shade"></span>
              <span class="dashboard-person-info">
                <strong><?= htmlspecialchars($person["full_name"]) ?><?= $personAge !== null ? ", " . (int) $personAge : "" ?></strong>
                <span><?= htmlspecialchars($person["location"] ?: ($person["occupation"] ?: "New to Crushify")) ?></span>
              </span>
              <span class="dashboard-person-heart" aria-hidden="true">&hearts;</span>
            </a>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>

    <aside class="dashboard-side-column">
      <div class="dashboard-profile-progress">
        <div class="dashboard-section-heading compact">
          <div>
            <span class="dashboard-eyebrow">MAKE A GREAT FIRST IMPRESSION</span>
            <h2>Your profile</h2>
          </div>
          <span class="dashboard-progress-value"><?= $profileCompletion ?>%</span>
        </div>
        <div class="progress dashboard-progress" role="progressbar" aria-label="Profile completion" aria-valuenow="<?= $profileCompletion ?>" aria-valuemin="0" aria-valuemax="100">
          <div class="progress-bar" style="width: <?= $profileCompletion ?>%"></div>
        </div>
        <p><?= $profileCompletion === 100 ? "Your profile is looking great. Keep it up!" : "A few more details can help people get to know you." ?></p>
        <?php if ($profileCompletion < 100): ?>
          <a class="dashboard-text-link" href="profile.php">Complete your profile <span aria-hidden="true">&rarr;</span></a>
        <?php else: ?>
          <a class="dashboard-text-link" href="profile.php">View your profile <span aria-hidden="true">&rarr;</span></a>
        <?php endif; ?>
      </div>

      <div class="dashboard-note">
        <span aria-hidden="true">&#10022;</span>
        <div>
          <strong>Good connections start with you.</strong>
          <p>Be yourself, be kind, and take your time getting to know someone.</p>
        </div>
      </div>
    </aside>
  </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
