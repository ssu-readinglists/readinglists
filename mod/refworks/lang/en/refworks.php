<?php
//module settings
$string['refworks'] = 'RefWorks';

$string['modulename'] = 'myReferences (Beta)';
$string['modulenameplural'] = 'myReferences';
$string['pluginname'] = 'MyReferences';
$string['pluginadministration'] = 'Administration';

$string['refworksname'] = 'Instance name<br><font color=RED><b>NOTE: THIS FEATURE IS UNDER DEVELOPMENT. Contact: [local data - email address]</b></font><br>';
$string['refworks_name_desc'] = 'The tile name that will be displayed in the module.';
$string['refworks_autopopfolder'] = 'Auto populated folder name';
$string['refworks_autopopdata'] = 'Upload auto populated reference data file (XML)';
$string['refworks_autopopdatatext'] = 'Auto populated reference data (XML)';
$string['refworks_autopopdataerror'] = 'The reference data entered/uploaded was invalid.';
$string['refworks_cglink'] = 'Computing guide link';
$string['refworks_cglink_desc'] = 'Link to the page on Computing Guide that gives help on MyReferences.';
$string['refworks_styles'] = 'Alternate output styles';
$string['refworks_styles_desc'] = 'Serialized array of output styles for bibliography to replace default in code.';
$string['refworks_link'] = 'Link to RefWorks';
$string['refworks_link_desc'] = 'URL to RefWorks.';
$string['refworks_shownewlogin'] = 'Show new account details';
$string['refworks_shownewlogin_desc'] = 'When creating a RefWorks account via Moodle show the login & password details.';

$string['refworks_collabacemail'] = 'Shared account email address';
$string['refworks_collabacemail_desc'] = 'Email address used as the email address for all shared accounts';

$string['refworks_autopopfolder_info'] = 'Using Auto populated reference data';
$string['refworks_autopopfolder_info_help'] = '<p>Auto populated folder name: If you fill in this field and select a file in the <em>Auto populated reference data</em> field this will enable auto-populating of references for this instance.
This means that anyone accessing this instance will have a folder created in their account named the value specified in the field. The folder will be populated with the reference data uploaded. Reference data must be in RefWorks XML format.
The data can be re-uploaded at any time and the new data will be used instead. However, the folder will only be created if it does not exist in the users account and so the new data will not be imported into any accounts that already have the folder in.
This functionality can be removed by setting the folder field to empty.</p>';
//capabilities
$string['refworks:connect'] = 'Connection to RefWorks account';
$string['refworks:bibliography'] = 'Create bibliography';
$string['refworks:export'] = 'Export references';
$string['refworks:folders'] = 'View/create/manage folders';
$string['refworks:delete'] = 'Delete references';
$string['refworks:update'] = 'Update references';
$string['refworks:collaboration'] = 'Access collaborative RefWorks accounts';
$string['refworks:collaboration_createacc'] = 'Create a collaborative account';
$string['refworks:collaboration_admin'] = 'Administer all collaborative accounts';
$string['refworks:runreport'] = 'Run reference reports';
$string['refworks:upload_attachments'] = 'Upload attachments in references';
$string['refworks:export_rwxml'] = 'Export RefWorks XML';
$string['refworks:export_collab'] = 'Export Collaborative activities (Moodle Filter)';
$string['refworks:allow_url_override'] = 'Allow manual URL entry in references';
$string['refworks:migrate_collab_accs'] = 'Access migrate collaborative accounts page';
//menus
$string['current_login'] = 'Current Login';
$string['personal_account'] = 'Personal account';

$string['references'] = 'References';
$string['view_all_refs'] = 'View all references';
$string['create_ref'] = 'Create reference';
$string['import_ref'] = 'Import references';
$string['export_ref'] = 'Export references';
$string['create_bib'] = 'Create bibliography';

$string['access_account'] = 'Access your account';

$string['support'] = 'Support';

$string['access_help'] = 'Further information and guidance';
$string['access_libnews'] = '[local data - text for link]';
$string['access_libnews_link'] = '[local data - link to referencing examples]';
             
