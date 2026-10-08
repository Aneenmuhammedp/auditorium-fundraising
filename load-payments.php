<?php

require_once "config.php";

header('Content-Type: application/json');

$page = isset($_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;

$limit = 10;

$offset = ($page - 1) * $limit;

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

try {

    if ($search !== '') {

        $stmt = $pdo->prepare("
            SELECT
                id,
                name,
                amount
            FROM payments
            WHERE name LIKE :search
            ORDER BY amount DESC, id DESC
            LIMIT :limit
            OFFSET :offset
        ");

        $stmt->bindValue(
            ':search',
            '%' . $search . '%',
            PDO::PARAM_STR
        );
    } else {

        $stmt = $pdo->prepare("
            SELECT
                id,
                name,
                amount
            FROM payments
            ORDER BY amount DESC, id DESC
            LIMIT :limit
            OFFSET :offset
        ");
    }


    $stmt->bindValue(
        ':limit',
        $limit,
        PDO::PARAM_INT
    );

    $stmt->bindValue(
        ':offset',
        $offset,
        PDO::PARAM_INT
    );

    $stmt->execute();


    $payments =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    echo json_encode([
        'success' => true,
        'payments' => $payments,
        'hasMore' =>
        count($payments) === $limit
    ]);
} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        $e->getMessage()
    ]);
}
