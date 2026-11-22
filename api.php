<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in']);
    exit;
}

try {
    $pdo = db();

    // ---- GET: latest 100 messages of a room ----
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare(
            'SELECT m.id, m.body, m.created_at, m.user_id, u.username
             FROM messages m JOIN users u ON u.id = m.user_id
             WHERE m.room_id = ?
             ORDER BY m.id DESC LIMIT 100'
        );
        $stmt->execute([(int)($_GET['room'] ?? 0)]);

        $out = [];
        foreach (array_reverse($stmt->fetchAll()) as $row) {
            $out[] = [
                'id'       => (int)$row['id'],
                'username' => $row['username'],
                'body'     => $row['body'],
                'time'     => date('H:i', strtotime($row['created_at'])),
                'mine'     => (int)$row['user_id'] === (int)$user['id'],
            ];
        }
        echo json_encode($out);
        exit;
    }

    // ---- POST: send or delete (CSRF-protected) ----
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token. Reload the page.']);
            exit;
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'send') {
            $roomId = (int)($_POST['room'] ?? 0);
            $body   = trim($_POST['body'] ?? '');

            $check = $pdo->prepare('SELECT 1 FROM rooms WHERE id = ?');
            $check->execute([$roomId]);

            if (!$check->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Room not found']);
                exit;
            }
            if ($body === '' || mb_strlen($body) > 500) {
                http_response_code(400);
                echo json_encode(['error' => 'Message must be 1-500 characters']);
                exit;
            }

            $ins = $pdo->prepare('INSERT INTO messages (room_id, user_id, body, created_at) VALUES (?, ?, ?, ?)');
            $ins->execute([$roomId, $user['id'], $body, date('Y-m-d H:i:s')]);
            echo json_encode(['ok' => true]);
            exit;
        }

        if ($action === 'delete') {
            // user_id in the WHERE clause means you can only delete your own messages
            $del = $pdo->prepare('DELETE FROM messages WHERE id = ? AND user_id = ?');
            $del->execute([(int)($_POST['id'] ?? 0), $user['id']]);
            echo json_encode(['ok' => $del->rowCount() > 0]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} catch (PDOException $ex) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);   // never expose $ex->getMessage() to users
}
