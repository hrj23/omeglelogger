<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Omegle Logger</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<script type="text/javascript" src="dateformat.js"></script>
		<script type="text/javascript" src="jquery.js"></script>
		<script type="text/javascript" src="jquery.scrollTo-1.4.1.js"></script>
		<style type="text/css">
			div {
				font-family:sans-serif;
				font-size:12px;
			}
			.logBox {
				border: 1px solid black;
				padding: 5px;
				width: 800px;
				height: 400px;
				overflow: auto;
			}
			.stranger1 {
				color: #ff0000;
			}
			.stranger2 {
				color: #0000ff;
			}
			.omegleLogger {
				color: #00cc00;
			}
			#sendMessage {
				width: 800px;
				height: 100px;
			}
		</style>
	</head>
	<body>
	<h3>Omegle Logger</h3>
	<div>Chat Log #: <span id="chatLogId"></span> <a id="logLink" href="#" target="_blank">View Log</a> <input type="button" id="sendLinkBtn" value="Send Log Link"/></div>
	<div class="stranger1">Stranger #1 Id: <span id="chatId1"></span> | <span id="chatId1Typing"></span></div>
	<div class="stranger2">Stranger #2 Id: <span id="chatId2"></span> | <span id="chatId2Typing"></span></div>
	<div>
		<input type="button" id="startChatBtn" value="Start Chat" />
		<input type="button" id="endChatBtn" value="End Chat" />
		<input type="checkbox" id="autoStartChat" checked="checked" /> Automatically start a new chat after one ends.
	</div>
	<div>
		<div id="txtChat" class="logBox"></div>
	</div>
	<div>
		Send to 
		<select id="sendTo">
			<option value="0">Both</option>
			<option value="1">Stranger #1</option>
			<option value="2">Stranger #2</option>
		</select>
		<br/>
		<textarea id="sendMessage"></textarea>
	</div>
