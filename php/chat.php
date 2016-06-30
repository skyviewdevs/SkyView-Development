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
            $userID = user::getUserIDFromUsername($username);

            $table = "loginKeys";
            $where = "id = '$userID'";
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

    public static function checkUserExistsByID($userID) {
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

    public static function checkUserExists($username) {
        $db = new db();
        $tableName = "users";
        $where = "`username` = '$username'";
        $numResults = $db->checkOccurrences($tableName, $where);
        if ($numResults === 1) {
            return true;
        } else {
            return false;
        }
    }
} //End of user class.

class chat {
    //Checks if a chat with a certain ID exists.
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

    //Sends and adds a new message to the correct chat table.
    public static function sendMessage($message, $chatID, $username, $uniqueKey) {
        $db = new db();

        $result = user::checkLogin($username, $uniqueKey);
        if ($result["success"]) {
            $userID = user::getUserIDFromUsername($username); //Acquire user id for message record

            if ($this->checkChatExists($chatID)) {
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
            return $arr;
        }

        $validLogin = user::checkLogin($authorName, $uniqueKey);
        if (!$validLogin["success"]) { //Check that the credentials are correct
            $arr = array("success" => false, "message" => "Incorrect user credentials.");
            return $arr;
        }

        if ($type === "single") { //between two users
            $data = array(
                "NAME" => "Chat name goes here",
                "AUTHOR" => $authorID,
                "TYPE" => 1
            );

            $chatID = $db->insert($data, "chatList");

            $customSQL = "CREATE TABLE csmg_$chatID (ID INT(11), MESSAGE VARCHAR(255), AUTHOR INT(11), DATE VARCHAR(255), PRIMARY KEY(ID))";
            $db->runCustomCommand($customSQL);

            $nextQuery = "INSERT INTO chatMembers (MEMBER_ID, CHAT_ID) VALUES ($authorID, $chatID)";
            $db->runCustomCommand($nextQuery);

            $arr = array(
                "success" => true,
                "message" => "Successfully created new chat.",
                "chatID" => $chatID
            );
            return $arr;

        } elseif ($type === "group") { //Group chat
            $data = array(
                "NAME" => $chatName,
                "AUTHOR" => $authorID,
                "TYPE" => 2
            );

            $chatID = $db->insert($data, "chatList"); //Register the chat in the table and get an ID

            $customSQL = "CREATE TABLE csmg_$chatID (ID INT(11), MESSAGE VARCHAR(255), AUTHOR INT(11), DATE VARCHAR(255), PRIMARY KEY(ID))";
            $db->runCustomCommand($customSQL);

            $nextQuery = "INSERT INTO chatMembers (MEMBER_ID, CHAT_ID) VALUES ($authorID, $chatID)";
            $db->runCustomCommand($nextQuery);

            $arr = array(
                "success" => true,
                "message" => "Created chat successfully",
                "chatID" => $chatID
            );
            return $arr;

        } else {
            $arr = array("success" => false, "message" => "Invalid chat type");
        }
    }

    //Add a user by ID to a chat by ID
    public static function addUserIDToChatByID($authorID, $authorLoginKey, $userID, $chatID) {
        if (user::checkLogin($authorID, $authorLoginKey) && user::checkUserExistsByID($userID)) { //Verify credentials and that the user being added exists
            if (chat::checkChatExists($chatID)) {
                $data = array(
                    "MEMBER_ID" => $userID,
                    "CHAT_ID" => $chatID
                );

                $db = new db();
                $db->insert($data, "chatMembers");

                $arr = array(
                    "success" => true,
                    "message" => "User added to chat successfully"
                );
                return $arr;
            } else {
                $arr = array(
                    "success" => false,
                    "message" => "Chat ID does not exist"
                );
                return $arr;
            }
        } else {
            $arr = array(
                "success" => false,
                "message" => "Invalid credentials."
            );
            return $arr;
        }
    }

    public static function removeUserIDFromChatByID($doingID, $doingKey, $userID, $chatID) {
        if (user::checkLogin($doingID, $doingKey) && user::checkUserExistsByID($userID)) {
            if (chat::checkChatExists($chatID)) {
                $where = "MEMBER_ID = '$userID'";
                $db = new db();

                $db->delete("chatMembers", $where);

                $arr = array(
                    "success" => true,
                    "message" => "User successfully removed from chat."
                );

                return $arr;
            } else {
                $arr = array(
                    "success" => false,
                    "message" => "Chat ID does not exist"
                );
                return $arr;
            }
        } else {
            $arr = array(
                "success" => false,
                "message" => "Invalid credentials."
            );
            return $arr;
        }
    }
} //end of chat class