//Authentication + account login
$string['invalid_email'] = 'The email address supplied [{$a}] is invalid.';
$string['connection_error'] = 'There has been a connection error. Unable to connect to RefWorks.';
$string['account_login_error'] = 'Unable to connect to your account. Please either create an account or supply details to an alternate account.';
$string['account_login_expire'] = 'Your session has expired. You must login to your account again.';
$string['general_error'] = 'There was an error with the requested RefWorks operation.';
$string['alt_login_missing'] = 'You must supply a user name and password to connect to an alternate account.';
$string['account_create_instructions'] = '<div class="instructions"><p>You are seeing this form because we have been unable to connect to your MyReferences library. Generally, this is because you do not have an existing account/collection (especially if this is the first time you have tried to use MyReferences).</p><p>You can create a MyReferences/RefWorks account by following this link to <a target="_blank" href="https://www.refworks.com/RWShibboleth/ShibbolethAuthenticate.asp?"><strong>login to RefWorks for the first time</strong></a>. Once you have logged in successfully, refresh this page and you should be connected automatically to your new MyReferences library.</p></div>';
$string['account_create_error'] = 'There was an error creating the account.<br/><br/>{$a}';
$string['account_create_error_alt'] = '<p>Automatic account creation failed. <a href=\"http://www.refworks.com/refworks\">You can manually create an account by selecting this link.</a></p>';
$string['account_create_success'] = 'Your account was successfully created.';
$string['account_create_details'] = 'The login details for your new account are:<br/>Login:{$a->name}<br/>Password:{$a->pass}';
$string['nocapability'] = 'You do not have the required permission to undertake this action.';

$string['acc_form_head'] = 'Use alternate account';
$string['acc_form_name'] = 'User name';
$string['acc_form_pass'] = 'Password';
$string['acc_form_submit'] = 'Login';

$string['newacc_form_head'] = 'Create a RefWorks account.';
$string['newacc_form_submit'] = 'Create account';

//Page heading strings
$string['sort_by'] = 'Sort by';
$string['sort_by_button'] = 'Sort';
$string['sort_last_modified'] = 'Last modified';
$string['sort_creation'] = 'Creation date';
$string['sort_author'] = 'Authors, primary';
$string['sort_title'] = 'Title, primary';
$string['pagelist_all'] = 'All';
$string['pagelist_page'] = 'Page';
$string['pagelist_previous'] = 'previous';
$string['pagelist_next'] = 'next';
//Output styles
$string['output_style'] = 'Output style';
$string['ouharvard'] = 'Open University (Harvard)';
$string['ouharvardhsc'] = 'Open University (Faculty of Health & Social Care)';
$string['mhra'] = 'MHRA-Modern Humanities Research Association';
$string['mla'] = 'MLA 7th Edition';

$string['style_not_found'] = 'Error retrieving chosen output style';
//Ref Items
$string['ref_delete'] = 'Select to delete reference';
$string['ref_update'] = 'Select to update reference';
$string['ref_sourcelink'] = 'Select to view source';
$string['ref_select'] = 'Select';
$string['attachment_alt'] = 'Select to download attachment {$a}';

//Import
$string['reference_file']='Reference file';
$string['reference_import_success']='Your import was successful';
$string['reference_import_failfile'] = 'The attempted file import has failed due to being of an unsupported type.';
$string['reference_import_fail'] = 'The attempted file import has failed.';

