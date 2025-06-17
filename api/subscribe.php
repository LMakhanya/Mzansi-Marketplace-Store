<?php

include $_SERVER["DOCUMENT_ROOT"] . '/api/classes.php';

$classSendEmail = new SendEmail();

// check if method is post 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subscribe'])) {

    $email = $_POST['email'];

    // validate email
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $result = $classSendEmail->sendSubscribeEmail($email);
        
        if ($result) {
            // On success, append success param
            header('Location: ' . $_SERVER['HTTP_REFERER'] . (strpos($_SERVER['HTTP_REFERER'], '?') === false ? '?' : '&') . 'status=success');
        } else {
            // On failure, append fail param
            header('Location: ' . $_SERVER['HTTP_REFERER'] . (strpos($_SERVER['HTTP_REFERER'], '?') === false ? '?' : '&') . 'status=fail');
        }
        exit;
    }
}
