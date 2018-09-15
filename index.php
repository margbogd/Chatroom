<?php
require 'vendor/autoload.php';

/**
 * Create a fake identity
 */
$faker = Faker\Factory::create();

$user = [
    'username' => $faker->name /* Give the user a random name */
];
$user['id'] = md5($user['username']);
?>

<!DOCTYPE html>
<html>
	<head>
		<title><?php print $user['username']; ?></title>
		<meta charset='utf-8' />		
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
		<script src="http://code.jquery.com/jquery-2.1.0-rc1.min.js"></script>
		<link rel="stylesheet" type="text/css" href="index.css">
		<link rel="stylesheet" type="text/css" href="popup.css">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
		<script src="popup.js"></script>
		

		<style type="text/css" media="screen">
			li span {
			  color: #66ccff;
			}
			.success {
			  color: green;
			}
			.error {
			  color: red;
			}

			#myMessage{
				color: lightskyblue;
				font-style: italic;
				font-weight: bold;
			}
			#othersMessage{
				color: rebeccapurple;
				font-style: italic;
				font-weight: bold;
			}

			#body {
				position: relative;
				margin-left: 50px;
				float: left;
				padding:0px;
			}

			#chat{
				width:800px;
				height: 400px;
				border:5px solid #CCC;
				background-color: white;
				overflow-y: scroll;
			}

			.btn{
				position:relative;
			}

			.user_list {
				width: 20%;
				height: 500px !important;
				border: 1px solid black;
				text-align: left;
				padding: 5px;
				float: left;
			}
		</style>

	</head>
	<body>
		<i class="fa fa-wechat" style="font-size:48px;color:red"></i>
		<ul>
		  <li><a class="active" href="login.html">Login</a></li>
		  <li><a class="active" href="signUp.html">Sign Up</a></li>
		</ul>
		
		
		<i><h4 id="message"></h4></i><!--System Message-->
		
	<div id="global_chat_body">
		<div id="chat"></div><br />
		<div id="inputs">
			<div class="input-group col-xs-4">
				<span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
				<input class="form-control input-sm " type="text" readonly id="username" name="message" value="<?php print $user['username']; ?>">
				<input class="form-control input-sm " type="hidden" id="userid" name="message" value="<?php print $user['id']; ?>">
			</div>
				<div class="input-group col-xs-4" id='message_input'>
					<span class="input-group-addon">Text</span>
					<textarea rows='3' cols='3' wrap='hard' maxlength='150' class="form-control input-lg " type="text" id="global_chat_input" name="message" value="" placeholder="Enter a Message"></textarea>
				</div>
				<br>
					<button type="button" id="global_chat_btn" class="btn btn-info" name="Submit" >Send message!</button>
		</div>
	</div>	

		<div class="chat-box">
			<div class="chat-header">Online Users</div>
			<div class="chat-body"></div>
		</div>
		
