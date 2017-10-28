<?php

include 'rf-databasedata.php';
include 'rf-common.php';

function sqlGetTopicsByName($userId, $t){
	global $servername; 
	global $dbusername;
	global $dbpassword; 
	global $dbname; 

	$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
	$sql = "SELECT topicname FROM RF_ReadForeverListData WHERE userid = '".$userId."' AND topicname = '".$t."'";
	$result = $conn->query($sql);

	$topics = array();
	while($row = $result->fetch_assoc()) {
		array_push($topics,  utf8_encode($row["topicname"]));		
	}
	$conn->close();

	return $topics;
}

function sqlAddTopic($userId, $t){
	global $servername; 
	global $dbusername;
	global $dbpassword; 
	global $dbname; 

	$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
	$safeTopicName = utf8_decode(mysqli_real_escape_string($conn, $t));

	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$sql = "INSERT INTO RF_ReadForeverListData (userid, topicname) VALUES ('".$userId."', '".$safeTopicName."')";
	$result = $conn->query($sql);
	$conn->close();
}

function sqlRemoveTopic($userId, $t){
	global $servername; 
	global $dbusername;
	global $dbpassword; 
	global $dbname; 

	$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
	$safeTopicName = utf8_decode(mysqli_real_escape_string($conn, $t));

	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$sql = "DELETE FROM RF_ReadForeverListData WHERE userid='".$userId."' AND topicname='".$safeTopicName."'";
	$result = $conn->query($sql);
	$conn->close();
}

function sqlGetBlacklistedList($userId){
	global $servername; 
	global $dbusername;
	global $dbpassword; 
	global $dbname; 

	$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
	$sql = "SELECT topicname FROM RF_ReadForeverListData WHERE userid = '".$userId."'";
	$result = $conn->query($sql);

	$blacklist = array();
	while($row = $result->fetch_assoc()) {
		array_push($blacklist,  utf8_encode($row["topicname"]));		
	}
	$conn->close();

	return $blacklist;
}

function retrieveBlacklist($username, $apikey){
	$userData = sqlGetUser($username);
	$userId = $userData['id'];
	return sqlGetBlacklistedList($userId);
}

/******************************/
/***** API endpoint logic *****/
/******************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
		if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'POST') {
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers');
		}
		exit;
	}

	try{
		$username = sanitizeUserName($_GET['username']);
		$apikey = sanitizeApiKey($_GET['apikey']);
		$operation = $_GET['operation'];

		$authIsValid = checkIfAuthenticationIsValid($username, $apikey);
		if($authIsValid !== TRUE) {
			throw new Exception("Not authenticated");
		}

		$json = file_get_contents('php://input');
		$topics = json_decode($json)->topics;

		$user = sqlGetUser($username);
		$userId = $user['id'];

		if($operation === "add") {
			foreach ($topics as &$t) {
				if(count(sqlGetTopicsByName($userId, $t)) === 0){
					sqlAddTopic($userId, sanitizeTopicName($t));
				}
			}
		} else if ($operation === "remove"){
			foreach ($topics as &$t) {
				sqlRemoveTopic($userId, sanitizeTopicName($t));
			}
		} else {
			throw new Exception("Invalid operation");			
		}
	}
	catch(Exception $e){
		displayError(500, $e->getMessage()); 
	}
}
else if($_SERVER['REQUEST_METHOD'] === 'GET'){	
	try{
		$username = sanitizeUserName($_GET['username']);
		$apikey = sanitizeApiKey($_GET['apikey']);

		$authIsValid = checkIfAuthenticationIsValid($username, $apikey);
		if($authIsValid !== TRUE) {
			throw new Exception("Not authenticated");
		}

		$blacklist = retrieveBlacklist($username, $apikey);
		displaySuccessResult(json_encode(array('blacklist' => $blacklist),  JSON_UNESCAPED_UNICODE));
	}
	catch(Exception $e){
		displayError(500, $e->getMessage()); 
	}
}
?>