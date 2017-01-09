<?php

class DataManager {

	private $conn;
	private $servername = "localhost";
	private $username = "root";
	private $password = "";
	private $dbname = "equiz";

	public function __construct(){
		$this->conn = $this->getConnection();
    }

    public function __destruct(){
        $this->closeConnection();
    }

    // create mysql connection
	private function getConnection(){
		// Create connection
		$conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
		// Check connection
		if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); } 
		return $conn;
	}

	// close mysql connection
	private function closeConnection(){
		$this->conn->close();
	}

	// reconnect mysql
	private function reConnection(){
		$this->closeConnection();
		$this->conn = $this->getConnection();
	}

	// create rule table
	public function createBookToBookRuleTable(){
		$sql = "CREATE TABLE `rule_book_to_book` (
				`id` bigint(10) NOT NULL AUTO_INCREMENT,
				`conditions` varchar(255) NOT NULL DEFAULT '',
				`conclusion` varchar(255) NOT NULL DEFAULT '',
				`lift` decimal(25,15) NOT NULL DEFAULT '0.0',
				`confidence` decimal(25,15) NOT NULL DEFAULT '0.0',
				PRIMARY KEY (`id`));";
		$this->conn->query($sql);
	}

	// create user recom books table
	public function createUserRecomBooksTable(){
		$sql = "CREATE TABLE `user_recom_books` (
				`id` bigint(10) NOT NULL AUTO_INCREMENT,
				`userid` varchar(255) NOT NULL DEFAULT '',
				`bookid` varchar(255) NOT NULL DEFAULT '',
				`conditions` varchar(255) NOT NULL DEFAULT '',
				`lift` decimal(25,15) NOT NULL DEFAULT '0.0',
				`confidence` decimal(25,15) NOT NULL DEFAULT '0.0',
				PRIMARY KEY (`id`));";
		$this->conn->query($sql);
	}

	// update book to book rules in database
	public function storeBookToBookRules($rules){
		// empty previous rules
		$sql = "TRUNCATE `rule_book_to_book`; ";
		$this->conn->query($sql);
		// parse rules
		$sql = "INSERT INTO rule_book_to_book 
				(conditions, conclusion, lift, confidence) VALUES ";
		$template = "('%s', '%s', '%f', '%f')";
		$records = Array();
		foreach($rules as &$rule) {
			$data = Array();
			array_push($data, join(';', $rule['cond']));
			array_push($data, join(';', $rule['conc']));
			array_push($data, $rule['lift']);
			array_push($data, $rule['confidence']);
			array_push($records, vsprintf($template, $data));
		}
		$sql = $sql.join(',', $records).';';
		$this->conn->query($sql);
		unset($rule);
	}

	public function storeUserRecomBooks($userid, $recom_books){
		$sql = "DELETE FROM user_recom_books WHERE userid = ".$userid.";";
		$this->conn->query($sql);
		$sql = "INSERT INTO user_recom_books 
				(userid, bookid, conditions, lift, confidence) VALUES ";
		$template = "('%s', '%s', '%s', '%f', '%f')";
		$records = Array();
		foreach($recom_books as &$recom_book) {
			$data = Array();
			array_push($data, $userid);
			array_push($data, $recom_book['bookid']);
			array_push($data, join(';', $recom_book['cond']));
			array_push($data, $recom_book['lift']);
			array_push($data, $recom_book['confidence']);
			array_push($records, vsprintf($template, $data));
		}
		$sql = $sql.join(',', $records).';';
		$this->conn->query($sql);
		unset($recom_book);
	}

	public function getUseridList(){
		$sql = "SELECT userid FROM mdl_user_info_data;";
		$result = $this->conn->query($sql);
		$userid_list = Array();
		while($row = $result->fetch_assoc()) {
			$userid = $row['userid'];
			$userid_list[$userid] = $userid;
		}
		sort($userid_list);
		return $userid_list;
	}

	// query users historical read books
	public function getReadBookRecords($userid = null){
		$sql = "SELECT mdl_quiz_attempts.userid, mdl_booklist.id AS bookid 
				FROM mdl_booklist, mdl_quiz_attempts 
				WHERE mdl_booklist.quiz_id = mdl_quiz_attempts.quiz %s
				ORDER BY userid;";
		if ($userid == null) $sql = vsprintf($sql, '');
		else $sql = vsprintf($sql, "AND mdl_quiz_attempts.userid = ".$userid);
		$result = $this->conn->query($sql);
		$records = Array();
		while($row = $result->fetch_assoc()) {
			$userid = $row['userid'];
			$bookid = $row['bookid'];
			$record = Array();
			$record['userid'] = $userid;
			$record['bookid'] = $bookid;
			array_push($records, $record);
		}
		return $records;
	}

	// read the book to book rules from database
	public function getBookToBookRules(){
		$sql = "SELECT * FROM rule_book_to_book;";
		$result = $this->conn->query($sql);
		$rules = Array();
		while($row = $result->fetch_assoc()) {
			$cond = explode(';', $row['conditions']);
			$conc = explode(';', $row['conclusion']);
			$lift = $row['lift'];
			$confidence = $row['confidence'];
			$rule = Array();
			$rule['cond'] = $cond;
			$rule['conc'] = $conc;
			$rule['lift'] = $lift;
			$rule['confidence'] = $confidence;
			array_push($rules, $rule);
		}
		return $rules;
	}

	public function getUserRecomBooks($userid){
		$sql = "SELECT bookid, conditions, lift, confidence
				FROM user_recom_books WHERE userid=".$userid.";";
		$result = $this->conn->query($sql);
		$recom_books = Array();
		while($row = $result->fetch_assoc()) {
			$recom_book = Array();
			$recom_book['bookid'] = $row['bookid'];
			$recom_book['cond'] = explode(';', $row['conditions']);
			$recom_book['lift'] = $row['lift'];
			$recom_book['confidence'] = $row['confidence'];
			array_push($recom_books, $recom_book);
		}
		return $recom_books;
	}
}

?>



<!-- parse mysqli result
	if ($result->num_rows > 0) {
	    // output data of each row
	    while($row = $result->fetch_assoc()) {
	        echo "UserID: " . $row["userid"]. " - BookID: " . $row["bookid"]. "<br>";
	    }
	} else {
	    echo "0 results";
	}
-->