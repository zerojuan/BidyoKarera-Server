<?php
	require './config.php';
	require './facebook.php';
	require './database.php';
	
	session_start();
	
	$facebook = new Facebook(array(
		'appId' => $appId,
		'secret' => $secret,
		'cookie' => 'true'
	));
	
	$session = $facebook->getSession();

	$function = $_POST['method'];	
	
	$uid = $session["uid"];
	
	
	if($function == "getMyData"){
		getMyData($uid);
	}else if($function == "getFriendsData"){
		getFriendsData($uid);
	}
		
	function getMyData($uid){
		$cn = new DbConnection();
		$cn->Open();
			
		$dbRec = new DbRecord();
		$dbRec->Connection = $cn;
		$query = "SELECT * FROM players WHERE uid='".$uid."'";
		$result = $dbRec->Get($query);
	
		$cn->Close();
		echo json_encode($result);
	}
	
	function getFriendsData($uid){
		$query = "SELECT uid, username FROM players, buddies 
				WHERE buddies.user_id='".$uid."' AND buddies.buddy_id = players.uid";		

		// Using the DbReader class to sequentially access a set of records
		$rec = null;
		$dbReader = new DbReader($query);
		$dbReader->Open();
		$i = 0;
		while ($dbReader->Next($rec))
		{
		  $result[$i] = $rec;
		  $i++;
		}
		$dbReader->Close();
	
		echo json_encode($result);
	}
?>