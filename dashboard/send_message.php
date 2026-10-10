<?php
require_once "../includes/auth_guard.php";
require_once "../config/db.php";

$userId = $_SESSION["user_id"];
$convId = intval($_POST["conversation_id"] ?? 0);
$msg    = trim($_POST["message"] ?? "");

/* -------------------------------------------------
   1) Basic validation
-------------------------------------------------- */
if ($convId <= 0 || $msg === "") {
  header("Location: inbox.php");
  exit();
}

/* -------------------------------------------------
   2) Verify this conversation belongs to the user
-------------------------------------------------- */
$check = $conn->prepare("
  SELECT user1, user2
  FROM conversations
  WHERE id=? AND (user1=? OR user2=?)
  LIMIT 1
");
$check->bind_param("iii", $convId, $userId, $userId);
$check->execute();
$conv = $check->get_result()->fetch_assoc();

if (!$conv) {
  // Not your conversation → block
  header("Location: inbox.php");
  exit();
}

/* -------------------------------------------------
   3) Insert message
-------------------------------------------------- */
$ins = $conn->prepare("
  INSERT INTO messages (conversation_id, sender_id, message)
  VALUES (?, ?, ?)
");
$ins->bind_param("iis", $convId, $userId, $msg);

if (!$ins->execute()) {
  header("Location: inbox.php?c=" . $convId);
  exit();
}

/* -------------------------------------------------
   4) Identify receiver
-------------------------------------------------- */
$receiver = ($conv['user1'] == $userId)
  ? $conv['user2']
  : $conv['user1'];

/* -------------------------------------------------
   5) Create notification for receiver
-------------------------------------------------- */
$text = "New message received 💬";
$n = $conn->prepare("
  INSERT INTO notifications (user_id, type, content)
  VALUES (?, 'message', ?)
");
$n->bind_param("is", $receiver, $text);
$n->execute();

/* -------------------------------------------------
   6) Redirect back to chat
-------------------------------------------------- */
header("Location: inbox.php?c=" . $convId);
exit();
