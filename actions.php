<?php
/*
 * These functions are registered with "actions", they could be called by themes using do_action()
 */

// Function to show "Newest messages"
function get_newmsg($max, $cat='all', $title='最新消息', $more='')
{
	global $wpdb;

	if ( !is_int($max) ) $max = 10;
	if ( $cat == 'all' )
	{
		$sql = "SELECT msg_id, msg_time, msg_title FROM " . WP_BTAEON_TABLE . " ORDER BY msg_id DESC LIMIT $max";
		$rows = $wpdb->get_results($sql);
	} elseif ( $cat > 0 ) {
		$sql = "SELECT msg_id, msg_time, msg_title FROM " . WP_BTAEON_TABLE . " WHERE msg_category='$cat' ORDER BY msg_id DESC LIMIT $max";
		$rows = $wpdb->get_results($sql);
	}

	if ( !empty($rows) )
	{
		$out = '<table id="newmsg">
			<colgroup>
				<col id="newmsg_time"/>
				<col />
			</colgroup>';
		foreach ( $rows as $row )
		{
			$out .= '<tr>
				<td>' . convert_timestamp($row->msg_time) . '</td>
				<td><a href="' . get_option("bt_showall_url") . '?mid=' . $row->msg_id . '">' . stripslashes($row->msg_title) . '</a></td>
				</tr>
				';
		}

		$out .= '</table>';
	} else {
		$out = '<p>找不到公告</p>';
	}
	
	echo '<h3>' . $title . '</h3>' . $out;
	if ( !empty($more) ) echo '<p id="newmsg_time_more"><a href="' . $more . '">更多</a></p>';
}
add_action('get_newmsg', 'get_newmsg', 10, 4);

// Function to show the search result
function get_bt_search($query, $curr_page)
{
	global $wpdb;
	$numrows = $wpdb->get_var("SELECT COUNT(msg_id) as rows FROM " . WP_BTAEON_TABLE . " WHERE msg_title LIKE '%$query%'");
	$max = 15;
        $numpages = ceil($numrows / $max);
	$offset = ($curr_page - 1) * $max;

	// Print the links to access each page
	$nav = '';
	for ( $spage = 1; $spage <= $numpages; $spage++ )
	{
		// With few pages, print all the links
		if ( $numpages < 4 )
		{
			if ( $spage == $curr_page ) $nav .= $spage; // No need to create a link to current page
			else $nav .= ' <a href="?sp=' . $spage . '&st=bt&sq=' . $query . '">' . $spage . '</a> ';
		} else {
			if ( $spage == $curr_page )$nav .= $spage; // No need to create a link to current page
			elseif ( $spage == 1 || $spage == $numpages ) $nav .= ''; // No need to create first and last (they are created by the first and last links afterwards)
			else {
				// Print links that are close to the current page (< 3 steps away)
				if ( $spage < ($curr_page + 3) && $spage > ($curr_page - 3) )
					$nav .= ' <a href="?sp=' . $spage . '&st=bt&sq=' . $query . '">' . $spage . '</a> ';
			}
		}
	}

	// print first, last, next, previous links
	if ( $curr_page > 1 )
	{
		$spage = $curr_page - 1;
		if ( $numpages >= 4 )
			$first = ' <a href="?sp=1&st=bt&sq=' . $query . '">&laquo;首頁</a> ';
		else
			$first = '';
	} else {
		$prev = '&nbsp;'; // We're on page one, no need to print previous link
		if ( $numpages >= 4 )
			$first = '&nbsp;';
		else
			$first = '';
	}

	if ( $curr_page < $numpages )
	{
		$spage = $curr_page + 1;
		if ( $numpages >= 4 )
			$last = ' <a href="?sp=' . $numpages . '&st=bt&sq=' . $query . '">末頁&raquo;</a> ';
		else
			$last = '';
	} else {
		$next = '&nbsp;'; // We're on the last page
		if ( $numpages >= 4 )
			$last = '&nbsp;';
		else
			$last = '';
	}
	
	// Grab the search results
	$rows = $wpdb->get_results("SELECT msg_id, msg_time, msg_owner, msg_title FROM " . WP_BTAEON_TABLE . " WHERE msg_title LIKE '%$query%' ORDER BY msg_id DESC LIMIT $offset, $max");
	if ( !empty($rows) )
	{
		$out = '<table>';
		foreach ( $rows as $row )
		{
			// Display display_name rather than login name
			$userinfo = get_userdatabylogin($row->msg_owner);
			$out .= '<tr>
				<td>' . convert_timestamp($row->msg_time) . '</td>
				<td>' . $userinfo->display_name . '</td>
				<td><a href="' . get_bloginfo('wpurl') . get_option("bt_showall_url") . '?mid=' . $row->msg_id . '">' . stripslashes($row->msg_title) . '</a></td>
				</tr>
				';
		}
		$out .= '</table>';
	} else {
		$out = '<p>找不到符合條件的公告</p>';
	}	
			
	echo $first . $nav . $last . $out . $first . $nav . $last;

}
add_action('get_bt_search', 'get_bt_search', 10, 2);

?>
