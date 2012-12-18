<?php
/*
 * Check if the user owns the message, return "admin" for admins
 * @user: Which user to check (Use user_login)
 * @msg_id: which msg to check
 */
Class PermCheck Extends Bulletaeon {
	function is_msg_owner ( $user, $msg_id ) {
		$msg = DBOperator::get_msg_by_id($msg_id);
		if ( $msg ) {
			if ( $msg->msg_owner == $user )
				return true;
			else
				return false;
		} else {
			return NULL;
		}
	}
}

?>
