<?php

require_once("db.class.php");

class user {
    public static function checkLogin($username, $uniqueKey) {
        $db = new db();

        $table = "users";
        $where = "username = '$username'";
        $numResults = $db->checkOccurrences($table, $where);
        if ($numResults === 0) { //The user does not exist
            $arr = array(
                "success" => false,
                "message" => "User does not exist."
            );
            return $arr;
        } elseif ($numResults > 1) { //This should never happen, but just in case it does.
            $arr = array(
                "success" => false,
                "message" => "Something went wrong."
            );
            return $arr;
        } else { //Everything should be fine
            $table = "loginKeys";
            $result = $db->select($table, $where);

            if ($result["loginKey"] === $uniqueKey) { //The keys are the same, it should work just fine.
                $arr = array(
                    "success" => true,
                    "message" => "User verified successfully."
                );
                return $arr;
            } else {
                $arr = array(
                    "success" => false,
                    "message" => "Login keys do not match."
                );
                return $arr;
            }
        }
    }

    public static function getUserIDFromUsername($username) {
        $db = new db();
        $tableName = "users";
        $where = "username = '$username'";
        $results = $db->select($tableName, $where);
        return $results["id"];
    }

    public static function getUsernameFromUserID($userID) {
        $db = new db();
        $tableName = "users";
        $where = "id = '$userID'";
        $results = $db->select($tableName, $where);
        return $results["username"];
    }

    public static function checkUserExists($username) {
        $db = new db();
        $tableName = "users";
        $where = "id = '$userID'";
        $numResults = $db->checkOccurrences($tableName, $where);
        if ($numResults === 1) {
            return true;
        } else {
            return false;
        }
    }
}

class chat {
    //Sends and adds a new message to the correct chat table.
    public static function sendMessage($message, $chatID, $username, $uniqueKey) {
        $db = new db();

        $result = user::checkLogin($username, $uniqueKey);
        if ($result["success"]) {
            $userID = user::getUserIDFromUsername($username); //Acquire user id for message record

            if (this.checkChatExists($chatID)) {
                $tableName = "cmsg_" . $chatID; //ID of the table that stores the messages in for the chat
                $data = array(
                    "MESSAGE" => trim($message),
                    "TIME" => date(),
                    "AUTHOR" => $userID
                );

                $messageID = $db->insert($data, $tableName);
                $arr = array(
                    "success" => true,
                    "message" => "Message sent successfully.",
                    "messageID" => $messageID
                );

                return $arr;
            } else {
                $arr = array("success" => false, "message" => "That chat does not exist.");
                return $arr;
            }
        } else {
            return $result;
        }
    }

    //Creates and registers a new chat within the chatList table.
    public static function createChat($chatName, $authorName, $type, $uniqueKey) {
        $db = new db();

        if (user::checkUserExists($authorName)) { //Check that the user exists before checking credentials
            $authorID = user::getUserIDFromUsername($authorName); //Acquire user ID
        } else {
            $arr = array("success" => false, "message" => "User does not exist!");
        }

        if (!user::checkLogin($authorName, $uniqueKey)) { //Check that the credentials are correct
            $arr = array("success" => false, "message" => "Incorrect user credentials.");
            return $arr;
        }

        if ($type === "single") { //between two users
            $data = array(
                "NAME" => $chatName,
                "AUTHOR" => $authorID,
                "TYPE" => 1
            );

            $chatID = $db->insert($data, "chatList");

            $customSQL = "CREATE TABLE csmg_$chatID (ID INT(11), MESSAGE VARCHAR(255), AUTHOR INT(11), DATE VARCHAR(255), PRIMARY KEY(ID))";
            $db->runCustomCommand($customSQL);

        } elseif ($type === "group") { //Group chat

        } else {
            $arr = array("success" => false, "message" => "Invalid chat type");
        }
    }

    public static function checkChatExists($chatID) {
        $db = new db();

        $tableName = "chatList";
        $where = "ID = '$chatID'";

        $numResults = $db->checkOccurrences($tableName, $where);
        if ($numResults === 1) {
            return true;
        } else {
            return false;
        }
    }
}

chat::createChat("Test Chat", "testuser", "single", "uniqueKey");