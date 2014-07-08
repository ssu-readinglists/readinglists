<?php

$string['pluginname'] = 'Bibliographic References';

//Config settings

$string['configsettings'] = 'References';

$string['about'] = 'References code library used to connect to services such as RefWorks and to manipulate bibliographic reference data.';

$string['rwhead'] = 'RefWorks API settings';

$string['config_accesskeyid'] = 'Access key';
$string['config_accesskeyid_desc'] = 'Your AccessKeyID provided by RefWorks';

$string['config_secretkey'] = 'Secret key';
$string['config_secretkey_desc'] = 'The secret key provided by RefWorks';

$string['config_groupcode'] = 'Group code';
$string['config_groupcode_desc'] = 'The group code that is used in RefWorks to identify your institution';

$string['config_tempun'] = 'RefWorks "temp" account username';
$string['config_tempun_desc'] = 'The username for a RefWorks account where references can be stored temporarily for processing';

$string['config_temppw'] = 'RefWorks "temp" account password';
$string['config_temppw_desc'] = 'The password for a RefWorks account where references can be stored temporarily for processing';

$string['config_groupid'] = 'Athens Group ID';
$string['config_groupid_desc'] = 'OU only setting for when RefWorks accounts are created using Athens authentication (do NOT set if otherwise or else account creation will break). Set to the group id that is present in all account names. Account names follow format: "PUID-UNIQUEID:GROUPID".';

$string['config_referencestyles'] = 'Custom Reference styles';
$string['config_referencestyles_desc'] = 'Define custom reference styles that override the defaults specified in /apbib/apibib_lib.php $referencestyles. Must be a serialized value.';

$string['ourlhead'] = 'Resource linking';
$string['config_openurl'] = 'Open URL resolver';
$string['config_openurl_desc'] = 'Set to the URL prefix of your Open URL resolving service (e.g. http://www.crossref.org/openurl?pid=name@someplace.com)';

$string['crossrefhead'] = 'DOI Lookup (CrossRef API)';
$string['config_crossrefuser'] = 'CrossRef API user';
$string['config_crossrefuser_desc'] = 'Username of account that is registered to access the CrossRef API. (http://www.crossref.org/requestaccount/)';

$string['config_crossrefpwd'] = 'CrossRef API password';
$string['config_crossrefpwd_desc'] = 'Password of account that is registered to access the CrossRef API. (Some accounts do not require password)';

$string['wchead'] = 'ISBN/ISSN Lookup (WorldCat Search API)';
$string['config_wcwskey'] = 'WorldCat API key';
$string['config_wcwskey_desc'] = 'Web service key/token that will authorise access to WorldCat search API.';

//Primo Configuration strings
$string['primohead'] = 'ISBN/ISSN Lookup (Primo)';
$string['config_primourl'] = 'Primo URL';
$string['config_primourl_desc'] = 'Base URL of your Primo Server';
$string['config_primoport'] = 'Port for your Primo X-Server';
$string['config_primoport_desc'] = 'Port for your Primo X-Server';
$string['config_primoinst'] = 'Primo Institution Code';
$string['config_primoinst_desc'] = 'Primo Institution Code';
$string['config_primoplinkback'] = 'Linkback syntax to Primo for print items';
$string['config_primoplinkback_desc'] = 'Path for forming linkback to Primo for print items. The Primo DocID will be appended to the end of this to form the link';
$string['config_primoolinkback'] = 'Linkback syntax to Primo for online items';
$string['config_primoolinkback_desc'] = 'Path for forming linkback to Primo for online items. The Primo DocID will be appended to the end of this to form the link';
$string['config_primolibs'] = 'Map abbreviated library names used by Primo to full names';
$string['config_primolibs_desc'] = 'A comma separated list of mappings. Each mapping should be of the form: "Abbrevation":"Full name". e.g. "BL":"British Library","NLW":"National Library of Wales"';
$string['config_bk_online_avail'] = 'Availability text for ebooks';
$string['config_bk_online_avail_desc'] = 'Will be used in the \'Availability\' field (\'av\') for books with an online location"';
$string['config_jn_online_avail'] = 'Availability text for ejournals';
$string['config_jn_online_avail_desc'] = 'Will be used in the \'Availability\' field (\'av\') for journals with an online location"';
$string['config_vid_online_avail'] = 'Availability text for videos';
$string['config_vid_online_avail_desc'] = 'Will be used in the \'Availability\' field (\'av\') for videos with an online location"';
$string['config_bk_print_avail'] = 'Availability text for ebooks';
$string['config_bk_print_avail_desc'] = 'Will be used in the \'Availability\' field (\'av\') for books with an physical location"';
$string['config_jn_print_avail'] = 'Availability text for ejournals';
$string['config_jn_print_avail_desc'] = 'Will be used in the \'Availability\' field (\'av\') for journals with an physical location"';
$string['config_vid_print_avail'] = 'Availability text for videos';
$string['config_vid_print_avail_desc'] = 'Will be used in the \'Availability\' field (\'av\') for videos with an physical location"';

