<?php

include 'rf-databasedata.php';
include 'rf-common.php';

function sqlInsertUser($username, $apikey){
	global $servername; 
	global $dbusername;
	global $dbpassword; 
	global $dbname; 

	$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
	$safeUsername = mysqli_real_escape_string($conn, $username);
	$safeApikey = mysqli_real_escape_string($conn, $apikey);

	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$sql = "INSERT INTO RF_ReadForeverUser (username, apikey) VALUES ('".$safeUsername."', '".$safeApikey."')";
	$result = $conn->query($sql);
	$conn->close();
	return $result;
}

function createNewUser($username){
	//Check if username exists 
	$user = sqlGetUser($username);
	if(count($user) > 0){
		throw new Exception("UserName already present, please provide related API Key");
	}

	//Generate new random API key
	$newKey = bin2hex(random_bytes(16));

	//Insert new user to DB
	$addResult = sqlInsertUser($username, $newKey);
	if($addResult !== TRUE){
		throw new Exception("SQL Error when adding new user");
	}

	//Return API key
	return $newKey;
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
		$newApiKey = createNewUser($username);
		displaySuccessResult(json_encode(array('apikey' => $newApiKey)));
	}
	catch(Exception $e){
		displayError(500, $e->getMessage()); 
	}
}
else if($_SERVER['REQUEST_METHOD'] === 'GET'){
	
	try{
		$username = sanitizeUserName($_GET['username']);
		$apikey = sanitizeApiKey($_GET['apikey']);
		$isValid = checkIfAuthenticationIsValid($username, $apikey);
		displaySuccessResult(json_encode(array('isAuthenticated' => $isValid)));
	}
	catch(Exception $e){
		displayError(500, $e->getMessage()); 
	}
}
?>