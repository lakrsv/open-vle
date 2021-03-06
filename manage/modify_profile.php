<?php

require_once __DIR__ . '/../auth/mysql_config.php';
require_once __DIR__ . '/../auth/role.php';
require_once __DIR__ . '/../header/auth_header.php';
require_once __DIR__ . '/../auth/login.php';
require_once __DIR__ . '/../classes/contactDetails.php';

if (!$userRole->HasPermission("manage_profile")) {
    header("Location: user-home.php");
}

$userId = filter_input(INPUT_POST, "userid", FILTER_SANITIZE_NUMBER_INT);
if (!$userId) {
    exit("Post params not set");
}

// Security check.
if (!$userRole->HasPermission("manage_users")) {
    $userId = $_SESSION["userid"];
}

TryChangeProfile($userId);

function TryChangeProfile($userId) {

    $response = array();

    if (HasFile()) {
        $response = TryChangePicture($userId);
        if (!$response["success"]) {
            echo json_encode($response);
            return FALSE;
        }
    }


    $name = filter_input(INPUT_POST, "name", FILTER_SANITIZE_STRING);
    if ($name) {
        $response = TryChangeName($userId, $name);
        if (!$response["success"]) {
            echo json_encode($response);
            return FALSE;
        }
    }

    $addressLine1 = filter_input(INPUT_POST, "addressLine1", FILTER_SANITIZE_STRING);
    $addressLine2 = filter_input(INPUT_POST, "addressLine2", FILTER_SANITIZE_STRING);
    $city = filter_input(INPUT_POST, "city", FILTER_SANITIZE_STRING);
    $zip = filter_input(INPUT_POST, "zip", FILTER_SANITIZE_STRING);
    $number = filter_input(INPUT_POST, "number", FILTER_SANITIZE_STRING);
    if ($addressLine1 && $addressLine2 && $city && $zip && $number) {
        $response = TryChangeContactDetails($userId, $addressLine1, $addressLine2, $city, $zip, $number);
        if (!$response["success"]) {
            echo json_encode($response);
            return FALSE;
        }
    }

    $response["success"] = TRUE;
    $response["message"] = "Successfully updated profile";
    echo json_encode($response);
    return TRUE;
}

function TryChangePicture($userId) {
    $allowedExtensions = [IMAGETYPE_JPEG, IMAGETYPE_PNG];
    $maxImageSize = 2000000;

    $file = $_FILES["file"];
    $imageType = exif_imagetype($file["tmp_name"]);

    $response = array();

    $isImage = getimagesize($file["tmp_name"]);
    if (!$isImage || !in_array($imageType, $allowedExtensions)) {
        $response['success'] = FALSE;
        $response['message'] = "Can't upload file, not a valid image (Valid Extensions are JPG and PNG)";
        return $response;
    }

    if ($file["size"] > $maxImageSize) {
        $response['success'] = FALSE;
        $response['message'] = "This image is too large. Max 2 MB";
        return $response;
    }

    list($width, $height, $type, $attr) = getimagesize($file["tmp_name"]);
    if ($width != 200 && $height != 200) {
        $response['success'] = FALSE;
        $response['message'] = "The image must be 200x200 pixels";
        return $response;
    }

    $fileData = file_get_contents($file["tmp_name"]);
    User::ChangeUserProfilePicture($userId, $fileData);

    $response['success'] = TRUE;
    $response['message'] = "Image successfully uploaded!";
    return $response;
}

function TryChangeName($userId, $newName) {
    $response = array();
    if ($newName && strlen($newName) > 1 && IsValidName($newName)) {
        if (User::UserWithIdExists($userId)) {
            User::ChangeUserName($userId, $newName);
            $response['success'] = TRUE;
            $response['message'] = "Successfully changed username!";
            return $response;
        }
    }

    $response['success'] = FALSE;
    $response['message'] = "Failed updating username";
    return $response;
}

function TryChangeContactDetails($userId, $addressLine1, $addressLine2, $city, $zip, $number) {
    $response = array();
    if (User::UserWithIdExists($userId)) {
        ContactDetails::AddContactDetailsForUser($userId, $addressLine1, $addressLine2, $city, $zip, $number);
        $response['success'] = TRUE;
        $response['message'] = "Successfully changed contact details!";
        return $response;
    }

    $response['success'] = FALSE;
    $response['message'] = "Failed changing contact details";
    return $response;
}

function HasFile() {
    if (isset($_FILES["file"]) && $_FILES["file"]["tmp_name"] != '') {
        if (is_uploaded_file($_FILES["file"]["tmp_name"])) {
            return TRUE;
        }
    }
    return FALSE;
}

function IsValidName($name) {
    return preg_match("/^[a-zA-Z'-]+$/", $name);
}
