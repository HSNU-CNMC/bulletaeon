<?php
// Function to handle attachments
function atta_upload( $time, $file, $action = 'add' )
{
	wp_get_current_user();
	global $current_user, $wpdb;

	// Change $file (string) to $file_arr (array), so it'll be easier to handle
	$atta = $_FILES['atta'];
	$file_arr = (empty($file)) ? '' : unserialize($file);
	foreach ( $atta['size'] as $key => $value )
	{
		// Handle editing attachments
		if ( $action == 'edit_save' ) 
		{
			// Did the user check the "Delete this file" checkbox?
			if ( isset($_POST['delete_atta'][$key]) && $_POST['delete_atta'][$key] == 'yes' )
			{
				$file_arr[$key] = '';
				$atta_ok = 1;
				continue;
			}
			// Is this upload originally empty?
			if ( empty($file_arr[$key]) )
			{
				// It's empty
				// Did the user submitted a new file? yes: it will do the checks below
				if ( $value == 0 )
				{
					$atta_ok = 1;
					continue;
				}
			} else {
				// It's not empty
				// Did the user submitted a new file? yes: it will do the checks below
				if ( $value == 0 )
				{
					$atta_ok = 1;
					continue;
				}
			}
		}

		if ( $value > 0 )
		{
			// Validate the uploaded file
			if ( !is_uploaded_file($atta['tmp_name'][$key]) )
			{
				echo '<div class="error"><p><strong>錯誤：</strong>Bad monkey! no babana! ' . $key . '</div>';
				$atta_ok = 0;
				break;
			}

			if ( $atta['error'][$key] > 0 )
			{
				switch ( $atta['error'][$key] )
				{
				case 1:
					echo '<div class="error"><p><strong>錯誤：</strong>檔案太大了！（upload_max_filesize）</div>';
					$atta_ok = 11;
					break;
				case 2: 
					echo '<div class="error"><p><strong>錯誤：</strong>檔案太大了！（max_file_size）</div>';
					$atta_ok = 12;
					break;
				case 3: 
					echo '<div class="error"><p><strong>錯誤：</strong>傳輸中斷，只上傳了一部分</div>';
					$atta_ok = 13;
					break;
				case 4: 
					echo '<div class="error"><p><strong>錯誤：</strong>沒有上傳任何檔案</div>';
					$atta_ok = 14;
					break;
				case 6: 
					echo '<div class="error"><p><strong>錯誤：</strong>未指定伺服器的暫存目錄</div>';
					$atta_ok = 16;
					break;
				case 7: 
					echo '<div class="error"><p><strong>錯誤：</strong>無法寫入磁碟</div>';
					$atta_ok = 17;
					break;
				}
			}

			// Check file type
			$filepart = pathinfo($atta['name'][$key]);
			$ext = array('csv', 'doc', 'xls', 'odt', 'ods', 'txt', 'pdf', 'jpg', 'png', 'gif');
			if ( in_array($filepart['extension'], $ext) )
			{
				$upload_dir = WP_CONTENT_DIR . '/bt_uploads/';
				if ( is_writable($upload_dir) )
				{
					// Get the time
					$date = explode(" ", $time);
					$date = $date[0];
					list($year, $month, $day) = explode("-", $date);

					// Check if the destination folder exists, if not, create it
					// The structure is bt_uploads/<username>/<year>/<month>/<filename>
					while ( !isset($u_exists) || $u_exists == 0 )
					{
						$ls_u = scandir($upload_dir);
						foreach ( $ls_u as $u )
						{
							if ( $u == $current_user->user_login )
							{
								$u_exists = 1;
								$upload_dir .= $u . '/';
								break;
							} else {
								$u_exists = 0;
							}
						}
						if ( $u_exists == 0 )
						{
							$dir = $upload_dir . $current_user->user_login;
							mkdir($dir);
						}
					}
					unset($u_exists);
					while ( !isset($y_exists) || $y_exists == 0 )
					{
						$ls_y = scandir($upload_dir);
						foreach ( $ls_y as $y )
						{
							if ( $y == $year )
							{
								$y_exists = 1;
								$upload_dir .= $y . '/';
								break;
							} else {
								$y_exists = 0;
							}
						}
						if ( $y_exists == 0 )
						{
							$dir = $upload_dir . $year;
							mkdir($dir);
						}
					}
					unset($y_exists);
					while ( !isset($m_exists) || $m_exists == 0 )
					{
						$ls_m = scandir($upload_dir);
						foreach ( $ls_m as $m )
						{
							if ( $m == $month )
							{
								$m_exists = 1;
								$upload_dir .= $m . '/';
								break;
							} else {
								$m_exists = 0;
							}
						}
						if ( $m_exists == 0 )
						{
							$dir = $upload_dir . $month;
							mkdir($dir);
						}
					}
					unset($m_exists);

					// Check if the filename already exists in the folder
					$i = 0;
					$filename = $filepart['basename'];
					$full_path = $filepart['dirname'] . $filepart['basename'];
					if ( file_exists($full_path) )
					{
						// Rename if already exists
						$filename = $filepart['filename'] . '_' . $i++ . '.' .$filepart['extension'];
					}

					$dest_file = $upload_dir . $filename;
					$temp_file = $atta['tmp_name'][$key];

					// Check for folder permission
					if ( !is_writable($upload_dir) )
					{
						echo '<div class="error"><p><strong>錯誤：</strong>無法寫入子目錄</p></div>';
						$atta_ok = 0;
						break;
					} else {
						// Move the file
						if ( !@move_uploaded_file($temp_file, $dest_file) )
						{
							echo '<div class="error"><p><strong>錯誤：</strong>無法移動檔案到子目錄</p></div>';
							$atta_ok = 0;
							break;
						} else {
							$atta_ok = 1;
							$file_arr[$key] = "bt_uploads/$u/$y/$m/$filename";
						}
					}
				} else {
					echo '<div class="error"><p><strong>錯誤：</strong>無法寫入上傳目錄</p></div>';
					$atta_ok = 0;
					break;
				}
			} else {
				echo '<div class="error"><p><strong>錯誤：</strong>不允許此檔案類型的附件</p></div>';
				$atta_ok = 0;
				break;
			}
		} elseif ( $value == 0 ) {
			$file_arr[$key] = '';
			$atta_ok = 1;
		} else {
			$atta_ok = 0;
			break;
		}
	}

	// Check if $file_arr only contains empty elements, if so, set it to empty to prevent overhead
	if ( is_array($file_arr) && count(array_filter($file_arr)) != 0 )
	{
		$file_empty = 0;
	} else {
		$file_empty = 1;
	}
	
	if ( $file_empty == 0 )
	{
		// Serialize $file_arr so we can save it to the database later
		$file_serialized = serialize($file_arr);
		$file_serialized = $wpdb->escape($file_serialized);
	} else {
		$file_serialized = '';
	}

	//echo "<div class=\"error\"><p>$file</p></div>";
	$reply = array('atta_ok' => $atta_ok, 'file' => $file_serialized);

	return $reply;
}
?>
