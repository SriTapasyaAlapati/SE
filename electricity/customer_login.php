<?php
session_start();

$error_message = '';
$success_message = '';

$global_password = "password";

$servername = "localhost";
$user = "root";
$password = "";
$dbname = "user";

if (!isset($_SESSION['customer_logged_in'])) {
    $_SESSION['customer_logged_in'] = false;
}


$conn = new mysqli($servername, $user, $password, $dbname);
$scno_list = [];
if ($conn->connect_error) {
    $error_message = "Database connection failed";
} else {
    $users_stmt = $conn->prepare("SELECT scno, name FROM user ORDER BY scno");
    $users_stmt->execute();
    $users_result = $users_stmt->get_result();
    while ($row = $users_result->fetch_assoc()) {
        $scno_list[] = $row;
    }
    $users_stmt->close();
    $conn->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $scno = trim($_POST['scno']);
    $input_password = trim($_POST['password']);
    
    if (empty($scno)) {
        $error_message = "Please select SCNO";
    } elseif ($input_password !== $global_password) {
        $error_message = "Invalid password. Use 'password'";
    } else {
        $user_found = false;
        $user_name = "";
        foreach ($scno_list as $user) {
            if ($user['scno'] == $scno) {
                $user_found = true;
                $user_name = $user['name'];
                break;
            }
        }
        
        if ($user_found) {
            $_SESSION['customer_logged_in'] = true;
            $_SESSION['customer_scno'] = $scno;
            $_SESSION['customer_name'] = $user_name;
            header("Location: customer_dashboard.php");
            exit();
        } else {
            $error_message = "Selected SCNO not found";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .login-container {
            width: 400px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
        
        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        select, input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        select:focus, input:focus {
            outline: none;
            border-color: #2196f3;
            box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
        }
        
        .login-btn {
            width: 100%;
            padding: 12px;
            background: #2196f3;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .login-btn:hover {
            background: #4CAF50;
        }
        
        .password-note {
            background: #fff3e0;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
            color: #f57c00;
            text-align: center;
            border: 1px dashed #ffb74d;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Customer Login</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="scno">Select SCNO:</label>
                <select id="scno" name="scno" required>
                    <option value="">-- Select SCNO --</option>
                    <?php foreach ($scno_list as $user): ?>
                        <option value="<?php echo $user['scno']; ?>" 
                            <?php echo (isset($_POST['scno']) && $_POST['scno'] == $user['scno']) ? 'selected' : ''; ?>>
                            <?php echo $user['scno'] . " - " . $user['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" 
                       value="password" 
                       placeholder="Enter password"
                       required>
            </div>
            
            <button type="submit" class="login-btn">Login</button>
            
            <div class="password-note">
                <strong>Note:</strong> Password is same for all users: <strong>password</strong>
            </div>
        </form>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('scno').focus();
        });
        
        document.getElementById('password').addEventListener('click', function() {
            this.type = 'text';
            setTimeout(() => {
                this.type = 'password';
            }, 1000);
        });
    </script>
</body>
</html>