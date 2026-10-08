<?php

require_once "config.php";

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
| Initial Payments
|--------------------------------------------------------------------------
*/

$limit = 10;

$stmt = $pdo->prepare("
    SELECT id, name, amount
    FROM payments
    ORDER BY amount DESC, id DESC
    LIMIT :limit
");

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();

$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Track Unique Visitor With Country
|--------------------------------------------------------------------------
*/

$visitorIp = $_SERVER['REMOTE_ADDR'] ?? '';

if ($visitorIp !== '') {

    $country = 'Unknown';

    $apiUrl =
        "http://ip-api.com/json/" .
        urlencode($visitorIp) .
        "?fields=status,country,countryCode";

    $response = @file_get_contents($apiUrl);

    if ($response) {

        $locationData = json_decode($response, true);

        if (
            isset($locationData['status']) &&
            $locationData['status'] === 'success'
        ) {
            $country = $locationData['country'] ?? 'Unknown';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Insert Visitor
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO visitors
        (ip_address, country)
        VALUES (:ip, :country)
    ");

    $stmt->execute([
        ':ip'      => $visitorIp,
        ':country' => $country
    ]);
}


/*
|--------------------------------------------------------------------------
| Total Unique Visitors
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM visitors
");

$uniqueVisitors = $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| Visitors By Country
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT country, COUNT(*) AS total
    FROM visitors
    WHERE country IS NOT NULL
      AND country != ''
    GROUP BY country
    ORDER BY total DESC
");

$visitorsByCountry = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Amount Wise Summary
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        amount,
        COUNT(*) AS person_count
    FROM payments
    GROUP BY amount
    ORDER BY amount DESC
");

$amountSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>AUDITORIUM FUND RAISING</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <style>
* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: "Segoe UI", Arial, sans-serif;
    color: #f5fbfc;
    background:
        linear-gradient(135deg, rgba(11, 45, 55, .94), rgba(25, 82, 94, .88)),
        #123f4b;
}

/* =========================
   AUDITORIUM IMAGE BOX
   ========================= */
.hero {
    position: relative;
    color: #ffffff;
    padding: 55px 20px 38px;
    border-radius: 0 0 28px 28px;

    background:
        linear-gradient(
            rgba(8, 43, 53, 0.30),
            rgba(12, 65, 76, 0.42)
        ),
        url("https://modarchs.in/images/projects/1588596032ConventioncentreatOchira.jpg")
        center center / cover no-repeat;

    border: 1px solid rgba(255,255,255,.18);
    border-top: 0;
    box-shadow: 0 15px 35px rgba(0,0,0,.24);
}

.hero::after {
    content: "";
    position: absolute;
    left: 50%;
    bottom: 0;
    width: 90px;
    height: 2px;
    transform: translateX(-50%);
    background: rgba(190, 239, 241, .85);
    border-radius: 10px;
}

.hero-title {
    font-size: clamp(24px, 4vw, 34px);
    font-weight: 700;
    letter-spacing: .5px;
    margin-bottom: 18px;
    text-shadow: 0 2px 8px rgba(0,0,0,.7);
}

.amount-label {
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    opacity: .9;
    margin-bottom: 5px;
    text-shadow: 0 2px 5px rgba(0,0,0,.7);
}

.amount {
    font-size: clamp(38px, 7vw, 58px);
    font-weight: 800;
    line-height: 1.15;
    color: #ffffff;
    text-shadow: 0 3px 12px rgba(0,0,0,.75);
}

.contributors {
    margin-top: 10px;
    font-size: 15px;
    opacity: .95;
    text-shadow: 0 2px 5px rgba(0,0,0,.7);
}

/* VISITOR CORNER */
.visitor-corner {
    position: absolute;
    top: 12px;
    left: 12px;
    right: 12px;
    width: calc(100% - 24px);
    overflow-x: auto;
    overflow-y: hidden;
    white-space: nowrap;
    padding: 6px 10px;
    border-radius: 9px;
    background: rgba(7, 43, 53, .30);
    border: 1px solid rgba(210, 247, 248, .22);
    color: #fff;
    font-size: 10px;
    text-align: left;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.visitor-country {
    display: inline-block;
    margin-right: 12px;
}

.visitor-count {
    font-weight: 700;
}

.visitor-corner::-webkit-scrollbar {
    height: 0;
}

/* SEARCH */
.search-box {
    max-width: 650px;
    margin: 25px auto 0;
}

.search-box .input-group {
    background: rgba(255,255,255,.10);
    border: 1px solid rgba(220,250,251,.45);
    border-radius: 13px;
    overflow: hidden;
    box-shadow: 0 8px 22px rgba(0,0,0,.18);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.search-box .input-group-text {
    background: transparent;
    border: 0;
    color: #e8fbfc;
    font-size: 18px;
    padding-left: 16px;
}

.search-box .form-control {
    border: 0;
    background: transparent;
    padding: 14px 10px;
    font-size: 16px;
    color: #ffffff;
}

.search-box .form-control::placeholder {
    color: rgba(255,255,255,.82);
}

.search-box .form-control:focus {
    box-shadow: none;
    background: transparent;
    color: #ffffff;
}

/* PAY BUTTON */
.pay-now-area {
    max-width: 650px;
    margin: 18px auto 0;
}

.pay-now-button {
    width: 100%;
    border: 1px solid rgba(220,250,251,.38);
    border-radius: 13px;
    padding: 13px 20px;
    background: rgba(86, 177, 188, .30);
    color: #ffffff;
    font-size: 17px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(0,0,0,.20);
    transition: .2s ease;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.pay-now-button:hover {
    background: rgba(105, 198, 207, .42);
    transform: translateY(-2px);
}

/* =========================
   REST OF PAGE - TRANSPARENT GLASS
   ========================= */
.main-container {
    max-width: 700px;
    margin: auto;
    padding: 28px 15px 35px;
}

.payment-card,
.summary-card {
    background: rgba(31, 91, 103, .62);
    border: 1px solid rgba(210,247,248,.18);
    border-radius: 18px;
    box-shadow: 0 15px 35px rgba(0,0,0,.20);
    overflow: hidden;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.payment-card-header,
.summary-header {
    padding: 20px;
    border-bottom: 1px solid rgba(220,250,251,.15);
}

.payment-card-header h4,
.summary-header h4 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #f4ffff;
}

.payment-list {
    padding: 0 20px;
}

.payment-item {
    padding: 18px 0;
    border-bottom: 1px solid rgba(220,250,251,.13);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.payment-item:last-child {
    border-bottom: 0;
}

.payment-name {
    font-size: 16px;
    font-weight: 600;
    color: #ffffff;
    word-break: break-word;
}

.payment-amount {
    font-size: 16px;
    font-weight: 700;
    color: #bcebed;
    white-space: nowrap;
}

.search-empty,
.empty-message {
    text-align: center;
    color: rgba(235,252,253,.78);
    padding: 40px 20px;
}

.search-empty {
    display: none;
}

#loading,
#noMore {
    display: none;
    text-align: center;
    padding: 20px;
    color: rgba(235,252,253,.78);
    font-size: 14px;
}

/* POPUPS */
.fund-popup-overlay {
    position: fixed;
    inset: 0;
    background: rgba(4, 29, 36, .68);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
    z-index: 99999;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
}

.fund-popup {
    position: relative;
    width: 100%;
    max-width: 600px;
    max-height: 96vh;
    overflow-y: auto;
    background: rgba(242, 250, 251, .96);
    color: #263b40;
    border: 1px solid rgba(255,255,255,.7);
    border-radius: 16px;
    box-shadow: 0 25px 70px rgba(0,0,0,.40);
    animation: popupAnimation .3s ease;
}

@keyframes popupAnimation {
    from { opacity: 0; transform: scale(.93); }
    to { opacity: 1; transform: scale(1); }
}

.fund-content {
    padding: 27px 22px 22px;
    color: #263b40;
    line-height: 1.65;
}

.fund-content h3 {
    text-align: center;
    font-weight: 750;
    color: #1f5965;
    margin-bottom: 18px;
    font-size: 21px;
    padding-right: 25px;
}

.fund-content p {
    margin-bottom: 10px;
    font-size: 14px;
}

.fund-close {
    position: absolute;
    right: 9px;
    top: 9px;
    width: 34px;
    height: 34px;
    border: none;
    border-radius: 50%;
    background: rgba(31, 89, 101, .12);
    color: #28515a;
    font-size: 23px;
    line-height: 1;
    cursor: pointer;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
}

.fund-close:hover {
    background: rgba(31, 89, 101, .20);
}

.fund-note {
    background: rgba(67, 145, 157, .10);
    border-left: 4px solid #347987;
    padding: 11px 13px;
    border-radius: 7px;
    margin-top: 15px;
    font-size: 13px;
}

.fund-note strong {
    color: #a32626;
}

.fund-contact {
    text-align: center;
    margin-top: 15px;
    padding: 11px;
    background: rgba(67, 145, 157, .10);
    border-radius: 8px;
    font-size: 16px;
    font-weight: 650;
}

.fund-contact a {
    color: #1f6471;
    text-decoration: none;
    font-weight: 750;
}

.fund-close-button {
    display: block;
    width: 100%;
    margin-top: 15px;
    padding: 11px;
    border: none;
    border-radius: 8px;
    background: #2d6976;
    color: #fff;
    font-size: 15px;
    font-weight: 650;
    cursor: pointer;
}

.fund-close-button:hover {
    background: #235762;
}

.payment-popup {
    display: none;
}

.payment-title {
    text-align: center;
    font-size: 22px;
    font-weight: 750;
    color: #1f5965;
    margin-bottom: 15px;
}

.payment-description {
    text-align: center;
    font-size: 14px;
    color: #596b70;
    margin-bottom: 15px;
}

.qr-container {
    text-align: center;
    margin: 10px auto 15px;
}

.payment-qr {
    display: block;
    width: 260px;
    max-width: 100%;
    height: auto;
    margin: 0 auto;
    border-radius: 10px;
    border: 1px solid #d6e4e6;
    padding: 8px;
    background: #fff;
}

.qr-note {
    text-align: center;
    margin-top: 12px;
    font-size: 14px;
    color: #596b70;
}

.summary-table-wrapper {
    overflow-x: auto;
}

.summary-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

.summary-table th,
.summary-table td {
    padding: 12px 15px;
    border-bottom: 1px solid rgba(220,250,251,.13);
    font-size: 14px;
}

.summary-table th {
    background: rgba(255,255,255,.07);
    font-weight: 700;
}

.summary-table td:last-child,
.summary-table th:last-child {
    text-align: right;
}

.summary-table tr:last-child td {
    border-bottom: 0;
}

footer {
    padding-bottom: 20px !important;
    color: rgba(220,247,248,.72);
}

footer .text-muted {
    color: rgba(220,247,248,.72) !important;
}

@media (max-width: 576px) {
    .hero {
        width: 100%;
        padding: 48px 15px 30px;
        border-radius: 0 0 20px 20px;

        background:
            linear-gradient(
                rgba(8, 43, 53, 0.30),
                rgba(12, 65, 76, 0.42)
            ),
            url("https://modarchs.in/images/projects/1588596032ConventioncentreatOchira.jpg")
            center center / contain no-repeat;
        background-color: #174b58;
    }

    .hero-title {
        font-size: 23px;
        line-height: 1.25;
    }

    .amount {
        font-size: 36px;
    }

    .visitor-corner {
        top: 8px;
        left: 8px;
        right: 8px;
        width: calc(100% - 16px);
        font-size: 9px;
        padding: 4px 6px;
    }

    .search-box {
        margin-top: 22px;
        width: 100%;
    }

    .search-box .form-control {
        font-size: 15px;
        min-width: 0;
    }

    .pay-now-area {
        width: 100%;
    }

    .pay-now-button {
        font-size: 16px;
        padding: 12px 16px;
    }

    .main-container {
        width: 100%;
        padding: 20px 12px 30px;
    }

    .payment-card {
        width: 100%;
        border-radius: 16px;
        background: rgba(31, 91, 103, .62);
    }

    .payment-card-header {
        padding: 17px 15px;
    }

    .payment-card-header h4 {
        font-size: 18px;
    }

    .payment-list {
        padding: 0 15px;
    }

    .payment-item {
        padding: 16px 0;
        gap: 10px;
    }

    .payment-name,
    .payment-amount {
        font-size: 15px;
    }

    .payment-amount {
        flex-shrink: 0;
    }

    .fund-popup {
        width: calc(100% - 20px);
        max-height: 96vh;
        border-radius: 14px;
    }

    .fund-content {
        padding: 22px 16px 16px;
        line-height: 1.5;
    }

    .fund-content h3 {
        font-size: 19px;
        margin-bottom: 14px;
    }

    .fund-content p {
        font-size: 13px;
        margin-bottom: 8px;
    }

    .fund-note {
        font-size: 12px;
        padding: 9px 10px;
        margin-top: 12px;
    }

    .fund-contact {
        font-size: 15px;
        padding: 9px;
        margin-top: 12px;
    }

    .fund-close-button {
        margin-top: 12px;
        padding: 9px;
        font-size: 14px;
    }

    .payment-qr {
        width: 230px;
    }

    .summary-table th,
    .summary-table td {
        padding: 10px 12px;
        font-size: 13px;
    }

    footer {
        padding-left: 12px;
        padding-right: 12px;
        text-align: center;
    }
}
</style>

</head>


<body>


<!-- =========================================================
     HERO
========================================================= -->

<div class="hero text-center">


    <!-- Visitor Countries -->

   


    <div class="hero-title">

        AUDITORIUM FUND RAISING

    </div>


    <div class="amount-label">

        Amount Collected

    </div>


    <div class="amount">

        ₹<?= number_format($total, 2) ?>

    </div>


    <div class="contributors">

        <?= number_format($count) ?>

        Contributors

    </div>


    <!-- Search -->

    <div class="search-box">

        <div class="input-group">

            <span class="input-group-text">
                🔍
            </span>

            <input
                type="text"
                id="nameSearch"
                class="form-control"
                placeholder="Search contributor name..."
                autocomplete="off">

        </div>

    </div>


    <!-- Pay Now -->

    <div class="pay-now-area">

        <button
            type="button"
            class="pay-now-button"
            id="openPaymentBtn">

            💳 Pay Now

        </button>

    </div>

</div>



<!-- =========================================================
     PAYMENT LIST
========================================================= -->

<div class="main-container">


    <div class="payment-card">


        <div class="payment-card-header">

            <h4>
                Payment Contributors
            </h4>

        </div>


        <div
            id="paymentList"
            class="payment-list">


            <?php if (count($payments) > 0): ?>


                <?php foreach ($payments as $payment): ?>

                    <div
                        class="payment-item"
                        data-name="<?= htmlspecialchars(
                            strtolower(
                                $payment['name']
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>">


                        <div class="payment-name">

                            <?= htmlspecialchars(
                                $payment['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>


                        <div class="payment-amount">

                            ₹<?= number_format(
                                $payment['amount'],
                                2
                            ) ?>

                        </div>


                    </div>

                <?php endforeach; ?>


            <?php else: ?>


                <div class="empty-message">

                    No payments added yet.

                </div>


            <?php endif; ?>


        </div>


        <!-- Search Empty -->

        <div
            id="searchEmpty"
            class="search-empty">

            No contributor found.

        </div>


        <!-- Loading -->

        <div id="loading">

            <div
                class="spinner-border spinner-border-sm text-primary"
                role="status">
            </div>

            <span class="ms-2">
                Loading...
            </span>

        </div>


        <!-- No More -->

        <div id="noMore">

            All payments loaded

        </div>


    </div>


    <!-- =====================================================
         AMOUNT SUMMARY
    ===================================================== -->

    <!-- <div class="summary-card">


        <div class="summary-header">

            <h4>
                Amount & Number of Persons
            </h4>

        </div>


        <div class="summary-table-wrapper">

            <table class="summary-table">

                <thead>

                    <tr>

                        <th>
                            Amount
                        </th>

                        <th>
                            Number of Persons
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (count($amountSummary) > 0): ?>

                        <?php foreach (
                            $amountSummary
                            as $summary
                        ): ?>

                            <tr>

                                <td>

                                    ₹<?= number_format(
                                        $summary['amount'],
                                        2
                                    ) ?>

                                </td>

                                <td>

                                    <?= number_format(
                                        $summary['person_count']
                                    ) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="2"
                                class="text-center">

                                No payment data available.

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div> -->


</div>



<!-- =========================================================
     FUND INFORMATION POPUP
========================================================= -->

<div
    id="fundPopup"
    class="fund-popup-overlay">


    <div class="fund-popup">


        <!-- Close X -->

        <button
            type="button"
            class="fund-close"
            id="fundCloseBtn"
            aria-label="Close">

            &times;

        </button>


        <div class="fund-content">


            <h3>

                ഓഡിറ്റോറിയം നിർമ്മാണ ഫണ്ട് സമാഹരണം

            </h3>


            <p>

                പ്രിയപ്പെട്ടവരെ,

            </p>


            <p>
നമ്മുടെ മഹല്ല്ന് കീഴിൽ നിർമ്മിക്കുന്ന പുതിയ ഓഡിറ്റോറിയത്തിന്റെ നിർമ്മാണ പ്രവർത്തനങ്ങൾ പുരോഗമിക്കുകയാണെന്ന് നിങ്ങൾക്കറിയാമല്ലോ.

            </p>


            <p>

               ഈ സൽകർമ്മത്തിൽ നമ്മൾ ഓരോരുത്തർക്കും പങ്കാളികളാകാൻ അവസരമൊരുക്കിക്കൊണ്ട് ഒരു ഫണ്ട് ശേഖരണം ആരംഭിക്കുകയാണ്. മഹല്ല് അംഗങ്ങൾക്കും നമ്മുടെ സുഹൃത്തുക്കൾക്കും കുടുംബാംഗങ്ങൾക്കും ഇതിലേക്ക് ഉദാരമായി സംഭാവന ചെയ്യാവുന്നതാണ്.

            </p>


            <p>

               ഈ ഫണ്ട് സമാഹരണത്തിന് നേതൃത്വം നൽകുന്നത്. എല്ലാവരിൽ നിന്നും ശേഖരിക്കുന്ന തുക നിർമ്മാണ കമ്മിറ്റിക്ക് കൈമാറുന്നതാണ്. നമ്മുടെ നാടിന്റെ ഈ വലിയ ആവശ്യത്തിനായി താങ്ങായി നിൽക്കാനും നമ്മളാൽ കഴിയുന്ന സഹായം നൽകാനും ഏവരോടും അഭ്യർത്ഥിക്കുന്നു. ഈ സന്ദേശം പരമാവധി ആളുകളിലേക്ക് എത്തിക്കുക.

            </p>


            <div class="fund-note">

                <strong>NB:</strong>

                തുക അയച്ച ശേഷം സ്ക്രീൻഷോട്ട് ദയവായി
                താഴെ കാണുന്ന നമ്പറിലേക്ക് അയക്കുക.

            </div>


            <div class="fund-contact">

                NAME:

                <a
                    href="https://wa.me/911234567890"
                    target="_blank"
                    rel="noopener">

                    +91 12345 67890

                </a>

            </div>


            <button
                type="button"
                class="fund-close-button"
                id="fundCloseButton">

                Close

            </button>


        </div>

    </div>

</div>



<!-- =========================================================
     PAYMENT POPUP
========================================================= -->

<div
    id="paymentPopup"
    class="fund-popup-overlay payment-popup">


    <div class="fund-popup">


        <!-- Close X -->

        <button
            type="button"
            class="fund-close"
            id="paymentCloseBtn"
            aria-label="Close">

            &times;

        </button>


        <div class="fund-content">


            <div class="payment-title">

                Make Your Payment

            </div>


            <div class="payment-description">

                Scan the QR code below using
                Google Pay, PhonePe, Paytm or
                any UPI application.

            </div>

               <!-- BANK DETAILS -->

    <h1>BANK DETAILS</h1>

    <p>
        <strong>Account Name:</strong>  SAMPLE NAME
    </p>

    <p>
        <strong>Bank Name:</strong> STATE BANK OF INDIA
    </p>

    <p>
        <strong>Account Number:</strong> 12345678901
    </p>

    <p>
        <strong>IFSC Code:</strong> SBIN0001234
    </p>

    <p>
        <strong>Branch:</strong> CALICUT MAIN BRANCH
    </p>

    <p>
        <strong>Account Type:</strong> SAVINGS ACCOUNT
    </p>



            <!-- QR CODE -->

            <div class="qr-container">

                <img
                    src="qr.jpeg"
                    alt="Payment QR Code"
                    class="payment-qr">

            </div>


            <div class="qr-note">

               പേയ്‌മെന്റ് പൂർത്തിയാക്കിയ ശേഷം, ദയവായി അതിന്റെ സ്ക്രീൻഷോട്ട് മിൻഹാജിന് വാട്സാപ്പിൽ അയക്കുക.

            </div>


            <div class="fund-contact">

                NAME:

                <a
                    href="https://wa.me/911234567890"
                    target="_blank"
                    rel="noopener">

                    +91 12345 67890

                </a>

            </div>


            <button
                type="button"
                class="fund-close-button"
                id="paymentCloseButton">

                Close

            </button>


        </div>

    </div>

</div>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="text-center py-3">

    <small class="text-muted">

        Technical Errors & Support:

        <a
            href="https://wa.me/918907015598"
            target="_blank"
            rel="noopener"
            class="text-muted text-decoration-none">

            Shamsu (8907015598)

        </a>

    </small>

</footer>



<script>

/*
|--------------------------------------------------------------------------
| FUND POPUP
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const fundPopup =
            document.getElementById(
                "fundPopup"
            );


        const fundCloseBtn =
            document.getElementById(
                "fundCloseBtn"
            );


        const fundCloseButton =
            document.getElementById(
                "fundCloseButton"
            );


        /*
        |--------------------------------------------------------------------------
        | Initial Popup
        |--------------------------------------------------------------------------
        */

        if (fundPopup) {

            fundPopup.style.display =
                "flex";

        }


        /*
        |--------------------------------------------------------------------------
        | Close X
        |--------------------------------------------------------------------------
        */

        if (fundCloseBtn) {

            fundCloseBtn.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();

                    event.stopPropagation();

                    if (fundPopup) {

                        fundPopup.style.display =
                            "none";

                    }

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Close Button
        |--------------------------------------------------------------------------
        */

        if (fundCloseButton) {

            fundCloseButton.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();

                    event.stopPropagation();

                    if (fundPopup) {

                        fundPopup.style.display =
                            "none";

                    }

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Click Outside Popup
        |--------------------------------------------------------------------------
        */

        if (fundPopup) {

            fundPopup.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target ===
                        fundPopup
                    ) {

                        fundPopup.style.display =
                            "none";

                    }

                }
            );

        }


    }
);



/*
|--------------------------------------------------------------------------
| PAYMENT POPUP
|--------------------------------------------------------------------------
*/

const paymentPopup =
    document.getElementById(
        "paymentPopup"
    );


const openPaymentBtn =
    document.getElementById(
        "openPaymentBtn"
    );


const paymentCloseBtn =
    document.getElementById(
        "paymentCloseBtn"
    );


const paymentCloseButton =
    document.getElementById(
        "paymentCloseButton"
    );


/*
|--------------------------------------------------------------------------
| Open Payment Popup
|--------------------------------------------------------------------------
*/

if (openPaymentBtn) {

    openPaymentBtn.addEventListener(
        "click",
        function () {

            if (paymentPopup) {

                paymentPopup.style.display =
                    "flex";

            }

        }
    );

}


/*
|--------------------------------------------------------------------------
| Close Payment X
|--------------------------------------------------------------------------
*/

if (paymentCloseBtn) {

    paymentCloseBtn.addEventListener(
        "click",
        function (event) {

            event.preventDefault();

            event.stopPropagation();

            paymentPopup.style.display =
                "none";

        }
    );

}


/*
|--------------------------------------------------------------------------
| Close Payment Button
|--------------------------------------------------------------------------
*/

if (paymentCloseButton) {

    paymentCloseButton.addEventListener(
        "click",
        function (event) {

            event.preventDefault();

            event.stopPropagation();

            paymentPopup.style.display =
                "none";

        }
    );

}


/*
|--------------------------------------------------------------------------
| Click Outside Payment Popup
|--------------------------------------------------------------------------
*/

if (paymentPopup) {

    paymentPopup.addEventListener(
        "click",
        function (event) {

            if (
                event.target ===
                paymentPopup
            ) {

                paymentPopup.style.display =
                    "none";

            }

        }
    );

}


/*
|--------------------------------------------------------------------------
| INFINITE SCROLL
|--------------------------------------------------------------------------
*/

let currentPage = 1;

let loading = false;

let hasMore = true;

let isSearching = false;


const paymentList =
    document.getElementById(
        "paymentList"
    );


const loadingBox =
    document.getElementById(
        "loading"
    );


const noMoreBox =
    document.getElementById(
        "noMore"
    );


const searchEmpty =
    document.getElementById(
        "searchEmpty"
    );


const nameSearch =
    document.getElementById(
        "nameSearch"
    );


/*
|--------------------------------------------------------------------------
| Load More Payments
|--------------------------------------------------------------------------
*/

function loadMorePayments() {


    if (
        loading ||
        !hasMore ||
        isSearching
    ) {

        return;

    }


    loading = true;

    loadingBox.style.display =
        "block";


    const nextPage =
        currentPage + 1;


    fetch(
        "load-payments.php?page=" +
        nextPage
    )


    .then(function (response) {

        if (!response.ok) {

            throw new Error(
                "Network response was not OK"
            );

        }

        return response.json();

    })


    .then(function (data) {


        if (
            data.success &&
            Array.isArray(
                data.payments
            ) &&
            data.payments.length > 0
        ) {


            data.payments.forEach(
                function (payment) {


                    const item =
                        document.createElement(
                            "div"
                        );


                    item.className =
                        "payment-item";


                    item.setAttribute(
                        "data-name",
                        String(
                            payment.name
                        ).toLowerCase()
                    );


                    item.innerHTML = `

                        <div class="payment-name">

                            ${escapeHtml(
                                payment.name
                            )}

                        </div>

                        <div class="payment-amount">

                            ₹${formatAmount(
                                payment.amount
                            )}

                        </div>

                    `;


                    paymentList.appendChild(
                        item
                    );

                }
            );


            currentPage =
                nextPage;


            if (
                data.payments.length < 10
            ) {

                hasMore = false;

                noMoreBox.style.display =
                    "block";

            }


        } else {


            hasMore = false;

            noMoreBox.style.display =
                "block";

        }

    })


    .catch(function (error) {

        console.error(
            "Error loading payments:",
            error
        );

    })


    .finally(function () {

        loading = false;

        loadingBox.style.display =
            "none";

    });

}


/*
|--------------------------------------------------------------------------
| Infinite Scroll Detection
|--------------------------------------------------------------------------
*/

window.addEventListener(
    "scroll",
    function () {


        if (isSearching) {

            return;

        }


        const scrollPosition =
            window.innerHeight +
            window.scrollY;


        const pageHeight =
            document.documentElement
                .scrollHeight;


        if (
            scrollPosition >=
            pageHeight - 300
        ) {

            loadMorePayments();

        }

    }
);



/*
|--------------------------------------------------------------------------
| NAME SEARCH
|--------------------------------------------------------------------------
*/

if (nameSearch) {

    nameSearch.addEventListener(
        "input",
        function () {


            const search =
                this.value
                    .trim()
                    .toLowerCase();


            const items =
                document.querySelectorAll(
                    ".payment-item"
                );


            isSearching =
                search !== "";


            let visibleCount = 0;


            items.forEach(
                function (item) {


                    const name =
                        item.getAttribute(
                            "data-name"
                        ) || "";


                    if (
                        search === "" ||
                        name.includes(search)
                    ) {

                        item.style.display =
                            "flex";

                        visibleCount++;

                    } else {

                        item.style.display =
                            "none";

                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | Search Empty
            |--------------------------------------------------------------------------
            */

            if (
                search !== "" &&
                visibleCount === 0
            ) {

                searchEmpty.style.display =
                    "block";

            } else {

                searchEmpty.style.display =
                    "none";

            }


            /*
            |--------------------------------------------------------------------------
            | Infinite Scroll Messages
            |--------------------------------------------------------------------------
            */

            if (search !== "") {

                loadingBox.style.display =
                    "none";

                noMoreBox.style.display =
                    "none";

            } else {

                if (!hasMore) {

                    noMoreBox.style.display =
                        "block";

                }

            }

        }
    );

}



/*
|--------------------------------------------------------------------------
| Amount Formatting
|--------------------------------------------------------------------------
*/

function formatAmount(amount) {

    return Number(amount)
        .toLocaleString(
            "en-IN",
            {
                minimumFractionDigits: 2,

                maximumFractionDigits: 2
            }
        );

}



/*
|--------------------------------------------------------------------------
| HTML Escape
|--------------------------------------------------------------------------
*/

function escapeHtml(text) {

    const div =
        document.createElement(
            "div"
        );

    div.textContent =
        text;

    return div.innerHTML;

}

</script>


</body>

</html>