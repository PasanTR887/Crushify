<?php
require_once "../includes/auth_guard.php";
require_once "../config/db.php";

$userId = $_SESSION["user_id"];

// get conversations
$sql = "
SELECT c.id, u.full_name
FROM conversations c
JOIN users u 
  ON u.id = IF(c.user1 = ?, c.user2, c.user1)
WHERE c.user1 = ? OR c.user2 = ?
ORDER BY c.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $userId, $userId, $userId);
$stmt->execute();
$conversations = $stmt->get_result();

$activeConv = $_GET["c"] ?? null;
?>
<!doctype html>
<html>
<head>
  <title>Inbox - Crushify</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<?php include "../includes/navbar.php"; ?>

<div class="container py-4">
  <div class="row">

    <!-- Chat list -->
    <div class="col-md-4">
      <div class="list-group">
        <?php while ($c = $conversations->fetch_assoc()): ?>
          <a class="list-group-item list-group-item-action
             <?= ($activeConv == $c['id']) ? 'active' : '' ?>"
             href="inbox.php?c=<?= $c['id'] ?>">
            <?= htmlspecialchars($c['full_name']) ?>
          </a>
        <?php endwhile; ?>

        <?php if ($conversations->num_rows === 0): ?>
          <div class="alert alert-info">No conversations yet 💔</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Messages -->
    <div class="col-md-8">
      <?php if ($activeConv): ?>
        <?php
        $msgs = $conn->prepare("
          SELECT * FROM messages
          WHERE conversation_id = ?
          ORDER BY created_at ASC
        ");
        $msgs->bind_param("i", $activeConv);
        $msgs->execute();
        $messages = $msgs->get_result();
        ?>

        <div class="card">
          <div class="card-body" style="height:350px; overflow-y:auto;">
            <?php while ($m = $messages->fetch_assoc()): ?>
              <div class="mb-2 <?= $m['sender_id'] == $userId ? 'text-end' : '' ?>">
                <span class="badge bg-<?= $m['sender_id'] == $userId ? 'primary' : 'secondary' ?>">
                  <?= htmlspecialchars($m['message']) ?>
                </span>
              </div>
            <?php endwhile; ?>
          </div>

          <div class="card-footer">
            <form method="post" action="send_message.php" class="d-flex">
              <input type="hidden" name="conversation_id" value="<?= $activeConv ?>">
              <input class="form-control me-2" name="message" required placeholder="Type a message...">
              <button class="btn btn-primary">Send</button>
            </form>
          </div>
        </div>

      <?php else: ?>
        <div class="alert alert-secondary">
          Select a conversation to start chatting 💬
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>
</body>
</html>
