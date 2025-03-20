<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_POST['sender_id'];
    $content = $_POST['content'];
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, content) VALUES (?, ?)");
    $stmt->execute([$sender_id, $content]);
    header("Location: messenger.php");
}
?>