<?php
/**
 * This acp class manages DKP pools and Loot systems
 * 
 * @package bbDKP.acp
 * @author Sajaki
 * @version $Id$
 * @copyright (c) 2009 bbdkp http://code.google.com/p/bbdkp/
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * 
 */

/**
 * @ignore
 */
if (! defined ( 'IN_PHPBB' ))
{
	exit ();
}
if (! defined ( 'EMED_BBDKP' ))
{
	$user->add_lang ( array ('mods/dkp_admin' ) );
	trigger_error ( $user->lang ['BBDKPDISABLED'], E_USER_WARNING );
}

class acp_dkp_sys extends bbDKP_Admin
{
	var $u_action;
	
	function error_check()
	{
		// we want the dkp name to be filled. 
		global $user;
		$this->fv->is_filled ( array (request_var ( 'dkpsys_name', ' ' ) => $user->lang ['FV_REQUIRED_NAME'] ) );
		return $this->fv->is_error ();
	}
	
	function main($id, $mode)
	{
		global $db, $user, $auth, $template, $sid, $cache;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
		$user->add_lang ( array ('mods/dkp_admin' ) );
		
		$link = '<br /><a href="' . append_sid ( "{$phpbb_admin_path}index.$phpEx", "i=dkp_sys&amp;mode=listdkpsys" ) . '"><h3>'. $user->lang['RETURN_DKPPOOLINDEX'].'</h3></a>';
		
		switch ($mode)
		{
			case 'adddkpsys' :
				$update = false;
				if ((isset ( $_GET [URI_DKPSYS] )))
				{
					// GET existing 
					$this->url_id = request_var ( URI_DKPSYS, 0 );
					$update = true;
					$sql = 'SELECT dkpsys_id, dkpsys_name, dkpsys_status
								FROM ' . DKPSYS_TABLE . '
								WHERE dkpsys_id = ' . (int) $this->url_id;
					$result = $db->sql_query ( $sql );
					if (! $row = $db->sql_fetchrow ( $result ))
					{
						trigger_error ( $user->lang ['ERROR_INVALID_DKPSYSTEM_PROVIDED'], E_USER_WARNING );
					}
					$db->sql_freeresult ( $result );
					$this->dkpsys = array (
						'dkpsys_id' => $row['dkpsys_id'], 
						'dkpsys_name' => $row['dkpsys_name'], 
						'dkpsys_status' => $row['dkpsys_status'] 
					 );
				} 
				else
				{
					// BLANK PAGE or SUBMIT
					$this->dkpsys = array (	
						'dkpsys_name' => utf8_normalize_nfc ( request_var ( 'dkpsys_name', '', true ) ), 
						'dkpsys_status' => request_var ( 'dkpsys_status', 'Y' ) );
				}
				
				$add = (isset ( $_POST ['add'] )) ? true : false;
				$submit = (isset ( $_POST ['update'] )) ? true : false;
   
                if ( $add || $submit)
                {
                  	if (!check_form_key('adddkpsys'))
					{
						trigger_error('FORM_INVALID');
					}
      			}
        			
				if ($add)
				{
					$this->dkpsys = array (
						'dkpsys_name' => utf8_normalize_nfc (request_var ( 'dkpsys_name', '', true )), 
						'dkpsys_status' => request_var ( 'dkpsys_status', 'Y' ));
					
					if ($this->dkpsys ['dkpsys_name'] == 'N' || $this->dkpsys ['dkpsys_status'] == 'Y')
					// add the dkp system
					{
						// "id" will be generated by sql - is autoincremented ! 
						// If table is created with autoincrement, or else this will fail
						$query = $db->sql_build_array ( 
							'INSERT', 
							array (
								'dkpsys_name' => $this->dkpsys ['dkpsys_name'], 
								'dkpsys_status' => $this->dkpsys ['dkpsys_status'], 
								'dkpsys_addedby' => $user->data ['username'], 
								'dkpsys_default' => 'N' ) );
						
						$db->sql_query ( 'INSERT INTO ' . DKPSYS_TABLE . $query );
						$log_action = array (
							'header' => 'L_ACTION_DKPSYS_ADDED', 
							'L_DKPSYS_NAME' => $this->dkpsys ['dkpsys_name'], 
							'L_DKPSYS_STATUS' => $this->dkpsys ['dkpsys_status'], 
							'L_ADDED_BY' => $user->data ['username'] );
						
						$this->log_insert ( array (
							'log_type' => $log_action ['header'], 
							'log_action' => $log_action ) );
						
						$success_message = sprintf ( $user->lang ['ADMIN_ADD_DKPSYS_SUCCESS'], $this->dkpsys ['dkpsys_name'] );
						trigger_error ( $success_message . $link );
					} 
					else
					{
						// status incorrect
						trigger_error ( $user->lang ['FV_DKPSTATUSYN'] . $link, E_USER_WARNING );
					}
				}
				
				if ($submit)
				{
					// update
					$this->url_id = request_var ( 'hidden_id', 0 );
					$this->dkpsys = array (
						'dkpsys_name' => utf8_normalize_nfc ( request_var ( 'dkpsys_name', ' ', true ) ), 
						'dkpsys_status' => request_var ('dkpsys_status', 'N'));
					
					// get the old name, status 
					$sql = 'SELECT dkpsys_name, dkpsys_status
							FROM ' . DKPSYS_TABLE . ' WHERE dkpsys_id=' . (int) $this->url_id;
					$result = $db->sql_query ( $sql );
					while ( $row = $db->sql_fetchrow ( $result ) )
					{
						$this->old_dkpsys = array (
							'dkpsys_name' => $row['dkpsys_name'], 
							'dkpsys_status' => $row['dkpsys_status'] );
					}
					$db->sql_freeresult ( $result );
					
					// Update the dkp sysname, status 
					$query = $db->sql_build_array ( 
							'UPDATE', 
							array (
								'dkpsys_name' => $this->dkpsys ['dkpsys_name'], 
								'dkpsys_status' => $this->dkpsys ['dkpsys_status'] ) );
					$sql = 'UPDATE ' . DKPSYS_TABLE . ' SET ' . $query . ' WHERE dkpsys_id=' . (int) $this->url_id;
					$db->sql_query ( $sql );
					
					// Logging, put old & new
					$log_action = array (
						'header' => 'L_ACTION_DKPSYS_UPDATED', 
						'id' => $this->url_id, 
						'L_DKPSYSNAME_BEFORE' => $this->old_dkpsys ['dkpsys_name'], 
						'L_DKPSYSSTATUS_BEFORE' => $this->old_dkpsys ['dkpsys_status'], 
						'L_DKPSYSNAME_AFTER' => $this->dkpsys ['dkpsys_name'], 
						'L_DKPSYSSTATUS_AFTER' => $this->dkpsys ['dkpsys_status'], 
						'L_DKPSYSUPDATED_BY' => $user->data ['username'] );
					
					$this->log_insert ( 
						array (
							'log_type' => $log_action ['header'], 
							'log_action' => $log_action ) );
					
					$success_message = sprintf ( $user->lang ['ADMIN_UPDATE_DKPSYS_SUCCESS'], $this->url_id, $this->dkpsys ['dkpsys_name'], $this->dkpsys ['dkpsys_status'] );
					trigger_error ( $success_message . $link );
				
				}
				
				$form_key = 'adddkpsys';
				add_form_key($form_key);
		
				$template->assign_vars ( array (
					'DKPSYS_ID' => $this->url_id, 
					'L_TITLE' => $user->lang ['ACP_ADDDKPSYS'], 
					'L_EXPLAIN' => $user->lang ['ACP_ADDDKPSYS_EXPLAIN'], 
					// Form vars
					'DKPSYS_ID' => $this->url_id, 
					// Form values
					'DKPSYS_NAME' => $this->dkpsys ['dkpsys_name'], 'DKPSYS_STATUS' => $this->dkpsys ['dkpsys_status'], 
					// Form validation
					'FV_NAME' => $this->fv->generate_error ( 'dkpsys_name' ), 
					'FV_VALUE' => $this->fv->generate_error ( 'dkpsys_status' ), 
					// Javascript messages
					'MSG_NAME_EMPTY' => $user->lang ['FV_REQUIRED_NAME'], 'MSG_STATUS_EMPTY' => $user->lang ['FV_REQUIRED_STATUS'], 
					// Buttons
					'S_ADD' => (! $this->url_id) ? true : false ) );

				$this->page_title = 'ACP_ADDDKPSYS';
				$this->tpl_name = 'dkp/acp_' . $mode;
				
				break;
			
			case 'listdkpsys' :
				// add dkpsys button redirect
				$showadd = (isset ( $_POST ['dkpsysadd'] )) ? true : false;
				$delete = (isset ( $_GET ['delete'] ) && isset ( $_GET [URI_DKPSYS] )) ? true : false;
				
				if ($showadd)
				{
					redirect ( append_sid ( "{$phpbb_admin_path}index.$phpEx", "i=dkp_sys&amp;mode=adddkpsys" ) );
					break;
				}
				if ($delete)
				{
					if (confirm_box ( true ))
					{
						$this->url_id = request_var ( URI_DKPSYS, 0 );
						$this->dkpsys = array (
							'dkpsys_name' => utf8_normalize_nfc ( request_var ( 'dkpsys_name', ' ', true ) ), 
							'dkpsys_status' => request_var ( 'dkpsys_status', 'N' ) );
						$sql = 'SELECT * FROM ' . RAIDS_TABLE . ' a, ' . EVENTS_TABLE . ' b WHERE b.event_id = a.event_id and b.event_dkpid = ' . (int) $this->url_id;
						
						// check for existing events, raids
						$result = $db->sql_query ( $sql );
						if ($row = $db->sql_fetchrow ( $result ))
						{
							trigger_error ( $user->lang ['FV_RAIDEXIST'], E_USER_WARNING );
						} 
						else
						{
							// no events found ?
							$sql = 'SELECT * FROM ' . EVENTS_TABLE . ' WHERE event_dkpid = ' . (int) $this->url_id;

							$result = $db->sql_query ( $sql );
							if ($row = $db->sql_fetchrow ( $result ))
							{
								trigger_error ( $user->lang ['FV_EVENTEXIST'], E_USER_WARNING );
								// there is a fk event_dkpid 'on delete restrict' on dkpsys_id
								// so you cant delete a dkpsys when theres still child event records
							} 
							else
							{
								$sql = 'DELETE FROM ' . DKPSYS_TABLE . ' WHERE dkpsys_id = ' . (int) $this->url_id;
								$db->sql_query ( $sql );
								$log_action = array (
									'header' => 'L_ACTION_DKPSYS_DELETED', 
									'id' => $this->url_id, 
									'L_DKPSYS_NAME' => $this->dkpsys ['dkpsys_name'], 
									'L_DKPSYS_STATUS' => $this->dkpsys ['dkpsys_status']);
								$this->log_insert ( array (
									'log_type' => $log_action ['header'], 
									'log_action' => $log_action ));
								$success_message = sprintf ($user->lang ['ADMIN_DELETE_DKPSYS_SUCCESS'], $this->dkpsys ['dkpsys_name'] );
								trigger_error ($success_message . $link );
							}
						}
					} 
					else
					{
						$s_hidden_fields = build_hidden_fields ( array (
							'delete' => true, 
							'dkpsys_id' => request_var ( URI_DKPSYS, 0 ) ) );
						$template->assign_vars ( array ('S_HIDDEN_FIELDS' => $s_hidden_fields ) );
						confirm_box ( false, $user->lang ['CONFIRM_DELETE_DKPSYS'], $s_hidden_fields );
					}
				}
				
				$sort_order = array (0 => array ('dkpsys_name', 'dkpsys_name desc' ), 1 => array ('dkpsys_id desc', 'dkpsys_id' ) );
				$current_order = switch_order ( $sort_order );
				
				$sql1 = 'SELECT * FROM ' . DKPSYS_TABLE;
				$result1 = $db->sql_query ( $sql1 );
				$rows1 = $db->sql_fetchrowset ( $result1 );
				$db->sql_freeresult ( $result1 );
				$total_dkpsys = count ( $rows1 );
				$start = request_var ( 'start', 0 );
				$sql = 'SELECT dkpsys_id, dkpsys_name, dkpsys_status , dkpsys_default FROM ' . DKPSYS_TABLE . ' ORDER BY ' . $current_order ['sql'];
				$dkpsys_result = $db->sql_query_limit ( $sql, $config ['bbdkp_user_elimit'], $start );
				if (! $dkpsys_result)
				{
					trigger_error ( $user->lang ['ERROR_INVALID_DKPSYSTEM_PROVIDED'], E_USER_WARNING );
				}
				while ( $dkpsys = $db->sql_fetchrow ( $dkpsys_result ) )
				{
					$template->assign_block_vars ( 'dkpsys_row', 
						array (
							'U_VIEW_DKPSYS' => append_sid ( "{$phpbb_admin_path}index.$phpEx", "i=dkp_sys&amp;mode=adddkpsys&amp;" . URI_DKPSYS . "={$dkpsys['dkpsys_id']}" ), 
							'U_DELETE_DKPSYS' => append_sid ( "{$phpbb_admin_path}index.$phpEx", "i=dkp_sys&amp;mode=listdkpsys&amp;delete=1&amp;" . URI_DKPSYS . "={$dkpsys['dkpsys_id']}" ), 
							'NAME' => $dkpsys ['dkpsys_name'], 
							'STATUS' => $dkpsys ['dkpsys_status'], 
							'DEFAULT' => $dkpsys ['dkpsys_default'] ) );
				}

				$db->sql_freeresult ( $dkpsys_result );
				// DEFAULT DKPSYS pulldown menu
				$sql4 = 'SELECT dkpsys_id, dkpsys_name, dkpsys_default FROM ' . DKPSYS_TABLE;
				if (! ($query4result = $db->sql_query ( $sql4 )))
				{
					trigger_error ( $user->lang ['ERROR_INVALID_DKPSYSTEM_PROVIDED'], E_USER_WARNING );
				}
				while ( $dkp = $db->sql_fetchrow ( $query4result ) )
				{
					$template->assign_block_vars ( 'dkpsysdef_row', 
						array (
							'VALUE' => $dkp ['dkpsys_name'], 
							'SELECTED' => ('Y' == $dkp ['dkpsys_default']) ? ' selected="selected"' : '', 
							'OPTION' => $dkp ['dkpsys_name'] ));
				}
				$db->sql_freeresult ( $query4result );
				$submit = (isset ( $_POST ['upddkpsysdef'] )) ? true : false;
				
				// DEFAULT DKPSYS submit buttonhandler
				if ($submit)
				{
					$sql = 'UPDATE ' . DKPSYS_TABLE . " SET dkpsys_default='N'";
					$db->sql_query ( $sql );
					$sql = 'UPDATE ' . DKPSYS_TABLE . " SET dkpsys_default='Y' 
						WHERE dkpsys_name = '" . $db->sql_escape ( request_var ( 'defaultsys', '' ) ) . "'";
					$db->sql_query ( $sql );
					$log_action = array (
						'header' => 'L_ACTION_DEFAULT_DKP_CHANGED', 
						'DKPSYSDEFAULT' => request_var ( 'defaultsys', '' ) );
					$this->log_insert ( array ('log_type' => $log_action ['header'], 'log_action' => $log_action ) );
					$success_message = sprintf ( $user->lang ['ADMIN_DEFAULTPOOL_SUCCESS'], request_var ( 'defaultsys', '' ) );
					trigger_error ( $success_message . $link) ;
				}
				
				$submit = (isset ( $_POST ['syncdkp'] )) ? true : false;
				if($submit)
				{
					$this->syncdkpsys();
				}
				
				$template->assign_vars ( array (
					'L_TITLE' => $user->lang ['ACP_LISTDKPSYS'], 
					'L_EXPLAIN' => $user->lang ['ACP_LISTDKPSYS_EXPLAIN'], 
					'O_NAME' => $current_order ['uri'] [0], 
					'O_STATUS' => $current_order ['uri'] [1], 
					'U_LIST_DKPSYS' => append_sid ( "{$phpbb_admin_path}index.$phpEx", "i=dkp_sys&amp;mode=listdkpsys&amp;" ), 
					'START' => $start, 
					'LISTDKPSYS_FOOTCOUNT' => sprintf ( $user->lang ['LISTDKPSYS_FOOTCOUNT'], $total_dkpsys, $config ['bbdkp_user_elimit'] ), 
					'DKPSYS_PAGINATION' => generate_pagination ( append_sid ( "{$phpbb_admin_path}index.$phpEx", "i=dkp_sys&amp;mode=listdkpsys&amp;" ) . "&amp;o=" . 
						$current_order ['uri'] ['current'], $total_dkpsys, $config ['bbdkp_user_elimit'], $start ) ) );
				$this->page_title = 'ACP_LISTDKPSYS';
				$this->tpl_name = 'dkp/acp_' . $mode;
				break;
		}
	}
	
