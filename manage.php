<?php
// Function to manage messages
function bt_manage_msgs()
{
	wp_get_current_user();
	global $wpdb, $current_user, $user_entries, $error_with_saving;
	
	$action = !empty( $_REQUEST['action']) ? $_REQUEST['action'] : '';
	$msg_id = !empty( $_REQUEST['msg_id']) ? $_REQUEST['msg_id'] : '';

	if ( $action == 'add' )
	{
		$title = !empty( $_REQUEST['msg_title']) ? $wpdb->escape(trim($_REQUEST['msg_title'])) : '';
		$owner = !empty( $_REQUEST['msg_owner']) ? $wpdb->escape(trim($_REQUEST['msg_owner'])) : '';
		$category = !empty( $_REQUEST['msg_category']) ? $wpdb->escape(trim($_REQUEST['msg_category'])) : '';
		$content = !empty( $_REQUEST['msg_content']) ? $wpdb->escape(trim($_REQUEST['msg_content'])) : '';
		$link = !empty( $_REQUEST['msg_link']) ? $wpdb->escape($_REQUEST['msg_link']) : '';
		// $file is just temporary, it will be changed after file attachment feature is completed.
		$file = !empty( $_REQUEST['msg_file']) ? $wpdb->escape(trim($_REQUEST['msg_file'])) : '';
		$time = !empty( $_REQUEST['msg_time']) ? $wpdb->escape(trim($_REQUEST['msg_time'])) : '';

		// Perform some validation
		// The title must be at least one character in length and no more than 30
		if ( mb_strlen($title, 'UTF-8') == 0 || mb_strlen($title, 'UTF-8') > 255 )
		{
			echo '<div class="error"><p><strong>錯誤：</strong>公告標題需有至少1字，至多255字</p></div>';
		} else {
			$title_ok = 1;
		}

		// Check if the category user selected exists
		/*$sql = "SELECT * FROM " . WP_BTAEON_CATEGORIES_TABLE . " WHERE category_id='" . $category . "';";
		$selected_category = $wpdb->get_row($sql);
		if ( empty($selected_category) )
		{
			echo '<div class="error"><p><strong>錯誤：</strong>您所選擇的公告分類不存在！</p></div>';
		} else {
			$category_ok = 1;
		}*/
		 
		// The content must be at least 1 character in length and no more than 2^32-1 (MySQL LONGTEXT)
		if ( mb_strlen($content, 'UTF-8') == 0 || mb_strlen($title, 'UTF-8') > pow(2,32)-1 )
		{
			echo '<div class="error"><p><strong>錯誤：</strong>公告內容需有至少1字，至多' . pow(2,32)-1 . '字</p></div>';
		} else {
			$content_ok = 1;
		}

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
					break;
				}
			}
			if ( $link_ok == 1 )
			{
				// If the links are valid, serialize $link. We'll save it to the database later
				$link_serialized = serialize($link);
				$link_serialized = $wpdb->escape($link_serialized);
			}
		} else {
			$link_ok = 1;
			// Set $link_serialized empty to prevent overhead
			$link_serialized = '';
		}

		// Check the uploaded file, call bt_upload.php
		$file = $wpdb->escape($file);
		$atta_return = atta_upload( $time, $file );
		$atta_ok = $atta_return['atta_ok'];
		$file = $atta_return['file'];

		// Operate the database if everything's alright
		if ( $title_ok == 1 && $content_ok == 1 && $link_ok == 1 && $atta_ok == 1 )
		{
			$sql = "INSERT INTO " . WP_BTAEON_TABLE . " SET 
				msg_title='$title',
				msg_owner='$owner',
				msg_category='$category',
				msg_content='$content',
				msg_link='$link_serialized',
				msg_file='$file',
				msg_time='$time'";
			//echo '<div class="error"><p><strong>sql</strong>' . $sql . '</p></div>';
			$wpdb->get_results($sql);

			$sql = "SELECT msg_id FROM " . WP_BTAEON_TABLE . " WHERE 
				msg_title='$title' AND 
				msg_owner='$owner' AND 
				msg_category='$category' AND 
				msg_content='$content' AND 
				msg_link='$link_serialized' AND 
				msg_file='$file' AND 
				msg_time='$time' LIMIT 1";
			$result = $wpdb->get_results($sql);
			if ( empty($result) || empty($result[0]->msg_id) )
			{
				echo '<div class="error"><p>我找在資料庫中不到您剛剛送出的資料，資料庫可能出問題了</p></div>';
			} else {
				// Redirect, this prevents sending the POST data again when user clicks reload
				$j = get_bloginfo('wpurl');
				echo '<div class="updated"><p>公告新增成功！</p></div>
					<p>本頁面將於5秒後自動跳轉，如果沒有，請按<a href="' . $j . '/wp-admin/admin.php?page=bulletaeon">這裡</a></p>
					<script type="text/javascript">
					<!--
					setTimeout("Redirect()",5000);
					function Redirect()
					{
						window.location = "' . $j . '/wp-admin/admin.php?page=bulletaeon"
					}
					//-->
					</script>';
				$added = 1;
				//echo '<div class="updated"><p>公告新增成功！</p></div>';
			}
		} else {
			// The form is going to be rejected due to field validation issues, so we preserve the users entries here
			$user_entries->msg_title = $title;
			$user_entries->msg_category = $category;
			$user_entries->msg_content = $content;
			$user_entries->msg_link = $link;
			$user_entries->msg_file = $file;
		}

	}
	elseif ( $action == 'edit_save' )
	{
		$title = !empty( $_REQUEST['msg_title']) ? $wpdb->escape(trim($_REQUEST['msg_title'])) : '';
		$owner = !empty( $_REQUEST['msg_owner']) ? $wpdb->escape(trim($_REQUEST['msg_owner'])) : '';
		$category = !empty( $_REQUEST['msg_category']) ? $wpdb->escape(trim($_REQUEST['msg_category'])) : '';
		$content = !empty( $_REQUEST['msg_content']) ? $wpdb->escape(trim($_REQUEST['msg_content'])) : '';
		$link = !empty( $_REQUEST['msg_link']) ? $wpdb->escape($_REQUEST['msg_link']) : '';
		// $file is just temporary, it will be changed after file attachment feature is completed.
		//$file = !empty( $_REQUEST['msg_file']) ? $wpdb->escape(trim($_REQUEST['msg_file'])) : '';
		$time = !empty( $_REQUEST['msg_time']) ? $wpdb->escape(trim($_REQUEST['msg_time'])) : '';

		if ( empty($msg_id) )
		{
			echo '<div class="error"><p><strong>錯誤：</strong>未指定公告 ID ！</p></div>';
		} else {
			// Grab original data from the database (It's too dangerous grabbing just from <input type="hidden" />)
			$sql = "SELECT msg_time, msg_file FROM " . WP_BTAEON_TABLE . " WHERE msg_id='$msg_id'";
			$orig_data = $wpdb->get_results($sql);
			$orig_data = $orig_data[0];
			$file = $orig_data->msg_file;
			//$time = $orig_data->msg_time;

			// Perform some validation
			// The title must be at least one character in length and no more than 30
			if ( mb_strlen($title, 'UTF-8') == 0 || mb_strlen($title, 'UTF-8') > 255 )
			{
				echo '<div class="error"><p><strong>錯誤：</strong>公告標題需有至少1字，至多255字</p></div>';
			} else {
				$title_ok = 1;
			}

			// Check if the category user selected exists
			/*$sql = "SELECT * FROM " . WP_BTAEON_CATEGORIES_TABLE . " WHERE category_id='" . $category . "';";
			$selected_category = $wpdb->get_row($sql);
			if ( empty($selected_category) )
			{
				echo '<div class="error"><p><strong>錯誤：</strong>您所選擇的公告分類不存在！</p></div>';
			} else {
				$category_ok = 1;
			}*/
			
			// The content must be at least 1 character in length and no more than 2^32-1
			if ( mb_strlen($content, 'UTF-8') == 0 || mb_strlen($title, 'UTF-8') > pow(2,32)-1 )
			{
				echo '<div class="error"><p><strong>錯誤：</strong>公告內容需有至少1字，至多' . pow(2,32)-1 . '字</p></div>';
			} else {
				$content_ok = 1;
			}

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
						break;
					}
				}
				if ( $link_ok == 1 )
				{
					// If the links are valid, serialize $link. We'll save it to the database later
					$link_serialized = serialize($link);
					$link_serialized = $wpdb->escape($link_serialized);
				}
			} else {
				$link_ok = 1;
				// Set $link_serialized empty to prevent overhead
				$link_serialized = '';
			}

			// Check the uploaded file, call bt_upload.php
			$atta_return = atta_upload( $time, $file, $action );
			$atta_ok = $atta_return['atta_ok'];
			$file = $atta_return['file'];

			// Operate the database if everything's alright
			if ( $title_ok == 1 && $content_ok == 1 && $link_ok == 1 && $atta_ok == 1 )
			{
				$sql = "UPDATE " . WP_BTAEON_TABLE . " SET 
					msg_title='$title',
					msg_owner='$owner',
					msg_category='$category',
					msg_content='$content',
					msg_link='$link_serialized',
					msg_time='$time',
					msg_file='$file' WHERE msg_id='$msg_id'";
				$wpdb->get_results($sql);

				$sql = "SELECT msg_id FROM " . WP_BTAEON_TABLE . " WHERE 
					msg_title='$title' AND 
					msg_owner='$owner' AND 
					msg_category='$category' AND 
					msg_content='$content' AND 
					msg_link='$link_serialized' AND 
					msg_time='$time' AND
					msg_file='$file' LIMIT 1";
				$result = $wpdb->get_results($sql);
				if ( empty($result) || empty($result[0]->msg_id) )
				{
					echo '<div class="error"><p>我找在資料庫中不到您剛剛送出的資料，資料庫可能出問題了</p></div>';
				} else {
					$j = get_bloginfo('wpurl');
					echo '<div class="updated"><p>公告編輯成功！</p></div>
						<p>本頁面將於5秒後自動跳轉，如果沒有，請按<a href="' . $j . '/wp-admin/admin.php?page=bulletaeon">這裡</a></p>
						<script type="text/javascript">
						<!--
						setTimeout("Redirect()",5000);
						function Redirect()
						{
							window.location = "' . $j . '/wp-admin/admin.php?page=bulletaeon"
						}
						//-->
						</script>';
					$edited = 1;
				}
			} else {
				// The form is going to be rejected due to field validation issues, so we preserve the users entries here
				$user_entries->msg_title = $title;
				$user_entries->msg_category = $category;
				$user_entries->msg_content = $content;
				$user_entries->msg_link = $link;
				$user_entries->msg_file = $file;
				$error_with_saving = 1;
			}
		}
	}
	elseif ( $action == 'delete' )
	{
		if ( empty($msg_id) )
		{
			echo '<div class="error"><p>您不能刪除不存在的公告</p></div>';
		} else {
			$sql = "SELECT * FROM " . WP_BTAEON_TABLE . " WHERE msg_id='" . $msg_id . "';";
			$result = $wpdb->get_results($sql);
			// Is current user a power user or owner of this message?
			if ( $current_user->user_login == $result[0]->msg_owner || $current_user->user_level >= 8 )
			{
				if ( empty($result) )
				{
					echo '<div class="error"><p><strong>錯誤：</strong>您所指定的公告不存在</p></div>';
				} else {
					$sql = "DELETE FROM " . WP_BTAEON_TABLE . " WHERE msg_id='" . $msg_id . "';";
					echo '<div class="updated"><p>' . $sql . '</p></div>';
					$wpdb->query($sql);

					$sql = "SELECT * FROM " . WP_BTAEON_TABLE . " WHERE msg_id='" . $msg_id . "';";
					$result = $wpdb->get_results($sql);

					if ( empty($result) || empty($result[0]->msg_id) )
					{
						echo '<div class="updated"><p>公告刪除成功！</p></div>';
					} else {
						echo '<div class="error"><p><strong>錯誤：</strong>儘管已經發出刪除命令，該公告在資料庫中依然存在。</p></div>';
					}
				}
			} else {
				echo '<div class="error"><p><strong>錯誤：</strong>您沒有權限刪除此公告</p></div>
					<p>本頁面將於4秒後自動跳轉，如果沒有，請按<a href="' . $j . '/wp-admin/admin.php?page=bulletaeon">這裡</a></p>
					<script type="text/javascript">
					<!--
					setTimeout("Redirect()",4000);
					function Redirect()
					{
						window.location = "' . $j . '/wp-admin/admin.php?page=bulletaeon"
					}
					//-->
					</script>';
				$deleted = 1;
			}
		}
	}

	// Now follows a little bit of code that pulls in the main components of this page; the edit form and the list of messages.
	echo '<div class="wrap">';
	if ( $action == 'edit' || ($action == 'edit_save' && $error_with_saving == 1) )
	{
		echo '<h2>編輯公告</h2>';

		if ( empty($msg_id) )
		{
			echo '<div class="error"><p>您並未指定公告 ID </p></div>';
		} else {
			bt_msgs_edit_form('edit_save', $msg_id);
		}
	} elseif ( ($action == 'add' && $added == 1) || ($action == 'edit_save' && $edited == 1) || ($action == 'delete' && $deleted == 1) ) {
		// Will redirect, so don't print anything
	} else {
		// Show the form in the beginning , or there're some errors with user input.
		echo '<h2>新增公告</h2>';
		bt_msgs_edit_form();
		echo '<h2>管理公告</h2>';
		bt_msgs_display();
	}
	echo '</div>';
}
// The message edit form for the manage messages admin page
function bt_msgs_edit_form($mode='add', $msg_id=false)
{
	wp_get_current_user();
	global $wpdb, $current_user, $user_entries;
	$data = false;

	if ( $msg_id != false )
	{
		if ( intval($msg_id) != $msg_id )
		{
			echo '<div class="error"><p>不乖！</p></div>';
			return;
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
			return;
		}
		// Recover users entries if they exist; in other words if editing an message went wrong
		if ( !empty($user_entries) )
		{
			$data = $user_entries;
		}
	} else {
		// Deal with possibility that form was submitted but not saved due to error - recover user's entries here
		$data = $user_entries;
	}
?>
<form name="msgform" enctype="multipart/form-data" id="msgform" class="wrap" method="post" action="<?php bloginfo('wpurl'); ?>/wp-admin/admin.php?page=bulletaeon&amp;reset=1">
	<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
	<input type="hidden" name="action" value="<?php echo $mode; ?>" />
	<input type="hidden" name="msg_id" value="<?php echo stripslashes($msg_id); ?>" />
	<input type="hidden" name="msg_owner" value="<?php 
	// Don't panic, data in this input box has NOTHING to do with authentication, NOR it will be saved to the database.
	echo ($mode=='edit_save') ? $data->msg_owner : $current_user->user_login; ?>" />
	<input type="hidden" name="msg_time" value="<?php echo current_time('mysql', 0); ?>" />
	<div id="linkadvanceddiv" class="postbox">
		<div>
			<label for="msg_title">公告標題</label>
			<input type="text" name="msg_title" id="msg_title" maxlength="255" value="<?php if ( !empty($data) ) echo htmlspecialchars(stripslashes($data->msg_title)); ?>" />
		</div>
		<div>
			<label>公告作者</label>
<?php 
			// This section of code has nothing to do with permission checking, it just shows the username
			if ( $mode == 'edit_save' )
			{
				$userinfo = get_userdatabylogin($data->msg_owner);
				echo $userinfo->display_name;
			} else {
				echo $current_user->display_name;
			}
?>
		</div>
		<div>
			<label for="msg_content">公告內容</label>
			<textarea name="msg_content" id="msg_content" rows="8" cols="50"><?php if ( !empty($data) ) echo htmlspecialchars(stripslashes($data->msg_content)); ?></textarea>
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
				if ( !empty($data) )
				{
					if ( $data->msg_category == $cat->category_id )
					{
						echo 'selected="selected"';
					}
				}
				echo '>'.stripslashes($cat->category_name).'</option>';
			}
