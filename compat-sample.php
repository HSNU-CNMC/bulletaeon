<?php
/*
 * Compatibility with old board system
 *
 * !!DANGEROUS!!
 * This file contains some custom codes to connect to another database,
 * and may cause errors or security flaws.
 * ENABLE ONLY WHEN NEEDED!!
 */

// Stop direct call
if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF']))
	die('You are not allowed to call this page directly.');

$enabled = get_option('bt_compat');
if ( $enabled == true )
{
	function connect_old_db()
	{
		global $link;
		$link = mysql_connect('localhost', 'user', 'pswd', true);

		if ( !$link )
		{
			die('Could not connect: ' . mysql_error());
		} 
		mysql_set_charset('utf8', $link);
		mysql_select_db('db', $link);
	}	
}
?>
