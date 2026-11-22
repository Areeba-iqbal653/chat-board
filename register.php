<?php
require __DIR__ . '/config.php';
if (current_user()) { header('Location: index.php'); exit; }

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Session expired. Reload the page and try again.';
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $error = 'Username must be 3-20 characters: letters, numbers or underscore.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $stmt = db()->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, ?)');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
            header('Location: login.php?registered=1');
            exit;
        } catch (PDOException $ex) {
            $error = ($ex->getCode() === '23000') ? 'That username is already taken.' : 'Could not create the account.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create account · Chat Board</title>
  <script src="theme.js"></script>
  <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
  <main class="auth-card">
    <h1>Create your account</h1>
    <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label for="username">Username</label>
      <input id="username" name="username" value="<?= e($username) ?>" maxlength="20" required autofocus>
      <label for="password">Password</label>
      <input id="password" type="password" name="password" required>
      <label for="confirm">Confirm password</label>
      <input id="confirm" type="password" name="confirm" required>
      <button type="submit">Create account</button>
    </form>
    <p class="switch">Already registered? <a href="login.php">Log in</a></p>
  </main>
</body>
</html>
