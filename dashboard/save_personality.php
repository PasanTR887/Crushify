<?php
require_once "../includes/auth_guard.php";
require_once "../config/db.php";

$userId = $_SESSION["user_id"];

$stmt = $conn->prepare("
INSERT INTO user_personality (user_id, personality_type, hobbies, interests, about_me)
VALUES (?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
personality_type=VALUES(personality_type),
hobbies=VALUES(hobbies),
interests=VALUES(interests),
about_me=VALUES(about_me)
");

$stmt->bind_param(
  "issss",
  $userId,
  $_POST['personality_type'],
  $_POST['hobbies'],
  $_POST['interests'],
  $_POST['about_me']
);

$stmt->execute();

header("Location: profile.php?saved=personality");
exit();
