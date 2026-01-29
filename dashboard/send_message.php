<?php
require_once "../includes/auth_guard.php";
require_once "../config/db.php";

$userId = $_SESSION["user_id"];
$convId = intval($_POST["conversation_id"] ?? 0);
$msg    = trim($_POST["message"] ?? "");

if ($convId && $msg !== "") {
  $stmt = $conn->prepare("
    INSERT INTO messages (conversation_id, sender_id, message)
    VALUES (?, ?, ?)
  ");
  $stmt->bind_param("iis", $convId, $userId, $msg);
  $stmt->execute();
}

//find receiver
$getUser = $conn->prepare("
  SELECT user1, user2 FROM conversations WHERE id=?
");
$getUser->bind_param("i", $convId);
$getUser->execute();
$conv = $getUser->get_result()->fetch_assoc();

$receive = ($conv['user1'] == $userId) ? $conv['user2'] : $conv['user1'];

//create notification
$text = "New message received 💬";
$n = $conn->prepare("
  INSERT INTO notifications (user_id, type, content)
  VALUES (?, 'message', ?)
");
$n->bind_param("is", $receive, $text);
$n->execute();

header("Location: inbox.php?c=" . $convId);
exit();
