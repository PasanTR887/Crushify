<?php
require_once "../includes/auth_guard.php";
require_once "../config/db.php";

$userId = $_SESSION["user_id"];

/* -----------------------------
   Open chat via ?user=ID
------------------------------ */
$openUser = intval($_GET["user"] ?? 0);

if ($openUser > 0 && $openUser !== $userId) {
  $u1 = min($userId, $openUser);
  $u2 = max($userId, $openUser);

  // must be matched
  $m = $conn->prepare("SELECT id FROM matches WHERE user1=? AND user2=? LIMIT 1");
  $m->bind_param("ii", $u1, $u2);
  $m->execute();
  $matchRes = $m->get_result();

  if ($matchRes->num_rows === 1) {
    // create conversation if missing
    $ins = $conn->prepare("INSERT IGNORE INTO conversations (user1, user2) VALUES (?, ?)");
    $ins->bind_param("ii", $u1, $u2);
    $ins->execute();

    // get conversation id
    $getC = $conn->prepare("SELECT id FROM conversations WHERE user1=? AND user2=? LIMIT 1");
    $getC->bind_param("ii", $u1, $u2);
    $getC->execute();
    $row = $getC->get_result()->fetch_assoc();

    if ($row && isset($row["id"])) {
      header("Location: inbox.php?c=" . intval($row["id"]));
      exit();
    }
  } else {
    header("Location: inbox.php?err=match_required");
    exit();
  }
}

/* -----------------------------
   Conversation list
------------------------------ */
$listQ = $conn->prepare("
  SELECT c.id,
         u.id AS other_user_id,
         u.full_name,
         u.profile_pic,
         (SELECT m.message
          FROM messages m
          WHERE m.conversation_id = c.id
          ORDER BY m.created_at DESC, m.id DESC
          LIMIT 1) AS latest_message,
         (SELECT m.created_at
          FROM messages m
          WHERE m.conversation_id = c.id
          ORDER BY m.created_at DESC, m.id DESC
          LIMIT 1) AS latest_message_at
  FROM conversations c
  JOIN users u ON u.id = IF(c.user1 = ?, c.user2, c.user1)
  WHERE c.user1 = ? OR c.user2 = ?
  ORDER BY COALESCE(latest_message_at, c.created_at) DESC
");
$listQ->bind_param("iii", $userId, $userId, $userId);
$listQ->execute();
$conversations = $listQ->get_result();

/* -----------------------------
   Active conversation (secure)
------------------------------ */
$activeConv = intval($_GET["c"] ?? 0);
$activeOtherName = "";
$activeOtherPic  = "default.jpg";

if ($activeConv > 0) {
  $chk = $conn->prepare("
    SELECT c.id,
           u.full_name,
           u.profile_pic
    FROM conversations c
    JOIN users u ON u.id = IF(c.user1 = ?, c.user2, c.user1)
    WHERE c.id = ?
      AND (c.user1 = ? OR c.user2 = ?)
    LIMIT 1
  ");
  $chk->bind_param("iiii", $userId, $activeConv, $userId, $userId);
  $chk->execute();
  $info = $chk->get_result()->fetch_assoc();

  if (!$info) {
    $activeConv = 0;
  } else {
    $activeOtherName = $info["full_name"] ?? "";
    $activeOtherPic  = $info["profile_pic"] ?? "default.png";
  }
}

$messages = null;
if ($activeConv > 0) {
  $msgs = $conn->prepare("
    SELECT * FROM messages
    WHERE conversation_id = ?
    ORDER BY created_at ASC
  ");
  $msgs->bind_param("i", $activeConv);
  $msgs->execute();
  $messages = $msgs->get_result();
}

$err = $_GET["err"] ?? "";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Inbox - Crushify</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/Crushify/assets/css/style.css">
</head>
<body class="bg-soft">

<?php include "../includes/navbar.php"; ?>

<div class="container py-4 container-narrow">

  <?php if ($err === "match_required"): ?>
    <div class="alert alert-warning">
      You can message only after you both like each other &hearts;
    </div>
  <?php endif; ?>

  <div class="cx-inbox">

    <!-- LEFT: Sidebar -->
    <aside class="cx-side">
      <div class="cx-side-head">
        <div class="cx-side-count">
        </div>
      </div>

      <div class="cx-chatlist">
        <?php if ($conversations->num_rows === 0): ?>
          <div class="cx-empty">
            <div class="cx-muted">No conversations yet</div>
          </div>
        <?php else: ?>
          <?php while ($c = $conversations->fetch_assoc()): ?>
            <a class="cx-chatitem <?= ($activeConv == $c['id']) ? 'active' : '' ?>"
               href="inbox.php?c=<?= (int)$c['id'] ?>">

              <div class="cx-avatar">
                <img
                  src="../assets/uploads/<?= rawurlencode(basename($c['profile_pic'] ?: 'default.jpg')) ?>"
                  onerror="this.src='../assets/uploads/default.jpg';"
                  alt="avatar"
                >
              </div>

              <div class="cx-chatmeta">
                <div class="cx-chatname"><?= htmlspecialchars($c['full_name']) ?></div>
                <div class="cx-chat-preview"><?= htmlspecialchars($c['latest_message'] ?: 'You matched - say hello!') ?></div>
              </div>

            </a>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </aside>

    <!-- RIGHT: Chat Area -->
    <main class="cx-chat">

      <?php if ($activeConv > 0): ?>
        <!-- Chat top bar -->
        <div class="cx-chat-head">
          <div class="d-flex align-items-center gap-2">
            <div class="cx-avatar lg">
              <img
                src="../assets/uploads/<?= rawurlencode(basename($activeOtherPic ?: 'default.jpg')) ?>"
                onerror="this.src='../assets/uploads/default.jpg';"
                alt="avatar"
              >
            </div>
            <div>
              <div class="cx-chat-title"><?= htmlspecialchars($activeOtherName) ?></div>
              <div class="cx-chat-status">
                <span class="cx-dot"></span>Matched on Crushify
              </div>
            </div>
          </div>
        </div>

        <!-- Messages -->
        <div class="cx-chat-body" id="chatBox">
          <?php if ($messages && $messages->num_rows === 0): ?>
            <div class="cx-empty center">
              <div class="cx-muted">Say hi</div>
            </div>
          <?php endif; ?>

          <?php if ($messages): ?>
            <?php while ($m = $messages->fetch_assoc()): ?>
              <?php $isMe = ($m["sender_id"] == $userId); ?>
              <div class="cx-msg-row <?= $isMe ? 'me' : 'other' ?>">
                <div class="cx-msg">
                  <?= htmlspecialchars($m["message"]) ?>
                </div>
              </div>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>

        <!-- Input bar -->
        <div class="cx-chat-input">
          <form method="post" action="send_message.php" class="cx-input-form">
            <input type="hidden" name="conversation_id" value="<?= (int)$activeConv ?>">

            <input class="cx-input" name="message" required maxlength="500" placeholder="Type a message...">

            <button class="cx-send-btn" type="submit" title="Send">
              &#10148;
            </button>
          </form>
        </div>

        <script>
          const box = document.getElementById('chatBox');
          if (box) box.scrollTop = box.scrollHeight;
        </script>

      <?php else: ?>
        <div class="cx-chat-head">
          <div class="cx-chat-title">Inbox</div>
        </div>
        <div class="cx-chat-body">
          <div class="cx-empty center">
            <div class="cx-title">Choose a conversation</div>
          </div>
        </div>
      <?php endif; ?>

    </main>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
