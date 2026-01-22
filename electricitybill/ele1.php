<?php
session_start();
$servername="localhost";
$user="root";
$password="";
$dbname="user";
$error_message='';
$success_message='';

if($_SERVER["REQUEST_METHOD"]=="POST") 
{
    $connection=new mysqli($servername,$user,$password,$dbname);
    if($connection->connect_error) 
    {
        exit("Connection failed: ".$connection->connect_error);
    }

    $scno=$_POST['scno'];
    $name=$_POST['name'];
    $uscno=$_POST['uscno'];
    $address=$_POST['address'];
    $area=$_POST['area'];
    $grp=strtoupper($_POST['grp']);
    
    $name=ucwords(strtolower($name));
    
    $insert_stmt=$connection->prepare("INSERT INTO user (scno, name, uscno, address, area, grp) VALUES (?, ?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("ssssss",$scno,$name,$uscno,$address,$area,$grp);
    
    if($insert_stmt->execute()) 
    {
        $success_message="Customer registration successful!";
    } 
    else 
    {
        if($connection->errno===1062) 
        {
            if(strpos($insert_stmt->error,'scno')!==false) 
            {
                $error_message="SC No already exists! Please generate a new one.";
            } 
            else 
            {
                $error_message="USC No already exists! Please generate a new one.";
            }
        } 
        else 
        {
            $error_message="Registration failed: ".$insert_stmt->error;
        }
    }
    $insert_stmt->close();
    $connection->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Customer</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="signup-container">
        <h2>New Customer</h2>
        
        <?php if(!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="scno"> SC No (PRIMARY KEY):</label>
                <div class="input-row">
                    <input type="text" id="scno" name="scno" class="primary-key" readonly required maxlength="9">
                    <button type="button" class="generate-btn" onclick="generateUniqueSCNo()">New SC</button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="name">Customer Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="uscno"> USC No (Unique):</label>
                <div class="input-row">
                    <input type="text" id="uscno" name="uscno" readonly required maxlength="9">
                    <button type="button" class="generate-btn" onclick="generateUniqueUSCNo()">New USC</button>
                </div>
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
                <p>D-Domestic C-Commercial I-Industrial</p>
                <label for="grp">Customer Group:</label>
                <select id="grp" name="grp" required>
                    <option value="">Select Group</option>
                    <option value="D">D</option>
                    <option value="C">C</option>
                    <option value="I">I</option>
                </select>
            </div>
            
            <button type="submit" class="submit-btn">Add Customer</button>
        </form>
    </div>

    <script>
    function generateUnique9Digit()
    {
        const timestamp=Date.now().toString().slice(-7);
        const random=Math.floor(Math.random()*90000).toString().padStart(2,'0');
        return (parseInt(timestamp+random)%1000000000).toString().padStart(9,'0');
    }
        
    function generateUniqueSCNo()
    {
        document.getElementById('scno').value=generateUnique9Digit();
    }
        
    function generateUniqueUSCNo()
    {
        document.getElementById('uscno').value=generateUnique9Digit();
    }
        
    window.onload=
    function()
    {
        generateUniqueSCNo();
        generateUniqueUSCNo();
    };
    </script>
</body>
</html>
