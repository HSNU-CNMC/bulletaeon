<?php
/*
 * If you need any more options, just add it to $bt_options array. Simple, isn't it?
 * When calling: get_options('bt_<id>');
 */
$pluginname = 'Bulletaeon';
$shortname = 'bt';

$bt_options = array(
	array(
		"name" => __('管理頁面每頁所顯示的公告數'),
		"desc" => __(''),
		"id" => $shortname."_msgs_per_page",
		"std" => "15",
		"type" => "text"),
	array(
		"name" => __('啓動舊系統相容模組？'),
		"desc" => __(''),
		"id" => $shortname."_compat",
		"std" => 'false',
		"type" => "checkbox"),
	array(
		"name" => __('Where do you insert the [btaeon cat=all] shortcode?'),
		"desc" => __(''),
		"id" => $shortname."_showall_url",
		"std" => "",
		"type" => "text"),
	array(
		"name" => __('Where do you insert the [btaeon cat=old] shortcode?'),
		"desc" => __(''),
		"id" => $shortname."_showold_url",
		"std" => "",
		"type" => "text"),
);

function bt_admin_redirect()
{
	global $pluginname, $bt_options;
	if ( isset($_GET['page']) && $_GET['page'] == 'btconf' )
	{
		if ( isset($_REQUEST['action']) && $_REQUEST['action'] == 'save' )
		{
			foreach ($bt_options as $value)
			{
				update_option( $value['id'], $_REQUEST[ $value['id'] ] );
			}
			foreach ($bt_options as $value)
			{
				if( isset( $_REQUEST[ $value['id'] ] ) )
				{
					update_option( $value['id'], $_REQUEST[ $value['id'] ]  );
				} else {
					delete_option( $value['id'] );
				}
			}
			header("Location: admin.php?page=btconf&saved=true");
			die;
		} elseif ( isset($_REQUEST['action']) && $_REQUEST['action'] == 'reset' ) {
			foreach ($bt_options as $value)
			{
				delete_option( $value['id'] );
			}
			header("Location: admin.php?page=btconf&reset=true");
			die;
		} elseif ( isset($_REQUEST['action']) && $_REQUEST['action'] == 'reset_widgets' ) {
			$null = null;
			update_option('sidebars_widgets',$null);
			header("Location: admin.php?page=btconf&reset=true");
			die;
		}
	}
}

function bt_config()
{
	global $bt_options;
	// Wrapper function for handling the ordinate Bulletaeon Options Admin Page
	bt_admin_generator($bt_options);
}

function bt_admin_generator($options_to_generate)
{
	// This function renders the actual admin page based on $options_to_generate
	global $pluginname, $shortname;
	if ( isset($_REQUEST['saved']) && $_REQUEST['saved'] ) echo '<div id="message" class="updated fade"><p><strong>'.$pluginname.' '.__('settings saved.','thematic').'</strong></p></div>';
	if ( isset($_REQUEST['reset']) && $_REQUEST['reset'] ) echo '<div id="message" class="updated fade"><p><strong>'.$pluginname.' '.__('settings reset.','thematic').'</strong></p></div>';
	if ( isset($_REQUEST['reset_widgets']) && $_REQUEST['reset_widgets'] ) echo '<div id="message" class="updated fade"><p><strong>'.$pluginname.' '.__('widgets reset.','thematic').'</strong></p></div>';
?>
<div="wrap">
	<?php if ( function_exists('screen_icon') ) screen_icon(); ?>
	<h2><?php echo $pluginname; ?> 設定</h2>
	<form method="post" action="">
		<table class="form-table">
		<?php foreach ($options_to_generate as $value) {
			switch ( $value['type'] )
			{
				case 'text': ?>
		<tr valign="top">
			<th scope="row"><label for="<?php echo $value['id']; ?>"><?php echo __($value['name'],'thematic'); ?></label></th>
			<td>
				<input name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" type="<?php echo $value['type']; ?>" value="<?php if ( get_option( $value['id'] ) != "") { echo get_option( $value['id'] ); } else { echo $value['std']; } ?>" />
				<?php echo __($value['desc'],'thematic'); ?>
			</td>
		</tr>
	<?php				break;

				case 'textarea':
							$ta_options = $value['options']; ?>
		<tr valign="top">
			<th scope="row"><label for="<?php echo $value['id']; ?>"><?php echo __($value['name'],'thematic'); ?></label></th>
			<td><textarea name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" cols="<?php echo $ta_options['cols']; ?>" rows="<?php echo $ta_options['rows']; ?>"><?php
			if ( get_option($value['id']) != '')
			{
				echo __(stripslashes(get_option($value['id'])),'thematic');
			} else {
				echo __($value['std'],'thematic');
			} ?></textarea><br />
			<?php echo __($value['desc'],'thematic'); ?>
			</td>
		</tr>
		<?php			break;
				case 'nothing':
						$ta_options = $value['options']; ?>
		</table>
			<?php echo __($value['desc'],'thematic'); ?>
		<table class="form-table">
		<?php			break;
				case 'radio': ?>
		<tr valign="top">
			<th scope="row"><?php echo __($value['name'],'thematic'); ?></th>
			<td>
			<?php foreach ( $value['options'] as $key=>$option )
				{
					$radio_setting = get_option($value['id']);
					if ( $radio_setting != '' )
					{
						if ($key == get_option($value['id']) )
						{
							$checked = 'checked="checked"';
						} else {
							$checked = '';
						}
				} else {
					if ( $key == $value['std'] )
					{
						$checked = 'checked="checked"';
					} else {
						$checked = '';
					}
				} ?>
				<input type="radio" name="<?php echo $value['id']; ?>" id="<?php echo $value['id'] . $key; ?>" value="<?php echo $key; ?>" <?php echo $checked; ?> />
				<label for="<?php echo $value['id'] . $key; ?>"><?php echo $option; ?></label><br />
				<?php } ?>
			</td>
		</tr>
		<?php			break;
				case 'checkbox': ?>
		<tr valign="top">
			<th scope="row"><?php echo __($value['name'],'thematic'); ?></th>
			<td>
				<?php
					if ( get_option($value['id']) )
					{
						$checked = 'checked="checked"';
					} else {
						$checked = '';
					}
				?>
				<input type="checkbox" name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" value="true" <?php echo $checked; ?> />
				<label for="<?php echo $value['id']; ?>"><?php echo __($value['desc'],'thematic'); ?></label>
			</td>
		</tr>
		<?php			break;
				default:
					break;
			}
		}
?>

	</table>

	<p class="submit">
		<input name="save" type="submit" value="<?php _e('Save changes','thematic'); ?>" />
		<input type="hidden" name="action" value="save" />
	</p>
</form>
<form method="post" action="">
	<p class="submit">
		<input name="reset" type="submit" value="<?php _e('Reset'); ?>" />
		<input type="hidden" name="action" value="reset" />
	</p>
</form>
</div>
<?php
}
?>
