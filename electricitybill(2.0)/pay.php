<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['customer_logged_in']) || !$_SESSION['customer_logged_in']) 
{
    header("Location: customer_login.php");
    exit();
}

$scno = $_SESSION['customer_scno'];
$paid_with_fine = false;

if ($conn && !$conn->connect_error) 
{
    $current_bill_sql = "SELECT * FROM bill WHERE scno = ? AND status = 'Unpaid' ORDER BY year DESC, month DESC, bill_datetime DESC LIMIT 1";
    if ($current_stmt = $conn->prepare($current_bill_sql)) 
    {
        $current_stmt->bind_param("s", $scno);
        $current_stmt->execute();
        $current_result = $current_stmt->get_result();
        $current_bill = $current_result->fetch_assoc();
        $current_stmt->close();
        
        if ($current_bill) 
        {
            if ($current_bill['late_fine'] > 0) 
            {
                $paid_with_fine = true;
            }
            
            $update_sql = "UPDATE bill SET status = 'Paid', payment_date = NOW() WHERE id = ?";
            if ($update_stmt = $conn->prepare($update_sql)) 
            {
                $update_stmt->bind_param("i", $current_bill['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                $_SESSION['last_payment_details'] = [
                    'amount' => $current_bill['duepayment'] + $current_bill['late_fine'],
                    'late_fine' => $current_bill['late_fine'],
                    'paid_with_fine' => $paid_with_fine,
                    'bill_id' => $current_bill['id'],
                    'bill_month' => $current_bill['month'],
                    'bill_year' => $current_bill['year']
                ];
            }
        } 
        else 
        {
            $sql = "UPDATE bill SET status = 'Paid', payment_date = NOW() WHERE scno = ? AND status = 'Unpaid'";
            if ($stmt = $conn->prepare($sql)) 
            {
                $stmt->bind_param("s", $scno);
                $stmt->execute();
                $stmt->close();
                
                $check_fine_sql = "SELECT SUM(late_fine) as total_fine FROM bill WHERE scno = ? AND status = 'Paid' AND DATE(payment_date) = CURDATE()";
                if ($check_stmt = $conn->prepare($check_fine_sql)) 
                {
                    $check_stmt->bind_param("s", $scno);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    $fine_data = $check_result->fetch_assoc();
                    $check_stmt->close();
                    $paid_with_fine = ($fine_data['total_fine'] > 0);
                }
            }
        }
    }
}

if ($paid_with_fine) 
{
    $_SESSION['payment_success'] = "with_fine";
} 
else 
{
    $_SESSION['payment_success'] = "without_fine";
}

header("Location: customer_dashboard.php");
exit();
?>