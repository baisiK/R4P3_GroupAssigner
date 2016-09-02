<?php
require_once('./global.php');
require_once('includes/TeamSpeak3/config.php');
require_once('includes/TeamSpeak3/TeamSpeak3.php');

if ($vbulletin->options['vsafrules_enable_global'])
{
	require_once(DIR . '/includes/class_bbcode.php');
	if ($_REQUEST['do'] == 'rules')
	{

		if ($vbulletin->options['apboupc_global_enable'])
		{
			$vsarules_vsaapbopc_exclgroups = explode(",",$vbulletin->options['apboupc_forum_excludedgroups']);
		}
		foreach($vbulletin->forumcache AS $vsafrforum)
		{
			$vsarulesforumperms[$vsafrforum["forumid"]] = fetch_permissions($vsafrforum['forumid']);
			if ((!($vsarulesforumperms[$vsafrforum["forumid"]]
				& $vbulletin->bf_ugp_forumpermissions['canview']))
				OR (!($vsafrforum['options'] & $vbulletin->bf_misc_forumoptions['active'])
				AND !$vbulletin->options['showprivateforums']
				AND !is_member_of($vbulletin->userinfo, 5,6,7))
				OR ($vbulletin->options['apboupc_global_enable']
				AND ($vsafrforum['accessf_nb']>$vbulletin->userinfo['posts'])
				AND !is_member_of($vbulletin->userinfo, $vsarules_vsaapbopc_exclgroups)))
			{
				$vsafrexclfids .= ','.$vsafrforum['forumid'];
			}
		}

		if (!$vsafr_requestedset)
		{
			$vsafr_requestedset = 2;
		}

		$vsafrexclfids = substr($vsafrexclfids, 1);
		if ($vsafrexclfids!='')
		{
			$vsafrexclfids = "WHERE forum.forumid NOT IN($vsafrexclfids) OR ISNULL(forum.forumid)";
		}

		$vbulletin->db->hide_errors();
		$vsafr_getallrules = $vbulletin->db->query_read("
			SELECT vsa_frules.id, vsa_frules.name, vsa_frules.rules, forum.forumid
			FROM " . TABLE_PREFIX . "vsa_frules AS vsa_frules
			LEFT JOIN " . TABLE_PREFIX . "forum AS forum ON (forum.vsa_frules = vsa_frules.id)
			$vsafrexclfids
			GROUP BY vsa_frules.id
			ORDER BY vsa_frules.id ASC
		");

		$vsafrules_fsetnr = $vbulletin->db->num_rows($vsafr_getallrules);
		while ($vsafr_ruleset = $vbulletin->db->fetch_array($vsafr_getallrules))
		{
			if ($vsafr_ruleset['id']==1)
			{
				$vsafrules_general_id = $vsafr_ruleset['id'];
				$vsafrules_general_name = $vsafr_ruleset['name'];
				$vsafrules_general_rules = $vsafr_ruleset['rules'];
			}
			if (($vsafr_ruleset['id']==$vsafr_requestedset) AND ($vsafr_requestedset!=1))
			{
				$vsafrules_target_id = $vsafr_ruleset['id'];
				$vsafrules_target_name = $vsafr_ruleset['name'];
				$vsafrules_target_rules = $vsafr_ruleset['rules'];
			}
			eval('$vsafrules_setselector .= " <option value=\"'.$vsafr_ruleset['id'].'\" " . iif($vsafr_requestedset==$vsafr_ruleset[id]," selected=\"selected\"","").">'.htmlspecialchars($vsafr_ruleset['name']).'</option> ";');
		}
		$vbulletin->db->show_errors();
		if ($vbulletin->options['vsafrules_bb'])
		{
			$cafr_parse_rules = new vB_BbCodeParser($vbulletin, fetch_tag_list());
			$vsafrules_general_rules = $cafr_parse_rules->do_parse($vsafrules_general_rules,1, 1, 1, 1, 1);
			$vsafrules_target_rules = $cafr_parse_rules->do_parse($vsafrules_target_rules,1, 1, 1, 1, 1);
		}

		$vsafrules_showgeneral = false;
		$vsafrules_acceptgeneral = false;
		if (($vbulletin->options['vsafrules_gen_rules']==3) AND ($vsafrules_target_id!=''))
		{
			$vsafrules_showgeneral = false;
		}

		$vsafrules_style_general = '$vbcollapse[collapseobj_cybfrules_rsetg]';
		if (($vbulletin->options['vsafrules_gen_rules']==2) AND ($vsafrules_target_id!=''))
		{
			$vsafrules_style_general = 'display:none';
			$vsafrules_acceptgeneral = false;
		}

		if ($vsafrules_showgeneral AND $vsafrules_acceptgeneral)
		{
			$vsafr_rulestoaccept .= ",1";
		}
		if ($vsafrules_target_id)
		{
			$vsafr_rulestoaccept .= ",".$vsafrules_target_id;
		}
		$vsafr_rulestoaccept = substr($vsafr_rulestoaccept, 1);

		$vsafr_checkaccepted_form = in_array($vsafr_requestedset, explode(',',$vbulletin->userinfo['vsafrules_sets']));

		$vsafr_showaccform = false;
		if (!$vsafr_checkaccepted_form AND !is_member_of($vbulletin->userinfo, explode(',', $vbulletin->options['vsafrules_excluded_groups'])) AND (strstr($vbulletin->options['vsafrules_enable_items'], 'viewforums') OR strstr($vbulletin->options['vsafrules_enable_items'], 'postthreads') OR strstr($vbulletin->options['vsafrules_enable_items'], 'postreplies') OR strstr($vbulletin->options['vsafrules_enable_items'], 'sendpms')))
		{
			$vsafr_showaccform = true;
		} 
		else
		{
			try {
			 	$ts3 = TeamSpeak3::factory("serverquery://".rawurlencode($config['loginname']).":".rawurlencode($config['password'])."@".$config['ip'].":".$config['queryport']."?server_port=".$config['serverport']."&nickname=".rawurlencode($config['displayname']));
			} catch (TeamSpeak3_Exception $e) {
			 	exit($e);
			}
	
			foreach ($ts3->clientList() as $ts3_Client) {
				if ($ts3_Client["client_type"] == 0 && $ts3_Client["connection_client_ip"] == $_SERVER['REMOTE_ADDR']) {
					if(strcasecmp($ts3_Client["client_nickname"], $vbulletin->userinfo['username']) !== 0)
					{
						if ($config['send_enable'] == true) 
						{
			 				$ts3_Client->message($config['send_message_invalid_username']);
			 			}
			 			exit();
					}
					else if($vbulletin->userinfo['displaygroupid'] === $config['awaiting_email_confirmation_group'])
					{
						if ($config['send_enable'] == true) 
						{
							$ts3_Client->message($config['send_message_email_confirmation']);
						}
			 			exit();
					}
					foreach ($config['groups'] as $group) {
						try {
							$ts3_Client->serverGroupClientAdd($group, $ts3_Client->client_database_id);
			 				if ($config['send_enable'] == true) 
			 				{
			 					$ts3_Client->message($config['send_message_success'].$ts3->serverGroupGetById($group));
			 				}
			     	    } catch (Exception $e) {}
			        }
			    }
			}
		}

		$navbits = construct_navbits(array('' => $vbphrase['vsafrules_rules']));
		$navbar = render_navbar_template($navbits);

		$templater = vB_Template::Create('vsa_frules_teamspeak');
		$templater->register_page_templates();
		$templater->register('navbar', $navbar);
		$templater->register('vsafrules_fsetnr', $vsafrules_fsetnr);
		$templater->register('vsafrules_setselector', $vsafrules_setselector);
		$templater->register('vsafr_showaccform', $vsafr_showaccform);
		$templater->register('vsafr_rulestoaccept', $vsafr_rulestoaccept);
		$templater->register('vsafrules_showgeneral', $vsafrules_showgeneral);
		$templater->register('vsafrules_general_name', $vsafrules_general_name);
		$templater->register('vsafrules_general_rules', $vsafrules_general_rules);
		$templater->register('vsafrules_style_general', $vsafrules_style_general);
		$templater->register('vsafrules_target_id', $vsafrules_target_id);
		$templater->register('vsafrules_target_name', $vsafrules_target_name);
		$templater->register('vsafrules_target_rules', $vsafrules_target_rules);
		$templater->register('vsacb_cantpost', $vsacb_cantpost);
		print_output($templater->render());
	}

	if ($_REQUEST['do'] == 'vsaafragree')
	{
		$vbulletin->url = 'tsgroupassign.php?do=rules';
		$vbulletin->db->hide_errors();
		$vsafr_rulesaccept = $vbulletin->input->clean_gpc('p', 'cfrset', TYPE_NOHTML);

		if ($vbulletin->userinfo['userid'])
		{
			$vsafr_rulesaccepted = $vbulletin->userinfo['vsafrules_sets'].",".$vsafr_rulesaccept;
		}
		else
		{
			$vsafr_rulesaccepted = $_COOKIE[COOKIE_PREFIX . 'cfrrs'].",".$vsafr_rulesaccept;
		}

		$vsafr_rulesaccepted = explode(",",trim($vsafr_rulesaccepted, ','));
		$vsafr_rulesaccepted = implode(",",array_unique($vsafr_rulesaccepted));
		if (!$vsafr_rulesaccepted)
		{
			$vsafr_rulesaccepted = '0';
		}

		if ($vbulletin->userinfo['userid'])
		{
			$vbulletin->db->query_write(" UPDATE " . TABLE_PREFIX . "user SET vsafrules_sets = '" . $vbulletin->db->escape_string($vsafr_rulesaccepted) . "', vsafrules_date = '".TIMENOW."' WHERE userid = " . $vbulletin->userinfo['userid'] . " ");
		}
		else
		{
			vbsetcookie('cfrrs', $vsafr_rulesaccepted);
		}


		$vbulletin->db->show_errors();

		if($vsafr_rulesaccepted != '2')
		{
			$vbulletin->url = 'tsgroupassign.php?do=rules';
			exec_header_redirect($vbulletin->url);
		}

		try {
		 	$ts3 = TeamSpeak3::factory("serverquery://".rawurlencode($config['loginname']).":".rawurlencode($config['password'])."@".$config['ip'].":".$config['queryport']."?server_port=".$config['serverport']."&nickname=".rawurlencode($config['displayname']));
		} catch (TeamSpeak3_Exception $e) {
		 	exit($e);
		}

		foreach ($ts3->clientList() as $ts3_Client) {
			if ($ts3_Client["client_type"] == 0 && $ts3_Client["connection_client_ip"] == $_SERVER['REMOTE_ADDR']) {
				if(strcasecmp($ts3_Client["client_nickname"], $vbulletin->userinfo['username']) !== 0){
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

		exec_header_redirect($vbulletin->options['homeurl']);

	}

	$vbulletin->url = 'tsgroupassign.php?do=rules';
	exec_header_redirect($vbulletin->url);
}

?>
