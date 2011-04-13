<?php
// Register shortcode
add_shortcode('btaeon', 'btaeon_shortcode');
/*
 * Shortcode syntax:
 * [btaeon max=<number of messages to be shown per page> cat=(<category id>|all)] 
 * Set cat=all to show messages in all categories.
 */

function btaeon_shortcode( $atts )
{
	global $wpdb, $wp_query;
	extract(shortcode_atts(array(
		'max'	=> 15,
		'cat'	=> 1,
	), $atts ));

	// Validate
	if ( $cat != 'all' && $cat != 'old' )
	{
		$max = intval($max);
		$cat = intval($cat);
	}

	// What are we doing? displaying table or a single message?
	$btp = (empty($_GET['btp'])) ? '' : $wpdb->escape($_GET['btp']);
	$mid = (empty($_GET['mid'])) ? '' : $wpdb->escape($_GET['mid']);
	if ( !empty($btp) && empty($mid) )
	{
		// Show the table
		$curr_page = intval($wpdb->escape($_GET['btp']));
	        if ( empty($curr_page) || $curr_page < 1 ) $curr_page = 1;
	
		return shortcode_table( $max, $cat, $curr_page );
	} elseif ( empty($btp) && !empty($mid) ) {
		// Show single message
		$mid = intval($wpdb->escape($_GET['mid']));
		if ( empty($mid) || $mid < 1 ) return '<strong>Illegal input</strong>';

		return shortcode_single( $mid, $cat);
	} else {
		return shortcode_table( $max, $cat, 1 );
	}
}

