<?php
session_start();

if (!isset($_SESSION['customer_logged_in']) || !$_SESSION['customer_logged_in']) {
    header("Location: customer_login_simple.php");
    exit();
}

$scno = $_SESSION['customer_scno'];
$name = $_SESSION['customer_name'];

$servername = "localhost";
$user = "root";
$password = "";
$dbname = "user";

$conn = new mysqli($servername, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_stmt = $conn->prepare("SELECT * FROM user WHERE scno = ?");
$user_stmt->bind_param("s", $scno);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$customer = $user_result->fetch_assoc();
$user_stmt->close();

$current_bill_stmt = $conn->prepare("
    SELECT * FROM bill 
    WHERE scno = ? 
    ORDER BY year DESC, month DESC, bill_datetime DESC 
    LIMIT 1
");
$current_bill_stmt->bind_param("s", $scno);
$current_bill_stmt->execute();
$current_bill_result = $current_bill_stmt->get_result();
$current_bill = $current_bill_result->fetch_assoc();
$current_bill_stmt->close();

// Get all bills for history
$all_bills_stmt = $conn->prepare("
    SELECT id, month, year, previous_reading, present_reading, 
           noofunits, current_month_bill, total_amount, 
           duepayment, status, bill_datetime 
    FROM bill 
    WHERE scno = ? 
    ORDER BY year DESC, month DESC
");
$all_bills_stmt->bind_param("s", $scno);
$all_bills_stmt->execute();
$all_bills_result = $all_bills_stmt->get_result();
$bills = [];
while ($row = $all_bills_result->fetch_assoc()) {
    $bills[] = $row;
}
$all_bills_stmt->close();

// Calculate totals
$total_consumption = 0;
$total_paid = 0;
$total_due = 0;
$total_generated = 0;

foreach ($bills as $bill) {
    $total_consumption += $bill['noofunits'];
    $total_generated += $bill['total_amount'];
    if ($bill['status'] == 'Paid') {
        $total_paid += $bill['duepayment'];
    } else {
        $total_due += $bill['duepayment'];
    }
}

$conn->close();

// Month names
$months = ["", "January", "February", "March", "April", "May", "June", 
          "July", "August", "September", "October", "November", "December"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electricity Bill - TSSPDCL</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', Courier, monospace;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .logout-container {
            max-width: 900px;
            margin: 0 auto 10px;
            text-align: right;
        }
        
        .logout-btn {
            background: #d32f2f;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-family: Arial, sans-serif;
        }
        
        .logout-btn:hover {
            background: #b71c1c;
        }
        
        .bill-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border: 2px solid black;
            padding: 0;
        }
        
        /* Header Section */
        .bill-header {
            border-bottom: 2px solid black;
            padding: 15px;
            background: white;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            background: #1976d2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
        }
        
        .company-info h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 3px;
            font-family: Arial, sans-serif;
        }
        
        .company-info p {
            font-size: 11px;
            margin: 2px 0;
        }
        
        .bill-type {
            text-align: right;
            font-size: 12px;
        }
        
        .bill-type h2 {
            font-size: 16px;
            margin-bottom: 5px;
            font-family: Arial, sans-serif;
        }
        
        /* Consumer Details */
        .section {
            border-bottom: 1px solid black;
            padding: 10px 15px;
        }
        
        .section-title {
            background: black;
            color: white;
            padding: 5px 10px;
            margin: -10px -15px 10px -15px;
            font-size: 12px;
            font-weight: bold;
            font-family: Arial, sans-serif;
        }
        
        .detail-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            padding: 3px 0;
            font-size: 12px;
            border-bottom: 1px dotted #ccc;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: bold;
        }
        
        .detail-value {
            padding-left: 10px;
        }
        
        /* Meter Reading Table */
        .reading-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 11px;
        }
        
        .reading-table th {
            background: black;
            color: white;
            padding: 8px 5px;
            text-align: center;
            font-weight: bold;
            border: 1px solid black;
        }
        
        .reading-table td {
            padding: 8px 5px;
            text-align: center;
            border: 1px solid black;
        }
        
        /* Charges Table */
        .charges-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 11px;
        }
        
        .charges-table th {
            background: black;
            color: white;
            padding: 6px 5px;
            text-align: left;
            font-weight: bold;
            border: 1px solid black;
        }
        
        .charges-table td {
            padding: 6px 5px;
            border: 1px solid black;
        }
        
        .charges-table .amount-col {
            text-align: right;
        }
        
        .total-row {
            background: #f0f0f0;
            font-weight: bold;
        }
        
        /* Amount Due Box */
        .amount-due-box {
            background: black;
            color: white;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
        }
        
        .amount-due-box .label {
            font-size: 14px;
            margin-bottom: 5px;
            font-family: Arial, sans-serif;
        }
        
        .amount-due-box .amount {
            font-size: 32px;
            font-weight: bold;
            font-family: Arial, sans-serif;
        }
        
        /* Payment Info */
        .payment-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 15px 0;
            font-size: 11px;
        }
        
        .qr-section {
            text-align: center;
        }
        
        .qr-code {
            width: 120px;
            height: 120px;
            border: 2px solid black;
            margin: 10px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
        }
        
        /* Footer */
        .bill-footer {
            padding: 15px;
            border-top: 2px solid black;
            text-align: center;
            font-size: 10px;
            background: #f9f9f9;
        }
        
        .bill-footer p {
            margin: 3px 0;
        }
        
        .status-paid {
            background: #4caf50;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
        }
        
        .status-unpaid {
            background: #ff9800;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
        }
        
        .success-message {
            background: #4CAF50;
            color: white;
            padding: 15px;
            text-align: center;
            max-width: 900px;
            margin: 0 auto 10px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .logout-container {
                display: none;
            }
            .success-message {
                display: none;
            }
            .bill-container {
                border: 2px solid black;
            }
        }
        
        @media (max-width: 768px) {
            .detail-row {
                grid-template-columns: 1fr;
                gap: 5px;
            }
            .payment-info {
                grid-template-columns: 1fr;
            }
            .reading-table, .charges-table, .history-table {
                font-size: 9px;
            }
        }
    </style>
