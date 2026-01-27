<?php
session_start();
require_once 'db_connect.php';

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    $scno = $conn->real_escape_string($_POST['scno']);
    $name = trim($_POST['name']);
    $uscno = $conn->real_escape_string($_POST['uscno']);
    $address = $conn->real_escape_string($_POST['address']);
    $area = $conn->real_escape_string($_POST['area']);
    $phoneno = $conn->real_escape_string($_POST['no']);
    $grp = strtoupper($conn->real_escape_string($_POST['grp']));
    
    if (preg_match('/[0-9]/', $name)) 
    {
        $error_message = "Name cannot contain numbers!";
    }
    elseif (!preg_match('/^[6-9][0-9]{9}$/', $phoneno)) 
    {
        $error_message = "Phone number must be exactly 10 digits and start with 6, 7, 8, or 9!";
    }
    else 
    {
        $name = ucwords(strtolower($name));
        $name = $conn->real_escape_string($name);
        
        $insert_stmt = $conn->prepare("INSERT INTO user (scno, name, uscno, address, area, phno, grp) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($insert_stmt) 
        {
            $insert_stmt->bind_param("sssssss", $scno, $name, $uscno, $address, $area, $phoneno, $grp);
            
            if ($insert_stmt->execute()) 
            {
                $success_message = "Customer registration successful!";
            } 
            else 
            {
                if ($conn->errno === 1062) 
                {
                    if (strpos($insert_stmt->error, 'scno') !== false) 
                    {
                        $error_message = "SC No already exists! Please generate a new one.";
                    } 
                    else 
                    {
                        $error_message = "USC No already exists! Please generate a new one.";
                    }
                } 
                else 
                {
                    $error_message = "Registration failed: " . $insert_stmt->error;
                }
            }
            $insert_stmt->close();
        } 
        else 
        {
            $error_message = "Database error: " . $conn->error;
        }
    }
}

$page_title = "New Customer Registration";
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

<div class="signup-container">
    <h2>New Customer Registration</h2>
    
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group">
            <label for="scno">SC No (PRIMARY KEY):</label>
            <div class="input-row">
                <input type="text" id="scno" name="scno" class="primary-key" readonly required maxlength="9">
                <button type="button" class="generate-btn" onclick="generateUniqueSCNo()">New SC</button>
            </div>
        </div>
        
        <div class="form-group">
            <label for="name">Customer Name:</label>
            <input type="text" id="name" name="name" required pattern="[A-Za-z\s]+" title="Name cannot contain numbers">
        </div>
        
        <div class="form-group">
            <label for="uscno">USC No (Unique):</label>
            <div class="input-row">
                <input type="text" id="uscno" name="uscno" readonly required maxlength="9">
                <button type="button" class="generate-btn" onclick="generateUniqueUSCNo()">New USC</button>
            </div>
        </div>
        <div class="form-group">
            <label for="no">Phone Number:</label>
            <input type="text" id="no" name="no" required pattern="[6-9][0-9]{9}" maxlength="10" title="Phone number must be 10 digits starting with 6, 7, 8, or 9">
        </div>
        <div class="form-group">
            <label for="address">Address:</label>
            <textarea id="address" name="address" required></textarea>
        </div>
        
        <div class="form-group">
            <label for="area">Area:</label>
            <input type="text" id="area" name="area" required>
        </div>
        
        <div class="form-group">
            <p style="font-size:0.8em; margin-bottom:5px; color:#666;">D-Domestic, C-Commercial, I-Industrial</p>
            <label for="grp">Customer Group:</label>
            <select id="grp" name="grp" required>
                <option value="">Select Group</option>
                <option value="D">D</option>
                <option value="C">C</option>
                <option value="I">I</option>
            </select>
        </div>
        
        <button type="submit" class="submit-btn">Add Customer</button>
        <a href="ele1.php" class="back-link">Refresh Page</a>
        <a href="admin.html" class="back-link">Back to Admin</a>
    </form>
</div>

<script>
    function generateUnique9Digit() 
    {
        const timestamp = Date.now().toString().slice(-7);
        const random = Math.floor(Math.random() * 90000).toString().padStart(2, '0');
        return (parseInt(timestamp + random) % 1000000000).toString().padStart(9, '0');
    }
    
    function generateUniqueSCNo() 
    {
        document.getElementById('scno').value = generateUnique9Digit();
    }
    
    function generateUniqueUSCNo() 
    {
        document.getElementById('uscno').value = generateUnique9Digit();
    }
    
    window.onload = function() 
    {
        generateUniqueSCNo();
        generateUniqueUSCNo();
    };
</script>

</body>
</html>