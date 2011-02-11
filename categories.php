<?php
// Function to handle the Categories admin page
function bt_manage_categories()
{
	global $wpdb;
	if ( current_user_can('manage_options') )
	{
		$action = !empty( $_REQUEST['action']) ? $_REQUEST['action'] : '';
		$cat_id = !empty( $_REQUEST['cat_id']) ? $_REQUEST['cat_id'] : '';

		if ( $action == 'add' )
		{
			$cat_name = !empty( $_REQUEST['cat_name']) ? $wpdb->escape(trim($_REQUEST['cat_name'])) : '';

			// Validate
			if ( mb_strlen($cat_name, 'UTF-8') == 0 || mb_strlen($cat_name, 'UTF-8') > 10 )
			{
				echo '<div class="error"><p><strong>錯誤：</strong>分類名稱需有至少1字，至多10字</p></div>';
				$cat_name_ok = 0;
			} else {
				$cat_name_ok = 1;
			}

			// Operate the database if everything's alright
			if ( $cat_name_ok == 1 )
			{
				$sql = "INSERT INTO " . WP_BTAEON_CATEGORIES_TABLE . " SET
					category_name='$cat_name'";
				$wpdb->get_results($sql);

				$sql = "SELECT category_id FROM " . WP_BTAEON_CATEGORIES_TABLE . " WHERE
					category_name='$cat_name' LIMIT 1";
				$result = $wpdb->get_results($sql);
				if ( empty($result) )
				{
					echo '<div class="error"><p>我找在資料庫中不到您剛剛送出的資料，資料庫可能出問題了</p></div>';
				} else {
					echo '<div class="updated"><p>分類新增成功！</p></div>';
				}
			} else {
				// Preserve user entry
				$user_entries->category_name = $cat_name;
			}
		} elseif ( $action == 'edit_save' ) {
			$cat_name = !empty( $_REQUEST['cat_name']) ? $wpdb->escape(trim($_REQUEST['cat_name'])) : '';

			// Validate
			if ( mb_strlen($cat_name, 'UTF-8') == 0 || mb_strlen($cat_name, 'UTF-8') > 10 )
			{
				echo '<div class="error"><p><strong>錯誤：</strong>分類名稱需有至少1字，至多10字</p></div>';
				$cat_name_ok = 0;
			} else {
				$cat_name_ok = 1;
			}

			// Operate the database if everything's alright
			if ( $cat_name_ok == 1 )
			{
				$sql = "UPDATE " . WP_BTAEON_CATEGORIES_TABLE . " SET
					category_name='$cat_name' WHERE category_id='$cat_id'";
				$wpdb->get_results($sql);
				echo '<div class="updated"><p>' . $sql . '</p></div>';

				$sql = "SELECT category_id FROM " . WP_BTAEON_CATEGORIES_TABLE . " WHERE
					category_name='$cat_name' LIMIT 1";
				echo '<div class="updated"><p>' . $cat_name . '</p></div>';
				$result = $wpdb->get_results($sql);
				if ( empty($result) )
				{
					echo '<div class="error"><p>我找在資料庫中不到您剛剛送出的資料，資料庫可能出問題了</p></div>';
				} else {
					echo '<div class="updated"><p>分類編輯成功！</p></div>';
				}
			} else {
				// Preserve user entry
				$user_entries->category_name = $cat_name;
				$error_with_saving = 1;
			}
		} elseif ( $action == 'delete' ) {
			if ( empty($cat_id) )
			{
				echo '<div class="error"><p>您不能刪除不存在的公告</p></div>';
			} else {
				$sql = "DELETE FROM " . WP_BTAEON_CATEGORIES_TABLE . " WHERE category_id='$cat_id'";
				$wpdb->query($sql);

				$sql = "SELECT * FROM " . WP_BTAEON_CATEGORIES_TABLE . " WHERE category_id='$cat_id'";
				$result = $wpdb->get_results($sql);
	
				if ( empty($result) )
				{
					echo '<div class="updated"><p>分類刪除成功！</p></div>';
				} else {
					echo '<div class="error"><p><strong>錯誤：</strong>儘管已經發出刪除命令，該分類在資料庫中依然存在。</p></div>';
				}
			}
		}

		// Now follows a little bit of code that pulls in the main components of this page; the edit form and the list of categories
		echo '<div class="wrap">';
		if ( $action == 'edit' || ($action == 'edit_save' && $error_with_saving == 1))
		{
			echo '<h2>編輯分類</h2>';
			if ( empty($cat_id) )
			{
				echo '<div class="error"><p>您並未指定分類 ID </p></div>';
			} else {
				bt_cats_edit_form('edit_save', $cat_id);
			}
		} else {
			echo '<h2>新增分類</h2>';
			bt_cats_edit_form();
			echo '<h2>管理分類</h2>';
			bt_cats_display();
		}
		echo '</div>';
	} else {
		echo '<div class="error"><p><strong>錯誤：</strong>您沒有權限檢視此頁</p></div>';
	}
}
// The edit form
function bt_cats_edit_form( $mode = 'add', $cat_id = false )
{
	global $wpdb, $user_entries;
	$data = false;

	if ( $cat_id != false )
	{
		if ( intval($cat_id) != $cat_id )
		{
			echo '<div class="error"><p>不乖！</p></div>';
			return;
		} else {
			$data = $wpdb->get_results("SELECT * FROM " . WP_BTAEON_CATEGORIES_TABLE . " WHERE category_id='$cat_id' LIMIT 1");
			if ( empty($data) )
			{
				echo '<div class="error"><p>找不到該公告</p></div>';
				return;
			}
			$data = $data[0];
		}
		// Recover user entry
		if ( !empty($user_entries) )
		{
			$data = $user_entries;
		}
	} else {
		$data = $user_entries;
	}
?>
<form name="catform" id="catform" class="wrap" method="post" action="<?php echo bloginfo('wpurl'); ?>/wp-admin/admin.php?page=btcat">
	<input type="hidden" name="action" value="<?php echo $mode; ?>" />
	<input type="hidden" name="cat_id" value="<?php echo $cat_id; ?>" />
	<div id="linkadvanceddiv" class="postbox">
		<div class="inside">
			<p class="inside-text"><span>分類名稱</span>
			<input type="text" name="cat_name" class="input" size="13" maxlength="10" value="<?php if ( !empty($data) ) echo $data->category_name; ?>" /></p>
		</div>
	</div>
	<input type="submit" name="save" class="button bold" value="儲存 &raquo;" />
</form>

<?php

}
// Show all categories
function bt_cats_display()
{
	global $wpdb;

	// Grab from the database
	$sql = 'SELECT * FROM ' . WP_BTAEON_CATEGORIES_TABLE . ' ORDER BY category_id DESC';
	$cats = $wpdb->get_results($sql);
	if ( !empty($cats) )
	{
?>
<table class="widefat page fixed" width="100%" cellpadding="3" cellspacing="3">
	<thead>
		<tr>
			<th width="3%" class="manage-column" scope="col">ID</th>
			<th class="manage-column" scope="col">分類名稱</th>
			<th width="3%" class="manage-column" scope="col">編輯</th>
			<th width="3%" class="manage-column" scope="col">刪除</th>
		</tr>
	</thead>
<?php
		foreach ( $cats as $cat )
		{
?>
	<tr>
		<th scope="row"><?php echo stripslashes($cat->category_id); ?></th>
		<td><?php echo stripslashes($cat->category_name); ?></td>
		<td><a href="<?php bloginfo('wpurl'); ?>/wp-admin/admin.php?page=btcat&amp;action=edit&amp;cat_id=<?php echo stripslashes($cat->category_id);?>" class='edit'>編輯</a></td>
		<td><a href="<?php bloginfo('wpurl'); ?>/wp-admin/admin.php?page=btcat&amp;action=delete&amp;cat_id=<?php echo stripslashes($cat->category_id);?>" class="delete" onclick="return confirm(您確定要刪除此分類？)">刪除</a></td>
	</tr>
<?php
		}
		echo '</table>';
	} else {
		echo '<p>資料庫中沒有公告</p>';
	}
}
?>
