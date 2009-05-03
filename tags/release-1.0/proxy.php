<?php
	require_once("config.php");
	
	function messageIsSpam($message) { // We don't want to forward on spam. This might get us banned.
		$sql = "SELECT * FROM Spamlist";
		$spamList = dbRead($sql);
		foreach ($spamList as $s) {
			$string .= $s['spam']."|";
		}
		$string = trim($string, "|");
		$spamRegex = "/\b(".$string.")\b/i";
		if (preg_match($spamRegex, $message)) {
			return true;
		} else {
			return false;
		}
	}
	
	$url = "http://omegle.com/";
	
	$action = $_REQUEST['action'];
	
	$postData = array();
	if ($_REQUEST['chatLogId']) {
		$chatLogId = $_REQUEST['chatLogId'];
	}
	if ($_REQUEST['id']) {
		$postData['id'] = $_REQUEST['id'];
	}
	if ($_REQUEST['msg']) {
		$postData['msg'] = $_REQUEST['msg'];
	}
	
	switch ($action) {
		case 'getChatLogId':
			$newChatLog = array();
			$newChatLog['startTime'] = time();
			$chatLogId = db_insert("ChatLog", $newChatLog);
			$return = $chatLogId;
			$askOmegle = false;
			break;
		case 'start':
			$url .= 'start';
			$askOmegle = true;
			break;
		case 'sendSpoofed':
			// Log this one
			$newMessage = array();
			$newMessage['chatLogId'] = $chatLogId;
			$newMessage['chatId'] = $_REQUEST['id'];
			$newMessage['messageTime'] = time();
			$newMessage['messageType'] = "spoof";
			$newMessage['message'] = $postData['msg'];
			db_insert("Message", $newMessage);
		case 'send':
			$url .= 'send';
			$askOmegle = true;
			break;
		case 'typing':
			$url .= 'typing';
			$askOmegle = true;
			break;
		case 'events':
			$url .= 'events';
			$askOmegle = true;
			break;
		case 'disconnect':
			$url .= 'disconnect';
			$askOmegle = true;
			break;
	}
	
	if ($askOmegle) {
		// create a new cURL resource
		$ch = curl_init();
		
		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_REFERER, "http://omegle.com/");
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']." ".md5(rand()));
		curl_setopt($ch, CURLOPT_HTTPHEADERS, array("Accept: application/json"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
		
		// grab URL and pass it to the browser
		$curlResult = curl_exec($ch);
		
		// close cURL resource, and free up system resources
		curl_close($ch);
		
		$curlReturn = json_decode($curlResult);
	}
	
	switch ($action) {
		case 'start':
			$return = $curlReturn;
			break;
		case 'send':
		case 'typing':
			$return['status'] = $curlReturn;
			break;
		case 'events':
			if (is_array($curlReturn) && count($curlReturn) > 0) { // Got messages, log them
				foreach ($curlReturn as $mId => $message) {
					if (messageIsSpam($message[1])) {
						$curlReturn[$mId][0] = "spam";
						
						$newMessage = array();
						$newMessage['chatLogId'] = $chatLogId;
						$newMessage['chatId'] = $_REQUEST['id'];
						$newMessage['messageTime'] = time();
						$newMessage['messageType'] = "spam";
						$newMessage['message'] = $message[1];
						db_insert("Message", $newMessage);
					} else {
						$newMessage = array();
						$newMessage['chatLogId'] = $chatLogId;
						$newMessage['chatId'] = $_REQUEST['id'];
						$newMessage['messageTime'] = time();
						$newMessage['messageType'] = $message[0];
						$newMessage['message'] = $message[1];
						db_insert("Message", $newMessage);
					}
				}
			}
			
			$return['messages'] = $curlReturn;
			if ($_REQUEST['id']) {
				$return['chatId'] = $_REQUEST['id'];
			}
			break;
		case 'disconnect':
			$sql = "UPDATE ChatLog SET endTime=".time()." WHERE (chatLogId=".dbEncode($chatLogId).")";
			dbWrite($sql);
			
			if ($_REQUEST['id']) {
				$return['chatId'] = $_REQUEST['id'];
			}
			$return['status'] = $curlReturn;
			break;
	}
	
	if ($return) {
		echo json_encode($return);
	}
	
?>
