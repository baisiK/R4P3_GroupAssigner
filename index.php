<?php
require("config.php");
require('libraries/TeamSpeak3/TeamSpeak3.php');

function log_write($message, $type)
{
	$enable_log = true;
	if ($enable_log) {
		$createlog = false;
	    $log = "[".date("h:i:s A")."][".$type."] ".$message."\n";
	    $logfile = "log.txt";
	    if (!file_exists($logfile)){
			$createlog = true;
	    }
	    $openfile = fOpen($logfile , "a+");
	    if ($createlog){
			fWrite($openfile, "[".date("h:i:s A")."][Information] Creating new log file (".$logfile.")\n");
	    }
	    fWrite($openfile, $log);
	    fClose($openfile);
	}
		return true;
}
function getUserIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            return $_SERVER['HTTP_CLIENT_IP'];
        else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(!empty($_SERVER['HTTP_X_FORWARDED']))
            return $_SERVER['HTTP_X_FORWARDED'];
        else if(!empty($_SERVER['HTTP_FORWARDED_FOR']))
            return $_SERVER['HTTP_FORWARDED_FOR'];
        else if(!empty($_SERVER['HTTP_FORWARDED']))
            return $_SERVER['HTTP_FORWARDED'];
        else if(!empty($_SERVER['REMOTE_ADDR']))
            return $_SERVER['REMOTE_ADDR'];
        else
            return false;
    }



if (!empty($config['ip']) and !empty($config['queryport']) and !empty($config['serverport']) and !empty($config['loginname']) and !empty($config['qpassword']) and !empty($config['displayname'])) {
 	try {
     	$ts3 = TeamSpeak3::factory('serverquery://'.rawurlencode($config['loginname']).':'.rawurlencode($config['qpassword']).'@'.rawurlencode($config['ip']).':'.rawurlencode($config['queryport']).'?server_port='.rawurlencode($config['serverport']).'&nickname='.rawurlencode($config['displayname']));
 	} catch (TeamSpeak3_Exception $e) {
     	exit($e);
 	}
 	foreach ($ts3->clientList() as $ts3_Client) {
 		if ($ts3_Client["client_type"] == 0 and $ts3_Client["connection_client_ip"] == getUserIp()) {
 			foreach ($config['groups'] as $group) {
 				try {
   			$ts3_Client->serverGroupClientAdd(intval($group), $ts3_Client->client_database_id);
     			if ($config['send_enable'] == true) {
	   				if ($config['send_method'] == 'text') {
	   					$ts3_Client->message($config['send_message'].$ts3->serverGroupGetById($group));
	   				} else if ($config['send_method'] == 'poke'){
	   					$ts3_Client->poke($config['send_message'].$ts3->serverGroupGetById($group));
	   			}
	 				}
	 				log_write($ts3_Client->client_nickname." got assigned to a group (".$ts3->serverGroupGetById($group)." / ".$group.")", "Information");
  	     	    } catch (Exception $e) {}
            }
        }
  	}
}
?>
