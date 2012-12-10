<?php
include_once('permcheck.php');

add_action( 'wp_ajax_update_sticky_msg', 'update_sticky_msg' );
function update_sticky_msg() {
	$set_sticky_id = intval($_POST['set_sticky']);

	$current_user = wp_get_current_user();
	if ( current_user_can('install_plugins') || PermCheck::is_msg_owner($current_user->user_login, $set_sticky_id) ) {
		global $wpdb;
		// First set stickied to un-stickied
		$sql = "UPDATE ".WP_BTAEON_TABLE." SET sticky=0 WHERE sticky=1 AND msg_owner='".$current_user->user_login."';";
		$wpdb->get_results($sql);
		// Then sticky the desired one
		$sql = "UPDATE ".WP_BTAEON_TABLE." SET sticky=1 WHERE msg_id='".$set_sticky_id."' AND msg_owner='".$current_user->user_login."';";
		$wpdb->get_results($sql);
		echo "成功將公告 $set_sticky_id 置頂";
	} else {
		echo '您不是該公告的擁有者';
	}
die;
}

add_action('wp_ajax_clear_sticky_msg', 'clear_sticky_msg');
function clear_sticky_msg() {
	$current_user = wp_get_current_user();
	global $wpdb;

	$sql = "UPDATE ".WP_BTAEON_TABLE." SET sticky=0 WHERE sticky=1 AND msg_owner='".$current_user->user_login."';";
	$wpdb->get_results($sql);
	echo "成功將您的所有公告取消置頂";
die;
}
?>
