<?php

/***************************************************************************
 *
 *	OUGC Avatar Approval plugin (/inc/languages/english/ougc_avatarapproval.php)
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

//UCP
$l['ougc_avatarapproval_ucp_avatarupdated'] = 'Your avatar will be updated as soon as it is approved.<br />You will now be returned to your User CP.';
$l['ougc_avatarapproval_ucp_notification'] = '<strong>Your avatar is currently awaiting for approval.</strong><br />Uploading a new file or file URL will update your approval submission.';
$l['ougc_avatarapproval_ucp_error_invalidavatar'] = 'You did not selected a valid avatar. Please select a valid avatar before continuing.';

// Global
$l['ougc_avatarapproval_notification_moderator'] = '<a href="{1}/modcp.php?action=avatarapproval"><strong>Moderator Notice:</strong> There is one avatar pending for approval.</a>';
$l['ougc_avatarapproval_notification_moderators'] = '<a href="{1}/modcp.php?action=avatarapproval"><strong>Moderator Notice:</strong> There are {2} avatars pending for approval.</a>';

// ModCP
$l['ougc_avatarapproval_modcp_nav'] = 'Avatar Queue';
$l['ougc_avatarapproval_modcp_list_empty'] = 'No avatars were found matching the selected criteria.';
$l['ougc_avatarapproval_modcp_date'] = 'Date';
$l['ougc_avatarapproval_modcp_approve'] = 'Approve';
$l['ougc_avatarapproval_modcp_reject'] = 'Reject';
$l['ougc_avatarapproval_modcp_approved'] = 'Approved';
$l['ougc_avatarapproval_modcp_rejected'] = 'Rejected';
$l['ougc_avatarapproval_modcp_username'] = 'Username';
$l['ougc_avatarapproval_modcp_avatar'] = 'Avatar';
$l['ougc_avatarapproval_modcp_approvedcount'] = 'Approved Votes';
$l['ougc_avatarapproval_modcp_rejectcount'] = 'Rejected Votes';
$l['ougc_avatarapproval_modcp_select'] = 'Select';
$l['ougc_avatarapproval_modcp_actions'] = 'Actions';
$l['ougc_avatarapproval_modcp_voted'] = '{1} user(s) voted.';
$l['ougc_avatarapproval_modcp_error_selfapprove'] = 'You cannot approve/reject your own avatar submission.';
$l['ougc_avatarapproval_modcp_error_invalidapproval'] = 'The selected approval is invalid.';
$l['ougc_avatarapproval_modcp_error_alreadyapproved'] = 'The selected approval has already been approved.';
$l['ougc_avatarapproval_modcp_error_alreadyrejected'] = 'The selected approval has already been rejected.';
$l['ougc_avatarapproval_modcp_error_doublevote'] = 'You are not allowed to double vote.';
$l['ougc_avatarapproval_modcp_redirect'] = 'The selected avatars has been processed.<br />You will now be redirected.';

// Send PM
$l['ougc_avatarapproval_pm_subject'] = 'Your avatar has been approved.';
$l['ougc_avatarapproval_pm_message'] = 'Hi {1}! This PM is an automatic notification to let you know your avatar change has been approved by one or more moderators / administrators.

Cheers, {2}.';
$l['ougc_avatarapproval_pm_subject_reject'] = 'Your avatar has been rejected.';
$l['ougc_avatarapproval_pm_message_reject'] = 'Hi {1}! This PM is an automatic notification to let you know your avatar change has been rejected by one or more moderators / administrators.

Cheers, {2}.';

// MyAlerts
$l['ougc_avatarapproval_myalerts_approved'] = '{1} approved your avatar. {2}';
$l['ougc_avatarapproval_myalerts_rejected'] = '{1} rejected your avatar. {2}';
$l['ougc_avatarapproval_myalerts_setting'] = 'Receive alert when your avatar is approved/rejected.';
$l['ougc_avatarapproval_myalerts_helpdoc'] = '<strong>Avatar Approval</strong>
<p>
	This alert type is received whenever one moderator approves/rejects your avatar update.
</p>';