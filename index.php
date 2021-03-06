<!DOCTYPE html>
<?php
/*  LOGOUT   */
session_start();
if (isset($_GET['action']) and $_GET['action'] == 'logout') {
    session_start();
    unset($_SESSION['username']);
    unset($_SESSION['password']);
    unset($_SESSION['logged_in']);
    header('Location: http://localhost/file-browser/');
    exit;
}

/*  ADD NEW directory   */
if (isset($_POST['newDir'])) {
    if (!is_dir($_POST['newDir'])) {
        mkdir($_GET['path'] . $_POST['newDir']);
    } else {
        print('<p style="color: red;">ERROR: ' . $_POST['newDir'] . ' directory already exist.<br> Please add directory with another name one more time.</p>');
    }
}

/*  DELETE file  */
if (isset($_POST['delete'])) {
    $deleteFile = $_GET['path'] . $_POST['fileName'];
    unlink($dir . $deleteFile);
}

/*  UPLOAD file  */
if (isset($_FILES['file'])) {

    $file_name = $_FILES['file']['name'];
    $file_size = $_FILES['file']['size'];
    $file_tmp = $_FILES['file']['tmp_name'];
    $file_type = $_FILES['file']['type'];

    // files extension - only permit jpegs, jpgs, pngs, txt and pdfs
    $file_ext = strtolower(end(explode('.', $_FILES['file']['name'])));
    $extensions = array("jpeg", "jpg", "png", "txt", "pdf");

    if (!($file_name === "")) {
        if (!(is_file($file_name))) {
            if (!(in_array($file_ext, $extensions) === false)) {
                if ($file_size < 3000000) {
                    move_uploaded_file($file_tmp, "./" . $_GET['path'] . $file_name);
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                } else {
                    print('<p style="color: red;">ERROR: ' . $file_name . ' file is too big. Max 3MB.</p>');
                }
            } else {
                print('<p style="color: red;">ERROR: This file extension not allowed.<br>Please choose a JPEG, JPG, PNG, TXT or PDF file.</p>');
            }
        } else {
            print('<p style="color: red;">ERROR: ' . $file_name . ' file already exist.<br>Please upload file with another name one more time.</p>');
        }
    } else {
        print('<p style="color: red;">ERROR: Please choose file to upload first.</p>');
    }
}

/*  DOWNLOAD file  */
if (isset($_POST['download'])) {

    $file = './' . $_GET["path"] . $_POST['fileName']; // file path
    $fileToDownloadEscaped = str_replace("&nbsp;", " ", htmlentities($file, null, 'utf-8')); // a&nbsp;b.txt --> a b.txt
    ob_clean();
    ob_start();
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename=' . basename($fileToDownloadEscaped));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fileToDownloadEscaped));
    ob_end_flush();
    readfile($fileToDownloadEscaped);
    exit;
}
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SP1 - File Browser</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <?php
        /*  LOGIN   */
        if (isset($_POST['login']) && !empty($_POST['username']) && !empty($_POST['password'])) {
            if ($_POST['username'] == 'Username' && $_POST['password'] == '1234') {
                $_SESSION['logged_in'] = true;
                $_SESSION['timeout'] = time();
                $_SESSION['username'] = 'Username';
                print('<br> Hello ' . $_POST['username'] . '!');
            }
        }

        /*  SUCCESSFULLY LOGGED IN  */
        if ($_SESSION['logged_in'] == true) {

            // start position:  ./ + 'path' 
            $dir = './' . $_GET['path'];

            print('<h2>Directory contents: ' . $_SERVER['REQUEST_URI'] . '</h2>');

            // back button
            print('<button class="buttonsOther" type="button" class="button"><a href=" ' . $_SERVER['HTTP_REFERER'] . '">Back</a></button><br><br>');

            // table (files and directories)
            print('<table id="browserTable">
             <tr>
                 <th>Type</th>
                 <th>Name</th>
                 <th>Actions</th>
             </tr>');
            printFilesAndDirectories($dir);
            print('</table>');

            // upload file
            print('<br><form action="" method="POST" enctype="multipart/form-data">
                        <input class="buttons" type="file" name="file"/><br>
                        <input class="buttons upload" type="submit" name="upload" value="Upload file"/>
                    </form>');

            // new directory
            print('<br><form action="" method="post">
                        <input class="textInput" type="text" name="newDir" placeholder="New directory name">
                        <input class="buttons" type="submit" value="Submit">
                    </form>');

            // Log out button
            echo '<br><button class="buttonsOther" type="button" class="button"><a href="index.php?action=logout">Log Out</button>';
        } else { // before user logged in print Log In form
            print('<h1>Welcome to SP1 - File Browser</h1>
                    <h2>Log In to start exploring!</h2>
                    <form action="" method="post">
                        <input class="textInput" type="text" name="username" placeholder="username - Username" required autofocus></br>
                        <input class="textInput" type="password" name="password" placeholder="password - 1234" required><br><br>
                        <button class="buttonsOther" type="submit" name="login">Log In</button>
                    </form>');
        }

        /*  TABLE PRINT FUNCTION  */
        function printFilesAndDirectories($dir)
        {
            // Current directory CONTENT (files and other directories). Note: array_diff() function deletes '.' and '..' from an array 
            $currentDir = array_diff(scandir($dir), array('.', '..'));

            foreach ($currentDir as $d) { //go through current directory content
                if (is_file($dir . $d)) {
                    // check if file is in our directory; if yes, print file path
                    print('<tr>
                                <td>File</td>
                                <td>' . $d . '</td>
                                <td>
                                    <form class="actions" action="" method="POST">
                                        <input type="hidden" name="fileName" value="' . $d . '">
                                        <button class="deleteButton" type="submit" name="delete" value="' . $$d . '" >Delete</button>
                                        <button class="buttons" type="submit" name="download" value="' . $$d . '">Download</button>
                                    </form>                                 
                                </td>
                            </tr>');
                }
                if (is_dir($dir . $d)) {
                    if (!isset($_GET['path'])) { // if there's no 'path', add 'path'
                        print('<tr>
                                    <td>Directory</td>
                                    <td><a href="' . $_SERVER['REQUEST_URI'] . '?path=' . $d . '/">' . $d . '</a></td>
                                    <td></td>
                                </tr>');
                    } else { // if there's 'path', print w/o new 'path'
                        print('<tr>
                                    <td>Directory</td>
                                    <td><a href="' . $_SERVER['REQUEST_URI'] . $d . '/">' . $d . '</a></td>
                                    <td></td>
                                </tr>');
                    }
                }
            }
        }
        ?>
    </div>
</body>

</html>