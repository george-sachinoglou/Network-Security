
<?php
    $username = $_POST['username'];
    $password = $_POST['password'];

    $connection = mysqli_connect('localhost','root','','GDPR');

    $username = stripcslashes($username);
    $password = stripcslashes($password);
    $username = mysqli_real_escape_string($connection, $username);
    $password = mysqli_real_escape_string($connection, $password);

    $query = "SELECT * FROM users WHERE username = '$username' AND password = SHA2(concat('$password',salt),256)";
    $res = mysqli_query($connection, $query);
    $found = mysqli_num_rows($res);

    if($found == 1){
        echo "<h1> LOGGED IN </center></h1>";
    }
    else{
        echo "<h1> LOGIN UNSUCCESFUL </h1>";
        }
?>