function shortcode_table( $max, $cat, $curr_page )
{
	global $wpdb, $link;
	// Count rows for paging
	if ( $cat == 'all' )
	{
		$numrows = $wpdb->get_var("SELECT COUNT(msg_id) as rows FROM " . WP_BTAEON_TABLE);
	} elseif ( COMPAT_MODE == true && $cat == 'old' ) {
		if ( function_exists('connect_old_db') )
		{
			connect_old_db();
			// Limit maximum row of data, old messages should not be needed, change when necessary 
			$result = mysql_query('SELECT pno FROM boards ORDER BY pno DESC LIMIT 500', $link);
			$numrows = mysql_num_rows($result);
		}
	} else {
		$numrows = $wpdb->get_var("SELECT COUNT(msg_id) as rows FROM " . WP_BTAEON_TABLE . " WHERE msg_category='$cat'");
	}
        $numpages = ceil($numrows / $max);
	$offset = ($curr_page - 1) * $max;

	// Print the links to access each page
	$nav = '';
	for ( $btpage = 1; $btpage <= $numpages; $btpage++ )
	{
		// With few pages, print all the links
		if ( $numpages < 15 )
		{
			if ( $btpage == $curr_page ) $nav .= $btpage; // No need to create a link to current page
			else $nav .= ' <a href="?btp=' . $btpage . '">' . $btpage . '</a> ';
		} else {
			if ( $btpage == $curr_page )$nav .= $btpage; // No need to create a link to current page
			elseif ( $btpage == 1 || $btpage == $numpages ) $nav .= ''; // No need to create first and last (they are created by the first and last links afterwards)
			else {
				// Print links that are close to the current page (< 10 steps away)
				if ( $btpage < ($curr_page + 10) && $btpage > ($curr_page - 10) )
				{
					$nav .= ' <a href="?btp=' . $btpage . '">' . $btpage . '</a> ';
				}
			}
		}
	}

	// print first, last, next, previous links
	if ( $curr_page > 1 )
	{
		$btpage = $curr_page - 1;
		if ( $numpages >= 15 )
		{
			$first = ' <a href="?btp=1">&laquo;首頁</a> ';
		} else {
			$first = '';
		}
	} else {
		$prev = '&nbsp;'; // We're on page one, no need to print previous link
		if ( $numpages >= 15 )
		{
			$first = '&nbsp;';
		} else {
			$first = '';
		}
	}

	if ( $curr_page < $numpages )
	{
		$btpage = $curr_page + 1;
		if ( $numpages >= 15 )
		{
			$last = ' <a href="?btp=' . $numpages . '">末頁&raquo;</a> ';
		} else {
			$last = '';
		}
	} else {
		$next = '&nbsp;'; // We're on the last page
		if ( $numpages >= 15 )
		{
			$last = '&nbsp;';
		} else {
			$last = '';
		}
	}

	// Grab from the database
	if ( $cat == 'all' )
	{
		$sql = "SELECT msg_id, msg_time, msg_owner, msg_title FROM " . WP_BTAEON_TABLE . " ORDER BY msg_time DESC LIMIT $offset, $max";
		$rows = $wpdb->get_results($sql);
	} elseif ( COMPAT_MODE == true && $cat == 'old' ) {
		// INNER JOIN `users` to select Chinese usernames 
		$result = mysql_query("SELECT boards.pno as msg_id, users.cont as msg_owner, boards.topi as msg_title, boards.time as msg_time FROM boards INNER JOIN users ON boards.user=users.user ORDER BY boards.pno DESC LIMIT $offset, $max");
		$rows = mysql_fetch_object($result);
	} else {
		$sql = "SELECT msg_id, msg_time, msg_owner, msg_title FROM " . WP_BTAEON_TABLE . " WHERE msg_category='$cat' ORDER BY msg_time DESC LIMIT $offset, $max";
		$rows = $wpdb->get_results($sql);
	}

	if ( !empty($rows) )
	{
		$out = '<table id="bt-list">
				<colgroup>
					<col id=bt-list-time />
					<col id=bt-list-owner />
					<col id=bt-list-title />
				</colgroup>
				<thead>
				<tr>
				<th>張貼時間</th>
				<th>公告單位</th>
				<th>公告標題</th>
				</tr>
				</thead>';
		if ( $cat == 'old' )
		{
			while ( $row = mysql_fetch_object($result) )
			{
				$out .= '<tr>
					<td>' . $row->msg_time . '</td>
					<td>' . $row->msg_owner . '</td>
					<td><a href="?mid=' . $row->msg_id . '">' . htmlspecialchars(stripslashes($row->msg_title)) . '</a></td>
					</tr>
					';
			}
			mysql_close($link);
		} else {
			foreach ( $rows as $row )
			{
				// Display display_name rather than login name
				$userinfo = get_userdatabylogin($row->msg_owner);
				$owner = ( empty($userinfo) ) ? '' : $userinfo->display_name;
				$out .= '<tr>
					<td>' . convert_timestamp($row->msg_time) . '</td>
					<td>' . $owner . '</td>
					<td><a href="?mid=' . $row->msg_id . '">' . htmlspecialchars(stripslashes($row->msg_title)) . '</a></td>
					</tr>
					';
			}
		}
		$out .= '</table>';
	} else {
		$out .= '<p>資料庫中沒有公告</p>';
	}
	
	return $first . $nav . $last . $out . $first . $nav . $last;
}

