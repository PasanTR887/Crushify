<?php
require_once "../includes/auth_guard.php";
require_once "../config/db.php";

$fromUser = $_SESSION["user_id"];
$toUser   = intval($_POST["to_user"] ?? 0);
$action   = $_POST["action"] ?? "";

if (!$toUser || !in_array($action, ['like','pass'])) {
  header("Location: find_match.php");
  exit();
}

// save like/pass
$stmt = $conn->prepare("
    INSERT INTO likes (from_user, to_user, action)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE action=VALUES(action)
");
$stmt->bind_param("iis", $fromUser, $toUser, $action);
$stmt->execute();

// if liked → check mutual like
if ($action === 'like') {

  // check if the other user already liked me
  $check = $conn->prepare("
    SELECT id FROM likes 
    WHERE from_user=? AND to_user=? AND action='like'
  ");
  $check->bind_param("ii", $toUser, $fromUser);
  $check->execute();
  $res = $check->get_result();

  // MUTUAL LIKE FOUND(match happens here)
  if ($res->num_rows === 1) {

    // keep order consistent
    $u1 = min($fromUser, $toUser);
    $u2 = max($fromUser, $toUser);

    // create match
    $match = $conn->prepare("
      INSERT IGNORE INTO matches (user1, user2)
      VALUES (?, ?)
    ");
    $match->bind_param("ii", $u1, $u2);
    $match->execute();

    //create conversation
    $conv = $conn->prepare("
      INSERT IGNORE INTO conversations (user1, user2)
      VALUES (?, ?)
    ");
    $conv->bind_param("ii", $u1, $u2);
    $conv->execute();

    $msg = "You have a new match 💘" ;

    $n1 = $conn->prepare("
      INSERT INTO notifications (user_id, type, content)
      VALUES (?, 'match', ?)
    ");
    $n1->bind_param("is", $fromUser, $msg);
    $n1->execute();

    $n2 = $conn->prepare("
      INSERT INTO notifications (user_id, type, content)
      VALUES (?, 'match', ?)
    ");
    $n2->bind_param("is", $toUser, $msg);
    $n2->execute();
    
  }
}


header("Location: find_match.php");
exit();
