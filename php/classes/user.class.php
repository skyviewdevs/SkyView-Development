<!--
    User class for Skyview server-side code.

    Author: Sam J Gunner
    Copyright (C) Sam J Gunner, 2016
    The following code is to be used under the Skyview project only.
-->

<?php
require_once("../global.inc.php"); //Require necessary files.
require_once("db.class.php");

class user { //User object, to be used as session variable.
    private $userID;
    private $username;
    private $emailAddress;
    private $joinDate;
    private $uniqueKey;
    private $db;

    //Register a new user, not to be used with an object.
    public function __construct($isNewUser, $username, $password, $email="") {
        $this->db = new db(); //Initialise new database object
        $this->username = $username;
        $this->emailAddress = $email;

        if ($isNewUser) { //Create new user
            $where = "`username` = '$username'"; //Check if user already exists.
            $table = "users";

            $occurrences = $this->db->checkOccurrences($table, $where);
            if ($occurrences === 0) {//User does not exist with that username.
                $salt = $this->generateRandomString(8);

                $data = array(
                    "username" => $this->username,
                    "hash" => $insPassword,
                    "email" => $this->email,
                    "salt" => $this->salt,
                    "joinDate" => $this->joinDate
                );
            } else {
                $arr = array(
                    "success" => false,
                    "message" => "User already exists."
                );
                return $arr;
            }
        }

        $this->login();
    }

    private function generateRandomString($length) {
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
        $randomString = "";

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $chars[random_int(0, 35)];
        }

        return $randomString;
    }

    static function checkPassword($userID, $password) { //Check if password is correct for a certain user ID
        $where = "id = '$userID'";
        $table = "users";

        $results = $this->db->select($table, $where); //Get user object
        $hash = $results["hash"];
        $salt = $results["salt"];

        $newPassword = hash("sha256", PASSWORD_PEPPER . $password . $salt); //Generate hash of password_get_info
        
        if ($newPassword === $hash) {
            return true;
        } else {
            return false;
        }
    }
}