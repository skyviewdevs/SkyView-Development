<?php
require_once("db.class.php");

/*
    User interaction page for Skyview project
    Author: Sam J Gunner
    Copyright (C) Sam Gunner, 2016
    Only for use within the Skyview project.
*/

//Begin functions

function getRandomString($length) {
    $chars = "ABCDEFGHJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
    $str = "";

    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[rand(0, 60)];
    }

    return $str;
}

function generateKey() {
    return getRandomString(64);
}

function generateSalt() {
    return getRandomString(128);
}

function getPepper($username) {
    $db = new db();
    $table = "users";
    $where = "username = '$username'";
    $result = $db->select($table, $where);
    return $result['pepper'];
}

function login($user, $hashedPassword) {
    $db = new db();
    $PASSWORD_PEPPER = getPepper($user);
    $password = hash("sha256", $PASSWORD_PEPPER . $hashedPassword); //Get hash of password with added pepper, salt has already been added to the original hash by the client
    $where = "username = '$user'";
    $table = "users";

    $result = $db->select($table, $where);
    if ($result["hash"] === $password) {
        $userid = $result["id"];
        $uniqueKey = generateKey();
        $table = "loginKeys";
        $data = array(
            "loginKey" => $uniqueKey,
            "date" => time()
        );
        $where = "id = '$userid'";

        $db->update($data, $table, $where);

        $arr = array("success" => true, "key" => $uniqueKey);
        return $arr;
    } else {
        $arr = array("success" => false, "message" => "invalid credentials");
        return $arr;
    }
}

function register($username, $password, $email, $salt) {
    $PASSWORD_PEPPER = generateSalt();

    $db = new db();
    $passwordTmp = hash("sha256", $PASSWORD_PEPPER . $password);

    $data = array(
        "username" => $username,
        "email" => $email,
        "hash" => $passwordTmp,
        "salt" => $salt,
        "pepper" => $PASSWORD_PEPPER,
        "joinDate" => time()
    );

    $table = "users";
    $where = "username = '$username'";

    $result = $db->checkOccurrences($table, $where);
    if ($result === 0) {
        $userID = $db->insert($data, "users");

        $loginKey = generateKey();
        $data = array(
            "id" => $userID,
            "loginKey" => $loginKey,
            "date" => time()
        );
        $db->insert($data, "loginKeys");

        $arr = array(
            "success" => true,
            "key" => $loginKey,
            "userID" => $userID
        );

        return $arr;
    } else {
        $arr = array(
            "success" => false,
            "message" => "User already exists"
        );
        return $arr;
    }
}

function getSalt($username) {
    $db = new db();
    $table = "users";
    $where = "username = '$username'";

    $results = $db->select($table, $where);
    return $results["salt"];
}

//Begin $_GET handling
if (isset($_GET['mode'])) {
    $mode = $_GET['mode'];

    switch($mode) {
        case "login":
            if (isset($_POST['username']) && isset($_POST['password'])) {
                $username = $_GET['username'];
                $hashedPassword = $_GET['password'];
                $arr = login($username, $hashedPassword);
                echo json_encode($arr);
            } else {
                $arr = array(
                    "success" => false,
                    "message" => "Required parameters not set"
                );
                echo json_encode($arr);
            }
            break;

        case "register":
            if (isset($_POST['username']) && isset($_POST['salt']) && isset($_POST['password'])) {
                $username = $_POST['username'];
                $salt = $_POST['salt'];
                $hashedPassword = $_POST['password'];
                $email = $_POST['email'];

                $arr = register($username, $password, $email, $salt);
                echo json_encode($arr);
            } else {
                $arr = array(
                    "success" => false,
                    "message" => "Required parameters not set"
                );
                echo json_encode($arr);
            }
            break;

        case "generateSalt":
            $arr = array(
                "success" => true,
                "salt" => generateSalt()
            );
            echo json_encode($arr);
            break;

        case "getSalt":
            if (isset($_POST['username'])) {
                $salt = getSalt($_POST['username']);
                $arr = array(
                    "success" => true,
                    "salt" => $salt
                );
                echo json_encode($arr);
            } else {
                $arr = array(
                    "success" => false,
                    "message" => "Username not specified."
                );
                echo json_encode($arr);
            }
            break;

        default:
            $arr = array(
                "success" => false,
                "message" => "Invalid operating mode"
            );
            echo json_encode($arr);
            break;
    }
} else {
    $arr = array(
        "success" => false,
        "message" => "Operating mode not set"
    );
    echo json_encode($arr);
}