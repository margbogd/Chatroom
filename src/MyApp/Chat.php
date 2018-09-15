<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
  protected $clients = null;
  protected $users = null;

  public function __construct() {
    $this->clients = new \SplObjectStorage;
  }

  public function onOpen(ConnectionInterface $conn) {
    // Store the new connection to send messages to later
    $this->clients->attach($conn);
    echo "New connection! ({$conn->resourceId})\n";
  }

  public function onMessage(ConnectionInterface $from, $msg) {
	  
	  foreach($this->clients as $client){
		  $package = json_decode($msg);
		  if(is_object($package) == true){
			  switch($package->type){
				  case 'message':
					if($from != $client){
							if(!empty($package->to_user)){
								foreach($this->users as $resourceId => $user){
									if($resourceId == $from->resourceId)
										continue;
									
									if($user['user']->id == $package->to_user){
											// insert to DB code here 
										$targetClient = $user['client'];
										$targetClient->send($msg);
										return;
									}
								}
							}
						$client->send($msg);	
					}
					break;
					case 'registration':
						$this->users[$from->resourceId] = [
							'user' => $package->user,
							'client' => $from
						];
						break;
					case 'userlist':
                        $list = [];
                        foreach ($this->users as $resourceId => $value) {
                            $list[$resourceId] = $value['user'];
                        }
                        $new_package = [
                            'users' => $list,
                            'type' => 'userlist'
                        ];
                        $new_package = json_encode($new_package);
						$client->send($new_package);
                        break;
			  }
		  }
	  }
  }

  public function onClose(ConnectionInterface $conn) {
    // The connection is closed, remove it, as we can no longer send it messages
    unset($this->users[$conn->resourceId]);
	$new_package = [
                            'users' => $this->users,
                            'type' => 'userlist'
                        ];
	$new_package = json_encode($new_package);
	
	foreach ($this->clients as $client){
		$client->send($new_package);
	}
	$this->clients->detach($conn);
    echo "Connection {$conn->resourceId} has disconnected\n";
  }

  public function onError(ConnectionInterface $conn, \Exception $e) {
	unset($this->users[$conn->resourceId]);
    echo "An error has occurred: {$e->getMessage()}\n";
    $conn->close();
  }
  
}