//Folders
$string['folders']= 'Folders';
$string['create_folder'] = 'Create folder';
$string['manage_folder'] = 'Manage folders';
$string['share_folder'] = 'Publish folders';
$string['folder_select'] = 'Select folder';
$string['folder_rename'] = 'Rename folder';
$string['add_to_folder'] = 'Select to add to a folder';
$string['add_to_folder_title'] = 'Add to folder';
$string['select_folder'] = 'Select folder';
$string['new_folder_name'] = 'New folder name';
$string['created_folder'] = 'New folder created';
$string['created_folder_error'] = 'There was an error in attempting to create the folder';
$string['addtofolder_success'] = 'Reference added to folder {$a}.';
$string['addtofolder_error'] = 'There was an error adding the reference to folder {$a}.';
$string['viewfolder'] = 'View all references in {$a}';
$string['folder_notfound'] = 'Folder not found.';
$string['remove_from_folder'] = 'Select to remove from folder';
$string['remove_only_folder'] = 'Select to only remove the folder';
$string['rename_folder'] = 'Select to rename the folder';
$string['delete_refs_and_folder'] = 'Select to delete all the contained references plus the folder';
$string['remove_from_folder_title'] = 'Remove from folder';
$string['sure_remove_folder'] = 'Are you sure you wish to remove the selected reference from the folder?';
$string['removefromfolder_success'] = 'Reference removed from folder {$a}.';
$string['removefromfolder_error'] = 'There was an error removing the reference from folder {$a}.';
$string['sure_remove_folder_and_refs'] = 'Are you sure you wish to delete the selected references plus the folder?';
$string['sure_remove_folder_only'] = 'Are you sure you wish to remove the selected folder?';

//Create Bibliography
$string['get_citations_error'] = 'There was an error in attempting to get the Citations.';
$string['createlib_error_noneselected']='No references have been selected for a Bibliography.';

//Manage/Update Refs
$string['delete_ref'] = 'Delete reference';
$string['update_ref'] = 'Update reference';
$string['sure_delete_ref'] = 'Are you sure you wish to delete the selected reference?';
$string['deleteref_success'] = 'Reference deleted.';
$string['deleteref_error'] = 'There was an error when deleting the reference';
$string['getref_error'] = 'There was an error when retrieving the selected reference.';
$string['refsave_error'] = 'There was an error saving the reference.';
$string['refsave_success'] = 'The reference was saved successfully.';
$string['refcreate_error'] = 'There was an error creating the reference.';
$string['refcreate_success'] = 'The reference was created successfully.';
$string['attach_file'] = 'Add attachment';
$string['attachment_error'] = 'There was a RefWorks error whilst attempting to download the selected attachment.<br/>{$a}';
$string['attachment_uploaderror'] = 'There was a RefWorks error whilst attempting to upload the selected attachment.<br/>{$a}';
$string['attach_file_remove'] = 'Remove attachment';
$string['attachment_deleteerror'] = 'There was a RefWorks error whilst attempting to delete the selected attachment.<br/>{$a}';

$string['form_reftype'] = 'Reference type';
$string['form_authors'] = 'Authors (<em>separate with ;</em>)';
$string['form_title'] = 'Title';
$string['form_title2'] = 'Secondary title';
$string['form_periodical'] = 'Journal title';
$string['form_year'] = 'Year';
$string['form_pub_date_free'] = 'Publication day/month';
$string['form_volume'] = 'Volume';
$string['form_edition'] = 'Edition (number only)';
$string['form_issue'] = 'Issue';
$string['form_page'] = 'Start page';
$string['form_otherpage'] = 'End page';
$string['form_publisher'] = 'Publisher';
$string['form_placepub'] = 'Place of publication';
$string['form_editor'] = 'Editor';
$string['form_isbn'] = '<acronym title="International Standard Serial Number">ISSN</acronym>/<acronym title="International Standard Book Number">ISBN</acronym>';
$string['form_s_isbn'] = 'Search <acronym title="International Standard Book Number">ISBN</acronym>';
$string['form_s_issn'] = 'Search <acronym title="International Standard Serial Number">ISSN</acronym>';
$string['form_doi'] = '<acronym title="Digital Object Identifier">DOI</acronym>';
$string['form_s_doi'] = 'Search <acronym title="Digital Object Identifier">DOI</acronym>';
$string['form_s_primosys'] = 'Search Primo System Number';
$string['form_url'] = 'Web address (URL)';
$string['form_OU'] = 'OU Course Material';
$string['form_u1'] = 'Module code';
$string['form_u2'] = 'Module name';
$string['form_u3'] = 'Unit number';
$string['form_doi_get'] = 'Get data (DOI)';
$string['form_isbn_get'] = 'Get data (ISBN)';
$string['form_issn_get'] = 'Get data (ISSN)';
$string['form_primosys_get'] = 'Get data (Primo System Number)';
$string['form_av'] = 'Availability';
$string['form_db'] = 'Database';
$string['form_jo'] = 'Journal Abbreviation';
$string['form_k1'] = 'Descriptors';
$string['form_u5'] = 'Staff Link [override SFX]<br/> (<em>enter a url or type None</em>)';
$string['form_no'] = 'Notes';
$string['form_retrieved'] = 'Retrieved date';
$string['form_sourcetype'] = 'Source type';
$string['form_sourcetype0'] = 'Print';
$string['form_sourcetype1'] = 'Electronic';
$string['form_wt'] = 'Website title';
// SSU require 'lk' field for APA references. owen@ostephens.com 28th March 2012
$string['form_lk'] = 'Links';

