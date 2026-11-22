<?php
require __DIR__ . '/config.php';
if (current_user()) { header('Location: index.php'); exit; }

$error = '';
$notice = isset($_GET['registered']) ? 'Account created. You can log in now.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Session expired. Reload the page and try again.';
    } else {
        $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);               // prevents session fixation
            $_SESSION['user_id']  = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: index.php');
            exit;
        }
        $error = 'Wrong username or password.';        // same message for both cases on purpose
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Log in · Chat Board</title>
  <script src="theme.js"></script>
  <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
  <main class="auth-card">
    <h1>Log in</h1>
    <?php if ($notice): ?><p class="notice"><?= e($notice) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label for="username">Username</label>
      <input id="username" name="username" maxlength="20" required autofocus>
      <label for="password">Password</label>
      <input id="password" type="password" name="password" required>
      <button type="submit">Log in</button>
    </form>
    <p class="switch">New here? <a href="register.php">Create an account</a></p>
  </main>
</body>
</html>
