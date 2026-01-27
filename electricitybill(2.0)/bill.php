<?php
date_default_timezone_set('Asia/Kolkata');
session_start();
require_once 'db_connect.php';

$error_message = '';
$success_message = '';
$scno_list = [];

if ($conn && !$conn->connect_error) 
{
    if ($users_stmt = $conn->prepare("SELECT scno, name, grp FROM user ORDER BY scno")) 
    {
        $users_stmt->execute();
        $users_result = $users_stmt->get_result();
        while ($row = $users_result->fetch_assoc()) 
        {
            $scno_list[] = $row;
        }
        $users_stmt->close();
    }
}

$current_month = date("n");
$current_year = date("Y");
$current_datetime = date("Y-m-d H:i:s");

if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    $scno = trim($_POST['scno']);
    $present_reading = (float)$_POST['present_reading'];

    if (empty($scno)) 
    {
        $error_message = "Please select SCNO";
    } 
    elseif (!preg_match("/^\d{9}$/", $scno)) 
    {
        $error_message = "SCNO must be exactly 9 digits";
    } 
    else 
    {
        $check_stmt = $conn->prepare("SELECT scno, name, grp FROM user WHERE scno=?");
        $check_stmt->bind_param("s", $scno);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows == 0) 
        {
            $error_message = "This SCNO doesn't exist";
        } 
        else 
        {
            $user_row = $result->fetch_assoc();
            $grp = $user_row['grp'];
            $user_name = $user_row['name'];
            
            $check_bill_stmt = $conn->prepare("SELECT id FROM bill WHERE scno=? AND month=? AND year=?");
            $check_bill_stmt->bind_param("sii", $scno, $current_month, $current_year);
            $check_bill_stmt->execute();
            $bill_result = $check_bill_stmt->get_result();

            if ($bill_result->num_rows > 0) 
            {
                $error_message = "A bill already exists for SCNO $scno ($user_name) for " . date("F Y");
            } 
            else 
            {
                $prev_reading = 0;
                $prev_stmt = $conn->prepare("SELECT present_reading FROM bill WHERE scno=? ORDER BY year DESC, month DESC, bill_datetime DESC LIMIT 1");
                $prev_stmt->bind_param("s", $scno);
                $prev_stmt->execute();
                $prev_result = $prev_stmt->get_result();
                if ($prev_result->num_rows > 0) 
                {
                    $row = $prev_result->fetch_assoc();
                    $prev_reading = (float)$row['present_reading'];
                }
                $prev_stmt->close();

                $previous_due = 0;
                $late_fine = 0;

                $last_payment_stmt = $conn->prepare("SELECT MAX(bill_datetime) as last_bill_date FROM bill WHERE scno=? AND status='Paid'");
                $last_payment_stmt->bind_param("s", $scno);
                $last_payment_stmt->execute();
                $last_payment_result = $last_payment_stmt->get_result();
                $last_payment_date = null;
                if ($last_payment_result->num_rows > 0) 
                {
                    $row = $last_payment_result->fetch_assoc();
                    $last_payment_date = $row['last_bill_date'];
                }
                $last_payment_stmt->close();

                if ($last_payment_date) 
                {
                    $unpaid_stmt = $conn->prepare("SELECT duepayment FROM bill WHERE scno=? AND status='Unpaid' AND bill_datetime > ?");
                    $unpaid_stmt->bind_param("ss", $scno, $last_payment_date);
                } 
                else 
                {
                    $unpaid_stmt = $conn->prepare("SELECT duepayment FROM bill WHERE scno=? AND status='Unpaid'");
                    $unpaid_stmt->bind_param("s", $scno);
                }

                $unpaid_stmt->execute();
                $unpaid_result = $unpaid_stmt->get_result();
                
                $unpaid_bills_count = 0;
                while ($unpaid_row = $unpaid_result->fetch_assoc()) 
                {
                    $previous_due += (float)$unpaid_row['duepayment'];
                    $unpaid_bills_count++;
                }
                $unpaid_stmt->close();
                
                if ($previous_due > 0) 
                {
                    $late_fine = 150;
                }

                $units = round($present_reading - $prev_reading, 2);
                
                if ($present_reading <= $prev_reading) 
                {
                     $error_message = "Present reading (" . number_format($present_reading, 2) . ") must be greater than previous (" . number_format($prev_reading, 2) . ")";
                } 
                else 
                {
                    $total_cost = 0;
                    
                    if ($grp == 'D') 
                    {
                        if ($units == 0) $total_cost = 25;
                        elseif ($units <= 50) $total_cost = $units * 1.5;
                        elseif ($units <= 100) $total_cost = (50 * 1.5) + (($units - 50) * 2.5);
                        elseif ($units <= 150) $total_cost = (50 * 1.5) + (50 * 2.5) + (($units - 100) * 3.5);
                        else $total_cost = (50 * 1.5) + (50 * 2.5) + (50 * 3.5) + (($units - 150) * 4.5);
                    } 
                    elseif ($grp == 'C') 
                    {
                        if ($units == 0) $total_cost = 25;
                        elseif ($units <= 50) $total_cost = $units * 2.5;
                        elseif ($units <= 100) $total_cost = (50 * 2.5) + (($units - 50) * 3.5);
                        elseif ($units <= 150) $total_cost = (50 * 2.5) + (50 * 3.5) + (($units - 100) * 4.5);
                        else $total_cost = (50 * 2.5) + (50 * 3.5) + (50 * 4.5) + (($units - 150) * 5.5);
                    } 
                    elseif ($grp == 'I') 
                    {
                        if ($units == 0) $total_cost = 25;
                        elseif ($units <= 50) $total_cost = $units * 3.5;
                        elseif ($units <= 100) $total_cost = (50 * 3.5) + (($units - 50) * 4.5);
                        elseif ($units <= 150) $total_cost = (50 * 3.5) + (50 * 4.5) + (($units - 100) * 5.5);   
                        else $total_cost = (50 * 3.5) + (50 * 4.5) + (50 * 5.5) + (($units - 150) * 6.5);
                    }

                    $current_month_bill = round($total_cost, 2);
                    $total_amount = round($current_month_bill + $previous_due + $late_fine, 2);
                    $duepayment = $total_amount;
                    $status = 'Unpaid';

                    $insert_stmt = $conn->prepare("INSERT INTO bill (scno, month, year, previous_reading, present_reading, noofunits, grp, current_month_bill, total_amount, duepayment, status, bill_datetime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert_stmt->bind_param("siidddsdddss", $scno, $current_month, $current_year, $prev_reading, $present_reading, $units, $grp, $current_month_bill, $total_amount, $duepayment, $status, $current_datetime);

                    if ($insert_stmt->execute()) 
                    {
                        $success_message = "Bill added successfully!<br>";
                        $success_message .= "Units: " . number_format($units, 2) . " | Bill: ₹" . number_format($current_month_bill, 2) . "<br>";
                        if ($late_fine > 0) $success_message .= "Late Fine: ₹150<br>";
                        $success_message .= "<strong>Total Due: ₹" . number_format($total_amount, 2) . "</strong>";
                    } 
                    else 
                    {
                        $error_message = "Error: " . $insert_stmt->error;
                    }
                    $insert_stmt->close();
                }
            }
            $check_bill_stmt->close();
        }
        $check_stmt->close();
    }
}

