<?php
require_once "../includes/auth_guard.php";
require_once "../config/db.php";

$userId = $_SESSION["user_id"];
$success = $_GET["saved"] ?? "";
$error = $_GET["error"] ?? "";

function calcAge($dob){
  if (!$dob) return "";
  try {
    return (new DateTime())->diff(new DateTime($dob))->y;
  } catch(Exception $e){
    return "";
  }
}

function selected($actual, $expected){
  return $actual === $expected ? "selected" : "";
}

function uploadUrl($file){
  $file = $file ?: "default.jpg";
  $safeFile = basename($file);
  $fullPath = __DIR__ . "/../assets/uploads/" . $safeFile;
  $version = file_exists($fullPath) ? filemtime($fullPath) : time();
  return "../assets/uploads/" . rawurlencode($safeFile) . "?v=" . $version;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_profile"])) {
  $full_name = trim($_POST["full_name"] ?? "");
  $bio = trim($_POST["bio"] ?? "");
  $occupation = trim($_POST["occupation"] ?? "");
  $marital_status = $_POST["marital_status"] ?? "";
  $relationship_type = $_POST["relationship_type"] ?? "";
  $gender = $_POST["gender"] ?? "";
  $looking_for = $_POST["looking_for"] ?? "";
  $dob = $_POST["dob"] ?? null;
  if ($dob === "") $dob = null;
  $location = trim($_POST["location"] ?? "");

  $personality_type = trim($_POST["personality_type"] ?? "");
  $hobbies = trim($_POST["hobbies"] ?? "");
  $interests = trim($_POST["interests"] ?? "");
  $about_me = trim($_POST["about_me"] ?? "");

  if ($full_name === "") {
    $error = "Full name is required.";
  } else {
    $up = $conn->prepare("
      UPDATE users SET
        full_name=?, bio=?, occupation=?, marital_status=?, relationship_type=?,
        gender=?, looking_for=?, dob=?, location=?
      WHERE id=?
    ");
    $up->bind_param(
      "sssssssssi",
      $full_name,$bio,$occupation,$marital_status,$relationship_type,
      $gender,$looking_for,$dob,$location,$userId
    );
    $up->execute();

    $ps = $conn->prepare("
      INSERT INTO user_personality (user_id, personality_type, hobbies, interests, about_me)
      VALUES (?, ?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        personality_type=VALUES(personality_type),
        hobbies=VALUES(hobbies),
        interests=VALUES(interests),
        about_me=VALUES(about_me)
    ");
    $ps->bind_param("issss", $userId, $personality_type, $hobbies, $interests, $about_me);
    $ps->execute();

    $_SESSION["user_name"] = $full_name;
    header("Location: profile.php?saved=profile");
    exit();
  }
}

$stmt = $conn->prepare("
  SELECT full_name,email,gender,looking_for,dob,location,bio,profile_pic,
         occupation, marital_status, relationship_type
  FROM users WHERE id=?
");
$stmt->bind_param("i",$userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$pq = $conn->prepare("SELECT * FROM user_personality WHERE user_id=?");
$pq->bind_param("i",$userId);
$pq->execute();
$personality = $pq->get_result()->fetch_assoc();

$ph = $conn->prepare("SELECT * FROM user_photos WHERE user_id=? ORDER BY created_at DESC, id DESC");
$ph->bind_param("i",$userId);
$ph->execute();
$photos = $ph->get_result();

$messages = [
  "profile" => "Profile updated successfully.",
  "photo" => "Photo uploaded successfully.",
  "personality" => "Personality updated successfully.",
];
$errors = [
  "upload" => "Choose a valid JPG, PNG, or WebP image and try again.",
  "size" => "Photos must be 8 MB or smaller.",
  "storage" => "Your photo could not be uploaded. Please try again later.",
  "save" => "Your photo could not be saved. Please try again.",
];
$success = $messages[$success] ?? "";
$error = $errors[$error] ?? $error;

$age = calcAge($user['dob'] ?? null);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Profile - Crushify</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/Crushify/assets/css/style.css">
</head>
<body class="bg-soft">
<?php include "../includes/navbar.php"; ?>

<div class="container py-4 container-narrow">

<?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="pf-shell">

<div class="pf-top">

<div class="pf-avatar-wrap">
  <div class="pf-avatar">
    <img src="<?= htmlspecialchars(uploadUrl($user['profile_pic'] ?? 'default.jpg')) ?>" alt="Profile photo">
  </div>
</div>

<div class="pf-bio-card">
  <div class="pf-bio-pill">Bio</div>
  <div class="pf-bio-grid">
    <div><strong>Name:</strong> <?= htmlspecialchars($user['full_name'] ?? '-') ?></div>
    <div><strong>Age:</strong> <?= calcAge($user['dob'] ?? null) !== '' ? htmlspecialchars(calcAge($user['dob'])) : '-' ?></div>
    <div><strong>Occupation:</strong> <?= htmlspecialchars(($user['occupation'] ?? '') !== '' ? $user['occupation'] : '-') ?></div>
    <div><strong>Location:</strong> <?= htmlspecialchars(($user['location'] ?? '') !== '' ? $user['location'] : '-') ?></div>
  </div>
</div>
</div>

<div class="pf-content">

<form id="photoForm" method="post" enctype="multipart/form-data" action="upload_photo.php" class="pf-photo-upload">
  <label class="form-label fw-semibold" for="profilePhoto">Update your profile photo</label>
  <input id="profilePhoto" class="form-control pf-file js-editable" type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" aria-describedby="photoHelp" disabled>
  <div id="photoHelp" class="form-text">JPG, PNG, or WebP - up to 8 MB. Selecting a photo uploads it as your profile picture.</div>
</form>

<form id="profileForm" method="post">
<input type="hidden" name="save_profile" value="1">

<div class="pf-section">
  <div class="pf-section-pill">About Me</div>
  <textarea class="pf-textarea js-editable" name="bio" disabled><?= htmlspecialchars($user['bio'] ?? "") ?></textarea>
</div>

<div class="pf-form-grid">
  <input class="pf-input js-editable" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? "") ?>" placeholder="Full Name" required disabled>
  <input class="pf-input js-editable" name="occupation" value="<?= htmlspecialchars($user['occupation'] ?? "") ?>" placeholder="Occupation" disabled>

  <select class="pf-input js-editable" name="marital_status" disabled>
    <option value="">Marital Status</option>
    <option value="single" <?= selected($user['marital_status'] ?? "", "single") ?>>Single</option>
    <option value="married" <?= selected($user['marital_status'] ?? "", "married") ?>>Married</option>
  </select>

  <select class="pf-input js-editable" name="relationship_type" disabled>
    <option value="">Relationship Type</option>
    <option value="dating" <?= selected($user['relationship_type'] ?? "", "dating") ?>>Dating</option>
    <option value="marriage" <?= selected($user['relationship_type'] ?? "", "marriage") ?>>Marriage</option>
  </select>

  <input type="date" class="pf-input js-editable" name="dob" value="<?= htmlspecialchars($user['dob'] ?? "") ?>" disabled>
  <div class="pf-location-row">
    <input class="pf-input js-editable" id="locationInput" name="location" value="<?= htmlspecialchars($user['location'] ?? "") ?>" placeholder="Location" disabled>
  </div>

  <select class="pf-input js-editable" name="gender" disabled>
    <option value="">Gender</option>
    <option value="male" <?= selected($user['gender'] ?? "", "male") ?>>Male</option>
    <option value="female" <?= selected($user['gender'] ?? "", "female") ?>>Female</option>
    <option value="other" <?= selected($user['gender'] ?? "", "other") ?>>Other</option>
  </select>

  <select class="pf-input js-editable" name="looking_for" disabled>
    <option value="">Looking For</option>
    <option value="male" <?= selected($user['looking_for'] ?? "", "male") ?>>Male</option>
    <option value="female" <?= selected($user['looking_for'] ?? "", "female") ?>>Female</option>
    <option value="other" <?= selected($user['looking_for'] ?? "", "other") ?>>Other</option>
  </select>
</div>

<div class="pf-section">
  <div class="pf-section-pill">Photos</div>
  <div class="pf-photo-grid mt-3">
    <?php while($p=$photos->fetch_assoc()): ?>
      <div class="pf-photo">
        <img src="<?= htmlspecialchars(uploadUrl($p['photo'])) ?>" alt="Uploaded photo">
      </div>
    <?php endwhile; ?>
  </div>
</div>

<div class="pf-section">
  <div class="pf-section-pill">Personality</div>
  <input class="pf-input js-editable" name="personality_type" placeholder="Personality Type" value="<?= htmlspecialchars($personality['personality_type'] ?? '') ?>" disabled>
  <input class="pf-input js-editable mt-2" name="hobbies" placeholder="Hobbies" value="<?= htmlspecialchars($personality['hobbies'] ?? '') ?>" disabled>
  <input class="pf-input js-editable mt-2" name="interests" placeholder="Interests" value="<?= htmlspecialchars($personality['interests'] ?? '') ?>" disabled>
  <textarea class="pf-textarea js-editable mt-2" name="about_me" disabled><?= htmlspecialchars($personality['about_me'] ?? '') ?></textarea>
</div>

<div class="pf-actions">
  <button class="btn btn-cx" id="editSaveBtn" type="button">Edit</button>
  <button class="btn btn-cx-outline" id="cancelEditBtn" type="button" disabled>Cancel</button>
</div>
</form>

</div>
</div>
</div>
<script>
(() => {
  const form = document.getElementById('profileForm');
  const photoForm = document.getElementById('photoForm');
  const photoInput = photoForm?.querySelector('input[type="file"]');
  const fields = document.querySelectorAll('.js-editable');
  const editSaveBtn = document.getElementById('editSaveBtn');
  const cancelEditBtn = document.getElementById('cancelEditBtn');
  let isEditing = false;

  function setEditing(enabled) {
    isEditing = enabled;
    fields.forEach((field) => {
      field.disabled = !enabled;
    });
    editSaveBtn.textContent = enabled ? 'Save' : 'Edit';
    cancelEditBtn.disabled = !enabled;
    document.body.classList.toggle('profile-editing', enabled);
  }

  editSaveBtn.addEventListener('click', () => {
    if (!isEditing) {
      setEditing(true);
      document.getElementById('locationInput')?.focus();
      return;
    }
    form.submit();
  });

  cancelEditBtn.addEventListener('click', () => {
    form.reset();
    if (photoInput) photoInput.value = '';
    setEditing(false);
  });

  photoInput?.addEventListener('change', () => {
    if (photoInput.files.length > 0) photoForm.submit();
  });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
