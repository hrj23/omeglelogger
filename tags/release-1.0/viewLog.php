<?php
	require_once("config.php");
	$chatLogId = $_REQUEST['chatLogId'];
	
	$sql = "SELECT MAX(chatLogId) AS maxChatLogId FROM ChatLog";
	$chatLogList = db_read($sql);
	$maxChatLogId = $chatLogList[0]['maxChatLogId'];
	
	$sql = "SELECT DISTINCT chatId FROM Message WHERE (chatLogId=".dbEncode($chatLogId).")";
	$chatIdList = db_read($sql);
	
	$stranger[$chatIdList[0]['chatId']]['label'] = "Stranger #1";
	$stranger[$chatIdList[0]['chatId']]['color'] = "#ff0000";
	$stranger[$chatIdList[1]['chatId']]['label'] = "Stranger #2";
	$stranger[$chatIdList[1]['chatId']]['color'] = "#0000ff";
	$stranger[1]['label'] = "OmegleLogger";
	$stranger[1]['color'] = "#009900";
	
	$sql = "SELECT * FROM Message WHERE (chatLogId=".dbEncode($chatLogId)." AND messageType!='typing') ORDER BY messageTime";
	$messageList = db_read($sql);
	
	require_once("header.php");	
?>
<p><a href="index.php">See the list of other conversations</a>.</p>
<p>
	<?php if ($chatLogId > 1) { ?>
	<a href="viewLog.php?chatLogId=<?=$chatLogId-1?>">Previous Conversation</a><br/>
	<?php } ?>
	<a href="viewLog.php?chatLogId=<?=rand(1, $maxChatLogId)?>">Random Conversation</a><br/>
	<?php if ($chatLogId < $maxChatLogId) { ?>
	<a href="viewLog.php?chatLogId=<?=$chatLogId+1?>">Next Conversation</a></p><br/>
	<?php } ?>
	
<div>
<?php
	foreach ($messageList as $message) {
		$color = $stranger[$message['chatId']]['color'];
		if ($message['messageType'] == "spoof") {
			$color = $stranger[1]['color'];
		}
?>
	<span style="color: <?=$color?>">[<?=date("m/d/Y h:i:sa", $message['messageTime'])?>] 
	<?php if ($message['messageType'] == "gotMessage") { ?>
	&lt;<?=$stranger[$message['chatId']]['label']?>&gt; <?=nl2br($message['message'])?>
	<?php } elseif ($message['messageType'] == "spoof") { ?>
	&lt;<?=$stranger[1]['label']?> --> <?=$stranger[$message['chatId']]['label']?>&gt; <?=$message['message']?>
	<?php } elseif ($message['messageType'] == "spam") { ?>
	&lt;<?=$stranger[$message['chatId']]['label']?>&gt; *FILTERED SPAM* <?=$message['message']?>
	<?php } elseif ($message['messageType'] == "waiting") { ?>
	Waiting for <?=$stranger[$message['chatId']]['label']?>
	<?php } elseif ($message['messageType'] == "connected") { ?>
	<?=$stranger[$message['chatId']]['label']?> Connected
	<?php } elseif ($message['messageType'] == "strangerDisconnected") { ?>
	<?=$stranger[$message['chatId']]['label']?> Disconnected
	<?php } ?>
	</span><br/>
<?php
	}
?>
</div>
<?php
	require_once("footer.php");
?>