<script type="text/javascript">
	
	var chat_user  = JSON.parse('<?php print addslashes(json_encode($user)); ?>');

    $(document).ready(function() {

      var conn = new WebSocket('ws://localhost:8080');
      var mess = document.getElementById('message');

	  var body = document.getElementById('global_chat_body');
	  var chat = document.getElementById('chat');
	  var chatInput = document.getElementById('global_chat_input');
	  var messageInputDiv = document.getElementById('message_input');

	  //var privateChat = document.getElementById('popup-box');	  
	  var msgInput = document.getElementsByClassName('msg-input');
      
      var connected = false;
	  
	  var username = document.getElementById('username');
	  var user_list = document.getElementsByClassName('chat-body')[0]; 

	  var messageNotification = new Audio('./audio/notification.mp3');
	  var messageSent = new Audio('./audio/sent.mp3');
	  var messageReceived = new Audio('./audio/received.mp3');
		  
      var m = function(string, cname) {
        mess.className = cname;
        mess.innerHTML = string;
      }
     	 
      conn.onopen = function(e) {
        m("Connection established!", 'success');
        connected = true;
		register_client();
		request_userlist();
      };

      conn.onclose = function(e) {
        m("Connection closed!", 'error');
        connected = false;
      };
      
      conn.onmessage = function(e) {
        var data = JSON.parse(e.data);
		if(data.type == 'message'){
			if(data.to_user==null){
				newChat(data);
				//messageNotification.play();
				messageReceived.play();
			}else{
				var x = data.userid;
				newPrivateChat(data,x);
				//messageNotification.play();
				messageReceived.play();
			}		
		}else if(data.type == 'userlist'){
			users_output(data.users);
		}
      };
	
	
	function clear_userlist(){
	
		while (user_list.firstChild){
			user_list.removeChild(user_list.firstChild);
		}
	}
	
	function register_client(){
		var package = {
			'user': chat_user,
			'type': 'registration',
		};
		package = JSON.stringify(package);
		conn.send(package);
	}
	
	function request_userlist(){
		var package = {
			'user': chat_user,
			'type': 'userlist',
		};
		package = JSON.stringify(package);
		conn.send(package);
	}
	
	function users_output(users){
		clear_userlist();
		for(var connid in users){
			if(users.hasOwnProperty(connid)){
				var user = users[connid];

				if(user.id == chat_user.id){
					// your id doesnt show on chat bar
				}else{
					/*user_list.innerHTML +=  '<div class="sidebar-name">'
            							+'<a href="javascript:register_popup('+'\''+user.id+'\''+','+'\''+user.username+'\''+');">'
            							+'<span>'+user.username+'</span>'
            							+' </a>'
            							+'</div>';*/

            		// the code below is for the toggle chat bar
            		user_list.innerHTML += '<div class="user">'
            							+'<a href="javascript:register_popup('+'\''+user.id+'\''+','+'\''+user.username+'\''+');">'
            							+'<span>'+user.username+'</span>'
            							+' </a>'
            							+'</div>';
				}
			}
		}
	
	}

	$('.chat-header').click(function(){
		$('.chat-body').slideToggle('slow');
	});

	  // Global Message
      $('#global_chat_input').keyup(function(event){
      		event.preventDefault();
      		if(event.keyCode == 13){
      			$('#global_chat_btn').click();
      		}
      });

      $(document).on('click', '#global_chat_btn', function() {
        if (username.value == '' || chatInput.value === '') {
          //alert('All Fields must be filled');
		 messageInputDiv.setAttribute("class","input-group col-xs-4 has-error");
          return;
        }else if(!connected) {
          //alert('connection is closed');
          return false;
        }
		messageInputDiv.setAttribute("class","input-group col-xs-4");
		var timeSent = new Date().timeNow();

        var data = {
			'user': username.value,
			'message': chatInput.value,
			'to_user': null,
			'type': 'message',
			'time': timeSent
			};
        newChat(data);
        messageSent.play();
        conn.send(JSON.stringify(data));
        chatInput.value = '';
        chatInput.focus();
		
        return false;
      });
	  
	  //Private message
	  
	  $(document).on('keypress' ,'.msg-input',function(event){
      		if(event.keyCode == 13){

      			if (username.value == '' || event.target.value == '') {
		          alert('All Fields must be filled');
		          return;
		        }else if(!connected) {
		          alert('connection is closed');
		          return false;
		        }

		        var userID = event.target.dataset.userid;
		        var to_user = event.target.id;

		        var timeSent = new Date().timeNow();

			    var data = {
				'user': username.value,
				'userid': chat_user.id,
				'message': event.target.value,
				'to_user': userID,
				'type': 'message',
				'time': timeSent
				};
				newPrivateChat(data,userID);
				messageSent.play();
		        conn.send(JSON.stringify(data));
		        event.target.value = '';
		        event.target.focus();
		        return false;

      		}
      });
	  	  
      function newChat(data) {
		if(data.user!=chat_user.username){
			var template = "<span id='othersMessage'>"+data.user+" : </span><i>"+data.message+"</i>";
		}else{
			var template = "<span id='myMessage'>"+data.user+" : </span><i>"+data.message+"</i>";
		}
        chat.innerHTML += template;
		var template = "<b> {"+data.time+"}</b><br>";
		chat.innerHTML += template;
		chat.scrollTop = chat.scrollHeight;
      }
	  
	  function newPrivateChat(data,userID) {
	  	var userDiv = document.getElementsByClassName('user');
	  	var userString = data.user;
	  	var found;

		if(data.user!=chat_user.username){
			/*for(var i=0;i < userDiv.length;i++){
				if(userDiv[i].name == userString){
					found = userDiv[i];
					if(found.querySelector('.badge') !== null){
						var msgNumber = found.querySelector('.badge').innerHTML;
						var increasedNumber = parseInt(msgNumber,10);
						increasedNumber++;
						found.querySelector('.badge').innerHTML = increasedNumber;
					}else{
						var elm = document.createElement('SPAN');
						elm.className = 'badge';
						elm.innerHTML = '1';
						found.appendChild(elm);
					}
					break;
				}
			}*/
			register_popup(userID,data.user);
			var popupBox = document.getElementById(userID);
			var msgBox = popupBox.querySelector('.popup-messages');
			var elm = document.createElement('DIV');
			elm.className = 'msg-a';
			elm.innerHTML = data.message;
			msgBox.appendChild(elm);
		}else{
			var popupBox = document.getElementById(userID);
			var msgBox = popupBox.querySelector('.popup-messages');
			var elm = document.createElement('DIV');
			elm.className = 'msg-b';
			elm.innerHTML = data.message;
			msgBox.appendChild(elm);
			//var msg = '<div class="msg-b">'+data.message+'</div>';
			//msgBox.insertBefore(msg,document.getElementsByClassName('msg-insert'));
			//$('<div class="msg-b">'+data.message+'</div>').insertBefore('.msg-insert');
		}
		//var template = "<i>"+data.time+"</i>"

		msgBox.scrollTop = msgBox.scrollHeight;
		//privateChat.scrollTop = privateChat.scrollHeight;
      }

	  Date.prototype.timeNow = function () {
			return ((this.getHours() < 10)?"0":"") + this.getHours() +":"+ ((this.getMinutes() < 10)?"0":"") + this.getMinutes() +":"+ ((this.getSeconds() < 10)?"0":"") + this.getSeconds();
		}  
	  
    });

  </script>
		
	</body>
<html>	