function shortcode_single( $mid, $cat)
{
	global $wpdb, $link;
	if ( $cat == 'all' )
	{
		$sql = "SELECT * FROM " . WP_BTAEON_TABLE . " WHERE msg_id='$mid'";
		$row = $wpdb->get_results($sql);
		$row = $row[0];
	} elseif ( $cat == 'old' ) {
		if ( function_exists('connect_old_db') )
		{
			connect_old_db();
			// INNER JOIN `users` to select Chinese usernames 
			$result = mysql_query("SELECT boards.pno as msg_id, users.cont as msg_owner, boards.topi as msg_title, boards.cont as msg_content, boards.time as msg_time FROM boards INNER JOIN users ON boards.user=users.user WHERE pno='$mid'");
			$row = mysql_fetch_object($result);
		}
	} else {
		$sql = "SELECT * FROM " . WP_BTAEON_TABLE . " WHERE msg_id='$mid' AND msg_category='$cat'";
		$row = $wpdb->get_results($sql);
		$row = $row[0];
	}

	if ( !empty($row) )
	{
		if ( $cat == 'old' )
		{
			// Replace English usernames with Chinese ones
			/*$search = array( 'register', 'experiment', 'guidance', 'computer', 'teaching', 'teacher', 'equipment', 'train', 'life', 'sanitation', 'physical', 'health', 'instructor', 'person', 'affair', 'library', 'cnmc', 'principal', 'book', 'jhsnu', 'art', 'music', 'account', 'art-life', 'language');
			$replace = array('註冊組', '實驗研究組', '輔導老師', '資訊人員', '教學組', '實習輔導組', '設備組', '訓育組', '生活輔導組', '衛生組', '體育組', '健康中心', '教官', '人事員', '庶務員', '行政人員', '網管小組', '校長室', '文書組', '國中部', '美術班', '音樂班', '會計室', '藝術生活學科中心', '第二外語資源中心');
			$row->user = str_replace($search, $replace, $row->user);*/
			$out = "<table id=\"sh-mid\">
				<tr>
					<td class=\"sh-mid-left\">公告標題</td>
					<td>" . htmlspecialchars(stripslashes($row->msg_title)) . "</td>
				</tr>
				<tr>
					<td class=\"sh-mid-left\">公告時間</td>
					<td>$row->msg_time</td>
				</tr>
				<tr>
					<td class=\"sh-mid-left\">公告單位</td>
					<td>$row->msg_owner</td>
				</tr>
				<tr>
					<td class=\"sh-mid-left\">內容</td>
					<td id=\"sh-mid-content\">" . nl2br(htmlspecialchars(stripslashes($row->msg_content))) . "</td>
				</tr>
				<tr>
					<td class=\"sh-mid-left\"></td>
					<td id=\"sh-mid-content\"><a href=\"http://www.hs.ntnu.edu.tw/code.php?id=1&s=news&url=1&p=bulletin/board/postview.php?pno=$row->msg_id\">以舊公告系統檢視</a></td>
				</tr>
				</table>";

		} else {
			// Display display_name rather than login name
			$userinfo = get_userdatabylogin($row->msg_owner);
			$owner = ( empty($userinfo) ) ? '' : $userinfo->display_name;
			$out = "<table id=\"sh-mid\">
				<colgroup>
					<col class=\"sh-mid-left\" />
					<col/>
				</colgroup>
				<tr>
					<td>公告標題</td>
					<td>" . htmlspecialchars(stripslashes($row->msg_title)) . "</td>
				</tr>
				<tr>
					<td>公告時間</td>
					<td>" . convert_timestamp($row->msg_time) . "</td>
				</tr>
				<tr>
					<td>公告單位</td>
					<td>$owner</td>
				</tr>
				<tr>
					<td>內容</td>
					<td id=\"sh-mid-content\">" . nl2br(htmlspecialchars(stripslashes($row->msg_content))) . "</td>
				</tr>";
			if ( !empty($row->msg_link) )
			{
				$out .= '<tr>
					<td>連結</td>
					<td>';
				$link_arr = unserialize($row->msg_link);
				$loop = '';
				foreach ( $link_arr as $key => $l )
				{
					$uri = $l[0];
					$description = $l[1];
					if ( empty($uri) && empty($description) )
					{
						continue;
					} else {
						if ( empty($description) )
							$description = $uri;
						$loop .= '<a href="' . $uri . '">' . $description . '</a>, ';
					}
				}
				$loop = rtrim($loop, ', ');
				$out .= $loop .'</td>
					</tr>
					<tr>';
			}

			if ( !empty($row->msg_file) )
			{
				$out .=	'<td>附件</td>
					<td>';
				$file_arr = unserialize($row->msg_file);
				$loop = '';
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
							$loop .= '<a href="' . $uri . '">' . $name . '</a>, ';
						}
					}
				}
				$loop = rtrim($loop, ', ');
				$out .= $loop . '</td>
					</tr>';
			}
			$out .= '</table>';
		}
	} else {
		$out = '<p>該公告已經被刪除了</p>';
	}
	$out .= '<a href="javascript:history.back()">上一頁</a>';

	return $out;
}

function convert_timestamp($timestamp)
{
	$date = date('Y-m-d', strtotime($timestamp));
	return $date;
}
?>