</head>
<body>
    <?php
    if (isset($_SESSION['payment_success'])) {
        echo '<div class="success-message">';
        echo '✅ Payment successful! All bills marked as paid.';
        echo '</div>';
        unset($_SESSION['payment_success']);
    }
    ?>
    
    

    <div class="bill-container">
        <div class="bill-header">
            <div class="header-top">
                <div class="logo-section">
                    <div class="logo">TS</div>
                    <div class="company-info">
                        <h1>SOUTHERN POWER DISTRIBUTION COMPANY OF TELANGANA LIMITED</h1>
                        <p>TSSPDCL :: Corporate Office: Vidyut Soudha, Hyderabad</p>
                        <p>Phone: 1912 | Website: www.tssouthernpower.com</p>
                    </div>
                </div>
                <div class="bill-type">
                    <h2>ELECTRICITY BILL</h2>
                    <?php if ($current_bill): ?>
                    <p><strong>Bill No:</strong> <?php echo $current_bill['id']; ?></p>
                    <p><strong>Bill Date:</strong> <?php echo date('d-M-Y', strtotime($current_bill['bill_datetime'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($current_bill): ?>
        <div class="section">
            <div class="section-title">CONSUMER DETAILS</div>
            <div class="detail-row">
                <div class="detail-label">Service Connection No (SCNO)</div>
                <div class="detail-value">: <?php echo htmlspecialchars($customer['scno'] ?? 'N/A'); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Unique Service Connection No</div>
                <div class="detail-value">: <?php echo htmlspecialchars($customer['uscno'] ?? 'N/A'); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Consumer Name</div>
                <div class="detail-value">: <?php echo htmlspecialchars($customer['name'] ?? 'N/A'); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Address</div>
                <div class="detail-value">: <?php echo htmlspecialchars($customer['address'] ?? 'N/A'); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Area / Circle</div>
                <div class="detail-value">: <?php echo htmlspecialchars($customer['area'] ?? 'N/A'); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Consumer Category</div>
                <div class="detail-value">: <?php echo htmlspecialchars($customer['grp'] ?? 'N/A'); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Billing Period</div>
                <div class="detail-value">: <?php echo $months[$current_bill['month']] . ' ' . $current_bill['year']; ?></div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">METER READING DETAILS</div>
            <table class="reading-table">
                <thead>
                    <tr>
                        <th>METER NO</th>
                        <th>PREVIOUS READING</th>
                        <th>PRESENT READING</th>
                        <th>CONSUMPTION (UNITS)</th>
                        <th>RECORDING DATE</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($customer['scno']); ?></td>
                        <td><?php echo number_format($current_bill['previous_reading'], 2); ?></td>
                        <td><?php echo number_format($current_bill['present_reading'], 2); ?></td>
                        <td><strong><?php echo number_format($current_bill['noofunits'], 2); ?></strong></td>
                        <td><?php echo date('d-M-Y', strtotime($current_bill['bill_datetime'])); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <div class="section-title">CHARGES BREAKDOWN</div>
            <table class="charges-table">
                <thead>
                    <tr>
                        <th>PARTICULARS</th>
                        <th>UNITS/RATE</th>
                        <th class="amount-col">AMOUNT (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Energy Charges</td>
                        <td><?php echo number_format($current_bill['noofunits'], 2); ?> Units</td>
                        <td class="amount-col"><?php echo number_format($current_bill['current_month_bill'], 2); ?></td>
                    </tr>
                    <tr>
                        <td>Fixed Charges</td>
                        <td>-</td>
                        <td class="amount-col">0.00</td>
                    </tr>
                    <tr>
                        <td>Customer Service Charges</td>
                        <td>-</td>
                        <td class="amount-col">0.00</td>
                    </tr>
                    <tr>
                        <td>Electricity Duty</td>
                        <td>-</td>
                        <td class="amount-col">0.00</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="2"><strong>CURRENT MONTH TOTAL</strong></td>
                        <td class="amount-col"><strong><?php echo number_format($current_bill['current_month_bill'], 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td colspan="2">Previous Arrears/Adjustments</td>
                        <td class="amount-col"><?php echo number_format($current_bill['total_amount'] - $current_bill['current_month_bill'], 2); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="2"><strong>TOTAL AMOUNT</strong></td>
                        <td class="amount-col"><strong><?php echo number_format($current_bill['total_amount'], 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="amount-due-box">
            <div class="label">TOTAL AMOUNT PAYABLE</div>
            <div class="amount">₹ <?php echo number_format($current_bill['duepayment'], 2); ?></div>
            <div style="margin-top: 10px; font-size: 12px;">
                Payment Status: <strong><?php echo $current_bill['status']; ?></strong>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">PAYMENT INFORMATION</div>
            <div class="payment-info">
                <div>
                    <p><strong>Payment Due Date:</strong> <?php echo date('d-M-Y', strtotime($current_bill['bill_datetime'] . ' +15 days')); ?></p>
                    <p><strong>Payment Methods:</strong></p>
                    <ul style="margin-left: 20px; margin-top: 5px;">
                        <li>Online Payment (Website/Mobile App)</li>
                        <li>TSSPDCL Customer Care Centers</li>
                        <li>Authorized Collection Centers</li>
                        <li>UPI / Net Banking / Credit/Debit Cards</li>
                    </ul>
                    <p style="margin-top: 10px;"><strong>UPI ID:</strong> tsspdcl@upi</p>
                </div>
                <div class="qr-section">
                    <div class="qr-code">
                        <div style="font-size: 10px; text-align: center;">QR CODE<br>FOR PAYMENT</div>
                    </div>
                    <p>Scan to Pay</p>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <div class="section">
            <div style="text-align: center; padding: 40px;">
                <h2>No Bill Generated Yet</h2>
                <p>Your electricity bill has not been generated yet.</p>
                <p>Please contact customer care for more information.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="bill-footer">
            <p><strong>IMPORTANT NOTES:</strong></p>
            <p>• Please pay your bill by the due date to avoid disconnection and late payment charges</p>
            <p>• For any queries or complaints, contact our 24x7 helpline: 1912</p>
            <p>• Please quote your Service Connection Number in all correspondence</p>
            <p style="margin-top: 10px;"><strong>This is a computer-generated bill and does not require signature</strong></p>
        </div>
        
        <?php if ($current_bill && $current_bill['status'] == 'Unpaid'): ?>
        <div style="padding: 20px; text-align: center; background: #f9f9f9; border-top: 2px solid black;">
            <form action="pay.php" method="post">
                <button type="submit" style="background: #4caf50; color: white; border: none; padding: 15px 40px; font-size: 16px; font-weight: bold; border-radius: 5px; cursor: pointer; font-family: Arial, sans-serif;">
                    PAY NOW - ₹<?php echo number_format($current_bill['duepayment'], 2); ?>
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>