<?php
    class db {
        //Declare variables
        protected $db_address = "localhost";
        protected $db_username = "testUser";
        protected $db_password = "testpass";
        protected $db_name = "skyview";
        public $conn;

        public function __construct()
        {
            $this->conn = new mysqli($this->db_address, $this->db_username, $this->db_password, $this->db_name);
            return true;
        }

        public function processRowSet($result, $isSingleRow = false) {
            $toReturn = array();

            while ($row = $result->fetch_assoc()) {
                array_push($toReturn, $row);
            }

            if ($isSingleRow) {
                return $toReturn[0];
            } else {
                return $toReturn;
            }
        }

        public function select($table, $where) {
            $query = "SELECT * FROM $table WHERE $where";
            $result = $this->conn->query($query);

            if (mysqli_num_rows($result) <= 1) {
                return $this->processRowSet($result, true);
            } else {
                return $this->processRowSet($result);
            }
        }

        public function update($data, $table, $where) {
            foreach ($data as $column => $value) {
                $query = "UPDATE $table SET $column = '$value' WHERE $where";
                $this->conn->query($query);
            }
            return true;
        }

        public function insert($data, $table) {
            $columns = "";
            $values = "";

            foreach ($data as $column => $value) {
                if ($columns !== "") {
                    $columns .= ", ";
                }
                $columns .= $column;

                if ($values !== "") {
                    $values .= ", ";
                }
                $values .= "'" . $value . "'";
            }

            $query = "INSERT INTO $table ($columns) VALUES ($values)";
            $results = $this->conn->query($query);

            return $this->conn->insert_id;
        }

        public function checkOccurrences($table, $where) {
            $query = "SELECT * FROM $table WHERE $where";
            $result = $this->conn->query($query);

            return mysqli_num_rows($result);
        }
    }