<?php
date_default_timezone_set('Asia/Kolkata');
session_start();
$servername = "localhost";
$user = "root";
$password = "";
$dbname = "user";
$error_message = '';
$success_message = '';
$scno_list = [];

$conn = new mysqli($servername, $user, $password, $dbname);
if ($conn->connect_error) {
    exit("Connection failed: " . $conn->connect_error);
}

$users_stmt = $conn->prepare("SELECT scno, name, grp FROM user ORDER BY scno");
$users_stmt->execute();
$users_result = $users_stmt->get_result();
while ($row = $users_result->fetch_assoc()) {
    $scno_list[] = $row;
}
$users_stmt->close();

$current_month = date("n");
$current_year = date("Y");
$current_datetime = date("Y-m-d H:i:s");
$LATE_PAYMENT_FINE_PERCENT = 10;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $scno = trim($_POST['scno']);
    $present_reading = (float)$_POST['present_reading'];

    if (empty($scno)) {
        $error_message = "Please select SCNO";
    } elseif (!preg_match("/^\d{9}$/", $scno)) {
        $error_message = "SCNO must be exactly 9 digits";
    } else {
        $check_stmt = $conn->prepare("SELECT scno, name, grp FROM user WHERE scno=?");
        $check_stmt->bind_param("s", $scno);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows == 0) {
            $error_message = "This SCNO doesn't exist";
        } else {
            $user_row = $result->fetch_assoc();
            $grp = $user_row['grp'];
            $user_name = $user_row['name'];
            $check_bill_stmt = $conn->prepare("SELECT id FROM bill WHERE scno=? AND month=? AND year=?");
            $check_bill_stmt->bind_param("sii", $scno, $current_month, $current_year);
            $check_bill_stmt->execute();
            $bill_result = $check_bill_stmt->get_result();

            if ($bill_result->num_rows > 0) {
                $error_message = "A bill already exists for SCNO $scno ($user_name) for " . date("F Y") . ". Cannot add duplicate bill.";
            } else {
                $prev_reading = 0;
                $prev_stmt = $conn->prepare("SELECT present_reading FROM bill WHERE scno=? ORDER BY year DESC, month DESC, bill_datetime DESC LIMIT 1");
                $prev_stmt->bind_param("s", $scno);
                $prev_stmt->execute();
                $prev_result = $prev_stmt->get_result();
                if ($prev_result->num_rows > 0) {
                    $row = $prev_result->fetch_assoc();
                    $prev_reading = (float)$row['present_reading'];
                }
                $prev_stmt->close();

                // Check for unpaid bills and calculate total due + fine
                $previous_due = 0;
                $late_fine = 0;

                // Get the date of the last payment (if any)
                $last_payment_stmt = $conn->prepare("SELECT MAX(bill_datetime) as last_bill_date FROM bill WHERE scno=? AND status='Paid'");
                $last_payment_stmt->bind_param("s", $scno);
                $last_payment_stmt->execute();
                $last_payment_result = $last_payment_stmt->get_result();
                $last_payment_date = null;
                if ($last_payment_result->num_rows > 0) {
                    $row = $last_payment_result->fetch_assoc();
                    $last_payment_date = $row['last_bill_date'];
                }
                $last_payment_stmt->close();

                // Only consider unpaid bills created AFTER the last payment
                if ($last_payment_date) {
                    $unpaid_stmt = $conn->prepare("SELECT duepayment, month, year FROM bill WHERE scno=? AND status='Unpaid' AND bill_datetime > ? ORDER BY year ASC, month ASC");
                    $unpaid_stmt->bind_param("ss", $scno, $last_payment_date);
                } else {
                    // If no payments ever made, consider all unpaid bills
                    $unpaid_stmt = $conn->prepare("SELECT duepayment, month, year FROM bill WHERE scno=? AND status='Unpaid' ORDER BY year ASC, month ASC");
                    $unpaid_stmt->bind_param("s", $scno);
                }

                $unpaid_stmt->execute();
                $unpaid_result = $unpaid_stmt->get_result();
                
                $unpaid_bills_count = 0;
                while ($unpaid_row = $unpaid_result->fetch_assoc()) {
                    $previous_due += (float)$unpaid_row['duepayment'];
                    $unpaid_bills_count++;
                }
                $unpaid_stmt->close();
                
                if ($previous_due > 0) {
                    $late_fine = round(($previous_due * $LATE_PAYMENT_FINE_PERCENT) / 100, 2);
                }

                $units = round($present_reading - $prev_reading, 2);
                if ($present_reading < $prev_reading) {
                    $error_message = "Present reading (" . number_format($present_reading, 2) . ") cannot be less than previous reading (" . number_format($prev_reading, 2) . ")";
                } else {
                    $total_cost = 0;
                    
                    if ($grp == 'D') {
                        // Domestic rates
                        if ($units <= 50) {
                            $total_cost = $units * 1.5;
                        } elseif ($units <= 100) {
                            $total_cost = (50 * 1.5) + (($units - 50) * 2);
                        } else {
                            $total_cost = (50 * 1.5) + (50 * 2) + (($units - 100) * 2.5);
                        }
                    } elseif ($grp == 'C') {
                        // Commercial rates
                        if ($units <= 50) {
                            $total_cost = $units * 2;
                        } elseif ($units <= 100) {
                            $total_cost = (50 * 2) + (($units - 50) * 3);
                        } else {
                            $total_cost = (50 * 2) + (50 * 3) + (($units - 100) * 4);
                        }
                    } elseif ($grp == 'I') {
                        // Industrial rates
                        if ($units <= 50) {
                            $total_cost = $units * 3;
                        } elseif ($units <= 100) {
                            $total_cost = (50 * 3) + (($units - 50) * 4);
                        } else {
                            $total_cost = (50 * 3) + (50 * 4) + (($units - 100) * 5);
                        }
                    }

                    // Current month's bill ONLY (includes fixed ₹100 charge)
                    $current_month_bill = round($total_cost, 2);
                    if ($current_month_bill > 0) {
                        $current_month_bill += 100; // Additional fixed charge
                    }

                    // Total Amount = Current Month + Previous Due + Late Fine
                    $total_amount = round($current_month_bill + $previous_due + $late_fine, 2);
                    
                    // duepayment = same as total_amount
                    $duepayment = $total_amount;

                    $status = 'Unpaid'; // Default status

                    // INSERT new bill with all three amounts
                    $insert_stmt = $conn->prepare("INSERT INTO bill (scno, month, year, previous_reading, present_reading, noofunits, grp, current_month_bill, total_amount, duepayment, status, bill_datetime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert_stmt->bind_param("siidddsdddss", $scno, $current_month, $current_year, $prev_reading, $present_reading, $units, $grp, $current_month_bill, $total_amount, $duepayment, $status, $current_datetime);

                    if ($insert_stmt->execute()) {
                        $success_message = "Bill added successfully for " . date("F Y") . "!";
                        if ($unpaid_bills_count > 0) {
                            $success_message .= "<br>Included $unpaid_bills_count unpaid bill(s) from previous months totaling ₹" . number_format($previous_due, 2);
                            if ($late_fine > 0) {
                                $success_message .= " + ₹" . number_format($late_fine, 2) . " late fine (" . $LATE_PAYMENT_FINE_PERCENT . "%)";
                            }
                        }
                        $success_message .= "<br><a href='main.html'>Go to Dashboard</a>";
                    } else {
                        $error_message = "Insert failed: " . $insert_stmt->error;
                    }
                    $insert_stmt->close();
                }
            }
            $check_bill_stmt->close();
        }
        $check_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add BILL</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .signup-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #c3e6cb;
        }
        .success-message a {
            color: #155724;
            font-weight: bold;
            text-decoration: underline;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-group span {
            display: inline-block;
            padding: 8px;
            background-color: #f0f0f0;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        select, input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        .rate-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            font-size: 0.9em;
            border-left: 4px solid #007bff;
        }
        .rate-info h4 {
            margin-top: 0;
            color: #333;
        }
        .rate-info ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        .rate-info strong {
            color: #0056b3;
        }
        .fine-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .fine-notice strong {
            color: #856404;
        }
        .payment-logic {
            background-color: #e7f3ff;
            border-left: 4px solid #17a2b8;
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .payment-logic strong {
            color: #0c5460;
        }
        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        .submit-btn:hover {
            background-color: #0056b3;
        }
        a.back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #007bff;
            text-decoration: none;
        }
        a.back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <h2>Add BILL</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <div class="payment-logic">
            <strong>💰 Payment Logic:</strong><br>
            Only unpaid bills created <strong>after the last payment</strong> will be added to the current bill.
            Once a bill is marked 'Paid', it won't be included in future bills.
        </div>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="scno">SCNO:</label>
                <select id="scno" name="scno" required>
                    <option value="">Select SCNO</option>
                    <?php foreach ($scno_list as $user): ?>
                        <option value="<?php echo $user['scno']; ?>" <?php echo (isset($_POST['scno']) && $_POST['scno'] == $user['scno']) ? 'selected' : ''; ?>>
                            <?php echo $user['scno'] . " - " . $user['name'] . " (Group: " . $user['grp'] . ")"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="present_reading">Present Reading:</label>
                <input type="number" id="present_reading" name="present_reading" min="0" step="0.01" required 
                       value="<?php echo isset($_POST['present_reading']) ? $_POST['present_reading'] : ''; ?>"
                       placeholder="Enter current meter reading">
            </div>
            
            <div class="form-group">
                <label>Bill Month / Year:</label>
                <span><?php echo date("F Y"); ?> (current)</span>
            </div>

            <div class="form-group">
                <label>Bill Date & Time:</label>
                <span><?php echo date("d-m-Y H:i:s"); ?></span>
            </div>

            <div class="fine-notice">
                <strong>⚠️ Important Notice:</strong><br>
                If there are unpaid previous bills <strong>created after the last payment</strong>, they will be added to the current bill along with a <?php echo $LATE_PAYMENT_FINE_PERCENT; ?>% late payment fine.
            </div>

            <div class="rate-info">
                <h4>Billing Rates Information:</h4>
                <strong>Group D (Domestic):</strong>
                <ul>
                    <li>0-50 units: ₹1.5/unit</li>
                    <li>51-100 units: ₹2/unit</li>
                    <li>Above 100 units: ₹2.5/unit</li>
                </ul>
                <strong>Group C (Commercial):</strong>
                <ul>
                    <li>0-50 units: ₹2/unit</li>
                    <li>51-100 units: ₹3/unit</li>
                    <li>Above 100 units: ₹4/unit</li>
                </ul>
                <strong>Group I (Industrial):</strong>
                <ul>
                    <li>0-50 units: ₹3/unit</li>
                    <li>51-100 units: ₹4/unit</li>
                    <li>Above 100 units: ₹5/unit</li>
                </ul>
                <em>Note: Additional charge of ₹100 applies to all bills + <?php echo $LATE_PAYMENT_FINE_PERCENT; ?>% fine on unpaid dues</em>
            </div>

            <button type="submit" class="submit-btn">Add Bill</button>
            <a href="main.html" class="back-link">Back to Dashboard</a>
        </form>
    </div>
</body>
</html>