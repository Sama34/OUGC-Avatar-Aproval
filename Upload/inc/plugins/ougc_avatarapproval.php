<?php

/***************************************************************************
 *
 *	OUGC Avatar Approval plugin (/inc/plugins/ougc_avatarapproval.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2014 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Allow moderators to manage avatar uploads/updates.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.');

// Run/Add Hooks
if(defined('IN_ADMINCP'))
{
	$plugins->add_hook('admin_config_settings_start', create_function('&$args', 'global $avatarapproval;	$avatarapproval->lang_load();'));
	$plugins->add_hook('admin_style_templates_set', create_function('&$args', 'global $avatarapproval;	$avatarapproval->lang_load();'));
	$plugins->add_hook('admin_config_settings_change', 'ougc_avatarapproval_settings_change');

	// Cache manager
	$funct = create_function('', '
			control_object($GLOBALS[\'cache\'], \'
			function update_ougc_avatarapproval()
			{
				global $avatarapproval;

				$avatarapproval->cache_update();
			}
		\');
	');
	$plugins->add_hook('admin_tools_cache_start', $funct);
	$plugins->add_hook('admin_tools_cache_rebuild', $funct);
	unset($funct);
}
else
{
	global $templatelist;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	else
	{
		$templatelist = '';
	}

	$templatelist .= 'ougcavatarapproval_notification';

	if(THIS_SCRIPT == 'modcp.php')
	{
		global $mybb;

		$templatelist .= ',ougcavatarapproval_modcp_nav';

		if($mybb->input['action'] == 'avatarapproval')
		{
			$templatelist .= ',ougcavatarapproval_modcp_list_item,ougcavatarapproval_modcp,ougcavatarapproval_modcp_list_options_tcat,ougcavatarapproval_modcp_buttons,ougcavatarapproval_modcp_list_empty';
		}
	}

	$plugins->add_hook('usercp_start', 'ougc_avatarapproval_usercp');
	$plugins->add_hook('usercp_avatar_end', 'ougc_avatarapproval_avatar_end');
	$plugins->add_hook('global_end', 'ougc_avatarapproval_global');
	$plugins->add_hook('modcp_start', 'ougc_avatarapproval_modcp');

	// My Alerts
	$plugins->add_hook('myalerts_load_lang', create_function('', 'global $avatarapproval; $avatarapproval->lang_load();'));
	$plugins->add_hook('misc_help_helpdoc_start', 'ougc_avatarapproval_myalerts_helpdoc');
	$plugins->add_hook('myalerts_alerts_output_end', 'ougc_avatarapproval_myalerts_output');
}

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

// Plugin API
function ougc_avatarapproval_info()
{
	global $lang, $avatarapproval;
	$avatarapproval->lang_load();

	return array(
		'name'			=> 'OUGC Avatar Approval',
		'description'	=> $lang->setting_group_ougc_avatarapproval_desc,
		'website'		=> 'http://omarg.me',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '1.8',
		'versioncode'	=> 1800,
		'compatibility'	=> '18*',
		'pl'			=> array(
			'version'	=> 12,
			'url'		=> 'http://mods.mybb.com/view/pluginlibrary'
		)
	);
}

// _activate() routine
function ougc_avatarapproval_activate()
{
	global $PL, $lang, $avatarapproval, $cache;
	ougc_avatarapproval_deactivate();

	// Add settings group
	$PL->settings('ougc_avatarapproval', $lang->setting_group_ougc_avatarapproval, $lang->setting_group_ougc_avatarapproval_desc, array(
		'bypassgroups'		=> array(
		   'title'			=> $lang->setting_ougc_avatarapproval_bypassgroups,
		   'description'	=> $lang->setting_ougc_avatarapproval_bypassgroups_desc,
		   'optionscode'	=> 'text',
		   'value'			=> '3,4,6'
		),
		'modgroups'			=> array(
		   'title'			=> $lang->setting_ougc_avatarapproval_modgroups,
		   'description'	=> $lang->setting_ougc_avatarapproval_modgroups_desc,
		   'optionscode'	=> 'text',
		   'value'			=> '3,4,6'
		),
		'appvotes'			=> array(
		   'title'			=> $lang->setting_ougc_avatarapproval_appvotes,
		   'description'	=> $lang->setting_ougc_avatarapproval_appvotes_desc,
		   'optionscode'	=> 'text',
		   'value'			=> 3
		),
		'rejvotes'			=> array(
		   'title'			=> $lang->setting_ougc_avatarapproval_rejvotes,
		   'description'	=> $lang->setting_ougc_avatarapproval_rejvotes_desc,
		   'optionscode'	=> 'text',
		   'value'			=> 2
		),
		'sendpm'			=> array(
		   'title'			=> $lang->setting_ougc_avatarapproval_sendpm,
		   'description'	=> $lang->setting_ougc_avatarapproval_sendpm_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
		),
		'myalerts'			=> array(
		   'title'			=> $lang->setting_ougc_avatarapproval_myalerts,
		   'description'	=> $lang->setting_ougc_avatarapproval_myalerts_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 0
		),
		'modgallery'		=> array(
		   'title'			=> $lang->setting_ougc_avatarapproval_modgallery,
		   'description'	=> $lang->setting_ougc_avatarapproval_modgallery_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 0
		),
		'adminoverride'		=> array(
		   'title'			=> $lang->setting_ougc_avatarapproval_adminoverride,
		   'description'	=> $lang->setting_ougc_avatarapproval_adminoverride_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 0
		),
		'queue_perpage'	=> array(
		   'title'			=> $lang->setting_ougc_avatarapproval_queue_perpage,
		   'description'	=> $lang->setting_ougc_avatarapproval_queue_perpage_desc,
		   'optionscode'	=> 'text',
		   'value'			=> 20
		),
		'maxwh'	=> array(
		   'title'			=> $lang->setting_ougc_avatarapproval_maxwh,
		   'description'	=> $lang->setting_ougc_avatarapproval_maxwh_desc,
		   'optionscode'	=> 'text',
		   'value'			=> '40x40'
		),
	));

	// Add template group
	$PL->templates('ougcavatarapproval', '<lang:setting_group_ougc_avatarapproval>', array(
		'notification'		=> '<div class="pm_alert">
	<div>{$message}</div>
</div>
{$br}',
		'modcp_nav'			=> '<tr><td class="trow1 smalltext"><a href="modcp.php?action=avatarapproval" class="modcp_nav_item" style="background: url(\'images/modcp/modqueue.gif\') no-repeat left center;">{$lang->ougc_avatarapproval_modcp_nav}</a></td></tr>',
		'modcp_list_empty'	=> '<tr>
	<td class="trow1" colspan="{$colspan}" align="center">
		{$lang->ougc_avatarapproval_modcp_list_empty}
	</td>
</tr>',
		'modcp'				=> '<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->ougc_avatarapproval_modcp_nav}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<table width="100%" border="0" align="center">
			<tr>
				{$modcp_nav}
				<td valign="top">
				{$errors}
				<form action="{$mybb->settings[\'bburl\']}/modcp.php?action=avatarapproval" method="post">
				<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
				<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
					<tr>
						<td class="thead" colspan="{$colspan}">
							<strong>{$lang->ougc_avatarapproval_modcp_nav}</strong><span style="float: right;">(<a href="{$mybb->settings[\'bburl\']}/modcp.php?action=avatarapproval&amp;status=1">{$lang->ougc_avatarapproval_modcp_approved}</a> - <a href="{$mybb->settings[\'bburl\']}/modcp.php?action=avatarapproval&amp;status=2">{$lang->ougc_avatarapproval_modcp_rejected}</a>)</span>
						</td>
					</tr>
					<tr>
						<td class="tcat"><strong class="smalltext">{$lang->ougc_avatarapproval_modcp_username}</strong></td>
						<td class="tcat" align="center"><strong class="smalltext">{$lang->ougc_avatarapproval_modcp_avatar}</strong></td>
						<td class="tcat" align="center"><strong class="smalltext">{$lang->ougc_avatarapproval_modcp_date}</strong></td>
						<td class="tcat" align="center"><strong class="smalltext">{$lang->ougc_avatarapproval_modcp_approvedcount}</strong></td>
						<td class="tcat" align="center"><strong class="smalltext">{$lang->ougc_avatarapproval_modcp_rejectcount}</strong></td>
						{$options_tcat}
					</tr>
					{$approvals}
				</table>
				{$buttons}
				</form>
				{$multipage}
				</td>
			</tr>
		</table>
		{$footer}
	</body>
</html>',
		'modcp_list_item'	=> '<tr>
	<td class="{$bgcolor}">{$approval[\'profilelink\']}</td>
	<td class="{$bgcolor}" align="center"><img src="{$approval[\'avatar\'][\'image\']}" alt="{$approval[\'username\']}" title="{$approval[\'username\']}" width="{$approval[\'avatar\'][\'width\']}" height="{$approval[\'avatar\'][\'height\']}" /></td>
	<td class="{$bgcolor}" align="center">{$approval[\'date\']}, {$approval[\'time\']}<br/></td>
	<td class="{$bgcolor}" align="center">{$appvotes}<br/>{$applist}</td>
	<td class="{$bgcolor}" align="center">{$rejvotes}<br/>{$rejlist}</td>
	{$options_row}
</tr>',
		'modcp_list_options_tcat'	=> '<td class="tcat" align="center"><strong class="smalltext">{$lang->ougc_avatarapproval_modcp_select}</strong></td>',
		'modcp_list_options_row'	=> '<td class="{$bgcolor}" align="center" width="1%"><input type="checkbox" class="checkbox" name="selected[]" value="{$approval[\'aid\']}"{$inlinecheck} /></td>',
		'modcp_buttons'	=> '<br /><div align="center"><input type="submit" class="button" name="approve" value="{$lang->ougc_avatarapproval_modcp_approve}" /><input type="submit" class="button" name="reject" value="{$lang->ougc_avatarapproval_modcp_reject}" /></div>'
	));

	// Modify templates
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('header', '#'.preg_quote('{$pm_notice}').'#', '<!--OUGC_AVATARAPPROVAL-->{$pm_notice}');
	find_replace_templatesets('usercp_avatar', '#'.preg_quote('{$avatar_error}').'#', '{$ougc_avatarapproval}{$avatar_error}');
	find_replace_templatesets('usercp_avatar_gallery', '#'.preg_quote('{$avatar_error}').'#', '{$ougc_avatarapproval}{$avatar_error}');
	find_replace_templatesets('modcp_nav', '#'.preg_quote('mcp_nav_ipsearch}</a></td></tr>').'#', 'mcp_nav_ipsearch}</a></td></tr><!--OUGC_AVATARAPPROVAL_NAV-->');

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = ougc_avatarapproval_info();

	if(!isset($plugins['avatarapproval']))
	{
		$plugins['avatarapproval'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/

	/*~*~* RUN UPDATES END *~*~*/

	$plugins['avatarapproval'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);
}

