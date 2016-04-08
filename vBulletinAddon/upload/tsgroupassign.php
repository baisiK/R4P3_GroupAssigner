<?php
require_once('./global.php');
require_once('includes/TeamSpeak3/config.php');
require_once('includes/TeamSpeak3/TeamSpeak3.php');

try {
 	$ts3 = TeamSpeak3::factory("serverquery://".rawurlencode($config['loginname']).":".rawurlencode($config['password'])."@".$config['ip'].":".$config['queryport']."?server_port=".$config['serverport']."&nickname=".rawurlencode($config['displayname']));
} catch (TeamSpeak3_Exception $e) {
 	exit($e);
}

$vbUserInfo = $vbulletin->db->query_first("SELECT * FROM ". TABLE_PREFIX ."user WHERE ipaddress = '".$_SERVER['REMOTE_ADDR']."' LIMIT 1");

foreach ($ts3->clientList() as $ts3_Client) {
	if ($ts3_Client["client_type"] == 0 && $ts3_Client["connection_client_ip"] == $_SERVER['REMOTE_ADDR']) {
		if(strcasecmp($ts3_Client["client_nickname"], $vbUserInfo['username']) !== 0){
			if ($config['send_enable'] == true) {
 				switch ($config['send_method']) {
 					case 'text':
 						$ts3_Client->message($config['send_message_invalid_username']);
 						break;
 					case 'poke':
 						$ts3_Client->poke($config['send_message_invalid_username']);
 						break;
 					default;
 						break;
 				}
 			}
 			exit();
		}
		foreach ($config['groups'] as $group) {
			try {
				$ts3_Client->serverGroupClientAdd($group, $ts3_Client->client_database_id);
 				if ($config['send_enable'] == true) {
 					switch ($config['send_method']) {
 						case 'text':
 							$ts3_Client->message($config['send_message_success'].$ts3->serverGroupGetById($group));
 							break;
 						case 'poke':
 							$ts3_Client->poke($config['send_message_success'].$ts3->serverGroupGetById($group));
 							break;
 						default;
 							break;
 					}
 				}
     	    } catch (Exception $e) {}
        }
    }
}

?>
