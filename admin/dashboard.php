<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config.php";


/*
|--------------------------------------------------------------------------
| Confirm Payment Received
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        isset($_POST['action']) &&
        $_POST['action'] === 'received' &&
        isset($_POST['id'])
    ) {

        $paymentId = intval($_POST['id']);

        if ($paymentId > 0) {

            $stmt = $pdo->prepare("
                UPDATE payments
                SET received = 1
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $paymentId
            ]);
        }

        $redirectPage = isset($_GET['page'])
            ? max(1, intval($_GET['page']))
            : 1;

        header("Location: dashboard.php?page=" . $redirectPage);
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Total Collected
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM payments
");

$total = $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| Paid Amount
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) AS paid
    FROM payments
    WHERE received = 1
");

$paidAmount = $stmt->fetch()['paid'];


/*
|--------------------------------------------------------------------------
| Unpaid Amount
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) AS unpaid
    FROM payments
    WHERE received = 0
");

$unpaidAmount = $stmt->fetch()['unpaid'];


/*
|--------------------------------------------------------------------------
| Total Contributors
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM payments
");

$count = $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

$limit = 100;

$page = isset($_GET['page'])
    ? max(1, intval($_GET['page']))
    : 1;

$offset = ($page - 1) * $limit;


/*
|--------------------------------------------------------------------------
| Get Payments
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM payments
    ORDER BY id DESC
    LIMIT :limit OFFSET :offset
");

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

$payments = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Total Pages
|--------------------------------------------------------------------------
*/

