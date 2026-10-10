<?php
require_once "../includes/auth_guard.php";
require_once "../config/db.php";

$currentUser = $_SESSION["user_id"];

$preferenceQuery = $conn->prepare("SELECT gender, looking_for FROM users WHERE id = ? LIMIT 1");
$preferenceQuery->bind_param("i", $currentUser);
$preferenceQuery->execute();
$preferences = $preferenceQuery->get_result()->fetch_assoc();
$lookingFor = $preferences["looking_for"] ?? "";
$currentGender = $preferences["gender"] ?? "";

/*
  Get users you haven't liked/passed yet
*/
$sql = "
SELECT u.id, u.full_name, u.profile_pic, u.dob, u.occupation, u.location,
  (SELECT COUNT(*) FROM likes l WHERE l.to_user = u.id AND l.action='like') AS like_count
FROM users u
WHERE u.id != ?
AND u.id NOT IN (SELECT to_user FROM likes WHERE from_user=?)
AND (? = '' OR u.gender IS NULL OR u.gender = '' OR u.gender = ?)
AND (? = '' OR u.looking_for IS NULL OR u.looking_for = '' OR u.looking_for = ?)
ORDER BY u.created_at DESC
LIMIT 12
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iissss", $currentUser, $currentUser, $lookingFor, $lookingFor, $currentGender, $currentGender);
$stmt->execute();
$users = $stmt->get_result();

/* helper: age */
function calcAge($dob){
  if (!$dob) return null;
  try {
    $d = new DateTime($dob);
    $now = new DateTime();
    return $now->diff($d)->y;
  } catch(Exception $e){
    return null;
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Discover - Crushify</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/Crushify/assets/css/style.css">
</head>
<body class="bg-soft">

<?php include "../includes/navbar.php"; ?>

<div class="container py-4 container-narrow">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <span class="dashboard-eyebrow">A LITTLE SPARK CAN GO A LONG WAY</span>
      <h1 class="cx-title mb-1">Meet someone new</h1>
      <div class="cx-muted">Explore profiles and send a like when someone catches your eye.</div>
    </div>

    <a class="btn btn-cx-outline" href="/Crushify/dashboard/profile.php">Edit Profile</a>
  </div>

  <div class="row g-4">

    <?php if ($users->num_rows === 0): ?>
      <div class="col-12">
        <div class="cx-card p-4 text-center">
          <div class="dashboard-empty-icon" aria-hidden="true">&hearts;</div>
          <h2 class="cx-title mb-1">No new profiles right now</h2>
          <div class="cx-muted">Check back soon. New people are joining all the time.</div>
        </div>
      </div>
    <?php endif; ?>

    <?php while ($u = $users->fetch_assoc()): ?>
      <?php
        $age = calcAge($u['dob'] ?? null);

        // occupation fallback
        $job = trim($u['occupation'] ?? "");
        if ($job === "") $job = "Crushify Member";

        $likeCount = intval($u['like_count'] ?? 0);
      ?>

      <div class="col-sm-6 col-lg-4">

        <div class="pro-card">

            <!-- Profile Image -->
            <img
              class="pro-card-img"
              src="../assets/uploads/<?= rawurlencode(basename($u['profile_pic'] ?: 'default.jpg')) ?>"
              alt="Profile"
              onerror="this.src='../assets/uploads/default.jpg';"
            >
            <a class="pro-card-link"
               href="view_profile.php?id=<?= (int)$u['id'] ?>"
               aria-label="View <?= htmlspecialchars($u['full_name']) ?>'s profile"></a>

            <!-- Top badges -->
            <div class="pro-top">

              <div class="pro-likebadge" title="Likes">
                &hearts; <?= $likeCount ?>
              </div>

              <!-- PASS -->
              <form method="post" action="match_action.php" class="m-0">
                <input type="hidden" name="to_user" value="<?= (int)$u['id'] ?>">
                <input type="hidden" name="action" value="pass">
                <button class="pro-x" type="submit" title="Pass">&times;</button>
              </form>

            </div>

            <!-- Messaging opens only for mutual matches. -->
            <a class="pro-msg" href="/Crushify/dashboard/inbox.php?user=<?= (int)$u['id'] ?>" title="Message">
              &#9993;
            </a>

            <!-- Bottom Info -->
            <div class="pro-info">
              <a class="pro-profile-link" href="view_profile.php?id=<?= (int)$u['id'] ?>">
                <div class="pro-name">
                <?= htmlspecialchars($u['full_name']) ?>
                </div>

                <div class="pro-job">
                <?= htmlspecialchars($job) ?>
                </div>

                <?php if ($age !== null): ?>
                  <div class="pro-age"><?= (int)$age ?> yrs</div>
                <?php endif; ?>

                <div class="pro-loc">
                  <?= htmlspecialchars($u['location'] ?? 'Unknown') ?>
                </div>
              </a>

              <!-- LIKE -->
              <form method="post" action="match_action.php" class="pro-likeform">
                <input type="hidden" name="to_user" value="<?= (int)$u['id'] ?>">
                <input type="hidden" name="action" value="like">
                <button class="pro-likebtn" type="submit" title="Like">&hearts;</button>
              </form>
            </div>
            </div>

        </div>

    <?php endwhile; ?>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
