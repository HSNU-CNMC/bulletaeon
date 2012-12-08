<?php
Class DBOperator Extends Bulletaeon {
	function add()
	{
		global $wpdb, $reqdata;
		$sql = "INSERT INTO " . WP_BTAEON_TABLE . " SET 
			msg_title='" . $reqdata['msg_title'] . "',
			msg_owner='" . $reqdata['msg_owner'] . "',
			msg_category='" . $reqdata['msg_category'] . "',
			msg_content='" . $reqdata['msg_content'] . "',
			msg_link='" . $reqdata['msg_link'] . "',
			msg_file='" . $reqdata['msg_file'] . "',
			msg_time='" . $reqdata['msg_time'] . "'";
		$wpdb->get_results($sql);

		$sql = "SELECT msg_id FROM " . WP_BTAEON_TABLE . " WHERE 
			msg_title='" . $reqdata['msg_title'] . "' AND 
			msg_owner='" . $reqdata['msg_owner'] . "' AND 
			msg_category='" . $reqdata['msg_category'] . "' AND 
			msg_content='" . $reqdata['msg_content'] . "' AND 
			msg_link='" . $reqdata['msg_link'] . "' AND 
			msg_file='" . $reqdata['msg_file'] . "' AND 
			msg_time='" . $reqdata['msg_time'] . "' LIMIT 1";
		$result = $wpdb->get_results($sql);
		if ( empty($result) || empty($result[0]->msg_id) )
		{
			echo '<div class="error"><p>我找在資料庫中不到您剛剛送出的資料，資料庫可能出問題了</p></div>';
			return false;
		}
		return true;
	}

	function edit_save()
	{
		global $wpdb, $reqdata;
		$sql = "UPDATE " . WP_BTAEON_TABLE . " SET 
			msg_title='" . $reqdata['msg_title'] . "',
			msg_owner='" . $reqdata['msg_owner'] . "',
			msg_category='" . $reqdata['msg_category'] . "',
			msg_content='" . $reqdata['msg_content'] . "',
			msg_link='" . $reqdata['msg_link'] . "',
			msg_time='" . $reqdata['msg_time'] . "',
			msg_file='" . $reqdata['msg_file'] . "' WHERE msg_id='" . $reqdata['msg_id'] . "'";
		$wpdb->get_results($sql);

		$sql = "SELECT msg_id FROM " . WP_BTAEON_TABLE . " WHERE 
			msg_title='" . $reqdata['msg_title'] . "' AND 
			msg_owner='" . $reqdata['msg_owner'] . "' AND 
			msg_category='" . $reqdata['msg_category'] . "' AND 
			msg_content='" . $reqdata['msg_content'] . "' AND 
			msg_link='" . $reqdata['msg_link'] . "' AND 
			msg_time='" . $reqdata['msg_time'] . "' AND
			msg_file='" . $reqdata['msg_file'] . "' LIMIT 1";
		$result = $wpdb->get_results($sql);
		if ( empty($result) || empty($result[0]->msg_id) )
		{
			echo '<div class="error"><p>我找在資料庫中不到您剛剛送出的資料，資料庫可能出問題了</p></div>';
			return false;
		}
		return true;
	}

	function delete($msg_id)
	{
		wp_get_current_user();
		global $wpdb, $current_user;
		$sql = "SELECT * FROM " . WP_BTAEON_TABLE . " WHERE msg_id='" . $msg_id . "';";
		$result = $wpdb->get_results($sql);
		// Is current user a power user or owner of this message?
		if ( $current_user->user_login == $result[0]->msg_owner || $current_user->user_level >= 8 )
		{
			if ( empty($result) )
			{
				echo '<div class="error"><p><strong>錯誤：</strong>您所指定的公告不存在</p></div>';
				Renderer::js_redirect(4000);
				return false;
			} else {
				$sql = "DELETE FROM " . WP_BTAEON_TABLE . " WHERE msg_id='" . $msg_id . "';";
				//echo '<div class="updated"><p>' . $sql . '</p></div>';
				$wpdb->query($sql);

				$sql = "SELECT * FROM " . WP_BTAEON_TABLE . " WHERE msg_id='" . $msg_id . "';";
				$result = $wpdb->get_results($sql);

				if ( !empty($result) || !empty($result[0]->msg_id) )
				{
					echo '<div class="error"><p><strong>錯誤：</strong>儘管已經發出刪除命令，該公告在資料庫中依然存在。</p></div>';
					Renderer::js_redirect(4000);
					return false;
				}
			}
		} else {
			echo '<div class="error"><p><strong>錯誤：</strong>您沒有權限刪除此公告</p></div>';
			Renderer::js_redirect(4000);
			return false;
		}
		return true;
	}

	function get_msg_by_id($msg_id)
	{
		global $wpdb;
		$sql = "SELECT * FROM " . WP_BTAEON_TABLE . " WHERE
			msg_id='" . intval($msg_id) . "';";
		return $wpdb->get_results($sql);
	}
}
?>
