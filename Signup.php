<?php

session_start();
require "./includes/library.php";

$errors = [];
$email_regex = "/^[^\s@]+@[^\s@]+\.[^\s@]+$/";

//get name from post or set to NULL if doesn't exist
if (isset($_POST['submit'])) {
    $valid = true;

    /* Process log-in request */
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password_check = $_POST['password-check'];

    //connect to DB
    $pdo = connectDB();

    /* Check the database for occurrences of $username */
    $sql = "SELECT username FROM `Users` WHERE username = ?";
    $statement = $pdo->prepare($sql);
    $statement->execute([ $username ]);
    $results = $statement->fetch();

     //Second implementation, would display all current errors
     if (!empty($results)) { //Database contains a user registered with the name
        array_push($errors, "Username taken.");
        $valid = false;
    }
    if (!preg_match($email_regex, $email)) { //Checks if the email passes regex
        array_push($errors, "Invalid E-mail Formatting.");
        $valid = false;
    }
    if ($password != $password_check) { //Checks if the passwords entered match
        array_push($errors, "Passwords Don't Match.");
        $valid = false;
    }


    if ($valid) { //If the entered passwords match, and the username isn't already taken, continue with hashing the password
        $options = ['cost' => 12];
        $password = password_hash($password, PASSWORD_DEFAULT, $options); //Hash the password and store it in the database

        $sql="INSERT INTO Users values (NULL,?,?,?)";
        $statement = $pdo->prepare($sql);
        $statement->execute([ $username, $password, $email ]);

        $_SESSION['username'] = $username; //Gets necessary session variables
        $_SESSION['userID'] = $pdo->lastInsertId(); //Gets the last inserted ID from the database, which should associate with the just added user

        header("Location: Profile"); //Redirects the user to their profile page
        exit();   
}
?>

<!DOCTYPE html>
<html lang="en">
  <body>
    <head>
      <meta charset="UTF-8">
      <title>Sign Up</title>
      <link rel="stylesheet" href="css/Signup.css">
      <!-- <link rel="stylesheet" href="./plugins/passwordStrength/pwdStyles.css">
      <link href="https://fonts.googleapis.com/css?family=Fredoka+One|Lato:300,400,700|Roboto:300,400,700&display=swap" rel="stylesheet">
      <script src="https://kit.fontawesome.com/1c8ee6a0f5.js" crossorigin="anonymous"></script>
      <link rel="stylesheet" href="css/passtrength.css"> -->
    </head>



   <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
     <div class="container">
       <h1>Sign Up</h1>
       <p>Please fill in this form to create an account.</p>
      
       <hr>   <!-- What is this ??? @tobi-->

       <label for="email"><b>Email</b></label>
       <input type="text" placeholder="Enter Email" name="email" id="email">

       <label for="username"><b>Username</b></label>
       <input type="text" placeholder="Enter username" name="username" id="username">
  
       <label for="psw"><b>Password</b></label>
       <input type="password" placeholder="Enter Password" name="password" id="password" autocomplete="password">

       <label for="psw"><b>Repeat Password</b></label>
       <input type="password" placeholder="Re-type Password" name="password-check" id="password" autocomplete="password">
  
       <hr>
       <button type="submit" class="registerbtn">Register</button>
     </div>
     <div class=" ">
                <?php foreach ($errors as $error): ?> <!-- Outputs all submission errors, if any -->
                    <p><?= $error ?></p>
                <?php endforeach; ?>
     </div>
    
     <div class=" ">
       <p>Already have an account? <a href="login.php">Login</a>.</p>
     </div>
    </form>

    <script type="text/javascript" src="scripts/jquery.Pstrength.min"></script> <!-- Script for password strength plugin -->
    <script>
        $('#password').passtrength({
            passwordToggle:false,
        });
    </script>

  </body>
</html> 