//Lookup messages
$string['isbn_primo'] = 'Tried checking ISBN on Primo.';
$string['isbn_worldcat'] = 'Tried checking ISBN on WorldCat.';
$string['issn_primo'] = 'Tried checking ISSN on Primo.';

//Auto Notes
$string['autonote_1'] = 'Digitised material';
$string['autonote_2'] = 'Ebook - printed version also available';
$string['autonote_3'] = 'Ebook only';

//Collaborative accounts
$string['team_account'] = 'Shared account';
$string['team_accounts'] = 'Shared accounts';
$string['team_createacc'] = 'Create shared account';
$string['team_manageacc'] = 'Manage shared accounts';
$string['team_login'] = 'Log in to account:';
$string['team_logout'] = 'Log out of shared account';
$string['team_loggingin'] = 'Logging in to shared account';
$string['team_loginnotallowed'] = 'You do not have permission to log in to this account';
$string['team_noaccess'] = 'Sorry, but this is only available from a shared account ({$a})';
$string['team_loginsuccess'] = 'Successfully logged in to shared account.';
$string['team_loginerror'] = 'There was an error logging in to the shared account';
$string['team_logoutsuccess'] = 'Logged out of shared account.';
$string['team_newaccname'] = 'Account name';
$string['team_createacc_exists'] = 'An account with this login already exists. The following users are account \'owners\':{$a}';
$string['team_createacc_samename'] = 'You have entered the same account name. You cannot rename an account with the existing name.';
$string['team_createacc_error'] = 'There was an error when attempting to create the shared account.';
$string['team_createacc_success'] = 'The shared account was successfully created.';
$string['team_manage_permission_acc'] = 'Manage account access permissions';
$string['team_manage_permission_accname'] = 'Access permissions for {$a}';
$string['team_manage_acc_deny'] = 'You do not have permission to manage access to this account.';
$string['team_shared_folder_link'] = 'Link to shared folder.';
$string['team_new_account_name'] = 'New account name';
$string['team_rename_account'] = 'Rename account';
$string['team_sure_remove_account'] = 'Are you sure you wish to delete the selected account ({$a})?';
$string['team_account_profile_not_found'] = 'The shared account login was not found';
$string['team_permanent_delete_account'] = 'Select to permanently delete the selected account ({$a})';
$string['team_restore_account'] = 'Select to restore the selected account ({$a})';
$string['team_sure_delete_account'] = 'Are you sure you wish to permanently delete the selected account ({$a})?';
$string['team_no_accounts'] = 'You currently own no shared accounts';
$string['team_update_error'] = 'There was an error whilst attempting to update the Refworks account';
$string['team_dbaccess_error'] = 'There was an error whilst attempting to access the shared account details from the database';
$string['team_deleteacc_error'] = 'There was an error whilst attempting to delete the shared account.';
$string['team_dbdelete_error'] = 'There was an error whilst attempting to delete the shared account details from the database, please contact the helpdesk.';
$string['changeinvites']='Add or remove members';

