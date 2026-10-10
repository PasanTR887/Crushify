<?php
require_once "../includes/auth_guard.php";
require_once "../config/db.php";

$profileId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$profileId || $profileId < 1) {
  http_response_code(404);
  $user = null;
} else {
  $stmt = $conn->prepare("
    SELECT full_name, dob, location, bio, profile_pic,
           occupation, marital_status, relationship_type
    FROM users
    WHERE id = ?
    LIMIT 1
  ");
  $stmt->bind_param("i", $profileId);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();

  if (!$user) {
    http_response_code(404);
  }
}

$photos = null;
$personality = null;
if ($user) {
  $photoQuery = $conn->prepare("SELECT photo FROM user_photos WHERE user_id = ? ORDER BY created_at DESC, id DESC");
  $photoQuery->bind_param("i", $profileId);
  $photoQuery->execute();
  $photos = $photoQuery->get_result();

  $personalityQuery = $conn->prepare("
    SELECT personality_type, hobbies, interests, about_me
    FROM user_personality
    WHERE user_id = ?
    LIMIT 1
  ");
  $personalityQuery->bind_param("i", $profileId);
  $personalityQuery->execute();
  $personality = $personalityQuery->get_result()->fetch_assoc();
}

$age = null;
if ($user && !empty($user["dob"])) {
  try {
    $birthDate = new DateTime($user["dob"]);
    $today = new DateTime("today");
    if ($birthDate <= $today) {
      $age = $birthDate->diff($today)->y;
    }
  } catch (Exception $exception) {
    $age = null;
  }
}

$profilePhoto = $user ? basename($user["profile_pic"] ?: "default.jpg") : "default.jpg";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $user ? htmlspecialchars($user["full_name"]) . " - Crushify" : "Profile not found - Crushify" ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/Crushify/assets/css/style.css">
</head>
<body class="bg-soft">
<?php include "../includes/navbar.php"; ?>

<main class="container py-4 container-narrow">
  <a href="find_match.php" class="btn btn-cx-outline mb-3">← Back to discovery</a>

  <?php if (!$user): ?>
    <section class="cx-card dashboard-empty">
      <div class="dashboard-empty-icon" aria-hidden="true">♡</div>
      <h1 class="cx-title h4">We couldn't find that profile</h1>
      <p>This profile may no longer be available.</p>
      <a class="btn btn-cx" href="find_match.php">Explore people</a>
    </section>
  <?php else: ?>
    <section class="public-profile-hero">
      <div class="public-profile-photo">
        <img
          src="../assets/uploads/<?= rawurlencode($profilePhoto) ?>"
          alt="<?= htmlspecialchars($user["full_name"]) ?>'s profile photo"
          onerror="this.src='../assets/uploads/default.jpg';"
        >
      </div>
      <div class="public-profile-content">
        <span class="public-profile-eyebrow">A CRUSHIFY INTRODUCTION</span>
        <h1><?= htmlspecialchars($user["full_name"]) ?><?= $age !== null ? ", " . (int) $age : "" ?></h1>
        <div class="public-profile-facts">
          <?php if ($user["occupation"]): ?><span class="public-profile-fact"><?= htmlspecialchars($user["occupation"]) ?></span><?php endif; ?>
          <?php if ($user["location"]): ?><span class="public-profile-fact"><?= htmlspecialchars($user["location"]) ?></span><?php endif; ?>
          <?php if ($user["relationship_type"]): ?><span class="public-profile-fact">Looking for <?= htmlspecialchars($user["relationship_type"]) ?></span><?php endif; ?>
          <?php if ($user["marital_status"]): ?><span class="public-profile-fact"><?= htmlspecialchars(ucfirst($user["marital_status"])) ?></span><?php endif; ?>
        </div>
        <p class="public-profile-bio"><?= $user["bio"] ? nl2br(htmlspecialchars($user["bio"])) : "Still writing their story. Explore their photos and interests below." ?></p>
        <a class="btn btn-crushify" href="find_match.php">Discover more people</a>
      </div>
    </section>

    <div class="public-profile-sections">
      <?php if ($photos && $photos->num_rows > 0): ?>
        <section class="public-profile-section">
          <h2>More photos</h2>
          <div class="public-profile-photo-grid">
            <?php while ($photo = $photos->fetch_assoc()): ?>
              <?php $photoName = basename($photo["photo"]); ?>
              <div class="pf-photo">
                <img
                  src="../assets/uploads/<?= rawurlencode($photoName) ?>"
                  alt="Photo of <?= htmlspecialchars($user["full_name"]) ?>"
                  loading="lazy"
                >
              </div>
            <?php endwhile; ?>
          </div>
        </section>
      <?php endif; ?>

      <?php if ($personality): ?>
        <?php
          $interests = [
            "Personality" => $personality["personality_type"],
            "Hobbies" => $personality["hobbies"],
            "Interests" => $personality["interests"],
          ];
          $hasInterests = (bool) array_filter($interests);
        ?>
        <?php if ($hasInterests || $personality["about_me"]): ?>
          <section class="public-profile-section">
            <h2>A little more about them</h2>
            <div class="public-profile-facts">
              <?php foreach ($interests as $label => $value): ?>
                <?php if ($value): ?><span class="public-profile-fact"><strong><?= htmlspecialchars($label) ?>:</strong> <?= htmlspecialchars($value) ?></span><?php endif; ?>
              <?php endforeach; ?>
            </div>
            <?php if ($personality["about_me"]): ?>
              <p class="public-profile-bio mb-0"><?= nl2br(htmlspecialchars($personality["about_me"])) ?></p>
            <?php endif; ?>
          </section>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
