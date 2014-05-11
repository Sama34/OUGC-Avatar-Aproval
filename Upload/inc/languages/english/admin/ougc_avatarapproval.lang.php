<?php

/***************************************************************************
 *
 *	OUGC Avatar Approval plugin (/inc/languages/english/admin/ougc_avatarapproval.php)
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

// Plugin API
$l['setting_group_ougc_avatarapproval'] = 'OUGC Avatar Approval';
$l['setting_group_ougc_avatarapproval_desc'] = 'Allow moderators to manage avatar uploads/updates.';

// Settings
$l['setting_ougc_avatarapproval_bypassgroups'] = 'Bypass Groups';
$l['setting_ougc_avatarapproval_bypassgroups_desc'] = 'Allowed usergroups to bypass this feature.';
$l['setting_ougc_avatarapproval_modgroups'] = 'Moderator Groups';
$l['setting_ougc_avatarapproval_modgroups_desc'] = 'Allowed usergroups to moderate this feature.';
$l['setting_ougc_avatarapproval_appvotes'] = 'Votes for Approval';
$l['setting_ougc_avatarapproval_appvotes_desc'] = 'Amount of moderator votes to approve avatars.';
$l['setting_ougc_avatarapproval_rejvotes'] = 'Votes for Rejection';
$l['setting_ougc_avatarapproval_rejvotes_desc'] = 'Amount of moderator votes to reject avatars.';
$l['setting_ougc_avatarapproval_sendpm'] = 'Send PM';
$l['setting_ougc_avatarapproval_sendpm_desc'] = 'Do you want to send an PM to users each time one of their avatars is approved/rejected?';
$l['setting_ougc_avatarapproval_myalerts'] = 'MyAlerts Integration';
$l['setting_ougc_avatarapproval_myalerts_desc'] = 'Do you want to send an alert to users each time one of their avatars is approved/rejected?';
$l['setting_ougc_avatarapproval_modgallery'] = 'Moderate Gallery Avatars';
$l['setting_ougc_avatarapproval_modgallery_desc'] = 'Do you want to moderate gallery avatar updates?';
$l['setting_ougc_avatarapproval_adminoverride'] = 'Admin Override';
$l['setting_ougc_avatarapproval_adminoverride_desc'] = 'Do you want to administrators to override "'.$l['setting_ougc_avatarapproval_appvotes'].'" and "'.$l['setting_ougc_avatarapproval_rejvotes'].'"?';
$l['setting_ougc_avatarapproval_queue_perpage'] = 'Items Per Page';
$l['setting_ougc_avatarapproval_queue_perpage_desc'] = 'Maximun number of items to show per page in the ModCP queue list.';
$l['setting_ougc_avatarapproval_maxwh'] = 'Maximum Avatar Dimensions';
$l['setting_ougc_avatarapproval_maxwh_desc'] = 'Maximun image dimensions for avatars shown in the ModCP queue list.';

// PluginLibrary
$l['ougc_avatarapproval_pl_required'] = 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later to be uploaded to your forum.';
$l['ougc_avatarapproval_pl_old'] = 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later, whereas your current version is {3}.';