//Main page
$string['viewinst'] = '
<p>
MyReferences allows you to build a personal library of references. A reference is a detailed description of a source used in your study or research and could include books, journals websites and many other types of material.
</p><p>
MyReferences helps you to create correctly formatted bibliographies (lists of all the materials that you have referred to in your work or have used as general reading to provide background reading for your study or research) and citations (an \'indicator\' you put in the text to alert the reader to the fact you are talking about somebody else\'s work or using somebody else\'s words) using the references you have saved. 
</p><p>
MyReferences is powered by the RefWorks software.  RefWorks is an online bibliographic management software package that enables you to store and manage your references electronically and automatically generate bibliographies.  RefWorks can be accessed directly if you need access to more advanced tools when creating and managing your references. An explanation of <a href="http://www.refworks.com/RWSingle/help/Refworks.htm">how to access and use RefWorks</a> is available on the RefWorks website.
</p>
';

//Invites
$string['selectpersoninvites']='Add or remove members';
$string['invite_instructions']='<p>
To invite people, enter their usernames or email addresses in the box below.
</p>
<ul>
<li>To get somebody\'s username, you need to ask them personally. This is the
name (not the password!) that they use to log in to the system.</li>
{$a}
</ul>
<p>
The system will automatically send an email to each person, with information
and a link to this activity.
</p>
';
$string['donesomething1']='The selected people have been added.';
$string['donesomething2']='Some usernames were not correct. Please check with the people concerned that you have their correct usernames.';
$string['donesomething3']='Some usernames were already listed as members. Please try again.';
$string['donesomething4']='No usernames entered. Please enter a list of usernames, separated by space.';
$string['donesomething5']='One or more of the people you entered do not have access to view shared accounts.';
$string['donesomething6']='No usernames selected. Please choose source of usernames and select names from dropdown.';
$string['donesomething11']='No members selected. Please select members from the list (use Ctrl-click, or &#x2318;-click for Mac users, to select more than one) before clicking Remove.';
$string['failed']='The following usernames caused problems: {$a}.';
$string['existing']='Existing members';
$string['new']='Invite new members';
$string['ownercheckbox'] = 'Add the new members as account owners.<br/>(An account owner can add and remove members and delete an account).';
$string['usernames']='Usernames to invite';
$string['confirmadd']='Confirm invitation';
$string['usernames']='Usernames to invite';
$string['confirmadd']='Confirm invitation';
$string['inviteconfirm_instructions']='
<p>
This is the list of people you\'re inviting, along with the email that
will be sent to them.
</p>
';
$string['invite_email_subject']='ONLINE READING LIST: Library Invitation to a shared MyReferences (RefWorks) account';
$string['invite_email_body']='
(This is an automated message. Please do not reply.)

{$a->victim},

{$a->creator} has invited you to take part in a MyReferences shared account
\'{$a->nametext}\'.

This is for the online reading list for your unit and will allow you to edit/add/delete readings as required. 

Please liaise with Andy Forbes or Hannah Young if you wish to make reading list changes during our pilot or want to learn more about the reading list system.

To access the shared account, select the following link:
{$a->url}
';
$string['sentinvitations']='Invitations sent';
$string['invite_sentto']='Invitation emails have been sent to the following people:';
$string['invite_notsentto']='For technical reasons, we were unable to send all the invitation emails. You will need to let the following people know about the activity yourself:';
$string['confirmremove']='Confirm remove';
$string['removeconfirm_instructions']='
<p>
You have requested that the following people be removed from the account.
Once removed, they will no longer be able to access it. They will be sent an
email (shown below) to inform them of this change.
</p>';
$string['remove_email_subject']='Removed from MyReferences shared account';
$string['remove_email_body']='
(This is an automated message. Please do not reply.)

{$a->victim},

{$a->creator} has removed you from the shared references account
\'{$a->nametext}\'.

You will no longer be able to access this account.
';
$string['removed']='Remove complete';
$string['remove_sentto']='Emails have been sent to the following people:';
$string['remove_notsentto']='For technical reasons, we were unable to send all the emails to inform people that they have been removed. You may wish to tell the following people yourself:';
$string['invite_nousers'] = 'There are no users to invite. This may be because the user(s) you selected do not have permission to view shared accounts.';
$string['makeowner'] = 'Make account owner';
$string['madeowner'] = 'Made the following members account owners:';

//Validation
$string['minimumchars'] = 'Minimum of {$a} characters';

//Retrieval of Ref using DOI
$string['doi_getref_error'] = 'There was an error when attempting to retrieve the selected reference information from <a href=\"http://www.crossref.org\" target=\"_blank\" >crossref</a> using the supplied DOI.';
$string['doi_getref_empty'] = 'No reference data was found at <a href=\"http://www.crossref.org\" target=\"_blank\" >Crossref</a> for this DOI.';
//Retrieval of Ref using ISBN
$string['isbn_getref_empty'] = 'No reference data was found for this ISBN.';
//Retrieval of Ref using ISSN
$string['issn_getref_empty'] = 'No reference data was found for this ISSN.';

//Reports
$string['reports'] = 'Reference reports';
$string['selectallrefs'] = 'Include all references';
$string['report_linkcheck'] = 'Check links';
$string['report_linkcheck_desc'] = 'Tests the generated link to the reference source.';
$string['report_linkcheck_totalrefs'] = 'Total number of references: {$a}';
$string['report_linkcheck_totallinks'] = 'Total number of source links: {$a}';
$string['report_linkcheck_totaloklinks'] = 'Number of links without error: {$a}';
$string['report_linkcheck_totalhttperrors'] = 'Number of links with HTTP error: {$a}';
$string['report_linkcheck_totalerrorlinks'] = 'Number of links that went to error site: {$a}';
$string['report_preview'] = 'Publish preview';
$string['report_preview_desc'] = 'Displays how the reference(s) will be published.';
$string['report_preview_type'] = 'Output type';
$string['report_preview_resourcepage'] = 'RefWorks RSS feed';
$string['report_preview_sc'] = 'Structured content';
$string['report_preview_collaborative'] = 'Collaborative activity';

//invite help
$string['invite'] = 'Managing access to collaborative accounts';
$string['invite_help'] = <<<INVI
<p>
Using this screen you can invite people to a new or existing shared account.
You can also remove people from an existing account.
</p>
<h2>Invite new members</h2>

<p>
To invite people, type in their usernames into the box.
You can type one or more names, separated by spaces and/or commas. Then
click <strong>Invite people</strong> and follow the prompts.
</p>

<ul>
<li>If you don't have somebody's username, you need to ask them for it (when
you meet them in person, by telephone or email, in a tutor group forum, or similar).</li>
<li>The system will check all the usernames. If there is an error, you'll be
told, and will have to remove or correct the username involved.</li>
<li>Once all usernames are correct, you will be shown a list of the full names
involved (to make sure you're inviting the right people) and given another
opportunity to cancel if needed. You'll also see a preview of the email that
will be sent.</li>
</ul>

<h2>Remove existing members</h2>

<p>
To remove people, select them from the <strong>Existing members</strong> list
by clicking on their names. You can select more than one person by Ctrl-clicking
(or on a Mac, &#x2318;-clicking). Then click <strong>Remove</strong> and follow
the prompts.
</p>

<p><i>Note for keyboard users: once you have tabbed to the list and used the
arrow keys to find the relevant name, pressing Ctrl+Space selects or deselects
it. You can use this technique to select multiple people.</i></p>

<ul>
<li>Similar to inviting people, you will see a confirmation that includes the
usernames you're removing and the email they will be sent.</li>
<li>There is currently no way to include a custom message within the
you-have-been-removed email.</li>
</ul>

<p>
Once you have removed people from the account it disappears from their shared
accounts list and they can no longer access it (even if they've bookmarked it).
<i>Note: removal might take a while to take full effect if the person is currently logged in to the account.</i>
</p>
INVI;
?>