	public function syncdkpsys()
	{
		global $user, $db, $phpbb_admin_path, $phpEx, $config;
		
		$link = '<br /><a href="' . append_sid ( "{$phpbb_admin_path}index.$phpEx", "i=dkp_sys&amp;mode=listdkpsys" ) . '"><h3>'. $user->lang['RETURN_DKPPOOLINDEX'].'</h3></a>';
		
		/* select raids */
		$sql = 'SELECT e.event_dkpid, d.member_id FROM '. 
			EVENTS_TABLE . ' e 
			INNER JOIN ' . RAIDS_TABLE. ' r ON e.event_id = r.event_id	 	
			INNER JOIN ' . RAID_DETAIL_TABLE . ' d ON r.raid_id = d.raid_id
			GROUP BY e.event_dkpid, d.member_id';
		
		$dkpcorr = 0;
		$dkpadded = 0;
		$dkpspentcorr = 0;
			
		$result0 = $db->sql_query ($sql);
		while ($row = $db->sql_fetchrow ( $result0 ))
		{
			$member_id = $row['member_id'];
			$event_dkpid = $row['event_dkpid'];

			/* select raid values */
			$sql = 'SELECT 
				MIN(r.raid_start) as first_raid,
				MAX(r.raid_start) as last_raid, 
				COUNT(d.raid_id) as raidcount,
				SUM(d.raid_value) as raid_value, 
				SUM(d.time_bonus) as time_bonus, 
				SUM(d.raid_decay) as raid_decay, 
				SUM(d.zerosum_bonus) as zerosum_bonus
				FROM '. EVENTS_TABLE . ' e 
				INNER JOIN ' . RAIDS_TABLE. ' r ON e.event_id = r.event_id	 	
				INNER JOIN ' . RAID_DETAIL_TABLE . ' d ON r.raid_id = d.raid_id
				WHERE d.member_id = ' . $member_id . ' 
				AND	e.event_dkpid = ' . $event_dkpid;
			$result = $db->sql_query ($sql);
			while ( ($rowd = $db->sql_fetchrow ( $result )) ) 
			{
				$first_raid = $rowd['first_raid'];
				$last_raid = $rowd['last_raid'];
				$raidcount= $rowd['raidcount'];
				$raid_value = $rowd['raid_value'];
				$time_bonus = $rowd['time_bonus'];
				$raid_decay= $rowd['raid_decay'];
				$zerosum_bonus = $rowd['zerosum_bonus'];
			}
			$db->sql_freeresult ( $result);
			
			$sql =  'SELECT count(*) as count FROM ' . MEMBER_DKP_TABLE . ' WHERE member_id = ' . $member_id . ' 
			AND	member_dkpid = ' . $event_dkpid; 
			$result = $db->sql_query ($sql);
			$count = $db->sql_fetchfield('count', 0, $result);
			$db->sql_freeresult ( $result);
			if($count ==1)
			{
				$sql =  'SELECT * FROM ' . MEMBER_DKP_TABLE . ' WHERE member_id = ' . $member_id . ' 
				AND	member_dkpid = ' . $event_dkpid; 
				$result = $db->sql_query ($sql);
				while ( ($rowe = $db->sql_fetchrow ( $result )) ) 
				{
					$first_raid_accounted = $rowe['member_firstraid'];
					$last_raid_accounted = $rowe['member_lastraid'];
					$raidcount_accounted= $rowe['member_raidcount'];
					$raid_value_accounted = $rowe['member_raid_value'];
					$time_bonus_accounted = $rowe['member_time_bonus'];
					$raid_decay_accounted= $rowe['member_raid_decay'];
					$zerosum_bonus_accounted = $rowe['member_zerosum_bonus'];
					$earned_accounted = $rowe['member_earned'];
				}
				$db->sql_freeresult ( $result);
			
				if(( $first_raid != $first_raid_accounted) ||
				($last_raid != $last_raid_accounted) ||
				($raidcount != $raidcount_accounted) ||
				($raid_value != $raid_value_accounted) ||
				($time_bonus != $time_bonus_accounted) ||
				($raid_decay != $raid_decay_accounted) ||
				($zerosum_bonus != $zerosum_bonus_accounted))
				{
					$dkpcorr +=1;
					
					$data = array(
				    'member_firstraid'      => $first_raid,
				    'member_lastraid'       => $last_raid,
				    'member_raidcount'      => $raidcount,
				    'member_raid_value'     => $raid_value,
				    'member_time_bonus'     => $time_bonus,
				    'member_raid_decay'     => $raid_decay,
					'member_zerosum_bonus'	=> $zerosum_bonus, 
				    'member_earned'     	=> $raid_value+$time_bonus+$zerosum_bonus-$raid_decay,					
					);
					
					$sql = 'UPDATE ' . MEMBER_DKP_TABLE . ' 
					SET ' . $db->sql_build_array('UPDATE', $data) . 
					' WHERE member_id = ' . $member_id . ' 
					AND	member_dkpid = ' . $event_dkpid; 
					$db->sql_query ($sql);
					
				}
			}
			else
			{
				//delete and reinsert
				$sql = 'DELETE FROM ' . MEMBER_DKP_TABLE . ' WHERE member_id = ' . $member_id . ' 
				AND	member_dkpid = ' . $event_dkpid;
				$db->sql_query ($sql);
				
				$data = array(
				'member_dkpid'      	=> $event_dkpid,
				'member_id'      		=> $member_id,
				'member_status'      	=> 1,
				'member_firstraid'      => $first_raid,
			    'member_lastraid'       => $last_raid,
			    'member_raidcount'      => $raidcount,
			    'member_raid_value'     => $raid_value,
			    'member_time_bonus'     => $time_bonus,
			    'member_raid_decay'     => $raid_decay,
				'member_zerosum_bonus'	=> $zerosum_bonus, 
			    'member_earned'     	=> $raid_value+$time_bonus+$zerosum_bonus-$raid_decay,
				);
				$dkpadded +=1;
				
				$sql = 'INSERT INTO ' . MEMBER_DKP_TABLE . $db->sql_build_array('INSERT', $data);
				$db->sql_query ($sql);
		
			}
		}
		$db->sql_freeresult ( $result0);	
			
		/* select loot */
		$sql = 'SELECT e.event_dkpid, i.member_id FROM '. 
				EVENTS_TABLE . ' e 
				INNER JOIN ' . RAIDS_TABLE. ' r ON e.event_id = r.event_id 
				INNER JOIN ' . RAID_ITEMS_TABLE . ' i ON r.raid_id = i.raid_id 
				GROUP BY e.event_dkpid, i.member_id' ;
		
		$result0 = $db->sql_query ($sql);

		while ($row = $db->sql_fetchrow ( $result0 ))
		{
			$member_id = $row['member_id'];
			$event_dkpid = $row['event_dkpid'];
			$item_value = 0;
			$item_decay =0;
			/* select lootvalues */
			$sql = 'SELECT 
				SUM(i.item_value) as item_value, 
				SUM(i.item_decay) as item_decay 
				FROM '. EVENTS_TABLE . ' e 
				INNER JOIN ' . RAIDS_TABLE. ' r ON e.event_id = r.event_id	 	
				INNER JOIN ' . RAID_ITEMS_TABLE . ' i ON i.raid_id = r.raid_id
				WHERE i.member_id = ' . $member_id . ' 
				AND	e.event_dkpid = ' . $event_dkpid;
			$result = $db->sql_query ($sql);
			while ( ($rowd = $db->sql_fetchrow ( $result )) ) 
			{
				$item_value = $rowd['item_value'];
				$item_decay= $rowd['item_decay'];
			}
			$db->sql_freeresult ( $result);
			
			$sql =  'SELECT count(*) as count FROM ' . MEMBER_DKP_TABLE . ' WHERE member_id = ' . $member_id . ' 
			AND	member_dkpid = ' . $event_dkpid; 
			$result = $db->sql_query ($sql);
			$count = $db->sql_fetchfield('count', 0, $result);
			$db->sql_freeresult ( $result);
			if($count == 1 )
			{
				$sql =  'SELECT * FROM ' . MEMBER_DKP_TABLE . ' WHERE member_id = ' . $member_id . ' 
				AND	member_dkpid = ' . $event_dkpid; 
				$result = $db->sql_query ($sql);
				while ( ($rowe = $db->sql_fetchrow ( $result )) ) 
				{
					$item_value_accounted = $rowe['member_spent'];
					$item_decay_accounted = $rowe['member_item_decay'];
				}
				$db->sql_freeresult ( $result);
				if(( $item_value  != $item_value_accounted) ||
				($item_decay  != $item_decay_accounted))
				{
					$dkpspentcorr += 1;
					/* account exists */
					$data = array(
				    'member_spent'     		=> $item_value,					
				    'member_item_decay'     => $item_decay,
					);
					
					$sql = 'UPDATE ' . MEMBER_DKP_TABLE . ' 
					SET ' . $db->sql_build_array('UPDATE', $data) . 
					' WHERE member_id = ' . $member_id . ' 
					AND	member_dkpid = ' . $event_dkpid; 
					$db->sql_query ($sql);
						
					
				}
			}
			// case count=0 is not possible
		}
		$db->sql_freeresult ( $result0);
		$message = sprintf($user->lang['ADMIN_DKPPOOLSYNC_SUCCESS'] , $dkpcorr  + $dkpspentcorr);
		trigger_error ( $message . $this->link , E_USER_NOTICE );
				
	}

}

?>