//Plugin strings (from 1.9 version)
$string['reference_xmlloaderror']='There was an error with the reference xml file';

$string['reference_exporttitle']='Export references';
$string['refexport_exportto']='Send to';
$string['refexport_downloadto']='Download as';

$string['refexport_error_noneselected']='No references have been selected to export.';
$string['refexport_error']='There has been a error in the export of the references.';

$string['refexport_ris']='RIS';
$string['refexport_risdesc']='Download the references in RIS file format.<br/>This format can be imported into a number of bibliographic management systems.';

$string['refexport_mystuff']='MyStuff';
$string['refexport_mystuffdesc']='Save the references to your personal MyStuff area.';

$string['refexport_mystuff_success']='Export to MyStuff was successful';
$string['refexport_mystuff_fail']='There was an error when exporting to MyStuff.';

$string['refexport_rwde']='MyReferences (RefWorks)';
$string['refexport_rwdedesc']='Save the references to your MyReferences library or RefWorks account.';

$string['refexport_rwde_createacc']='Select to create an account and add the selected references';
$string['refexport_rwde_submit']='Select to export to an existing RefWorks account';
$string['refexport_rwde_linktoref']='Access your RefWorks account';
$string['refexport_rwde_linktorefdesc']='(Use this link to automatically login to your RefWorks account.)';

$string['refexport_rwde_selfolder'] = 'Select a folder';
$string['refexport_rwde_none'] = 'None';
$string['refexport_rwde_save'] = 'Save references';

$string['refexport_moodlefilter']='Collaborative activity';
$string['refexport_moodlefilterdesc']='Produce shareable references that can be added to an activity such as a forum, wiki or blog.';

$string['refexport_bibordfa']='Web Site';
$string['refexport_bibordfadesc']='Produce reference text that can be be added to an external website such as a collaborative website, personal blog or a community forum.';
//Strings for refexport - references filter
$string['ref_filter_instruct'] = '<p>This export allows you to publish and share references within activities in your course website. When you publish and share a reference others will be able to visit the source of the reference (if available) and will be able to export/save the reference for their own use.</p><p>You might want to use this feature to:</p><ol><li>Post a reference in your course forum so others can view the source.</li><li>Share references in a collaborative wiki.</li></ol><p>To use this feature:</p><ol><li>Copy the text below into your clipboard (One way to do this is to right-click on the text when it is highlighted and select <em>Copy</em>.).</li><li>Paste the text into the edit text box in the place in the activity in which you wish to share the reference. For example, in a forum you can paste the text anywhere within a post.</li></ol>';
$string['clipboardcopy'] = 'Copy to clipboard';

$string['refexport_rwxml']='RefWorks XML';
$string['refexport_rwxmldesc']='Download the references in XML file format.<br/>This format can be imported into a RefWorks/MyReferences account.';

$string['linktorefworks'] = '[http://refworks.com]';