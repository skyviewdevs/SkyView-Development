<?php
require_once("db.class.php");

const PASSWORD_PEPPER = "Put pepper here";
$db = new db();

function getRandomString($length) {
    $chars = "ABCDEFGHJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
    $str = "";

    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[random_int(0, 35)];
    }

    return $str;
}

function generateKey() {
    return getRandomString(64);
}

function generateSalt() {
    return getRandomString(128);
}

function login($user, $hashedPassword) {
    $password = hash("sha256", PASSWORD_PEPPER . $hashedPassword); //Get hash of password with added pepper, salt has already been added to the original hash by the client
    $where = "username = '$user'";
    $table = "users";

    $result = $db->select($table, $where);
    if ($result["hash"] === $password) {
        $userid = $result["id"];
        $uniqueKey = generateKey();
        $table = "loginKeys";
        $data = array(
            "loginkey" => $uniqueKey,
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

function register($username, $password, $email) {
    $salt = generateSalt();
    $passwordTmp = hash("sha256", $password . $salt);
    $passwordTmp = hash("sha256", PASSWORD_PEPPER . $passwordTmp);

    $data = (
        "username" => $username,
        "email" => $email,
        "hash" => $passwordTmp,
        "salt" => $salt,
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
    } else {
        $arr = array(
            "success" => false,
            "message" => "User already exists"
        );
        return $arr;
    }
}