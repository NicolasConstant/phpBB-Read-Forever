<?php

function displayError($errorCode, $errorMessage){
	header('HTTP/1.1 '.$errorCode.' '.$errorMessage );
	die(json_encode(array('message' => $errorMessage)));
}

function displaySuccessResult($jsondata){
	header('Content-type: application/json');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
	echo $jsondata;
}

function checkIfAuthenticationIsValid($username, $apikey){
	$user = sqlGetUser($username);
	if(count($user) === 0){
		return false;
	} 

	$storedApiKey = $user['apikey'];
	if($apikey == $storedApiKey){
		return true;
	}	
	return false;
}

function sqlGetUser($username){
	global $servername; 
	global $dbusername;
	global $dbpassword; 
	global $dbname; 

	$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
	$safeUsername = mysqli_real_escape_string($conn, $username);

	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
	$sql = "SELECT id, username, apikey FROM RF_ReadForeverUser WHERE username = '".$safeUsername."'";
	$result = $conn->query($sql);

	$resultdata = array();
	if ($result->num_rows > 0) {
		$firstrow = $result->fetch_assoc();

		if($firstrow !== null){		
			$resultdata = array('id' => $firstrow["id"], 'username' => $firstrow["username"],  'apikey' => $firstrow["apikey"]);
		}
	}

	$conn->close();
	return $resultdata;
}

function sanitizeUserName($username){
	return trim(strtolower($username));
}

function sanitizeApiKey($apikey){
	return trim($apikey);
}

function sanitizeTopicName($topicname){
	return trim($topicname);
}

?>