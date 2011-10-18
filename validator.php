<?php
/*
 * This file handles verification of $_GET and $_POST
 */
Class Validator Extends Bulletaeon {
	function check_everything()
	{
		global $wpdb, $reqdata;
		foreach ( $reqdata as $key => $value )
		{
			if ( is_string($value) )
				$value = !empty($value) ? $wpdb->escape(trim($value)) : '';
		}

		if ( isset($reqdata['action']) && $reqdata['action'] == 'edit_save' )
		{
			if ( empty($reqdata['msg_id']) )
			{
				echo '<div class="error"><p><strong>錯誤：</strong>未指定公告 ID ！</p></div>';
				Renderer::js_redirect(5000);
				return false;
			} else {
				// Grab original data from the database (It's too dangerous grabbing just from <input type="hidden" />)
				$sql = "SELECT msg_time, msg_file FROM " . WP_BTAEON_TABLE . " WHERE msg_id='" . $reqdata['msg_id'] . "'";
				$orig_data = $wpdb->get_results($sql);
				$orig_data = $orig_data[0];
				if ( !$reqdata['update_time'] )
					$reqdata['msg_time'] = $orig_data->msg_time;
				$atta_return = atta_upload( $orig_data->msg_time, $orig_data->msg_file, 'edit_save' );
				$reqdata['msg_file'] = $atta_return['file'];
			}
		} elseif ( isset($reqdata['action']) && $reqdata['action'] == 'add' ) {
			$atta_return = atta_upload( $reqdata['msg_time'], '', 'add' );
			$reqdata['msg_file'] = $atta_return['file'];
		}

		if ( !self::check_title($reqdata['msg_title']) ) return false;
		if ( !self::check_content($reqdata['msg_content']) ) return false;
		if ( !self::check_links($reqdata['msg_link']) ) return false;
		
		return true;
	}

	function check_title( $title = '' )
	{
		// The title must be at least one character in length and no more than 30
		if ( mb_strlen($title, 'UTF-8') == 0 || mb_strlen($title, 'UTF-8') > 255 )
		{
			echo '<div class="error"><p><strong>錯誤：</strong>公告標題需有至少1字，至多255字</p></div>';
			return false;
		} else {
			return true;
		}
	}

	function check_content( $content = '' )
	{
		// The content must be at least 1 character in length and no more than 2^32-1 (MySQL LONGTEXT)
		if ( mb_strlen($content, 'UTF-8') == 0 || mb_strlen($content, 'UTF-8') > pow(2,32)-1 )
		{
			echo '<div class="error"><p><strong>錯誤：</strong>公告內容需有至少1字，至多' . pow(2,32)-1 . '字</p></div>';
			return false;
		} else {
			return true;
		}
	}

	function check_links( $link = array() )
	{	
		global $wpdb, $reqdata;
		// Separate the URLs, then check if it's valid
		// check if $link only contains empty element
		foreach ( $link as $l )
		{
			if ( count(array_filter($l)) != 0 )
			{
				$link_empty = 0;
				break;
			} else {
				$link_empty = 1;
			}
		}
		if ( $link_empty == 0 ) 
		{
			foreach ( $link as $key => $i )
			{
				$i[0] = trim($i[0]);
				$i[1] = trim($i[1]);
				if ( $i[0] != '' && preg_match('/^https?\:\/\//', $i[0]) && !preg_match('/^https?\:\/\/$/', $i[0]) )
				{
					$link_ok = 1;
				}
				elseif ( empty($i[0]) && empty($i[1]) )
				{
					$link_ok = 1;
				} else {
					$link_ok = 0;
					echo '<div class="error"><p><strong>錯誤：</strong>連結位址' . $key . '需為 http:// 或 https:// 開頭，或為空白</p></div>';
					return false;
					break;
				}
			}
			if ( $link_ok == 1 )
			{
				// If the links are valid, serialize $link. We'll save it to the database later
				$reqdata['msg_link'] = $wpdb->escape(serialize($link));
				return true;
			}
		} else {
			// Set $reqdata['msg_link'] empty to prevent overhead
			$reqdata['msg_link'] = '';
			return true;
		}
	}
}
?>
