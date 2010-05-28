<?php
/**
* This class manages guildmembers dkp adjustments
* 
* Powered by bbdkp © 2009 The bbDkp Project Team
* If you use this software and find it to be useful, we ask that you
* retain the copyright notice below.  While not required for free use,
* it will help build interest in the bbDkp project.
* 
* @package bbDkp.acp
* @copyright (c) 2009 bbdkp http://code.google.com/p/bbdkp/
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* @version $Id$
* 
**/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* @package module_install
*/

class acp_dkp_adj_info
{
	function module()
	{
		return array(
			'filename'	=> 'acp_dkp_adj',
		    'title'	=> 'ACP_DKP_MDKP',
			'version'	=> '1.1.0',
			'modes'		=> array(			
				'addiadj'	=> array('title' => 'ACP_DKP_ADDADJ', 'auth' => 'acl_a_dkp', 'cat' => array('ACP_DKP_MDKP')),
				'listiadj'	=> array('title' => 'ACP_DKP_LISTADJ', 'auth' => 'acl_a_dkp', 'cat' => array('ACP_DKP_MDKP')),
				),
		);
	}

	function install()
	{
	}

	function uninstall()
	{
	}
}

?>
