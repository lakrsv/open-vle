<?php

require_once __DIR__ . '/../auth/mysql_config.php';
require_once __DIR__ . '/../auth/role.php';
require_once __DIR__ . '/../auth/login.php';
require_once __DIR__ . '/../header/auth_header.php';

if (!isset($_POST['action'], $_POST['user'])) {
    exit("Post params not set");
}

if (!$userRole->HasPermission("manage_users")) {
    header("Location: user-home.php");
}

$user = $_POST['user'];
$action = $_POST['action'];

switch ($action) {
    case 'delete':
        return TryDeleteUser($user);
    case 'add':
        // Get password from POST
        if (!isset($_POST['password'])) {
            exit("Password not set");
        }
        $password = $_POST['password'];
        return TryAddUser($user, $password);
    case 'change-role':
        if (!isset($_POST["roleName"])) {
            exit("roleName not set");
        }
        $roleId = Role::GetRoleIdFromRoleName($_POST["roleName"]);
        return TryChangeUserRole($user, $roleId);
}

function TryDeleteUser($userId) {
    $response = array();
    if (!User::UserWithIdExists($userId)) {
        $response['success'] = FALSE;
        $response['message'] = "Can't delete user as the user doesn't exist";
        echo json_encode($response);
        return FALSE;
    } else {
        User::DeleteUserWithId($userId);
        $response['success'] = TRUE;
        $response['message'] = "Successfully deleted user";
        echo json_encode($response);
        return TRUE;
    }
}

function TryAddUser($userName, $password) {
    $response = array();

    if (strlen($password) < 6) {
        $response['success'] = FALSE;
        $response['message'] = "Password must be atleast 6 characters";
        echo json_encode($response);
        return FALSE;
    }

    if (User::UserWithNameExists($userName)) {
        $response['success'] = FALSE;
        $response['message'] = "Can't add user as a user with this name already exists";
        echo json_encode($response);
        return FALSE;
    } else {
        User::AddUserWithNameAndPassword($userName, $password);
        $userId = User::GetUserIdFromName($userName);
        User::ChangeUserRole($userId, Role::GetRoleIdFromRoleName("learner"));
        
        $response['success'] = TRUE;
        $response['message'] = "Successfully added user";
        echo json_encode($response);
        return TRUE;
    }
}

function TryChangeUserRole($userId, $roleId) {
    $response = array();
    if (!User::UserWithIdExists($userId)) {
        $response['success'] = FALSE;
        $response['message'] = "Can't change role for user that does not exist";
        echo json_encode($response);
        return FALSE;
    } else {
        User::ChangeUserRole($userId, $roleId);
        $response['success'] = TRUE;
        $response['message'] = "Successfully changed role of user";
        echo json_encode($response);
        return TRUE;
    }
}