<?php
require_once 'auth/change_password.php';

$token1 = $_GET['token1'];
$token2 = $_GET['token2'];

if (!ChangePassword::AreTokensValid($token1, $token2)) {
    echo "This password reset request has expired or is not valid.";
    die();
}
?>

<!doctype html>
<html lang="en">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">

        <!-- Custom CSS -->
        <link rel="stylesheet" href="css/custom.css">

        <!-- JQuery -->
        <script
            src="https://code.jquery.com/jquery-3.3.1.min.js"
            integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
        crossorigin="anonymous"></script>

        <title>OpenVLE - Change Password</title>
    </head>
    <body>

        <script>
            $(function () {
                $('[data-toggle="tooltip"]').tooltip();
            });
        </script>

        <?php
        echo '<div id="token1" style="display: none;">' . $token1 . '</div>'
        ?>

        <?php
        echo '<div id="token2" style="display: none;">' . $token2 . '</div>'
        ?>

        <div class="container">
            <div class="row">
                <div class="col-xl-12 col-centered">

                    <div class="row justify-content-center">
                        <form id="change-password" method="post">
                            <div class="form-group">
                                <label for="new-password">New Password</label>
                                <input type="password" class="form-control" id="new-password" placeholder="Enter new password">
                            </div>
                            <div class="form-group">
                                <label for="confirm-password">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm-password" placeholder="Confirm new password">
                            </div>
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </form>

                        <script>
                            $(document).ready(function () {
                                $('#change-password').submit(function (e) {
                                    e.preventDefault();
                                    $.ajax({
                                        type: "POST",
                                        url: "auth/change_password.php",
                                        data: {
                                            newPassword: $('#new-password').val(),
                                            confirmPassword: $('#confirm-password').val(),
                                            token1: $('#token1').text(),
                                            token2: $('#token2').text()
                                        },
                                        success: function (data) {
                                            data = $.parseJSON(data);
                                            var $success = data.success;
                                            var $message = data.message;
                                            
                                            if ($success) {
                                                alert($message)
                                                window.location = 'index.php';
                                            } else {
                                                alert($message);
                                            }
                                        }
                                    });
                                });
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>
    </body>
</html>