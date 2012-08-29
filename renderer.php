<?php
Class Renderer Extends Bulletaeon {
	function manage_main()
	{
		// Copy $_REQUEST for manipulation and validation
		global $reqdata;
		$reqdata = $_REQUEST;
	
		if ( isset($_GET['sent']) && $_GET['sent'] == '1' )
		{
			switch ( $_REQUEST['action'] ) {
				case 'add':
					if ( !Validator::check_everything() || !DBOperator::add() )
					{
						// User input is invalid or database operation failed, show the form again.
						echo '<div class="wrap">';
						echo '<h2>新增公告</h2>';
						// Recover user input
						$user_entries = (object) $_POST;
						$this->manage_form('add', false, $user_entries);
						echo '</div>';
						break;
					}
					echo '<div class="updated"><p>公告新增成功！</p></div>';
					Renderer::js_redirect(4000);
					break;
				case 'edit_save':
					if ( !Validator::check_everything() || !DBOperator::edit_save() )
					{
						// User input is invalid or database operation failed, show the form again.
						echo '<div class="wrap">';
						echo '<h2>編輯公告</h2>';
						// Recover user input
						$user_entries = (object) $_POST;
						$this->manage_form('edit_save', $reqdata['msg_id'], $user_entries);
						echo '</div>';
						break;
					}
					echo '<div class="updated"><p>公告編輯成功！</p></div>';
					Renderer::js_redirect(5000);
					break;
				case 'delete':
					if ( !DBOperator::delete($reqdata['msg_id']) ) break;
					echo '<div class="updated"><p>公告刪除成功！</p></div>';
					Renderer::js_redirect(4000);
					break;
			}
		} elseif ( isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit' ) {
			$msg_to_edit = !empty( $_REQUEST['msg_id']) ? $_REQUEST['msg_id'] : '';
			echo '<div class="wrap">';
			echo '<h2>編輯公告</h2>';
			$this->manage_form('edit_save', $msg_to_edit);
			echo '</div>';
		} else {
			echo '<div class="wrap">';
			echo '<h2>新增公告</h2>';
			$this->manage_form();
			echo '<h2>管理公告</h2>';
			$this->manage_table();
			echo '</div>';
		}
	
	}

	function manage_form( $mode = 'add' , $msg_id = false , $user_entries = '')
	{
		wp_get_current_user();
		global $wpdb, $current_user;
		$data = false;
	
		if ( $msg_id != false )
		{
			if ( intval($msg_id) != $msg_id )
			{
				echo '<div class="error"><p>不乖！</p></div>';
				return false;
			} else {
				$data = $wpdb->get_results("SELECT * FROM " . WP_BTAEON_TABLE . " WHERE msg_id='$msg_id' LIMIT 1");
				if ( empty($data) )
				{
					echo '<div class="error"><p>找不到該公告</p></div>';
					return;
				}
				$data = $data[0];
			}
			// Check permission
			if ( !($current_user->user_login == $data->msg_owner || $current_user->user_level >= 8) )
			{
				echo '<div class="error"><p>被我抓到了！別亂改別人的公告！</p></div>';
				return false;
			}
		} else {
			// Recover users entries if they exist; in other words if editing an message went wrong
			if ( !empty($user_entries) )
			{
				$data = $user_entries;
				// Set msg_file empty to prevent errors
				$data->msg_file = '';
			}
		}
?>
<script type="text/javascript">
	/* <![CDATA[ */
	jQuery(document).ready(function($) {
		$(".group_link > div:last").append('<a href="#" class="add_link">新增連結</a>');
		$(".group_file:last").append('<a href="#" class="add_file">新增附件</a>');
		var i = $("div.group_link").length;
		
		$(".add_link").click(function() {
			var $newlink = $(".group_link:first").clone(true).insertAfter(".group_link:last");

			i++;
			$newlink.children("div:first").children("input").attr("name", "msg_link[" + i + "][0]");
			$newlink.children("div:first").children("input").attr("value", "");
			$newlink.children("div:last").children("input").attr("name", "msg_link[" + i + "][1]");
			$newlink.children("div:last").children("input").attr("value", "");
			$newlink.children("div:last").append('<a href="#" class="remove_link">移除連結</a>');

			$(".remove_link").click(function() {
				$(this).parents('div.group_link').remove();
				i = $("div.group_link").length;
				return false;
			});
			return false;
		});

		$(".add_file").click(function() {
			var $newfile = $(".group_file:first").clone(true).insertAfter(".group_file:last");

			$newfile.children("label").html("附件");
			$newfile.append('<a href="#" class="remove_file">移除附件</a>');

			$(".remove_file").click(function() {
				$(this).parent().remove();
				return false;
			});
			return false;
		});

		$(".delete_file > input").change(function()
		{
			//$(this).css('text-decoration', 'line-through');
			if(!$(this).hasClass('checked'))
			{
				$(this).parent().children('.delete_filename').css('text-decoration', 'line-through');
				$(this).addClass('checked');
			}
			else
			{
				$(this).parent().children('.delete_filename').css('text-decoration', 'none');
				$(this).removeClass('checked');
			}
		});
	});
	//]]>
</script>
<form name="msgform" enctype="multipart/form-data" id="msgform" class="wrap" method="post" action="<?php bloginfo('wpurl'); ?>/wp-admin/admin.php?page=bulletaeon&amp;sent=1">
	<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
	<input type="hidden" name="action" value="<?php echo $mode; ?>" />
	<input type="hidden" name="msg_id" value="<?php echo stripslashes($msg_id); ?>" />
	<input type="hidden" name="msg_owner" value="<?php 
	// Don't panic, data in this input box has NOTHING to do with authentication, NOR it will be saved to the database.
	echo ($mode=='edit_save' ) ? $data->msg_owner : $current_user->user_login; ?>" />
	<input type="hidden" name="msg_time" value="<?php echo current_time('mysql', 0); ?>" />
	<div id="linkadvanceddiv" class="postbox">
		<div>
			<label for="msg_title">公告標題</label>
			<input type="text" name="msg_title" id="msg_title" maxlength="255" value="
<?php 			if ( !empty($data) && empty($user_entries) )
			{
				echo htmlspecialchars(stripslashes($data->msg_title));
			} elseif ( !empty($user_entries) ) {
				echo htmlspecialchars(stripslashes($user_entries->msg_title));
			}
?>" />
		</div>
		<div>
			<label>公告作者</label>
<?php 
			// This section of code has nothing to do with permission checking, it just shows the username
			if ( $mode == 'edit_save' )
			{
				$userinfo = get_user_by('login', $data->msg_owner);
				echo $userinfo->display_name;
			} else {
				echo $current_user->display_name;
			}
?>
		</div>
<?php
			if ( $mode == 'edit_save' )
			{?>
		<div>
			<label for="msg_time">將公告時間更新為編輯時間</label>
			<input type="checkbox" name="update_time" />
		</div>
<?php			}?>
		<div>
			<label for="msg_content">公告內容</label>
			<textarea name="msg_content" id="msg_content" rows="8" cols="50">
<?php 			if ( !empty($data) && empty($user_entries) )
			{
				echo htmlspecialchars(stripslashes($data->msg_content));
			} elseif ( !empty($user_entries) ) {
				echo htmlspecialchars(stripslashes($user_entries->msg_content));
			}
?></textarea>
		</div>
		<div>
			<label for="msg_category">公告分類</label>
			<select name="msg_category" id="msg_category">
<?php
			// Grab categories from database
			$sql = "SELECT * FROM " . WP_BTAEON_CATEGORIES_TABLE . ";";
			$cats = $wpdb->get_results($sql);
			foreach($cats as $cat)
			{
				echo '<option value="'.stripslashes($cat->category_id).'"';
				if ( !empty($data) && empty($user_entries) )
				{
					if ( $data->msg_category == $cat->category_id )
					{
						echo 'selected="selected"';
					}
				} elseif ( !empty($user_entries) ) {
					if ( $user_entries->msg_category == $cat->category_id )
					{
						echo 'selected="selected"';
					}
				}
				echo '>'.stripslashes($cat->category_name).'</option>';
			}
?>
			</select>
		</div>
<?php			if ( $mode == 'edit' || ( $mode == 'edit_save' && empty($user_entries) ) )
			{
				if ( empty($data->msg_link) )
				{
					for ( $i = 1; $i <= 3; $i++ )
					{
						echo '<div class="group_link"><div><label for="msg_link">連結位址</label>';
						echo '<input type="text" name="msg_link[' . $i . '][0]" id="msg_link" cols="80" value="" /></div>';
						echo '<div><label for="msg_link_descr">連結文字</label>';
						echo '<input type="text" name="msg_link[' . $i . '][1]" id="msg_link_descr" cols="30" value="" /></div></div>';
					}
				} else {
					// In edit mode, we'll grab the original data from the database
					$link_arr = unserialize($data->msg_link);
					foreach($link_arr as $key => $l)
					{
						$uri = $l[0];
						$description = $l[1];
						echo '<div class="group_link"><div><label for="msg_link">連結位址（可為空）</label>';
						echo '<input type="text" name="msg_link[' . $key . '][0]" id="msg_link" cols="80" value="' . $uri . '" /></div>';
						echo '<div><label for="msg_link_descr">連結文字</label>';
						echo '<input type="text" name="msg_link[' . $key . '][1]" id="msg_link_descr" cols="30" value="' . $description . '" /></div></div>';
					}
				}
			} elseif ( !empty($user_entries) ) {
				// Previous save failed. recover from $data ($data==$user_entries==$_POST)
				foreach($user_entries->msg_link as $key => $l)
				{
					$uri = $l[0];
					$description = $l[1];
					echo '<div class="group_link"><div><label for="msg_link">連結位址（可為空）</label>';
					echo '<input type="text" name="msg_link[' . $key . '][0]" id="msg_link" cols="80" value="' . $uri . '" /></div>';
					echo '<div><label for="msg_link_descr">連結文字</label>';
					echo '<input type="text" name="msg_link[' . $key . '][1]" id="msg_link_descr" cols="30" value="' . $description . '" /></div></div>';
				}
			} else {
				for ( $i = 1; $i <= 3; $i++ )
				{
					echo '<div class="group_link"><div><label for="msg_link">連結位址（可為空）</label>';	
					echo '<input type="text" name="msg_link[' . $i . '][0]" id="msg_link" cols="80" value="" /></div>';
					echo '<div><label for="msg_link_descr">連結文字</label>';
					echo '<input type="text" name="msg_link[' . $i . '][1]" id="msg_link_descr" cols="30" value="" /></div></div>';
				}
			}
		if ( $mode == 'edit_save' )
		{
			if ( !empty($data->msg_file) )
			{
				$file_arr = (empty($data->msg_file)) ? array('', '', '') : unserialize($data->msg_file);
				$j = 0;
				foreach ( $file_arr as $i )
				{
					if ( !empty($i) )
					{
						$file_link = pathinfo($i);
						$name = $file_link['basename'];
						if ( empty($name) )
						{
							echo '<div><label>錯誤：檔名爲空</label></div>';
						} else {
								echo '<div class="delete_file"><label for="delete">原有附件</label><span class="delete_filename">' . $name . '</span>&nbsp;&nbsp;&nbsp;<input type="checkbox" name="delete_atta[]" id="delete" value="yes" />刪除此檔案？</div>
											<div class="group_file"><label for="file">上傳新附件</label><input type="file" id="file" name="atta[]"></div>';
						}
						$j++;
					}
				}
				// What if there is only one file submitted previously?
				// The maximum uploads allowed will be configured in the Config, use 2 temporarily
				while ( $j < 2 )
				{
					echo '<div class="group_file">
						<label for="file">附件</label>
						<input type="file" name="atta[]" id="file">
					</div>';
					$j++;
				}
			} else {
					echo '<div class="group_file">
						<label for="file">附件</label>
						<input type="file" name="atta[]" id="file">
					</div>
					<div class="group_file">
						<label for="file">附件</label>
						<input type="file" name="atta[]" id="file">
					</div>';
			}
		} else {
				echo '<div class="group_file">
					<label for="file">附件</label>
					<input type="file" name="atta[]" id="file">
				</div>
				<div class="group_file">
					<label for="file">附件</label>
					<input type="file" name="atta[]" id="file">
				</div>';
		}

?>
	</div>
	<input type="submit" name="save" class="button bold" value="儲存 &raquo;" />
</form>
<?php
		return true;
	}

	// Used on the manage messages admin page to display a list of messages
	function manage_table()
	{
		wp_get_current_user();
		global $wpdb, $current_user;
	
		// Paging
		if ( isset($_GET['msgp']) )
			$curr_page = intval($wpdb->escape($_GET['msgp']));
		if ( !isset($curr_page) ) $curr_page = 1;
		$baseurl = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=bulletaeon';
	
		// If current user is admin, show all users' posts
		if ( $current_user->user_level >= 8 )
		{
			$numrows = $wpdb->get_var("SELECT COUNT(msg_id) as rows FROM " . WP_BTAEON_TABLE);
		} else {
			$numrows = $wpdb->get_var("SELECT COUNT(msg_id) as rows FROM " . WP_BTAEON_TABLE . " WHERE msg_owner='$current_user->user_login'");
		}

		// Get the option, if it's not set, use 15
		$msgs_per_page = (get_option('bt_msgs_per_page')) ? get_option('bt_msgs_per_page') : 15;
		$numpages = ceil($numrows / $msgs_per_page);
		$offset = ($curr_page - 1) * $msgs_per_page;
	
		// Print the link to access each page
		$nav = '';
		for ( $msgpage = 1; $msgpage <= $numpages; $msgpage++ )
		{
			// With few pages, print all the links
			if ( $numpages < 15 )
			{
				if ( $msgpage == $curr_page ) $nav .= $msgpage; // No need to create a link to current page
				else $nav .= ' <a href="' . $this->add_querystring($baseurl, 'msgp', $msgpage) . '">' . $msgpage . '</a> ';
			// With many pages
			} else {
				if ( $msgpage == $curr_page )$nav .= $msgpage; // No need to create a link to current page
				elseif ( $msgpage == 1 || $msgpage == $numpages ) $nav .= ''; // No need to create first and last (they are created by the first and last links afterwards)
				else {
					// Print links that are close to the current page (< 10 steps away)
					if ( $msgpage < ($curr_page + 10) && $msgpage > ($curr_page - 10) )
					{
						$nav .= ' <a href="' . $this->add_querystring($baseurl, 'msgp', $msgpage) . '">' . $msgpage . '</a> ';
					}
				}
			}
		}
	
		// print first, last, next, previous links
		if ( $curr_page > 1 )
		{
			$msgpage = $curr_page - 1;
			//$prev = ' <a href="' . $this->add_querystring($baseurl, 'msgp', $msgpage) . '">&laquo;上一頁</a> ';
			$first = ($numpages > 15) ? ' <a href="' . $this->add_querystring($baseurl, 'msgp', '1') . '">&laquo;首頁</a> ' : '';
		} else {
			$prev = '&nbsp;'; // We're on page one, no need to print previous link
			$first = ($numpages > 15) ? '&nbsp;' : '';
		}
	
		if ( $curr_page < $numpages )
		{
			$msgpage = $curr_page + 1;
			//$next = ' <a href="' . $this->add_querystring($baseurl, 'msgp', $msgpage) . '">下一頁&raquo;</a> ';
			$last = ($numpages > 15) ? ' <a href="' . $this->add_querystring($baseurl, 'msgp', $numpages) . '">末頁&raquo;</a> ' : '';
		} else {
			$next = '&nbsp;'; // We're on the last page
			$last = ($numpages > 15) ? '&nbsp;' : '';
		}
	
	
		// Grab from the database
		if ( $current_user->user_level >= 8 )
		{
			$sql = "SELECT " . WP_BTAEON_TABLE . ".* , " . WP_BTAEON_CATEGORIES_TABLE . ".category_name FROM " . WP_BTAEON_TABLE . " INNER JOIN " . WP_BTAEON_CATEGORIES_TABLE . " ON " . WP_BTAEON_TABLE . ".msg_category=" . WP_BTAEON_CATEGORIES_TABLE . ".category_id ORDER BY msg_time DESC LIMIT $offset, $msgs_per_page";
		} else {
			$sql = "SELECT " . WP_BTAEON_TABLE . ".* , " . WP_BTAEON_CATEGORIES_TABLE . ".category_name FROM " . WP_BTAEON_TABLE . " INNER JOIN " . WP_BTAEON_CATEGORIES_TABLE . " ON " . WP_BTAEON_TABLE . ".msg_category=" . WP_BTAEON_CATEGORIES_TABLE . ".category_id WHERE " . WP_BTAEON_TABLE . ".msg_owner='$current_user->user_login' ORDER BY msg_time DESC LIMIT $offset, $msgs_per_page";
		}
		$msgs = $wpdb->get_results($sql);
		if ( !empty($msgs) )
		{
			echo '<p id="bt-navbar"><strong class="bt-navbar-left">', $first, $nav, $last, '</strong>';
			echo '<em class="bt-navbar-right">第' . $curr_page . '頁，共' . $numpages . '頁</em></p>';
	?>
			<table class="widefat page fixed" width="100%" cellpadding="3" cellspacing="3">
	                        <thead>
	                            <tr> 
	                                <th width="3%" class="manage-column" scope="col">ID</th>
	                                <th class="manage-column" scope="col">標題</th>
	                                <th width="5%" class="manage-column" scope="col">作者</th>
	                                <th width="30%" class="manage-column" scope="col">內容</th>
	                                <th width="5%" class="manage-column" scope="col">分類</th>
	                                <th class="manage-column" scope="col">連結</th>
	                                <th class="manage-column" scope="col">附件</th>
	                                <th width="7%" class="manage-column" scope="col">時間</th>
	                                <th width="3%" class="manage-column" scope="col">編輯</th>
	                                <th width="3%" class="manage-column" scope="col">刪除</th>
	                            </tr>
	                        </thead>
	<?php
			foreach ( $msgs as $msg )
			{
	?>
			<tr>
				<th scope="row"><?php echo stripslashes($msg->msg_id); ?></th>
				<td><?php echo htmlspecialchars(stripslashes($msg->msg_title)); ?></td>
				<td><?php $userinfo = get_user_by('login', $msg->msg_owner);
					echo $userinfo->display_name; ?></td>
				<td><?php echo htmlspecialchars(stripslashes($msg->msg_content)); ?></td>
				<td><?php echo stripslashes($msg->category_name); ?></td>
				<td><?php
				if ( !empty($msg->msg_link) )
				{
					$links = unserialize($msg->msg_link);
					$out = '';
					foreach ( $links as $i )
					{
						$uri = $i[0];
						$description = $i[1];
						if ( empty($uri) && empty($description) )
						{
							continue;
						} else {
							if ( empty($description) )
								$description = $uri;
							$out .= '<a href="' . $uri . '">' . $description . '</a>, ';
						}
					}
					$out = rtrim($out, ', ');
					echo $out;
				}?>
				</td>
				<td><?php
				if ( !empty($msg->msg_file) )
				{
					$file_arr = (empty($msg->msg_file)) ? array('', '', '') : unserialize($msg->msg_file);
					$out = '';
					foreach ( $file_arr as $i )
					{
						if ( !empty($i) )
						{
							$file_link = pathinfo($i);
							$name = $file_link['basename'];
							$uri = WP_CONTENT_URL . '/' . $file_link['dirname'] . '/' . rawurlencode($file_link['basename']);
							if ( empty($uri) && empty($name) )
							{
								continue;
							} else {
								$out .= '<a href="' . $uri . '">' . $name . '</a>, ';
							}
						}
					}
					$out = rtrim($out, ', ');
					echo $out;
				}
	?>
				</td>
				<td><?php echo stripslashes($msg->msg_time); ?></td>
				<?php
				// Check if the current user is the owner of this message, or power user
				if ( $current_user->user_login == $msg->msg_owner || $current_user->user_level >= 8 )
				{
	?>
		<td><a href="<?php bloginfo('wpurl'); ?>/wp-admin/admin.php?page=bulletaeon&amp;action=edit&amp;msg_id=<?php echo stripslashes($msg->msg_id);?>" class="edit">編輯</a></td>
		<td><a href="<?php bloginfo('wpurl'); ?>/wp-admin/admin.php?page=bulletaeon&amp;sent=1&amp;action=delete&amp;msg_id=<?php echo stripslashes($msg->msg_id);?>" class="delete" onclick="return confirm('您確定要刪除此公告？')">刪除</a></td>
	<?php
				} else {
					echo '<td></td><td></td>';
				}
			echo '</tr>';
			}
			echo '</table>';
			echo '<strong class="bt-navbar-left">' . $first . $nav . $last . '</strong>';
			echo '<em class="bt-navbar-right">第' . $curr_page . '頁，共' . $numpages . '頁</em>';
		} else {
			echo '<p>資料庫中沒有公告</p>';
		}
	}

	// Functions to handle $_GET
	function add_querystring($url, $key, $value)
	{
		$url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
		$url = substr($url, 0, -1);
		if (strpos($url, '?') === false)
		{
			return ($url . '?' . $key . '=' . $value);
		} else {
			return ($url . '&' . $key . '=' . $value);
		}
	}
	
	function rm_querystring($url, $key)
	{
		$url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
		$url = substr($url, 0, -1);
		return ($url);
	}

	function js_redirect($timeout = '5000')
	{
		// Redirect, this prevents sending the POST data again when user clicks reload
		$j = get_bloginfo('wpurl');
	
		echo '<p>本頁面將於', $timeout/1000 , '秒後自動跳轉，如果沒有，請按<a href="' , $j , '/wp-admin/admin.php?page=bulletaeon">這裡</a></p>
		<script type="text/javascript">
		<!--
		setTimeout("Redirect()",', $timeout ,');
		function Redirect()
		{
			window.location = "' , $j , '/wp-admin/admin.php?page=bulletaeon"
		}
		//-->
		</script>';
	}
}

?>