$page_title = "Generate Bill";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | TSSPDCL</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="signup-container" style="max-width: 600px;">
    <h2>Generate Electricity Bill</h2>
    
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group">
            <label for="scno">Select Customer (SCNO):</label>
            <select id="scno" name="scno" required>
                <option value="">Select SCNO</option>
                <?php foreach ($scno_list as $user): ?>
                    <option value="<?php echo $user['scno']; ?>" <?php echo (isset($_POST['scno']) && $_POST['scno'] == $user['scno']) ? 'selected' : ''; ?>>
                        <?php echo $user['scno'] . " - " . $user['name'] . " (" . $user['grp'] . ")"; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="present_reading">Present Meter Reading:</label>
            <input type="number" id="present_reading" name="present_reading" min="0" step="0.01" required 
                   value="<?php echo isset($_POST['present_reading']) ? $_POST['present_reading'] : ''; ?>"
                   placeholder="Enter current reading">
        </div>
        
        <div class="input-row" style="margin-bottom: 20px; font-size: 0.9em; color: #555;">
            <div>Date: <strong><?php echo date("d-M-Y"); ?></strong></div>
            <div style="margin-left:auto;">Billing Period: <strong><?php echo date("F Y"); ?></strong></div>
        </div>

        <button type="submit" class="submit-btn" style="margin-bottom: 10px;">Generate Bill</button>
        <a href="main.html" class="back-link">Back to main</a>
    </form>
</div>

</body>
</html>