$totalPages = ceil($count / $limit);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>AUDITORIUM FUND RAISING - Admin</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <style>
        body {
            background: #f5f7fa;
            min-height: 100vh;
        }


        .navbar-brand {
            font-weight: 600;
        }


        /*
        |--------------------------------------------------------------------------
        | Summary Cards
        |--------------------------------------------------------------------------
        */

        .summary-card {
            border: 0;
            border-radius: 15px;
        }


        .summary-title {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 5px;
        }


        .summary-value {
            font-size: 27px;
            font-weight: 700;
        }


        /*
        |--------------------------------------------------------------------------
        | Table
        |--------------------------------------------------------------------------
        */

        .table th {
            white-space: nowrap;
        }


        .table td {
            vertical-align: middle;
        }


        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        .received-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #d1e7dd;
            color: #146c43;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }


        .pending-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #fff3cd;
            color: #997404;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }


        /*
        |--------------------------------------------------------------------------
        | Actions
        |--------------------------------------------------------------------------
        */

        .action-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: nowrap;
        }


        .action-buttons form {
            margin: 0;
        }


        .action-buttons .btn {
            white-space: nowrap;
        }


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        .pagination {
            margin-bottom: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | Mobile
        |--------------------------------------------------------------------------
        */

        @media (max-width: 767px) {

            body {
                font-size: 14px;
            }


            .navbar {
                padding: 10px 0;
            }


            .navbar-brand {
                font-size: 15px;
            }


            .navbar .container {
                padding-left: 12px;
                padding-right: 12px;
            }


            .container {
                padding-left: 12px;
                padding-right: 12px;
            }


            /*
            |--------------------------------------------------------------------------
            | Summary Cards Mobile
            |--------------------------------------------------------------------------
            */

            .summary-title {
                font-size: 11px;
            }


            .summary-value {
                font-size: 20px;
            }


            .summary-card .card-body {
                padding: 14px;
            }


            /*
            |--------------------------------------------------------------------------
            | Page Header
            |--------------------------------------------------------------------------
            */

            .page-header {
                display: flex;
                flex-direction: column;
                align-items: stretch !important;
                gap: 10px;
            }


            .page-header h4 {
                font-size: 20px;
            }


            .page-header .btn {
                width: 100%;
            }


            /*
            |--------------------------------------------------------------------------
            | Table Horizontal Scroll
            |--------------------------------------------------------------------------
            */

            .payment-table-wrapper {
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }


            .payment-table {
                min-width: 650px;
            }


            .payment-table th,
            .payment-table td {
                padding: 10px;
                font-size: 13px;
            }


            /*
            |--------------------------------------------------------------------------
            | Pagination Mobile
            |--------------------------------------------------------------------------
            */

            .pagination {
                gap: 3px;
            }


            .pagination .page-link {
                padding: 6px 9px;
                font-size: 12px;
            }


            .page-info {
                font-size: 11px;
            }

        }


        /*
        |--------------------------------------------------------------------------
        | Small Mobile
        |--------------------------------------------------------------------------
        */

        @media (max-width: 400px) {

            .navbar-brand {
                font-size: 13px;
            }


            .navbar .btn {
                font-size: 12px;
                padding: 5px 9px;
            }


            .summary-value {
                font-size: 18px;
            }


            .summary-card .card-body {
                padding: 12px;
            }


            .payment-table {
                min-width: 620px;
            }

        }
    </style>

</head>


<body>


    <!-- =========================================================
     NAVBAR
========================================================= -->

    <nav class="navbar navbar-dark bg-primary">

        <div class="container">

            <span class="navbar-brand">
                AUDITORIUM FUND RAISING - Admin
            </span>


            <a
                href="logout.php"
                class="btn btn-light btn-sm">
                Logout
            </a>

        </div>

    </nav>


    <!-- =========================================================
     MAIN
========================================================= -->

    <div class="container py-4">


        <!-- =====================================================
         SUMMARY CARDS
    ====================================================== -->

        <div class="row g-3 mb-4">


            <!-- TOTAL COLLECTED -->

            <div class="col-6 col-lg-3">

                <div class="card summary-card shadow-sm h-100">

                    <div class="card-body">

                        <div class="summary-title">
                            Total Collected
                        </div>


                        <div class="summary-value text-primary">

                            ₹<?= number_format(
                                    $total,
                                    2
                                ) ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- PAID AMOUNT -->

            <div class="col-6 col-lg-3">

                <div class="card summary-card shadow-sm h-100">

                    <div class="card-body">

                        <div class="summary-title">
                            Paid Amount
                        </div>


                        <div class="summary-value text-success">

                            ₹<?= number_format(
                                    $paidAmount,
                                    2
                                ) ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- UNPAID AMOUNT -->

            <div class="col-6 col-lg-3">

                <div class="card summary-card shadow-sm h-100">

                    <div class="card-body">

                        <div class="summary-title">
                            Unpaid Amount
                        </div>


                        <div class="summary-value text-danger">

                            ₹<?= number_format(
                                    $unpaidAmount,
                                    2
                                ) ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- CONTRIBUTORS -->

            <div class="col-6 col-lg-3">

                <div class="card summary-card shadow-sm h-100">

                    <div class="card-body">

                        <div class="summary-title">
                            Contributors
                        </div>


                        <div class="summary-value">

                            <?= number_format($count) ?>

                        </div>

                    </div>

                </div>

            </div>


        </div>


        <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

        <div
            class="
            page-header
            d-flex
            justify-content-between
            align-items-center
            mb-3
        ">

            <h4 class="mb-0">
                Payments
            </h4>


            <a
                href="add-payment.php"
                class="btn btn-primary">
                + Add Payment
            </a>

        </div>


        <!-- =====================================================
         PAYMENT TABLE
    ====================================================== -->

        <div class="card border-0 shadow-sm">

            <div class="card-body p-0">

                <div class="payment-table-wrapper">

                    <table
                        class="
                        table
                        table-hover
                        mb-0
                        payment-table
                    ">


                        <thead class="table-light">

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Name
                                </th>

                                <th>
                                    Amount
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (count($payments) > 0): ?>


                                <?php foreach (
                                    $payments
                                    as $loopIndex => $payment
                                ): ?>


                                    <tr>


                                        <!-- SERIAL NUMBER -->

                                        <td>

                                            <?= $offset + $loopIndex + 1 ?>

                                        </td>


                                        <!-- NAME -->

                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $payment['name'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </strong>

                                        </td>


                                        <!-- AMOUNT -->

                                        <td class="text-success fw-bold">

                                            ₹<?= number_format(
                                                    $payment['amount'],
                                                    2
                                                ) ?>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <?php if (
                                                isset($payment['received']) &&
                                                $payment['received'] == 1
                                            ): ?>

                                                <span class="received-badge">

                                                    ✓ Received

                                                </span>

                                            <?php else: ?>

                                                <span class="pending-badge">

                                                    Pending

                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- ACTION -->

                                        <td>

                                            <div class="action-buttons">
                                                <a
                                                    href="receipt.php?id=<?= (int)$payment['id'] ?>"
                                                    target="_blank"
                                                    class="btn btn-primary btn-sm">

                                                    🧾 Generate Receipt

                                                </a>
                                                <?php if (
                                                    !isset($payment['received']) ||
                                                    $payment['received'] == 0
                                                ): ?>


                                                    <!-- CONFIRM RECEIVED -->

                                                    <form
                                                        method="POST"
                                                        action="dashboard.php?page=<?= $page ?>"
                                                        onsubmit="
                                                    return confirm(
                                                        'Are you sure this payment has been received?'
                                                    );
                                                ">

                                                        <input
                                                            type="hidden"
                                                            name="action"
                                                            value="received">


                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= $payment['id'] ?>">


                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-success">

                                                            ✓ Confirm Received

                                                        </button>

                                                    </form>


                                                <?php else: ?>


                                                    <button
                                                        type="button"
                                                        class="
                                                    btn
                                                    btn-sm
                                                    btn-outline-success
                                                "
                                                        disabled>

                                                        ✓ Received

                                                    </button>


                                                <?php endif; ?>


                                                <!-- DELETE -->

                                                <a
                                                    href="delete-payment.php?id=<?= $payment['id'] ?>"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="
                                                return confirm(
                                                    'Delete this payment?'
                                                );
                                            ">

                                                    Delete

                                                </a>



                                            </div>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php else: ?>


                                <tr>

                                    <td
                                        colspan="5"
                                        class="
                                    text-center
                                    text-muted
                                    py-5
                                ">

                                        No payments found.

                                    </td>

                                </tr>


                            <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <!-- =====================================================
         PAGINATION
    ====================================================== -->

        <?php if ($totalPages > 1): ?>


            <nav
                class="mt-4"
                aria-label="Payment pagination">

                <ul
                    class="
                    pagination
                    justify-content-center
                    flex-wrap
                ">


                    <!-- PREVIOUS -->

                    <?php if ($page > 1): ?>

                        <li class="page-item">

                            <a
                                class="page-link"
                                href="?page=<?= $page - 1 ?>">
                                Previous
                            </a>

                        </li>

                    <?php else: ?>

                        <li class="page-item disabled">

                            <span class="page-link">
                                Previous
                            </span>

                        </li>

                    <?php endif; ?>


                    <?php

                    $startPage = max(
                        1,
                        $page - 2
                    );

                    $endPage = min(
                        $totalPages,
                        $page + 2
                    );

                    ?>


                    <!-- FIRST PAGE -->

                    <?php if ($startPage > 1): ?>

                        <li class="page-item">

                            <a
                                class="page-link"
                                href="?page=1">
                                1
                            </a>

                        </li>


                        <?php if ($startPage > 2): ?>

                            <li class="page-item disabled">

                                <span class="page-link">
                                    ...
                                </span>

                            </li>

                        <?php endif; ?>

                    <?php endif; ?>


                    <!-- PAGE NUMBERS -->

                    <?php for (
                        $i = $startPage;
                        $i <= $endPage;
                        $i++
                    ): ?>

                        <li
                            class="
                            page-item
                            <?= $i == $page
                                ? 'active'
                                : '' ?>
                        ">

                            <a
                                class="page-link"
                                href="?page=<?= $i ?>">

                                <?= $i ?>

                            </a>

                        </li>

                    <?php endfor; ?>


                    <!-- LAST PAGE -->

                    <?php if ($endPage < $totalPages): ?>

                        <?php if (
                            $endPage < $totalPages - 1
                        ): ?>

                            <li class="page-item disabled">

                                <span class="page-link">
                                    ...
                                </span>

                            </li>

                        <?php endif; ?>


                        <li class="page-item">

                            <a
                                class="page-link"
                                href="?page=<?= $totalPages ?>">

                                <?= $totalPages ?>

                            </a>

                        </li>

                    <?php endif; ?>


                    <!-- NEXT -->

                    <?php if ($page < $totalPages): ?>

                        <li class="page-item">

                            <a
                                class="page-link"
                                href="?page=<?= $page + 1 ?>">
                                Next
                            </a>

                        </li>

                    <?php else: ?>

                        <li class="page-item disabled">

                            <span class="page-link">
                                Next
                            </span>

                        </li>

                    <?php endif; ?>


                </ul>

            </nav>


            <!-- PAGE INFORMATION -->

            <div
                class="
                page-info
                text-center
                text-muted
                small
                mt-3
            ">

                Page <?= $page ?>

                of <?= $totalPages ?>

                &nbsp; | &nbsp;

                Showing

                <?= $offset + 1 ?>

                -

                <?= $offset + count($payments) ?>

                of

                <?= $count ?>

                payments

            </div>


        <?php endif; ?>


    </div>


    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


</body>

</html>