<script type="text/javascript">
	$(document).ready(function(){
		var url = "proxy.php";
		var chatLogId = "";
		var chatId1 = "";
		var chatId2 = "";
		var timer = null;
		
		// If we don't get a message for 5 minutes, it might be because we've connected to ourselves. Kill the chat and start a new one
		function resetTimer() {
			killTimer();
			timer = setTimeout(function(){
				logchat(1, "No messages for 5 minutes.");
				endAllChat();
			}, (5 * 60 * 1000));
		}
		
		function killTimer() {
			if (timer) {
				clearTimeout(timer);
			}
		}
		
		function logchat(chatId, message) {
			
			message = message.replace("\n", "<br/>");
			
			var today = new Date();
			var html = "[" + today.format("h:i:s") + "] ";
			if (chatId == chatId1) {
				html = html + "Stranger 1: ";
				spanClass = "stranger1";
			} else if (chatId == chatId2) {
				html = html + "Stranger 2: ";
				spanClass = "stranger2";
			} else if (chatId == 1) {
				html = html + "OmegleLogger: ";
				spanClass = "omegleLogger";
			} else {
				html = html + "Unknown Stranger: ";
			}
			html = html + message;
			
			html = "<div class=\"" + spanClass + "\">" + html + "</div>";
			
			$("#txtChat").html($("#txtChat").html() + html);
			
			$("#txtChat").scrollTo('max');
		}
		
		function update() {
			$("#chatLogId").html(chatLogId);
			$("#chatId1").html(chatId1);
			$("#chatId2").html(chatId2);
			$("#logLink").attr("href", "viewLog.php?chatLogId=" + chatLogId);
		}
		
		function startAllChat() {
			doChat = true;
			$("#txtChat").html("");
			getChatLogId();
		}
		
		function getChatLogId() {
			$.getJSON(url, {action: 'getChatLogId'}, function(data){
				chatLogId = data;
				update();
				startChat1();
				startChat2();
			});
		}
		
		function startChat1() {
			$.getJSON(url, {action: 'start', chatLogId: chatLogId}, function(data){
				chatId1 = data;
				update();
				pollChat(chatId1);
			});
		}
		
		function startChat2() {
			$.getJSON(url, {action: 'start', chatLogId: chatLogId}, function(data){
				chatId2 = data;
				update();
				pollChat(chatId2);
			});
		}

		function pollChat(chatId) {
			if (chatId != "") {
				$.getJSON(url, {action: 'events', chatLogId: chatLogId, id: chatId}, function(data){
					var chatId = "";
					if (data) {
						chatId = data.chatId;
						if (data.messages) {
							jQuery.each(data.messages, function() {
								var arr = this;
								var type = arr[0];
								var value = arr[1];
								
								if (type == "waiting") {
									logchat(chatId, "Waiting for stranger");
									resetTimer();
								} else if (type == "connected") {
									logchat(chatId, "Connected");
									resetTimer();
								} else if (type == "gotMessage") {
									resetTimer();
									if (chatId == chatId1) { // coming from stranger1, send to stranger 2
										$("#chatId1Typing").html("");
										sendMessage(chatId2, value);
									} else { // coming from stranger 2, send to stranger 1
										$("#chatId2Typing").html("");
										sendMessage(chatId1, value);
									}
									
									logchat(chatId, value);
								} else if (type =="typing") {
									if (chatId == chatId1) { // coming from stranger1, send to stranger 2
										$("#chatId1Typing").html("Stranger #1 is typing");
										sendTyping(chatId2);
									} else { // coming from stranger 2, send to stranger 1
										$("#chatId2Typing").html("Stranger #2 is typing");
										sendTyping(chatId1);
									}
								} else if (type == "spam") { // Don't forward spam. might get us banned from omegle
									resetTimer();
									logchat(chatId, "*SPAM* " + value);
								} else if (type == "strangerDisconnected") {
									logchat(chatId, "Stranger Disconnected.");
									endAllChat();
								}
							});
						}
					}					

					update();
					if (chatId == chatId1 || chatId == chatId2) { // Only poll again if the current chat is still running
						pollChat(chatId);
					}
				});
			}
		}
		
		function sendMessage(chatId, message) {
			if (chatId != "") {
				$.getJSON(url, {action: 'send', chatLogId: chatLogId, id: chatId, msg: message}, function(data){
					if (data.status == "win") {
					} else {
						//logchat(chatId, "Error sending previous message");
					}
				});
			}
		}
		
		function sendTyping(chatId) {
			if (chatId != "") {
				$.getJSON(url, {action: 'typing', chatLogId: chatLogId, id: chatId}, function(data){
					if (data.status == "win") {
					} else {
						//logchat(chatId, "Error sending previous message");
					}
				});
			}
		}
		
		function sendSpoofedMessage(chatId, message) {
			if (chatId != "") {
				$.getJSON(url, {action: 'sendSpoofed', chatLogId: chatLogId, id: chatId, msg: message}, function(data){
					if (data.status == "win") {
					} else {
						//logchat(chatId, "Error sending previous message");
					}
				});
			}
		}

		function injectMessage() {
			var msg = $("#sendMessage").val();
			$("#sendMessage").val("");
			if (msg) {
				if ($("#sendTo").val() == 0) {
					logchat(1, "To Stranger #1: " + msg);
					sendSpoofedMessage(chatId1, msg);
					logchat(1, "To Stranger #2: " + msg);
					sendSpoofedMessage(chatId2, msg);
				} else if ($("#sendTo").val() == 1) {
					logchat(1, "To Stranger #1: " + msg);
					sendSpoofedMessage(chatId1, msg);
				} else if ($("#sendTo").val() == 2) {
					logchat(1, "To Stranger #2: " + msg);
					sendSpoofedMessage(chatId2, msg);
				}
			}
		}
		
		function sendLogLink() {
			msg = "<?="http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])?>/viewLog.php?chatLogId=" + chatLogId;
			logchat(1, "To Stranger #1: " + msg);
			sendSpoofedMessage(chatId1, msg);
			logchat(1, "To Stranger #2: " + msg);
			sendSpoofedMessage(chatId2, msg);
		}
		
		function endChat(chatId) {
			$.getJSON(url, {action: 'disconnect', chatLogId: chatLogId, id: chatId}, function(data){
			});
			if (chatId1 == chatId) {
				chatId1 = "";
				$("#chatId1Typing").html("");
			}
			if (chatId2 == chatId) {
				chatId2 = "";
				$("#chatId2Typing").html("");
			}
			update();
		}
		
		function endAllChat() {
			killTimer();
			logchat(1, "Chat Ended.");
			endChat(chatId1);
			endChat(chatId2);
			if ($('#autoStartChat').attr("checked")) {
				logchat(1, "Starting new chat in 5 seconds...");
				t = setTimeout(function(){startAllChat();}, 5000);
			}
		}
		
		function stopChat() {
			endAllChat();
		}
		
		$("#startChatBtn").click(startAllChat);
		$("#endChatBtn").click(stopChat);
	    $("#sendMessage").bind("keypress",
		    function(e) {
		        if (e.keyCode == 13 && !(e.shiftKey || e.altKey || e.metaKey)) {
		            injectMessage();
		            e.preventDefault();
		        }
	    });
	    $("#sendLinkBtn").click(sendLogLink);
	});
</script>

	</body>
</html>
