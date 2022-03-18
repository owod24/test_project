<?php
session_start();
require "./includes/library.php";

if (!(isset($_SESSION['username']) && $_SESSION['username'] != '')) {
    header("Location: Login.php");
    exit();
}

if (isset($_POST['deleteList'])){
    $listID = $_POST['listID'];

    /* Connect to DB */
    $pdo = connectDB();

    //Delete all entries
    $query = "DELETE FROM bucket_entries WHERE fk_listid=?";
    $statement = $pdo->prepare($query);
    $statement->execute([$listID]); // fill with passed in id

    $query = "DELETE FROM bucket_lists WHERE id=?";
    $statement = $pdo->prepare($query);
    $statement->execute([$listID]); // fill with passed in id
}

$errors = [];

/* Connect to DB */
$pdo = connectDB();

$query = "SELECT email FROM `bucket_users` WHERE id=?";
$statement = $pdo->prepare($query);
$statement->execute([$_SESSION['userID']]);
$email = $statement->fetch();

$query = "SELECT * FROM `bucket_lists` WHERE fk_userid = ? ORDER BY title";
$statement = $pdo->prepare($query);
$statement->execute([$_SESSION['userID']]);
$lists = $statement->fetchAll();

if (isset($_POST['submit'])) {
    //Gets all the lists the user is associated with
    $query = "SELECT id FROM `bucket_lists` WHERE fk_userid=?";
    $statement = $pdo->prepare($query);
    $statement->execute([$_SESSION['userID']]);
    $userLists = $statement->fetchAll();

    //Deletes all of the entries in the lists the user is associated with
    foreach ($userLists as $list) {
        $query = "DELETE FROM `bucket_entries` WHERE fk_listid=?";
        $statement = $pdo->prepare($query);
        $statement->execute([$list['id']]);
    }

    //Deletes all the lists the user is associated with
    $query = "DELETE FROM `bucket_lists` WHERE fk_userid=?";
    $statement = $pdo->prepare($query);
    $statement->execute([$_SESSION['userID']]);

    //Deletes the user
    $query = "DELETE FROM `bucket_users` WHERE id=?";
    $statement = $pdo->prepare($query);
    $statement->execute([$_SESSION['userID']]);

    header('Location: Logout.php');
    exit();
}

if (isset($_POST['completeCreation'])) {
    $date = date("Y-m-d");
    $private = 0;
    if (!empty($_POST['privacy'])) {
        $private = 1;
    }

    $query="INSERT INTO `bucket_lists`(`title`, `fk_userid`, `created`, `description`, `private`) VALUES (?,?,?,?,?)";
    $statement = $pdo->prepare($query);
    $statement->execute([$_POST['title'], $_SESSION['userID'], $date, $_POST['description'], $private]);
    header("Refresh:0");
}
?>

<head>
    <script src="scripts/Profile.js"></script>
</head>
<!-- HTML Starts -->
<?php include "./includes/header.php"; ?>
    <div class="main-box large">
        <h1><?php echo ucfirst($_SESSION['username'])?>'s Profile</h1> <!-- Loads the username into the title -->

        <div class="leftAlignText">
            <h3 class="profileFormat">Account Details</h3> <!-- Displays the users account details, such as email and password -->
            <p><b>Username</b>: <?php echo $_SESSION['username']?></p>
            <p><b>E-mail</b>: <?php echo $email['email']?></p>
        </div>

        <h3 class="space profileFormat">Your Bucket Lists</h3>

        <div class="bucketListNav"> <!-- Nav bar which contains the create list button -->
            <button id="createList" data-open-modal="createListModal" name="createList" data-tippy-content="Create A List"><i class="fas fa-plus"></i></button>
            <div id="createListModal" class="modal"> <!-- Creates a modal where the user can enter their new list information -->
                <div class="modal-content">
                    <span class="close-btn">&times;</span>
                    <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST" class="wide">
                        <div class="addModalContent">
                            <label for="title" class="addLabel">Title</label>
                            <input id="title" name="title" type="text" placeholder="Title" required> <!-- Title, required before form submission -->
                        </div>
                        <div class="addModalContent">
                            <label for=description" class="addLabel">Description</label> <!-- Description, required before form submission -->
                            <textarea name="description" id="description" cols="30" rows="10" required></textarea>
                        </div>
                        <div>
                            <label for="privacy"></i>Private</label> <!-- Allows the user to select if they want their list private or public -->
                            <input id="privacy" name="privacy" type="checkbox">
                        </div>

                        <button id="completeCreation" name="completeCreation" class="centered createButton">Submit</button> <!-- Completes and processes the form -->
                    </form>
                </div>
            </div>
        </div>

        <table class="profile">
            <tr> <!-- Table which displays all of the users lists and relevant information -->
                <th>List Name</th>
                <th>Privacy Setting</th>
                <th>Description</th>
                <th>Date Created</th>
                <th>Link</th>
            </tr>

            <?php foreach ($lists as $list): ?> <!-- Iterates over each gathered list -->
                <tr>
                    <td><?= $list['title'] ?></td> <!-- Title -->

                    <?php if ($list['private'] == 0): ?> <!-- Privacy setting, displayed in text rather than 0,1 which is stored in the database -->
                        <td>Public</td>
                    <?php else: ?>
                        <td>Private</td>
                    <?php endif ?>

                    <td><?= $list['description'] ?></td> <!-- Description -->
                    <td><?= $list['created'] ?></td> <!-- Date created -->
                    <td><a class="listLink" href="<?php //Creates a link based on the current URL, appends necessary text to create the link
                        $currPath = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                        echo substr($currPath, 0, strrpos($currPath, '/')) . "/DisplayList?id=" . $list['id'];
                        //Removes the last portion of the link, and replaces it with displaylist with an associated ID
                        ?>"><?php
                            $currPath = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                            echo substr($currPath, 0, strrpos($currPath, '/')) . "/DisplayList?id=" . $list['id'];
                            ?></a></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h3 class="space profileFormat">Other Operations</h3>
        <form id="delete-form" action="<?= $_SERVER['PHP_SELF'] ?>" method="POST" onsubmit="getConfirmation()"> <!-- Allows the user to delete their account, confirms with JS validation in a popup alert -->
            <button id="submit" name="submit" class="delete">Delete Account</button>
        </form>


    </div>

    <script> //This JS prevents the page from resubmitting the form on refresh, or form submission
        if ( window.history.replaceState ) {
            window.history.replaceState( null, null, window.location.href );
        }
    </script>
<?php include "./includes/footer.php"; ?>