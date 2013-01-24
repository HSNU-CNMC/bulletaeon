<?php
include_once('permcheck.php');

add_action( 'wp_ajax_update_sticky_msg', 'update_sticky_msg' );
function update_sticky_msg() {
	$set_sticky_id = intval($_POST['set_sticky']);

	$current_user = wp_get_current_user();
	if ( PermCheck::is_msg_owner($current_user->user_login, $set_sticky_id) ) {
		global $wpdb;
		// First set stickied to un-stickied
		if ( $wpdb->update( WP_BTAEON_TABLE,
			array(
				'sticky' => 0,
			),
			array(
				'sticky' => 1,
				'msg_owner' => $current_user->user_login,
			)) === false ) {//When update fails
				echo "資料庫操作失敗:1";
die;
			}
		// Then sticky the desired one
		if ( $wpdb->update( WP_BTAEON_TABLE,
			array(
				'sticky' => 1,
			),
			array(
				'msg_id' => $set_sticky_id,
				'msg_owner' => $current_user->user_login,
			)) == 0 ) {//When return false (error) or 0 (updated zero row)
				echo "資料庫操作失敗:2";
die;
			}
		echo "成功將公告 $set_sticky_id 置頂:1";
	} else {
		echo '您不是該公告的擁有者';
	}
die;
}

add_action('wp_ajax_clear_sticky_msg', 'clear_sticky_msg');
function clear_sticky_msg() {
	$current_user = wp_get_current_user();
	global $wpdb;
	if ( $wpdb->update( WP_BTAEON_TABLE,
		array(
			'sticky' => 0
		),
		array(
			'sticky' => 1,
			'msg_owner' => $current_user->user_login,
		)) == 0 ) {//When return false (error) or 0 (updated zero row)
		echo "資料庫操作失敗:3";
die;
	}
	echo "成功將您的所有公告取消置頂";
die;
}
?>
