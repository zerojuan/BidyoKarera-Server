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
		
	$me = null;
	
	if($session){		
		try{			
			$uid = $facebook->getUser();			
			$me = $facebook->api('/me');
			//Check if user already played the game before
			$cn = new DbConnection();
			$cn->Open();
			
			$dbRec = new DbRecord();
			$dbRec->Connection = $cn;
			$query = "SELECT * FROM players WHERE uid='".$me["id"]."'";
			$result = $dbRec->Get($query);
			
			//If player is a new user
			if($result == null){
				$dbRec->Insert("INSERT INTO players(uid, username) VALUES ('".$me["id"]."','".$me["first_name"]."')");
			}
			
			//Populate buddy list
			$friends = $facebook->api('/me/friends');			
			foreach($friends["data"] as $friend){
				if($dbRec->Get("SELECT * FROM players, buddies WHERE players.uid='".$friend["id"]."'")){
					if($dbRec->Get("SELECT * FROM buddies WHERE buddies.user_id = '".$me["id"]."' AND buddies.buddy_id = '".$friend["id"]."'") == NULL){
						$dbRec->Insert("INSERT INTO buddies(user_id, buddy_id) VALUES ('".$me["id"]."','".$friend["id"]."')");
						$dbRec->Insert("INSERT INTO buddies(user_id, buddy_id) VALUES ('".$friend["id"]."','".$me["id"]."')");
					}					
				}
			}
			
			$cn->Close();
		}catch(FacebookAPIException $e){
			error_log($e);
		}
	}		

	//loadXMLFiles
	$assetsXML = loadFile("assets.xml");
	
	
	
	function loadFile($fileName){
		$fh = fopen($fileName, 'r');
		$theData = fread($fh, filesize($fileName));
		fclose($fh);
		return $theData;
	}
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title>Tada Poker!</title>		
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js"></script>
		<script type="text/javascript" src="http://connect.facebook.net/en_US/all.js"></script>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>

		<script type="text/javascript" src="FBJSBridge.js?"></script>
	</head>
	<body>
		<div id="fb-root"></div>
		<script type="text/javascript">
			function embedPlayer() {
				var flashvars = {
							isNew: "<?php echo $isNew; ?>",
							baseURL: "<?php echo $basedirectory;?>",
							lobbySkin: "<?php echo $lobbySkin;?>",
							navSkin: "<?php echo $navSkin;?>",
							tableSkin: "<?php echo $tableSkin;?>",
							jsConfig:"getXML",
							uid: "<?php echo "1:".$uid;?>"
						};

				embedSWF("<?php echo $basedirectory.$mainApp ?>", "flashContent", "760", "640", "9.0.0", flashvars);
			}			

			function init() { 	
				FB.init({
     				appId  : <?php echo $facebook->getAppId(); ?>,
     	     		session : <?php echo json_encode($session); ?>,
     				status : true, // check login status
     				cookie : true, // enable cookies to allow the server to access the session
		     		xfbml  : true  // parse XFBML
   				});
   				<?php 
   					if($me){
   						echo "embedPlayer()";
   					}else{
   						//echo $redirectURL;
   						//echo $facebook->getAppId();
   						echo "redirect('".$facebook->getAppId()."','','".$redirectURL."');";
   					}
   				?>
			}			

		
			function getXML(type){
				if(type == "sounds"){
					return escape("<data base='assets/sounds'> <as name='TestSound'>/location.swf</as></data>");
				}else if(type == "assets"){
					return "<?php echo rawurlencode($assetsXML);?>";
				}else if(type == "popups"){
					return "<data base='assets/popups'> <as name='TestPopup'>/location.swf</as></data>"
				}
			}

			//Redirect for authorization for application loaded in an iFrame on Facebook.com 
			function redirect(id,perms,uri) {
				alert("Redirect attempted")
				var params = window.location.toString().slice(window.location.toString().indexOf('?'));
				top.location = 'https://graph.facebook.com/oauth/authorize?client_id='+id+'&scope='+perms+'&redirect_uri='+uri+params;				 
			}
			
			init();
		</script>
		<div id="flashContent"></div>
		Hello Mothers!
	</body>
</html>