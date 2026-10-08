<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Get payment ID
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    die('Invalid payment ID.');
}

// Get payment
$stmt = $pdo->prepare("
    SELECT id, name, amount
    FROM payments
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $id
]);

$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    die('Payment not found.');
}

// PDF options
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);

$receiptNo = 'MUB-' . str_pad($payment['id'], 5, '0', STR_PAD_LEFT);

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<style>
@page {
    margin: 40px;
}

body {
    font-family: DejaVu Sans, sans-serif;
    color: #333333;
    margin: 0;
    padding: 0;
    font-size: 13px;
    line-height: 1.6;
}

.receipt-box {
    max-width: 580px;
    margin: 0 auto;
    border: 1px solid #dcdcdc;
    padding: 30px;
}

.header {
    text-align: center;
    border-bottom: 2px solid #333333;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.title {
    font-size: 20px;
    font-weight: bold;
    color: #111111;
    margin: 0;
}

.subtitle {
    font-size: 12px;
    color: #666666;
    margin-top: 4px;
}

.details-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 25px;
}

.details-table td {
    padding: 6px 0;
    vertical-align: top;
}

.label {
    width: 35%;
    font-weight: bold;
    color: #555555;
}

.value {
    color: #111111;
}

.amount-table {
    width: 100%;
    border-collapse: collapse;
    border-top: 1px solid #cccccc;
    border-bottom: 1px solid #cccccc;
    margin-bottom: 25px;
}

.amount-table th {
    text-align: left;
    padding: 10px 0;
    font-size: 12px;
    color: #555555;
}

.amount-table td {
    padding: 12px 0;
    font-size: 15px;
    font-weight: bold;
    color: #111111;
}

.text-right {
    text-align: right;
}

.thankyou {
    text-align: center;
    font-size: 13px;
    color: #444444;
    margin-top: 25px;
}

.footer {
    text-align: center;
    margin-top: 25px;
    padding-top: 12px;
    border-top: 1px solid #eeeeee;
    font-size: 10px;
    color: #888888;
}
</style>

</head>

<body>

<div class="receipt-box">

    <div class="header">
        <div class="title">AUDITORIUM FUND</div>
        <div class="subtitle">Payment Receipt</div>
    </div>

    <table class="details-table">
        <tr>
            <td class="label">Receipt No:</td>
            <td class="value">' . htmlspecialchars($receiptNo) . '</td>
        </tr>
        <tr>
            <td class="label">Received From:</td>
            <td class="value">' . htmlspecialchars($payment['name']) . '</td>
        </tr>
        <tr>
            <td class="label">Payment ID:</td>
            <td class="value">#' . (int)$payment['id'] . '</td>
        </tr>
        <tr>
            <td class="label">Date:</td>
            <td class="value">' . date('d-m-Y') . '</td>
        </tr>
    </table>

    <table class="amount-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Auditorium Contribution</td>
                <td class="text-right">₹' . number_format((float)$payment['amount'], 2) . '</td>
            </tr>
        </tbody>
    </table>

    <div class="thankyou">
        Thank you for your valuable contribution.
    </div>

    <div class="footer">
        This is a computer-generated receipt for the Auditorium Fund.
    </div>

</div>

</body>
</html>
';

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

$dompdf->stream(
    'AUDITORIUM-FUND-Receipt-' . $payment['id'] . '.pdf',
    [
        'Attachment' => false
    ]
);

exit;