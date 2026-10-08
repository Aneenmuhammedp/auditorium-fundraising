<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $amount = $_POST['amount'] ?? '';

    if ($name === '') {

        $error = "Please enter name.";

    } elseif (!is_numeric($amount) || $amount <= 0) {

        $error = "Please enter a valid amount.";

    } else {

        $stmt = $pdo->prepare("
            INSERT INTO payments (name, amount)
            VALUES (?, ?)
        ");

        $stmt->execute([
            $name,
            number_format((float)$amount, 2, '.', '')
        ]);

        header("Location: dashboard.php");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Add Payment</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6">

            <div class="card shadow-sm border-0">

                <div class="card-body p-4">

                    <h3 class="mb-4">
                        Add Payment
                    </h3>


                    <?php if ($error): ?>

                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>

                    <?php endif; ?>


                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">
                                Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                class="form-control"
                                placeholder="Enter contributor name"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Amount
                            </label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    ₹
                                </span>

                                <input
                                    type="number"
                                    name="amount"
                                    class="form-control"
                                    min="1"
                                    step="0.01"
                                    placeholder="Enter amount"
                                    required
                                >

                            </div>

                        </div>


                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-success"
                            >
                                Add Payment
                            </button>

                            <a
                                href="dashboard.php"
                                class="btn btn-secondary"
                            >
                                Cancel
                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>