<?php
require __DIR__ . '/config.php';
require_login();

$rooms = db()->query('SELECT id, name FROM rooms ORDER BY id')->fetchAll();
if (!$rooms) { exit('No rooms found. Import schema.sql first.'); }

$roomId = (int)($_GET['room'] ?? $rooms[0]['id']);
$room = null;
foreach ($rooms as $r) { if ((int)$r['id'] === $roomId) { $room = $r; } }
if (!$room) { $room = $rooms[0]; $roomId = (int)$room['id']; }

$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf" content="<?= e(csrf_token()) ?>">
  <title><?= e($room['name']) ?> · Chat Board</title>
  <script src="theme.js"></script>
  <link rel="stylesheet" href="style.css">
</head>
<body class="chat-page" data-room="<?= $roomId ?>">
  <aside class="sidebar">
    <h1>Chat Board</h1>
    <nav aria-label="Rooms">
      <?php foreach ($rooms as $r): ?>
        <a href="?room=<?= (int)$r['id'] ?>"
           class="<?= (int)$r['id'] === $roomId ? 'active' : '' ?>"># <?= e($r['name']) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="me">
      <span>Signed in as <strong><?= e($user['username']) ?></strong></span>
      <button type="button" id="themeToggle">Dark mode</button>
      <form method="post" action="logout.php">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <button type="submit">Log out</button>
      </form>
    </div>
  </aside>

  <main class="chat">
    <header><h2># <?= e($room['name']) ?></h2></header>
    <ul id="messages" aria-live="polite"></ul>
    <p id="status" class="status" hidden></p>
    <form id="chatForm" autocomplete="off">
      <input id="text" type="text" maxlength="500" placeholder="Message #<?= e($room['name']) ?>" required>
      <button type="submit">Send</button>
    </form>
  </main>
  <script src="chat.js"></script>
</body>
</html>
