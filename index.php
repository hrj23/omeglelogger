<?php
	require_once("config.php");

	$sql = "SELECT cl.chatLogId, cl.startTime, COUNT(m.messageId) AS lineCount FROM ChatLog AS cl LEFT JOIN Message AS m ON (cl.chatLogId=m.chatLogId AND m.messageType='gotMessage') GROUP BY cl.chatLogId ORDER BY chatLogId DESC";
	$chatList = db_read($sql);

	require_once("header.php");
?>
<div>
<?php
	foreach ($chatList as $chat) {
?>
<a href="viewLog.php?chatLogId=<?=$chat['chatLogId']?>"><?=date("m/d/Y h:i:sa", $chat['startTime'])?></a> (<?=$chat['lineCount']?> lines)<br/>
<?php
	}
?>
</div>
<?php
	require_once("footer.php");
?>