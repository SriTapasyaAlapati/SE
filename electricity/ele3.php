<?php
session_start();

if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true)
{
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$user = "root";
$password = "";
$dbname = "user";
$message = '';
$message_type = '';

$connection = new mysqli($servername, $user, $password, $dbname);
if($connection->connect_error)
{
    exit("Couldn't connect: " . $connection->connect_error);
}

$user_id = $_SESSION['id'];
$sql = "SELECT id, noofunits,duepayment FROM bill WHERE id = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();
$due=$user_data['duepayment'];
$current_units = $user_data['noofunits'];
$total_cost = 0;

if($current_units<=50)
{
    $total_cost=$current_units*1.5;
}
elseif($current_units<=100)
{
    $total_cost =(50*1.5)+(($current_units - 50)*2);
}
else
{
    $total_cost =(50*1.5)+(50*2)+(($current_units-100)*2.5);
}
    $total_cost=$total_cost+$due+25;

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }
        .user-info {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            text-align: center;
        }
        .user-info p {
            margin: 5px 0;
            font-size: 16px;
        }
        .units-display {
            font-size: 32px;
            font-weight: bold;
            color: #4CAF50;
            margin: 10px 0;
        }
        .message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-logout {
            background: #f44336;
            margin-top: 20px;
        }
        .btn-logout:hover {
            background: #da190b;
        }
        .operation-section {
            border-top: 2px solid #e0e0e0;
            padding-top: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Dashboard</h2>
        
        <div class="user-info">
            <p><strong>Welcome</strong></p>
            <p>User ID: <?php echo htmlspecialchars($user_id); ?></p>
            <div class="units-display">
                Total Number of  Units: <?php echo htmlspecialchars($user_data['noofunits']); ?>
            </div>
            <div class="units-display" style="color: #2196F3;">
                Due payment: ₹<?php echo htmlspecialchars($user_data['duepayment']); ?>
            </div>
            <div class="units-display" style="color: #FF9800; font-size: 24px;">
                Total Cost: ₹<?php echo number_format($total_cost, 2); ?>
            </div>
            <div style="margin-top: 10px; font-size: 14px; color: #666;">
                <p><strong>Pricing:</strong></p>
                <p>First 50 units: ₹1.5/unit</p>
                <p>Next 50 units (51-100): ₹2/unit</p>
                <p>Above 100 units: ₹2.5/unit</p>
            </div>
        </div>

        <?php if(!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>