?>
			</select>
		</div>
<?php			if ( $mode == 'edit' || $mode == 'edit_save' )
		{
			// In edit mode, we'll grab the original data from the database
			if ( empty($data->msg_link) )
			{
				for ( $i = 1; $i <= 3; $i++ )
				{
					echo '<div><label for="msg_link">連結位址' . $i . '（可為空）</label>';	
					echo '<input type="text" name="msg_link[' . $i . '][0]" id="msg_link" cols="80" value="" /></div>';
					echo '<div><label for="msg_link_descr">連結文字</label>';
					echo '<input type="text" name="msg_link[' . $i . '][1]" id="msg_link_descr" cols="30" value="" /></div>';
				}
			} else {
				$link_arr = unserialize($data->msg_link);
					
				foreach($link_arr as $key => $l)
				{
					$uri = $l[0];
					$description = $l[1];
					echo '<div><label for="msg_link">連結位址' . $key . '（可為空）</label>';
					echo '<input type="text" name="msg_link[' . $key . '][0]" id="msg_link" cols="80" value="' . $uri . '" /></div>';
					echo '<div><label for="msg_link_descr">連結文字</label>';
					echo '<input type="text" name="msg_link[' . $key . '][1]" id="msg_link_descr" cols="30" value="' . $description . '" /></div>';
				}
			}
		} else {
			for ( $i = 1; $i <= 3; $i++ )
			{
				echo '<div><label for="msg_link">連結位址' . $i . '（可為空）</label>';	
				echo '<input type="text" name="msg_link[' . $i . '][0]" id="msg_link" cols="80" value="" /></div>';
				echo '<div><label for="msg_link_descr">連結文字</label>';
				echo '<input type="text" name="msg_link[' . $i . '][1]" id="msg_link_descr" cols="30" value="" /></div>';
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
								echo '<div><label for="delete">原有附件</label>' . $name . '&nbsp;&nbsp;&nbsp;<input type="checkbox" name="delete_atta[]" id="delete" value="yes" />刪除此檔案？</div>
											<div><label for="file">上傳新附件</label><input type="file" id="file" name="atta[]"></div>';
						}
						$j++;
					}
				}
				// What if there is only one file submitted previously?
				// The maximum uploads allowed will be configured in the Config, use 2 temporarily
				while ( $j < 2 )
				{
					echo '<div>
									<label for="file">附件</label>
									<input type="file" name="atta[]" id="file">
							</div>';
					$j++;
				}
			} else {
					echo '<div>
									<label for="file">附件</label>
									<input type="file" name="atta[]" id="file">
								</div>
								<div>
									<label for="file">附件</label>
									<input type="file" name="atta[]" id="file">
								</div>';
			}
		} else {
				echo '<div>
								<label for="file">附件</label>
								<input type="file" name="atta[]" id="file">
							</div>
							<div>
								<label for="file">附件</label>
								<input type="file" name="atta[]" id="file">
							</div>';
		}

