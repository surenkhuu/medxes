<?php

class DbHandler {

    private $conn;
    function __construct() {
        require_once 'dbConnect.php';
        // opening db connection
        $db = new dbConnect();
        $this->conn = $db->connect();
    }
  /**
   * get connection
   */
     public function getConn() {
         return $this->conn;
     }
     /**
      * Fetching single record
      */
    public function getOneRecord($query) {
        $r = $this->conn->query($query.' LIMIT 1') or die($this->conn->error.__LINE__);
        $this->write_log('getOneRecord',($r ? 1 : 0), $query);
        return $result = $r->fetch_assoc();
    }

    public function getRecord($query) {
        $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
        $this->write_log('getRecord',($r ? 1 : 0), $query);
        return $result = $r;
    }
     public function deleteRecord($query) {
        $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
        $this->write_log('DELETE',($r ? 1 : 0), $query);
        return $result = ($r !== false ? true : false);
    }
    /**
     * INSERT
     */
    public function insertIntoTable($obj, $column_names, $table_name) {

        $c = (array) $obj;
        $keys = array_keys($c);
        $columns = '';
        $values = '';
        foreach($column_names as $desired_key){
           if(!in_array($desired_key, $keys)) {
                $$desired_key = '';
            }else{
                $$desired_key = $c[$desired_key];
            }
            $columns = $columns.$desired_key.',';
            $values = $values."'".$$desired_key."',";
        }
        $query = "INSERT INTO ".$table_name."(".trim($columns,',').") VALUES(".trim($values,',').")";
        $r = $this->conn->query($query) or die($this->conn->error.__LINE__);

        if ($r) {
            $new_row_id = $this->conn->insert_id;
            return $new_row_id;
            } else {
            return NULL;
        }
    }
     /**
     * Update
     */
    public function updateTable($obj, $column_names, $table_name, $where_clause) {

        $c = (array) $obj;
        $keys = array_keys($c);
        $columns = '';
        $values = '';
        $whereSQL = '';

            if(!empty($where_clause))
            {
                if(substr(strtoupper(trim($where_clause)), 0, 5) != 'WHERE')
                {
                    $whereSQL = " WHERE ".$where_clause;
                } else
                {
                    $whereSQL = " ".trim($where_clause);
                }
            }

                    $query = "UPDATE ".$table_name." SET ";
                    $sets = array();
                    foreach($column_names as $desired_key){
                       if(!in_array($desired_key, $keys)) {
                            $$desired_key = '';
                        }else{
                            $$desired_key = $c[$desired_key];
                            $sets[] = "`".$desired_key."` = '".$$desired_key."'";
                        }
                    }
                    $query .= implode(', ', $sets);
                    $query .= $whereSQL;

                $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
                if ($r) {
                    return true;
                    } else {
                    return NULL;
                }
    }
    public function insertQuery($query) {
        $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
        if ($r) {
            $new_row_id = $this->conn->insert_id;
             $this->write_log('INSERT', 1, $query);
            return $new_row_id;
            } else {
             $this->write_log('INSERT', 0, $query);
            return NULL;
        }
    }
    public function updateQuery($query) {
        $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
        if ($r) {
            $this->write_log('UPDATE', 1, $query);
            return true;
            } else {
            $this->write_log('UPDATE', 0, $query);
            return NULL;
        }
    }

    public function startTransaction() {
        $this->conn->autocommit(FALSE);
        $r = $this->conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE) or die($this->conn->error.__LINE__);
        if ($r) {
            return true;
            } else {
            return NULL;
        }
    }
    public function commitTransaction() {
     $r = $this->conn->commit() or die($this->conn->error.__LINE__);

        if ($r) {
            return true;
            } else {
            return NULL;
        }
    }
    public function rollbackTransaction() {
       $r = $this->conn->rollback() or die($this->conn->error.__LINE__);
        if ($r) {
            return true;
            } else {
            return NULL;
        }
    }

public function write_log($event, $success, $comments="") {

    $user = isset($_SESSION['userid']) ? $_SESSION['userid'] : null;
    $comments = mysqli_real_escape_string($this->conn, $comments);
    $sql = "insert into log ( date, event, username, success, comments) " .
            "values ( NOW(), '" . $event . "','" . $user ."'," . $success . ",'" . $comments ."')";
    $r = $this->conn->query($sql) or die($this->conn->error.__LINE__);
        if ($r) {
            $new_row_id = $this->conn->insert_id;
            return $new_row_id;
            } else {
            return NULL;
        }

}
public function getSession(){
    if (!isset($_SESSION)) {
        session_start();
    }
    $sess = array();
    if(isset($_SESSION['userid']))
    {
        $sess["userid"] = $_SESSION['userid'];
        $sess["fullname"] = $_SESSION['fullname'];
        $sess["id"] = $_SESSION['id'];
        $sess["userinfo"] = $_SESSION['userinfo'];
    }
    else
    {
        $sess["userid"] = '';
        $sess["fullname"] = '';
        $sess["id"] = '';
        $sess["userinfo"] = '';
    }
    return $sess;
}
public function destroySession(){
    if (!isset($_SESSION)) {
    session_start();
    }
    if(isSet($_SESSION['userid']))
    {
        unset($_SESSION['userid']);
        unset($_SESSION['id']);
        unset($_SESSION['fullname']);
        unset($_SESSION['userinfo']);
        $info='info';
        if(isSet($_COOKIE[$info]))
        {
            setcookie ($info, '', time() - $cookie_time);
        }
        $msg="Logouts...";
    }
    else
    {
        $msg = "Cannot login...";
    }
    return $msg;
}

}

?>