// _deactivate() routine
function ougc_avatarapproval_deactivate()
{
	ougc_avatarapproval_pl_check();

	// Revert template edits
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('header', '#'.preg_quote('<!--OUGC_AVATARAPPROVAL-->').'#', '', 0);
	find_replace_templatesets('usercp_avatar_gallery', '#'.preg_quote('{$ougc_avatarapproval}').'#', '', 0);
	find_replace_templatesets('usercp_avatar', '#'.preg_quote('{$ougc_avatarapproval}').'#', '', 0);
	find_replace_templatesets('modcp_nav', '#'.preg_quote('<!--OUGC_AVATARAPPROVAL_NAV-->').'#', '', 0);
}

// _install() routine
function ougc_avatarapproval_install()
{
	global $db;

	// Create our table(s)
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_avatarapproval` (
			`aid` bigint(30) UNSIGNED NOT NULL AUTO_INCREMENT,
			`uid` bigint(30) NOT NULL DEFAULT '0',
			`avatar` varchar(200) NOT NULL DEFAULT '',
			`avatardimensions` varchar(10) NOT NULL DEFAULT '',
			`avatartype` varchar(10) NOT NULL DEFAULT '',
			`status` smallint(1) NOT NULL DEFAULT '0',
			`appvotes` text NOT NULL,
			`rejvotes` text NOT NULL,
			`dateline` int(10) NOT NULL DEFAULT '0',
			PRIMARY KEY (`aid`)
		) ENGINE=MyISAM{$db->build_create_table_collation()};"
	);

	// Add DB entries
	if(!$db->field_exists('avatarapproval', 'users'))
	{
		$db->add_column('users', 'avatarapproval', 'bigint(30) NOT NULL DEFAULT \'0\'');
	}

	if($db->table_exists('alert_settings') && $db->table_exists('alert_setting_values'))
	{
		$query = $db->simple_select('alert_settings', 'id', 'code=\'ougc_avatarapproval\'');

		if(!($id = (int)$db->fetch_field($query, 'id')))
		{
			$id = (int)$db->insert_query('alert_settings', array('code' => 'ougc_avatarapproval'));
	
			// Only update the first time
			$db->delete_query('alert_setting_values', 'setting_id=\''.$id.'\'');

			$query = $db->simple_select('users', 'uid');
			while($uid = (int)$db->fetch_field($query, 'uid'))
			{
				$settings[] = array(
					'user_id'		=> $uid,
					'setting_id'	=> $id,
					'value'			=> 1
				);
			}

			if(!empty($settings))
			{
				$db->insert_query_multiple('alert_setting_values', $settings);
			}
		}
	}
}

// _is_installed() routine
function ougc_avatarapproval_is_installed()
{
	global $db;

	return $db->table_exists('ougc_avatarapproval');
}

// _uninstall() routine
function ougc_avatarapproval_uninstall()
{
	global $db, $PL, $cache;
	ougc_avatarapproval_pl_check();

	// Drop DB entries
	$db->drop_table('ougc_avatarapproval');
	if($db->field_exists('avatarapproval', 'users'))
	{
		$db->drop_column('users', 'avatarapproval');
	}

	$PL->cache_delete('ougc_avatarapproval');
	$PL->settings_delete('ougc_avatarapproval');
	$PL->templates_delete('ougcavatarapproval');

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['avatarapproval']))
	{
		unset($plugins['avatarapproval']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		$PL->cache_delete('ougc_plugins');
	}

}

// PluginLibrary dependency check & load
function ougc_avatarapproval_pl_check()
{
	global $lang, $avatarapproval;
	$avatarapproval->lang_load();
	$info = ougc_avatarapproval_info();

	if(!file_exists(PLUGINLIBRARY))
	{
		flash_message($lang->sprintf($lang->ougc_avatarapproval_pl_required, $info['pl']['url'], $info['pl']['version']), 'error');
		admin_redirect('index.php?module=config-plugins');
		exit;
	}

	global $PL;

	$PL or require_once PLUGINLIBRARY;

	if($PL->version < $info['pl']['version'])
	{
		flash_message($lang->sprintf($lang->ougc_avatarapproval_pl_old, $info['pl']['url'], $info['pl']['version'], $PL->version), 'error');
		admin_redirect('index.php?module=config-plugins');
		exit;
	}
}

// Language support for settings
function ougc_avatarapproval_settings_change()
{
	global $db, $mybb;

	$query = $db->simple_select('settinggroups', 'name', 'gid=\''.(int)$mybb->input['gid'].'\'');
	$groupname = $db->fetch_field($query, 'name');
	if($groupname == 'ougc_avatarapproval')
	{
		global $plugins, $avatarapproval;
		$avatarapproval->lang_load();

		if($mybb->request_method == 'post')
		{
			global $settings;

			$gids = '';
			if(isset($mybb->input['ougc_avatarapproval_bypassgroups']) && is_array($mybb->input['ougc_avatarapproval_bypassgroups']))
			{
				$gids = $avatarapproval->clean_ints($mybb->input['ougc_avatarapproval_bypassgroups'], true);
			}

			$mybb->input['upsetting']['ougc_avatarapproval_bypassgroups'] = $gids;

			$gids = '';
			if(isset($mybb->input['ougc_avatarapproval_modgroups']) && is_array($mybb->input['ougc_avatarapproval_modgroups']))
			{
				$gids = $avatarapproval->clean_ints($mybb->input['ougc_avatarapproval_modgroups'], true);
			}

			$mybb->input['upsetting']['ougc_avatarapproval_modgroups'] = $gids;

			return;
		}

		$plugins->add_hook('admin_formcontainer_output_row', 'ougc_avatarapproval_formcontainer_output_row');
	}
}

// Friendly settings
function ougc_avatarapproval_formcontainer_output_row(&$args)
{
	if($args['row_options']['id'] == 'row_setting_ougc_avatarapproval_bypassgroups')
	{
		global $form, $settings;

		$args['content'] = $form->generate_group_select('ougc_avatarapproval_bypassgroups[]', explode(',', $settings['ougc_avatarapproval_bypassgroups']), array('multiple' => true, 'size' => 5));
	}
	if($args['row_options']['id'] == 'row_setting_ougc_avatarapproval_modgroups')
	{
		global $form, $settings;

		$args['content'] = $form->generate_group_select('ougc_avatarapproval_modgroups[]', explode(',', $settings['ougc_avatarapproval_modgroups']), array('multiple' => true, 'size' => 5));
	}
}

// Hijack the default avatar upload/url process
function ougc_avatarapproval_usercp()
{
	global $mybb;

	if($mybb->input['action'] != 'do_avatar' || $mybb->request_method != 'post' || isset($mybb->input['remove']) || (isset($mybb->input['gallery']) && !$mybb->settings['ougc_avatarapproval_modgallery']))
	{
		return;
	}

	global $avatarapproval;

	if($avatarapproval->is_member($mybb->settings['ougc_avatarapproval_bypassgroups']))
	{
		return;
	}

	// Verify incoming POST request
	verify_post_check($mybb->input['my_post_key']);

	global $plugins, $avatar_error, $lang;
	$avatarapproval->lang_load();

	$plugins->run_hooks('usercp_do_avatar_start');

	$avatar_error = '';

	$updated_avatar = array(
		'uid'				=> $mybb->user['uid'],
		'avatar'			=> '',
		'avatardimensions'	=> '',
		'avatartype'		=> ''
	);

	if(isset($mybb->input['gallery']))
	{
		if(empty($mybb->input['avatar']))
		{
			$avatar_error = $lang->error_noavatar;
		}

		$mybb->input['gallery'] = str_replace(array('./', '..'), '', $mybb->input['gallery']);
		$mybb->input['avatar'] = str_replace(array('./', '..'), '', $mybb->input['avatar']);

		if(empty($avatar_error))
		{
			global $db;

			$avatarpath = '';
			if($mybb->input['gallery'] != 'default')
			{
				$avatarpath = $mybb->input['gallery'].'/';
			}

			$avatarpath = $mybb->settings['avatardir'].'/'.$avatarpath.$mybb->input['avatar'];

			if(!file_exists($avatarpath))
			{
				$avatar_error = $lang->ougc_avatarapproval_ucp_error_invalidavatar;
			}
			else
			{
				$dimensions = @getimagesize($avatarpath);

				$updated_avatar['avatar'] = $db->escape_string($avatarpath);
				$updated_avatar['avatardimensions'] = (int)$dimensions[0].'|'.(int)$dimensions[1];
				$updated_avatar['avatartype'] = 'gallery';
			}
		}
	}
	elseif(!empty($_FILES['avatarupload']['name']))
	{
		!empty($mybb->usergroup['canuploadavatars']) or error_no_permission();

		$avatar = $avatarapproval->avatar_upload();
		if($avatar['error'])
		{
			$avatar_error = $avatar['error'];
		}
		else
		{
			$updated_avatar['avatar'] = $avatar['avatar'];
			$updated_avatar['avatardimensions'] = $avatar_dimensions = '';
			$updated_avatar['avatartype'] = 'upload';
			if($avatar['width'] > 0 && $avatar['height'] > 0)
			{
				$updated_avatar['avatardimensions'] = $avatar_dimensions = (int)$avatar['width'].'|'.(int)$avatar['height'];
			}
		}
	}
	else
	{
		global $ext, $file;

		$mybb->input['avatarurl'] = preg_replace('#script:#i', '', (string)$mybb->input['avatarurl']);
		$ext = get_extension($mybb->input['avatarurl']);

		// Copy the avatar to the local server (work around remote URL access disabled for getimagesize)
		$file = fetch_remote_file($mybb->input['avatarurl']);

		if(!$file)
		{
			$avatar_error = $lang->error_invalidavatarurl;
		}
		else
		{
			global $tmp_name, $fp;

			$tmp_name = $mybb->settings['avataruploadpath'].'/remote_'.md5(random_str());
			$fp = @fopen($tmp_name, 'wb');

			if(!$fp)
			{
				$avatar_error = $lang->error_invalidavatarurl;
			}
			else
			{
				global $width, $height, $type;

				fwrite($fp, $file);
				fclose($fp);
				list($width, $height, $type) = @getimagesize($tmp_name);
				@unlink($tmp_name);

				if(!$type)
				{
					$avatar_error = $lang->error_invalidavatarurl;
				}
			}
		}

		if(empty($avatar_error))
		{
			if($width && $height && $mybb->settings['maxavatardims'])
			{
				global $maxwidth, $maxheight;

				list($maxwidth, $maxheight) = explode('x', my_strtolower($mybb->settings['maxavatardims']));
				if(($maxwidth && $width > $maxwidth) || ($maxheight && $height > $maxheight))
				{
					$avatar_error = $lang->sprintf($lang->error_avatartoobig, $maxwidth, $maxheight);
				}
			}

			if(empty($avatar_error))
			{
				global $db, $avatar_dimensions;

				$updated_avatar['avatar'] = $mybb->input['avatarurl'];
				$updated_avatar['avatardimensions'] = $avatar_dimensions = '';
				$updated_avatar['avatartype'] = 'remote';

				if($width > 0 && $height > 0)
				{
					$updated_avatar['avatardimensions'] = $avatar_dimensions = (int)$width.'|'.(int)$height;
				}
			}
		}
	}

	if(empty($avatar_error))
	{
		if(!$avatarapproval->avatar_queue_exists($mybb->user['uid']))
		{
			$method = 'insert_approval';
		}
		else
		{
			$method = 'update_approval';
			
		}
		$avatarapproval->{$method}($updated_avatar);

		$plugins->run_hooks('usercp_do_avatar_end');

		redirect($mybb->settings['bburl'].'/usercp.php', $lang->ougc_avatarapproval_ucp_avatarupdated);
	}

	$mybb->input['action'] = 'avatar';
	$avatar_error = inline_error($avatar_error);
}

// Notify users about their approbalawaiting
function ougc_avatarapproval_avatar_end()
{
	global $ougc_avatarapproval, $mybb;

	$ougc_avatarapproval = '';
	if(!($aid = $mybb->user['avatarapproval'] = (int)$mybb->user['avatarapproval']))
	{
		return;
	}

	global $activegallery;

	if(isset($activegallery) && !$mybb->settings['ougc_avatarapproval_modgallery'])
	{
		return;
	}

	global $avatarapproval;

	if($avatarapproval->is_member($mybb->settings['ougc_avatarapproval_bypassgroups']))
	{
		return;
	}

	global $lang, $templates;
	$avatarapproval->lang_load();

	$message = $lang->ougc_avatarapproval_ucp_notification;

	$br = '';
	eval('$ougc_avatarapproval = "'.$templates->get('ougcavatarapproval_notification').'";');
}

// Notification for moderators
function ougc_avatarapproval_global()
{
	global $header;

	if(!my_strpos($header, '<!--OUGC_AVATARAPPROVAL-->'))
	{
		return;
	}

	global $mybb;

	if(!$mybb->user['uid'] || !$mybb->usergroup['canmodcp'])
	{
		return;
	}

	global $avatarapproval;

	if(!$avatarapproval->is_member($mybb->settings['ougc_avatarapproval_modgroups']))
	{
		return;
	}

	global $PL;
	$PL or require_once PLUGINLIBRARY;

	$count = 0;
	if($approvals = $PL->cache_read('ougc_avatarapproval'))
	{
		foreach($approvals as $aid => $uids)
		{
			if(!$avatarapproval->is_member($uids['uids'], array('usergroup' => $mybb->user['uid'])))
			{
				++$count;
			}
		}
		unset($approvals, $approval);
	}

	if(!$count)
	{
		return;
	}

	global $lang, $templates;
	$avatarapproval->lang_load();

	$message = $lang->sprintf($lang->ougc_avatarapproval_notification_moderator, $mybb->settings['bburl']);
	if($count > 1)
	{
		$message = $lang->sprintf($lang->ougc_avatarapproval_notification_moderators, $mybb->settings['bburl'], my_number_format($count));
	}

	$br = '<br />';
	eval('$notification = "'.$templates->get('ougcavatarapproval_notification').'";');
	$header = str_replace('<!--OUGC_AVATARAPPROVAL-->', $notification, $header);
}

// Moderation queue
function ougc_avatarapproval_modcp()
{
	global $mybb, $avatarapproval, $modcp_nav, $templates, $lang;

	$permission = ($mybb->usergroup['cancp'] || $avatarapproval->is_member($mybb->settings['ougc_avatarapproval_modgroups']));

	if($permission)
	{
		$avatarapproval->lang_load();

		eval('$nav = "'.$templates->get('ougcavatarapproval_modcp_nav').'";');
		$modcp_nav = str_replace('<!--OUGC_AVATARAPPROVAL_NAV-->', $nav, $modcp_nav);
	}

	if($mybb->input['action'] != 'avatarapproval')
	{
		return;
	}

	$permission or error_no_permission();

	global $headerinclude, $header, $theme, $footer, $db;

	// Make navigation
	add_breadcrumb($lang->nav_modcp, $mybb->settings['bburl'].'/modcp.php');
	add_breadcrumb($lang->ougc_avatarapproval_modcp_nav, $mybb->settings['bburl'].'/modcp.php?action=avatarapproval');

	$errors = '';
	if($mybb->request_method == 'post')
	{
		// Verify incoming POST request
		verify_post_check($mybb->input['my_post_key']);

		if(empty($mybb->input['selected']))
		{
			$errors[] = $lang->ougc_avatarapproval_modcp_error_noselected;
		}
		else
		{
			foreach($mybb->input['selected'] as $aid)
			{
				$avatarapproval->get_approval($aid);

				if(!$avatarapproval->aid)
				{
					$errors[] = $lang->ougc_avatarapproval_modcp_error_invalidapproval;
					continue;
				}

				if($avatarapproval->approval['uid'] == $mybb->user['uid'])
				{
					$errors[] = $lang->ougc_avatarapproval_modcp_error_selfapprove;
					continue;
				}

				if($avatarapproval->approval['status'])
				{
					$lang_var = ($avatarapproval->approval['status'] == 1 ? 'ougc_avatarapproval_modcp_error_alreadyapproved' : 'ougc_avatarapproval_modcp_error_alreadyrejected');
					$errors[] = $lang->sprintf($lang->{$lang_var});
					continue;
				}

				if($avatarapproval->approval['appvotes'] || $avatarapproval->approval['rejvotes'])
				{
					if($avatarapproval->is_member($avatarapproval->approval['appvotes'].','.$avatarapproval->approval['rejvotes'], array('usergroup' => $mybb->user['uid'])))
					{
						$errors[] = $lang->ougc_avatarapproval_modcp_error_doublevote;
					}
				}
			}
		}

		if(empty($errors))
		{
			$method = (isset($mybb->input['approve']) ? 'avatar_approve' : 'avatar_reject');
			foreach($mybb->input['selected'] as $aid)
			{
				$avatarapproval->get_approval($aid);

					$avatarapproval->{$method}();
			}

			redirect($mybb->settings['bburl'].'/modcp.php?action=avatarapproval', $lang->ougc_avatarapproval_modcp_redirect);
		}
	}

	$errors = (empty($errors) ? '' : inline_error($errors));

	global $PL;
	$PL or require_once PLUGINLIBRARY;

	// Approve gallery avatars if not being moderated, kind of useless
	/*if(!$mybb->settings['ougc_avatarapproval_modgallery'])
	{
		$query = $db->simple_select('ougc_avatarapproval', 'aid', 'type=\'gallery\'');
		while($aid = $db->fetch_field($query, 'aid'))
		{
			$avatarapproval->avatar_approve($aid);
		}
	}*/

	$limit = (int)$mybb->settings['ougc_avatarapproval_queue_perpage'];

	$colspan = 5;
	$options_tcat = $buttons = '';
	$where = $input = $options = array();

	// Build a where clause
	if(empty($mybb->input['status']))
	{
		$colspan = 6;
		$where[] = 'status=\'0\'';
		eval('$options_tcat = "'.$templates->get('ougcavatarapproval_modcp_list_options_tcat').'";');
		eval('$buttons = "'.$templates->get('ougcavatarapproval_modcp_buttons').'";');
	}
	else
	{
		$where[] = 'status=\''.((int)$mybb->input['status'] == 1 ? 1 : 2).'\'';
	}

	foreach($mybb->input as $key => &$val)
	{
		switch($key)
		{
			case 'limit':
				$input[$key] = $limit = (int)$val;
				break;
			case 'order_by':
				$val = my_strtolower($val);
				if(in_array($val, array('avatartype', 'dateline')))
				{
					$options[$key] = $val;
				}
				$input[$key] = $val;
				break;
			case 'order_dir':
				$options[$key] = (my_strtolower($val) == 'asc' ? 'ASC' : 'DESC');
				$input[$key] = $val;
				break;
		}
	}

	$where = implode(' AND ', $where);

	// Query to get the queue count
	$query = $db->simple_select('ougc_avatarapproval', 'COUNT(aid) AS queuecount', $where, $options);
	$queuecount = (int)$db->fetch_field($query, 'queuecount');

	$limit = $limit > 99 ? 100 : ($limit < 1 ? 20 : $limit);

	$mybb->input['page'] = (int)$mybb->input['page'];
	if($mybb->input['page'] > 0)
	{
		$start = ($mybb->input['page'] - 1)*$limit;
		if($mybb->input['page'] > ceil($queuecount/$limit))
		{
			$start = 0;
			$mybb->input['page'] = 1;
		}
	}
	else
	{
		$start = 0;
		$mybb->input['page'] = 1;
	}

	$multipage = (string)multipage($queuecount, $limit, $mybb->input['page'], $PL->url_append($_SERVER['PHP_SELF'], $input));

	$order_by = '';
	if($options)
	{
		if(!isset($options['order_by']))
		{
			$options['order_by'] = 'dateline';
		}
		if(!isset($options['order_dir']))
		{
			$options['order_dir'] = 'DESC';
		}
		$order_by = 'a.'.$options['order_by'].' '.$options['order_dir'].', ';
	}

	if(!$queuecount)
	{
		$buttons = '';
		eval('$approvals = "'.$templates->get('ougcavatarapproval_modcp_list_empty').'";');
	}
	else
	{
		$settings['ougc_format_avatar'] = $settings['ougc_avatarapproval_maxwh'];

		$bgcolor = alt_trow(true);

		$query = $db->simple_select('ougc_avatarapproval', '*', $where, array('limit_start' => $start, 'limit' => $limit));

		$userscache = $uids = array();
		while($approval = $db->fetch_array($query))
		{
			$uids[(int)$approval['uid']] = (int)$approval['uid'];

			$appvotes = $avatarapproval->clean_ints($approval['appvotes']);
			foreach($appvotes as $uid)
			{
				$uids[(int)$uid] = (int)$uid;
			}

			$rejvotes = $avatarapproval->clean_ints($approval['rejvotes']);
			foreach($rejvotes as $uid)
			{
				$uids[(int)$uid] = (int)$uid;
			}
		}
		unset($approval, $appvotes, $rejvotes, $uid);

		$query2 = $db->simple_select('users', 'uid, username, usergroup, displaygroup', 'uid IN (\''.implode('\',\'', $uids).'\')');
		while($user = $db->fetch_array($query2))
		{
			$userscache[(int)$user['uid']] = array(
				'username'		=> htmlspecialchars_uni($user['username']),
				'usergroup'		=> (int)$user['usergroup'],
				'displaygroup'	=> (int)$user['displaygroup']
			);
		}
		unset($uids);

		$db->data_seek($query, 0);

		$num_rows = $db->num_rows($query);
		$alreadyvotedcount = 0;
		$selectedids = array_flip((array)$mybb->input['selected']);

		while($approval = $db->fetch_array($query))
		{
			$user = $userscache[$approval['uid']];

			$approval['aid'] = (int)$approval['aid'];
			$approval['uid'] = (int)$approval['uid'];
			$approval['avatar'] = ougc_format_avatar(array(
				'uid'				=> $approval['uid'],
				'avatar'			=> $approval['avatar'],
				'avatardimensions'	=> $approval['avatardimensions'],
			));
			$approval['status'] = (int)$approval['status'];

			$appvotes = $rejvotes = 0;
			$approval['appvotes'] = $avatarapproval->clean_ints($approval['appvotes']);
			$approval['rejvotes'] = $avatarapproval->clean_ints($approval['rejvotes']);

			$alreadyvoted = false;

			$applist = $rejlist = $comma = '';
			foreach($approval['appvotes'] as $appvote)
			{
				if($appvote == $mybb->user['uid'])
				{
					$alreadyvoted = true;
				}

				++$appvotes;
				$applist .= $comma.format_name(build_profile_link($userscache[$appvote]['username'], $appvote['uid']), $userscache[$appvote]['usergroup'], $userscache[$appvote]['displaygroup']);
				$comma = ', ';
			}
			$comma = '';
			foreach($approval['rejvotes'] as $rejvote)
			{
				if($rejvote == $mybb->user['uid'])
				{
					$alreadyvoted = true;
				}

				++$rejvotes;
				$rejlist .= $comma.format_name(build_profile_link($userscache[$rejvote]['username'], $rejvote['uid']), $userscache[$rejvote]['usergroup'], $userscache[$rejvote]['displaygroup']);
				$comma = ', ';
			}

			$appvotes = $lang->sprintf($lang->ougc_avatarapproval_modcp_voted, my_number_format($appvotes));
			$rejvotes = $lang->sprintf($lang->ougc_avatarapproval_modcp_voted, my_number_format($rejvotes));

			$approval['dateline'] = (int)$approval['dateline'];
			$approval['date'] = my_date($mybb->settings['dateformat'], $approval['dateline']);
			$approval['time'] = my_date($mybb->settings['timeformat'], $approval['dateline']);

			$approval['profilelink'] = build_profile_link($user['username'], $approval['uid']);
			$approval['username_formatted'] = format_name($approval['profilelink'], $user['usergroup'], $user['displaygroup']);

			$inlinecheck = '';

			if(isset($selectedids[$approval['aid']]) && !$alreadyvoted)
			{
				$inlinecheck = ' checked="checked"';
			}

			if($alreadyvoted)
			{
				++$alreadyvotedcount;
				$inlinecheck = ' disabled="disabled"';
			}

			$options_row = '';
			if(empty($mybb->input['status']))
			{
				eval('$options_row = "'.$templates->get('ougcavatarapproval_modcp_list_options_row').'";');
			}
			eval('$approvals .= "'.$templates->get('ougcavatarapproval_modcp_list_item').'";');
			$bgcolor = alt_trow();
		}
	}

	if($num_rows == $alreadyvotedcount)
	{
		$buttons = '';
	}

	eval('$page = "'.$templates->get('ougcavatarapproval_modcp').'";');
	output_page($page);
	exit;
}

// MyAlerts: Help Documents
function ougc_avatarapproval_myalerts_helpdoc()
{
	global $helpdoc, $lang, $settings;

	if($helpdoc['name'] != $lang->myalerts_help_alert_types)
	{
        return;
    }

	global $settings;

    if($settings['ougc_avatarapproval_myalerts'])
    {
		global $avatarapproval;
		$avatarapproval->lang_load();

        $helpdoc['document'] .= $lang->ougc_avatarapproval_myalerts_helpdoc;
    }
}

// MyAlerts: Output
function ougc_avatarapproval_myalerts_output(&$args)
{
	global $mybb;

	if($args['alert_type'] != 'ougc_avatarapproval' || !$mybb->user['myalerts_settings']['ougc_avatarapproval'])
	{
		return;
    }

	global $avatarapproval, $lang;
	$avatarapproval->lang_load();

	$lang_var = 'ougc_avatarapproval_myalerts_approved';
	if($args['content'][0] == 2)
	{
		$lang_var = 'ougc_avatarapproval_myalerts_rejected';
	}

	$args['message'] = $lang->sprintf($lang->{$lang_var}, $args['user'], $args['dateline']);
	$args['rowType'] = 'avatarapproval';
}

// control_object by Zinga Burga from MyBBHacks ( mybbhacks.zingaburga.com ), 1.62
if(!function_exists('control_object'))
{
	function control_object(&$obj, $code)
	{
		static $cnt = 0;
		$newname = '_objcont_'.(++$cnt);
		$objserial = serialize($obj);
		$classname = get_class($obj);
		$checkstr = 'O:'.strlen($classname).':"'.$classname.'":';
		$checkstr_len = strlen($checkstr);
		if(substr($objserial, 0, $checkstr_len) == $checkstr)
		{
			$vars = array();
			// grab resources/object etc, stripping scope info from keys
			foreach((array)$obj as $k => $v)
			{
				if($p = strrpos($k, "\0"))
				{
					$k = substr($k, $p+1);
				}
				$vars[$k] = $v;
			}
			if(!empty($vars))
			{
				$code .= '
					function ___setvars(&$a) {
						foreach($a as $k => &$v)
							$this->$k = $v;
					}
				';
			}
			eval('class '.$newname.' extends '.$classname.' {'.$code.'}');
			$obj = unserialize('O:'.strlen($newname).':"'.$newname.'":'.substr($objserial, $checkstr_len));
			if(!empty($vars))
			{
				$obj->___setvars($vars);
			}
		}
		// else not a valid object or PHP serialize has changed
	}
}

if(!function_exists('ougc_format_avatar'))
{
	/**
	 * Formats an avatar to a certain dimension.
	 *
	 * @param array User 'uid', 'avatar', and 'avatardimensions'.
	 * @return array Avatar data ('image', 'width', and 'height').
	**/
	function ougc_format_avatar($avatar=array('uid' => 0))
	{
		static $cache = array();

		if(!isset($cache[$avatar['uid']]))
		{
			global $lang, $settings;
			$avatar['uid'] = (int)$avatar['uid'];

			if(empty($avatar['avatar']))
			{
				// MyBB 1.7 compatible
				$avatar['avatar'] = isset($settings['useravatar']) ? $settings['useravatar'] : $settings['ougc_format_avatar_default'];
				$avatar['avatardimensions'] = isset($settings['useravatardims']) ? $settings['useravatardims'] : $settings['ougc_format_avatar_dimensions'];
			}

			$avatar['avatar'] = htmlspecialchars_uni($avatar['avatar']);
			$dimensions = explode('|', $avatar['avatardimensions']);

			if(isset($dimensions[0]) && isset($dimensions[1]))
			{
				// MyBB 1.7 compatible
				list($maxwidth, $maxheight) = isset($settings['maxavatardims']) ? $settings['maxavatardims'] : $settings['ougc_format_avatar'];
				if($dimensions[0] > (int)$maxwidth || $dimensions[1] > (int)$maxheight)
				{
					require_once MYBB_ROOT.'inc/functions_image.php';
					$scale = scale_image($dimensions[0], $dimensions[1], (int)$maxwidth, (int)$maxheight);
				}
			}

			$cache[$avatar['uid']] = array(
				'image'		=> htmlspecialchars_uni($avatar['avatar']),
				'width'		=> isset($scale['width']) ? (int)$scale['width'] : (int)$dimensions[0],
				'height'	=> isset($scale['height']) ? (int)$scale['height'] : (int)$dimensions[1]
			);
		}

		return $cache[$avatar['uid']];
	}
}

// Our awesome class
class OUGC_AvatarApproval
{
	// Path to upload avatars to
	public $uploadpath = './uploads/avatars/approval';

	// AID which has just been updated/inserted/deleted
	public $aid = 0;

	// Current approval to proccess
	public $approval = array();

	// Approval update status
	private $approve_status = 1;

	// Update approval instead of inserting new one
	private $insert_as_update = false;

	// Build the class
	function __construct()
	{
		global $settings;

		if(isset($settings['avataruploadpath']))
		{
			$this->uploadpath = $settings['avataruploadpath'].'/approval';
		}

		if($settings['ougc_avatarapproval_myalerts'])
		{
			$settings['myalerts_alert_ougc_avatarapproval'] = 1;
		}
	}

	// Loads language strings
	function lang_load()
	{
		global $lang;

		isset($lang->setting_group_ougc_avatarapproval) or $lang->load('ougc_avatarapproval');

		// MyAlerts, ugly bitch
		if(isset($lang->ougc_avatarapproval_myalerts_setting))
		{
			$lang->myalerts_setting_ougc_avatarapproval = $lang->ougc_avatarapproval_myalerts_setting;
		}
	}

	// $PL->is_member(); helper
	function is_member($gids, $user=false)
	{
		global $PL;
		$PL or require_once PLUGINLIBRARY;

		return (bool)$PL->is_member((string)$gids, $user);
	}

	// Clean input
	function clean_ints($val, $implode=false)
	{
		if(!is_array($val))
		{
			$val = (array)explode(',', $val);
		}

		foreach($val as $k => &$v)
		{
			$v = (int)$v;
		}

		$val = array_filter($val);

		if($implode)
		{
			$val = (string)implode(',', $val);
		}

		return $val;
	}

	// Check if avatar upload exists in the queue system for user
	function avatar_queue_exists($uid)
	{
		global $db;
		$uid = (int)$uid;

		$query = $db->simple_select('ougc_avatarapproval', 'aid', 'uid=\''.$uid.'\' AND status=\'0\'');
		$this->aid = (int)$db->fetch_field($query, 'aid');

		return (bool)$this->aid;
	}

	// Approve an avatar in queue
	function avatar_approve()
	{
		global $mybb;

		$key = 'appvotes';
		if($this->approve_status == 2)
		{
			$key = 'rejvotes';
		}

		$votes = $this->clean_ints($this->approval[$key].','.$mybb->user['uid']);
		$this->approval[$key] = implode(',', $votes);

		$this->approval['status'] = (int)((count($votes) >= $mybb->settings['ougc_avatarapproval_'.$key]) || ($mybb->settings['ougc_avatarapproval_adminoverride'] && $mybb->usergroup['cancp']));

		if($this->approval['status'] && $this->approve_status == 2)
		{
			$this->approval['status'] = 2;
		}

		if($this->approval['status'])
		{
			global $lang;
			$this->lang_load();

			$lang_var = 'ougc_avatarapproval_pm_subject';
			$lang_var_message = 'ougc_avatarapproval_pm_message';
			if($this->approve_status == 2)
			{
				$lang_var .= '_reject';
				$lang_var_message .= '_reject';
			}
			else
			{
				// Upload avatar type needs special handling
				if($this->approval['avatartype'] == 'upload')
				{
					require_once MYBB_ROOT.'inc/functions_upload.php';
					remove_avatars($this->approval['uid']);

					if($this->approval['avatartype'] == 'upload')
					{
						$oldfile = MYBB_ROOT.$this->approval['avatar'];
						$this->approval['avatar'] = str_replace('approval/', '', $this->approval['avatar']);
						$newfile = MYBB_ROOT.$this->approval['avatar'];
						$rename = rename($oldfile, $newfile);
					}
				}
				

				global $db;

				$db->update_query('users', array(
					'avatar'			=> $db->escape_string($this->approval['avatar'].'?'.TIME_NOW),
					'avatardimensions'	=> $db->escape_string($this->approval['avatardimensions']),
					'avatartype'		=> $db->escape_string($this->approval['avatartype']),
				), 'uid=\''.(int)$this->approval['uid'].'\'');
			}

			$this->update_user_approval($this->approval['uid']);

			$this->send_pm(array(
				'subject'		=> $lang->{$lang_var},
				'message'		=> $lang->{$lang_var_message},
				'touid'			=> $this->approval['uid']
			), -1, true);

			$this->my_alerts($this->approval['status'], $this->approval['uid']);
		}

		$this->update_approval($this->approval);

		return (bool)$this->approval['status'];
	}

	// Reject an avatar in queue
	function avatar_reject()
	{
		$this->approve_status = 2;

		return $this->avatar_approve();
	}

	// Upload a new avatar in to the file system (Copied from MyBB 1.6.12)
	function avatar_upload($avatar=false)
	{
		global $mybb, $lang;

		if($avatar === false)
		{
			$avatar = &$_FILES['avatarupload'];
		}

		if(!is_uploaded_file($avatar['tmp_name']))
		{
			$ret['error'] = $lang->error_uploadfailed;
			return $ret;
		}

		// Check we have a valid extension
		$ext = get_extension(my_strtolower($avatar['name']));
		if(!preg_match('#^(gif|jpg|jpeg|jpe|bmp|png)$#i', $ext))
		{
			$ret['error'] = $lang->error_avatartype;
			return $ret;
		}

		require_once MYBB_ROOT.'inc/functions_upload.php';

		$filename = 'avatar_'.$mybb->user['uid'].'.'.$ext;
		$file = upload_file($avatar, $this->uploadpath, $filename);
		if($file['error'] || !file_exists($this->uploadpath.'/'.$filename))
		{
			@unlink($this->uploadpath.'/'.$filename);
			$ret['error'] = $lang->error_uploadfailed;
			return $ret;
		}

		// Check if this is a valid image or not
		$img_dimensions = @getimagesize($this->uploadpath.'/'.$filename);
		if(!is_array($img_dimensions))
		{
			@unlink($this->uploadpath.'/'.$filename);
			$ret['error'] = $lang->error_uploadfailed;
			return $ret;
		}

		// Check avatar dimensions
		if(!empty($mybb->settings['maxavatardims']))
		{
			list($maxwidth, $maxheight) = explode('x', $mybb->settings['maxavatardims']);
			if(($maxwidth && $img_dimensions[0] > $maxwidth) || ($maxheight && $img_dimensions[1] > $maxheight))
			{
				// Automatic resizing enabled?
				if($mybb->settings['avatarresizing'] == 'auto' || ($mybb->settings['avatarresizing'] == 'user' && $mybb->input['auto_resize'] == 1))
				{
					require_once MYBB_ROOT.'inc/functions_image.php';
					$thumbnail = generate_thumbnail($this->uploadpath.'/'.$filename, $this->uploadpath, $filename, $maxheight, $maxwidth);
					if(!$thumbnail['filename'])
					{
						@unlink($this->uploadpath.'/'.$filename);
						$ret['error'] = $lang->sprintf($lang->error_avatartoobig, $maxwidth, $maxheight).'<br /><br />'.$lang->error_avatarresizefailed;
						return $ret;
					}
					else
					{
						// Reset filesize
						$avatar['size'] = filesize($this->uploadpath.'/'.$filename);
						// Reset dimensions
						$img_dimensions = @getimagesize($this->uploadpath.'/'.$filename);
					}
				}
				else
				{
					@unlink($this->uploadpath."/".$filename);
					$ret['error'] = $lang->sprintf($lang->error_avatartoobig, $maxwidth, $maxheight);
					if($mybb->settings['avatarresizing'] == 'user')
					{
						$ret['error'] .= '<br /><br />'.$lang->error_avataruserresize;
					}
					return $ret;
				}
			}
		}

		// Next check the file size
		if($avatar['size'] > ((int)$mybb->settings['avatarsize']*1024) && $mybb->settings['avatarsize'] > 0)
		{
			@unlink($this->uploadpath.'/'.$filename);
			$ret['error'] = $lang->error_uploadsize;
			return $ret;
		}

		// Check a list of known MIME types to establish what kind of avatar we're uploading
		switch(my_strtolower($avatar['type']))
		{
			case 'image/gif':
				$img_type =  1;
				break;
			case 'image/jpeg':
			case 'image/x-jpg':
			case 'image/x-jpeg':
			case 'image/pjpeg':
			case 'image/jpg':
				$img_type = 2;
				break;
			case 'image/png':
			case 'image/x-png':
				$img_type = 3;
				break;
			default:
				$img_type = 0;
		}

		// Check if the uploaded file type matches the correct image type (returned by getimagesize)
		if($img_dimensions[2] != $img_type || $img_type == 0)
		{
			@unlink($this->uploadpath.'/'.$filename);
			$ret['error'] = $lang->error_uploadfailed;
			return $ret;
		}

		$ret = array(
			'avatar'	=> $this->uploadpath.'/'.$filename,
			'width'		=> (int)$img_dimensions[0],
			'height'	=> (int)$img_dimensions[1]
		);

		global $plugins;

		$ret = $plugins->run_hooks('upload_avatar_end', $ret);

		return $ret;
	}

	// Get approval from DB
	function get_approval($aid)
	{
		static $cache = array();

		if(!isset($cache[$aid]))
		{
			global $db;

			$query = $db->simple_select('ougc_avatarapproval', '*', 'aid=\''.(int)$aid.'\'');
			$cache[$aid] = (array)$db->fetch_array($query);
		}

		$this->approval = $cache[$aid];
		$this->aid = (int)$cache[$aid]['aid'];

		return (bool)$this->aid;
	}

	// Insert new approval into DB
	function insert_approval($data)
	{
		global $db;

		$insert_data = array(
			'uid'				=> (int)$data['uid'],
			'avatar'			=> '',
			'avatardimensions'	=> '',
			'avatartype'		=> '',
			'status'			=> 0,
			'appvotes'			=> '',
			'rejvotes'			=> '',
			'dateline'			=> TIME_NOW
		);

		if(isset($data['avatar']))
		{
			$insert_data['avatar'] = $db->escape_string((string)$data['avatar']);
		}

		if(isset($data['avatardimensions']))
		{
			$insert_data['avatardimensions'] = $db->escape_string((string)$data['avatardimensions']);
		}

		if(isset($data['avatartype']))
		{
			$insert_data['avatartype'] = $db->escape_string((string)$data['avatartype']);
		}

		if(isset($data['status']))
		{
			$insert_data['status'] = (int)$data['status'];
		}

		if(isset($data['appvotes']))
		{
			$insert_data['appvotes'] = $db->escape_string((string)$data['appvotes']);
		}

		if(isset($data['rejvotes']))
		{
			$insert_data['rejvotes'] = $db->escape_string((string)$data['rejvotes']);
		}

		if(isset($data['dateline']))
		{
			$insert_data['dateline'] = (int)$data['dateline'];
		}

		if($this->insert_as_update)
		{
			$db->update_query('ougc_avatarapproval', $insert_data, 'aid=\''.$this->aid.'\'');
		}
		else
		{
			$this->aid = (int)$db->insert_query('ougc_avatarapproval', $insert_data);
		}

		$this->update_user_approval($insert_data['uid'], $this->aid);

		$this->update_cache();
	}

	// Update approval from DB
	function update_approval($data)
	{
		$this->insert_as_update = true;
		$this->insert_approval($data);
	}

	// Update user field from DB
	function update_user_approval($uid, $aid=0)
	{
		global $db;

		$db->update_query('users', array('avatarapproval' => (int)$aid), 'uid=\''.(int)$uid.'\'');

		return true;
	}

	// Update moderator notification cache
	function update_cache()
	{
		global $db, $cache;

		$query = $db->simple_select('ougc_avatarapproval', 'aid, appvotes, rejvotes', 'status=\'0\'');

		$update = array();

		while($approval = $db->fetch_array($query))
		{
			$update[(int)$approval['aid']] = array('uids' => $this->clean_ints($approval['appvotes'].','.$approval['rejvotes'], true));
		}

		$db->free_result($query);

		$cache->update('ougc_avatarapproval', $update);
	}

	// Send a Private Message to a user  (Copied from MyBB 1.7)
	function send_pm($pm, $fromid=0, $admin_override=false)
	{
		global $mybb;

		if(!$mybb->settings['ougc_avatarapproval_sendpm'] || !$mybb->settings['enablepms'] || !is_array($pm))
		{
			return false;
		}

		if (!$pm['subject'] ||!$pm['message'] || !$pm['touid'] || (!$pm['receivepms'] && !$admin_override))
		{
			return false;
		}

		global $lang, $session;
		$lang->load('messages');

		require_once MYBB_ROOT."inc/datahandlers/pm.php";

		$pmhandler = new PMDataHandler();

		$user = get_user($pm['touid']);

		// Build our final PM array
		$pm = array(
			'subject'		=> $pm['subject'],
			'message'		=> $lang->sprintf($pm['message'], $user['username'], $mybb->settings['bbname']),
			'icon'			=> -1,
			'fromid'		=> ($fromid == 0 ? (int)$mybb->user['uid'] : ($fromid < 0 ? 0 : $fromid)),
			'toid'			=> array($pm['touid']),
			'bccid'			=> array(),
			'do'			=> '',
			'pmid'			=> '',
			'saveasdraft'	=> 0,
			'options'	=> array(
				'signature'			=> 0,
				'disablesmilies'	=> 0,
				'savecopy'			=> 0,
				'readreceipt'		=> 0
			)
		);

		if(isset($mybb->session))
		{
			$pm['ipaddress'] = $mybb->session->packedip;
		}

		// Admin override
		$pmhandler->admin_override = (int)$admin_override;

		$pmhandler->set_data($pm);

		if($pmhandler->validate_pm())
		{
			$pmhandler->insert_pm();
			return true;
		}

		return false;
	}

	// MyAlerts support
	function my_alerts($status, $touid)
	{
		global $mybb, $cache;

		if(!$mybb->settings['ougc_avatarapproval_myalerts'])
		{
			return;
		}

		$plugins = (array)$cache->read('euantor_plugins');

		if(empty($plugins['myalerts']))
		{
			return;
		}

		$info = ougc_avatarapproval_info();

		if(str_replace('.', '', $plugins['myalerts']['version']) < $info['myalerts'])
		{
			return;
		}

		global $Alerts;

		if(!(!empty($Alerts) && $Alerts instanceof Alerts))
		{
			return;
		}

		global $db;

		// Get list of users
		$Alerts->addAlert($touid, 'ougc_avatarapproval', 0, $mybb->user['uid'], array($status));
	}
}
$GLOBALS['avatarapproval'] = new OUGC_AvatarApproval;