?>
	</div>
	<input type="submit" name="save" class="button bold" value="儲存 &raquo;" />
</form>

<?php
}
// Used on the manage messages admin page to display a list of messages
function bt_msgs_display()
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
			else $nav .= ' <a href="' . add_querystring($baseurl, 'msgp', $msgpage) . '">' . $msgpage . '</a> ';
		// With many pages
		} else {
			if ( $msgpage == $curr_page )$nav .= $msgpage; // No need to create a link to current page
			elseif ( $msgpage == 1 || $msgpage == $numpages ) $nav .= ''; // No need to create first and last (they are created by the first and last links afterwards)
			else {
				// Print links that are close to the current page (< 10 steps away)
				if ( $msgpage < ($curr_page + 10) && $msgpage > ($curr_page - 10) )
				{
					$nav .= ' <a href="' . add_querystring($baseurl, 'msgp', $msgpage) . '">' . $msgpage . '</a> ';
				}
			}
		}
	}

	// print first, last, next, previous links
	if ( $curr_page > 1 )
	{
		$msgpage = $curr_page - 1;
		//$prev = ' <a href="' . add_querystring($baseurl, 'msgp', $msgpage) . '">&laquo;上一頁</a> ';
		$first = ($numpages > 15) ? ' <a href="' . add_querystring($baseurl, 'msgp', '1') . '">&laquo;首頁</a> ' : '';
	} else {
		$prev = '&nbsp;'; // We're on page one, no need to print previous link
		$first = ($numpages > 15) ? '&nbsp;' : '';
	}

	if ( $curr_page < $numpages )
	{
		$msgpage = $curr_page + 1;
		//$next = ' <a href="' . add_querystring($baseurl, 'msgp', $msgpage) . '">下一頁&raquo;</a> ';
		$last = ($numpages > 15) ? ' <a href="' . add_querystring($baseurl, 'msgp', $numpages) . '">末頁&raquo;</a> ' : '';
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
		echo '<p id="bt-navbar"><strong class="bt-navbar-left">' . $first . $nav . $last . '</strong>';
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
			<td><?php $userinfo = get_userdatabylogin($msg->msg_owner);
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
						break;
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
							break;
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
				/*echo '<td><a href="' . bloginfo('wpurl') . '/wp-admin/admin.php?page=bulletaeon&action=edit&msg_id=' . stripslashes($msg->msg_id) . '" class="edit">編輯</a></td>';
				echo '<td><a href="' . bloginfo('wpurl') . '/wp-admin/admin.php?page=bulletaeon&action=delete&msg_id=' . stripslashes($msg->msg_id) . '" class="delete" onclick="return confirm(您確定要刪除此公告？)">刪除</a></td>';*/
?>
	<td><a href="<?php bloginfo('wpurl'); ?>/wp-admin/admin.php?page=bulletaeon&amp;action=edit&amp;msg_id=<?php echo stripslashes($msg->msg_id);?>" class="edit">編輯</a></td>
	<td><a href="<?php bloginfo('wpurl'); ?>/wp-admin/admin.php?page=bulletaeon&amp;action=delete&amp;msg_id=<?php echo stripslashes($msg->msg_id);?>" class="delete" onclick="return confirm('您確定要刪除此公告？')">刪除</a></td>
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
?>
