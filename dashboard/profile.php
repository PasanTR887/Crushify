<?php
require_once "../includes/auth_guard.php";
require_once "../config/db.php";

$userId = $_SESSION["user_id"];
$success = "";
$error = "";

//Fetch current user data
$stmt = $conn->prepare("SELECT full_name,email,gender,looking_for,dob,location,bio,profile_pic FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $gender = $_POST["gender"] ?? null;
    $looking_for = $_POST["looking_for"] ?? "any" ;
    $dob = $_POST["dob"] ?? null ;
    $location = trim($_POST["location"] ?? "");
    $bio = trim($_POST["bio"] ?? "");

    //Basic validation
    if ($full_name === "") {
        $error = "Full name is required.";
    } else {

    //Handle profile picture upload 
    $newPic = $user["profile_pic"];

    if (!empty($_FILES["profile_pic"]["name"])) {
        $allowed = ["image/jpeg","image/png","image/webp"];
        if (!in_array($_FILES["profile_pic"]["type"], $allowed)) {
            $error = "Only JPG, PNG, WEBP images are allowed.";
        } elseif ($_FILES["profile_pic"]["size"] > 2 * 1024 *1024) {
            $error = "Image must be under 2MB.";
        } else {
            $ext = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
            $fileName = "user_" . $userId . "_" . time() . "." . $ext;
            $targetPath = "../assets/uploads/" . $fileName;

            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetPath)) {
                $newPic = $fileName ;

                //delete old pic if not default
                if ($user["profile_pic"] && $user["profile_pic"] !== "default.png") {
                    $oldPath = "../assets/uploads/" . $user["profile_pic"];
                    if (file_exists($oldPath)) @unlink($oldPath);
                }
            } else {
                $error = "Failed to upload image.";
            }
            }
        }

        //upload profile in DB
        if ($error === "") {
            $up = $conn->prepare("
                UPDATE users
                SET full_name=?, gender=?, looking_for=?, dob=?, location=?, bio=?, profile_pic=?
                WHERE id=?
            ");
            $up->bind_param("sssssssi", $full_name,$gender,$looking_for,$dob,$location,$bio,$newPic,$userId);

            if ($up->execute()) {
                $success = "Profile updated successfully.";

                //update session name so navbar shows new name instently
                $_SESSION["user_name"] = $full_name;

                //refresh user data
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                $error = "Update failed.Try again.";
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <title>Profile - Crushify</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<?php include "../includes/navbar.php"; ?>

<div class="container py-4" style="max-width: 900px;">
  <h3 class="mb-3">Edit Profile</h3>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <div class="row g-3">

          <div class="col-md-4 text-center">
            <img
              src="../assets/uploads/<?= htmlspecialchars($user['profile_pic'] ?? 'default.png') ?>"
              class="rounded-circle mb-3"
              style="width:160px;height:160px;object-fit:cover;"
              onerror="this.src='../assets/uploads/default.png';"
            >
            <div class="mb-2">
              <input type="file" class="form-control" name="profile_pic" accept="image/*">
              <small class="text-muted">JPG/PNG/WEBP, max 2MB</small>
            </div>
          </div>

          <div class="col-md-8">
            <div class="mb-2">
              <label class="form-label">Full Name</label>
              <input class="form-control" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
            </div>

            <div class="mb-2">
              <label class="form-label">Email (read-only)</label>
              <input class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
            </div>

            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">Gender</label>
                <select class="form-select" name="gender">
                  <option value="">Select</option>
                  <option value="male"   <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                  <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                  <option value="other"  <?= ($user['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Looking For</label>
                <select class="form-select" name="looking_for">
                  <option value="any"   <?= ($user['looking_for'] ?? 'any') === 'any' ? 'selected' : '' ?>>Any</option>
                  <option value="male"  <?= ($user['looking_for'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                  <option value="female"<?= ($user['looking_for'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                </select>
              </div>
            </div>

            <div class="row g-2 mt-1">
              <div class="col-md-6">
                <label class="form-label">Date of Birth</label>
                <input type="date" class="form-control" name="dob" value="<?= htmlspecialchars($user['dob'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Location</label>
                <input class="form-control" name="location" value="<?= htmlspecialchars($user['location'] ?? '') ?>">
              </div>
            </div>

            <div class="mt-2">
              <label class="form-label">Bio</label>
              <textarea class="form-control" name="bio" rows="4"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>

            <div class="mt-3">
              <button class="btn btn-primary">Save Changes</button>
              <a class="btn btn-outline-secondary" href="index.php">Back</a>
            </div>

          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
