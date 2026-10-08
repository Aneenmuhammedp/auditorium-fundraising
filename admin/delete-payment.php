<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config.php";

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {

    $stmt = $pdo->prepare("
        DELETE FROM payments
        WHERE id = ?
    ");

    $stmt->execute([$id]);
}

header("Location: dashboard.php");
exit;