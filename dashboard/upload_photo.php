<?php
require_once "../includes/auth_guard.php";
require_once "../config/db.php";

$userId = $_SESSION["user_id"];

$upload = $_FILES["photo"] ?? null;
$uploadError = $upload["error"] ?? UPLOAD_ERR_NO_FILE;
if (!$upload || $uploadError !== UPLOAD_ERR_OK) {
  $error = in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
    ? "size"
    : "upload";
  header("Location: profile.php?error=" . $error);
  exit();
}

$maxFileSize = 8 * 1024 * 1024;
$tmpName = $upload["tmp_name"];
$uploadDir = __DIR__ . "/../assets/uploads";

if (($upload["size"] ?? 0) > $maxFileSize) {
  header("Location: profile.php?error=size");
  exit();
}

$imageInfo = is_uploaded_file($tmpName) ? getimagesize($tmpName) : false;
$allowedTypes = [
  IMAGETYPE_JPEG => "jpg",
  IMAGETYPE_PNG => "png",
  IMAGETYPE_WEBP => "webp",
];

if (!$imageInfo || !isset($allowedTypes[$imageInfo[2]])) {
  header("Location: profile.php?error=upload");
  exit();
}

$name = "photo_" . $userId . "_" . bin2hex(random_bytes(12)) . "." . $allowedTypes[$imageInfo[2]];
$path = $uploadDir . "/" . $name;

if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
  header("Location: profile.php?error=storage");
  exit();
}

if (!move_uploaded_file($tmpName, $path)) {
  error_log("Crushify photo upload could not move the uploaded file for user " . $userId);
  header("Location: profile.php?error=storage");
  exit();
}

try {
  $conn->begin_transaction();

  $stmt = $conn->prepare("INSERT INTO user_photos (user_id, photo) VALUES (?,?)");
  $stmt->bind_param("is", $userId, $name);
  $stmt->execute();

  $profile = $conn->prepare("UPDATE users SET profile_pic=? WHERE id=?");
  $profile->bind_param("si", $name, $userId);
  $profile->execute();

  $conn->commit();
} catch (mysqli_sql_exception $exception) {
  $conn->rollback();
  if (is_file($path) && !unlink($path)) {
    error_log("Crushify photo upload could not remove an unreferenced file: " . $path);
  }
  error_log("Crushify photo upload database save failed for user " . $userId . ": " . $exception->getMessage());
  header("Location: profile.php?error=save");
  exit();
}

header("Location: profile.php?saved=photo");
exit();
