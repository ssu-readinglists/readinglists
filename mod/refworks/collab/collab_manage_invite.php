<?php
//re-use of shared activities invite system (re-purposed invite.php)

//This page is not directly called - should be included in a page that has accid sent to it

require_once($CFG->libdir . '/formslib.php');

global $CFG, $USER, $COURSE, $OUTPUT, $DB;


$sainvite = optional_param('sainvite', 0, PARAM_INT);                     // this is specifically for invite.php
$selectpersonnojs=optional_param('select_person_nojs',0,PARAM_INT);        // set to 1 if javascript disabled
$redirectback=optional_param('redirectback',0,PARAM_INT);                // set to 1 if javascript disabled
$selectednames=optional_param('selectednames',0,PARAM_ALPHANUM);    // cancel ````


$accid=required_param('accid',PARAM_INT);

if (optional_param('redirecttocourse','',PARAM_ALPHA)) {
    redirect(refworks_base::return_link('./../view.php'));
}

$asowner=optional_param('asowner',0,PARAM_INT);//whether any new participants should be 'owners'

//$cmid=required_param('activity',PARAM_INT);
$justcreated=optional_param('justcreated',0,PARAM_INT);
//$userid=required_param('user',PARAM_INT);
$userid = $USER->id;


$activityuser=$USER;
$info = new stdClass();

$invitepageurl=refworks_base::return_link('collab/collab_manage_users.php?accid='.$accid.
($justcreated?'&justcreated=1':''));

$activityfields="<input type='hidden' name='accid' value='$accid' />
<input type='hidden' name='user' value='$userid' />".
"<input type='hidden' name='sesskey' value='".sesskey()."' />".
($justcreated?'<input type="hidden" name="justcreated" value="1" />':'').
($asowner?'<input type="hidden" name="asowner" value="1" />':'');

if (refworks_base::$isinstance) {
    $activityfields.="<input type='hidden' name='id' value='".refworks_base::$cm->id."' />";
}


function get_email($info,$targetname,$emailtype) {
    global $USER,$CFG;
    $info->victim=$targetname;
    $info->creator=fullname($USER);
    $info->url=refworks_base::return_link('collab/collab_login.php?accid='.required_param('accid',PARAM_INT));
    //$info->sitename=get_site()->shortname;
    $info->nametext=htmlspecialchars_decode(refworks_collab_lib::get_account_details(required_param('accid',PARAM_INT))->name);
    //$info->summarytext=format_text_email(trusttext_strip($info->summary),FORMAT_HTML);
    $email->subject=get_string($emailtype.'_email_subject','refworks',$info);
    $email->body=trim(get_string($emailtype.'_email_body','refworks',$info));
    $email->preview="<h3>{$email->subject}</h3>{$email->body}";
    return $email;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action=required_param('action',PARAM_ALPHA);
    if ($action=='add' || $action=='noreallyadd') {
        if (isset($_POST['cancel'])) {
            $add=stripslashes(optional_param('add','',PARAM_RAW));//TODO Check this, not sure what add does and what default should be?
            redirect($invitepageurl.'&add='.urlencode(preg_replace('/\s+/',' ',$add)));
        }

        $users=array();
        $failed=array();
        $badname=false;


        $add=stripslashes(required_param('add',PARAM_RAW));
        // Split names on spaces and commas
        $names=preg_split('~[\s,]+~',strtolower($add),-1,PREG_SPLIT_NO_EMPTY);
        if (count($names)===0) {
            redirect($invitepageurl.'&donesomething=4');
            exit;
        }
        $names=array_unique($names);
        // Get participants list
        $participants=refworks_collab_lib::get_participants($accid);
        $existingids=array();
        foreach ($participants as $someuserid) {
            $existingids[$someuserid]=true;
        }
        // Search for each name and see if any are missing or don't exist
        $existingname=false;
        foreach ($names as $key=>$name) {
			// If $name looks like an email, try to lookup username
			// Added by owen@ostephens.com 28th March 2012
			if(preg_match('/@/',$name) > 0) {
				if($user = $DB->get_record('user',array('email' => addslashes($name)))) {
					$name = $user->username;
				}
			}
            if (!$user=$DB->get_record('user',array('username' => addslashes($name)))) {
                $badname=true;
                $failed[]=$name;
                continue;
            }
            if (array_key_exists($user->id,$existingids)) {
                $existingname=true;
                $failed[]=$name;
                unset($names[$key]);
                continue;
            }
            if (!refworks_base::check_capabilities('mod/refworks:collaboration',$user->id)) {
                $banned=true;
                $failed[]=$name;
                unset($names[$key]);
                continue;
            }
            $users[]=$user;
        }
        if (count($failed)>0) {
            redirect($invitepageurl.'&donesomething='.
            ($badname ? 2 : ($banned ? 5: 3)).'&add='.urlencode(implode(' ',$names)).'&failed='.
            urlencode(implode(', ',$failed)));
            exit;
        }

        // OK, we now have a valid list of users to add. Are we really adding them?
        if ($action==='noreallyadd') {
            //Check valid session for security purposes
            confirm_sesskey();
            // Yep, we're really adding them
            $sent=array();
            $notsent=array();
            $invitedlist='';
            foreach ($users as $user) {
                // Add participant
                refworks_collab_lib::add_participant($accid,$user->id,$asowner);

                // Send the email!
                $email=get_email($info,fullname($user),'invite');
                if (email_to_user(
                $user,$USER,$email->subject,$email->body)===true) {
                    $sent[]=$user;
                } else {
                    $notsent[]=$user;
                }
            }

            // Display the header...

            print '<div class="sa_mainbit">';
            echo $OUTPUT->heading(get_string('sentinvitations','refworks'));


            // Show the information about emails that were sent and that
            // weren't
            if (count($sent)>0) {
                print '<p>'.get_string('invite_sentto','refworks').'</p><ul>';
                foreach ($sent as $user) {
                    print '<li>'.fullname($user). ' ('.htmlspecialchars($user->username).')</li>';
                }
                print '</ul>';
            }
            if (count($notsent)>0) {
                print '<p>'.get_string('invite_notsentto','refworks').'</p><ul>';
                foreach ($notsent as $user) {
                    print '<li>'.fullname($user). ' ('.htmlspecialchars($user->username).')</li>';
                }
                print '</ul>';
            }

        } else {
            // This is just the preview/check page

            if (!isset($users[0])) {
                //No users in list (e.g. some didn't have capability)
                print_error('invite_nousers','refworks',$invitepageurl);
            }
            // Display the header...
            //$strsharedactivities=get_string('formatsharedactv','format_sharedactv');
            $strinvite=get_string('changeinvites','refworks');
            $strconfirmadd=get_string('confirmadd','refworks');
            $strcancel=get_string('cancel');

            print '<div class="sa_mainbit">';
            //print_heading($strconfirmadd);

            // Show the initial information
            print_string('inviteconfirm_instructions','refworks');
            print '<ul>';
            foreach ($users as $user) {
                print '<li>'.fullname($user).
                    ' ('.htmlspecialchars($user->username).')</li>';
            }
            $email=get_email($info,fullname(reset($users)),'invite');
            print <<<END
</ul>
<pre>
            {$email->preview}
</pre>
<form method='post' action='collab_manage_users.php'><div>
            $activityfields
<input type='hidden' name='action' value='noreallyadd' />
END;


            print '<input type="hidden" name="add" value="'.$add.'" />';


            print <<<END
<input type='submit' value='$strconfirmadd' />
<input type='submit' name='cancel' value='$strcancel' />
</div></form>
END;
        }
        print '</div>';
        refworks_base::write_footer();
        exit;
    }
    if ($action==='remove' || $action==='noreallyremove') {
        // Check and clean parameter
        if (!array_key_exists('remove',$_POST) || !is_array($_POST['remove'])) {
            redirect($invitepageurl.'&donesomething=11');
        }
        if (isset($_POST['cancel'])) {
            redirect($invitepageurl);
        }
        $remove=$_POST['remove'];
        $removedatahtml='';
        foreach ($remove as $someuserid) {
            if (!preg_match('/^[0-9]+$/',$someuserid)) {
                error('Remove parameter has incorrect format',$invitepageurl);
            }
            $removedatahtml.='<input type="hidden" name="remove[]" value="'.$someuserid.'" />';
        }

        // Get participants list and check everyone is in it
        $participants=refworks_collab_lib::get_participants($accid);
        $partids=array();
        foreach ($participants as $part) {
            $partids[] = $part->id;
        }
        $existingids=array();
        //$participants=$participants[SHAREDACTV_ROLE_PARTICIPANT];
        $users=array();
        foreach ($remove as $someuserid) {
            if (!in_array($someuserid,$partids)) {
                error('Requested user '.$someuserid.' is not a participant',$invitepageurl);
            }
            $users[$someuserid]=$DB->get_record('user',array('id' => $someuserid));
        }

        //bug 9485 Added ability to set existing members to owners
        $makeowner = optional_param('makeowner','',PARAM_TEXT);
        if ($makeowner!='') {
            print '<div class="sa_mainbit">';
            print '<p>'.get_string('madeowner','refworks').'</p><ul>';
            print '<ul>';
            foreach ($users as $user) {
                refworks_collab_lib::add_participant($accid,$user->id,1);
                print '<li>'.fullname($user). ' ('.htmlspecialchars($user->username).')</li>';
            }
            print '</ul>';
        }else

        // Right, parameters are ok
        if ($action==='noreallyremove') {
            //Check valid session for security purposes
            confirm_sesskey();
            // Yep, we're really removing them
            $sent=array();
            $notsent=array();
            $removedlist='';
            foreach ($users as $user) {
                // Add participant
                refworks_collab_lib::remove_participant($accid,$user->id);
                $removedlist.=$user->id.' ';

                // Send the email!
                $email=get_email($info,fullname($user),'remove');
                if (email_to_user(
                $user,$USER,$email->subject,$email->body)===true) {
                    $sent[]=$user;
                } else {
                    $notsent[]=$user;
                }
            }

            print '<div class="sa_mainbit">';
            //print_heading($strsent);

            // Show the information about emails that were sent and that
            // weren't
            if (count($sent)>0) {
                print '<p>'.get_string('remove_sentto','refworks').'</p><ul>';
                foreach ($sent as $user) {
                    print '<li>'.fullname($user). ' ('.htmlspecialchars($user->username).')</li>';
                }
                print '</ul>';
            }
            if (count($notsent)>0) {
                print '<p>'.get_string('remove_notsentto','refworks').'</p><ul>';
                foreach ($notsent as $user) {
                    print '<li>'.fullname($user). ' ('.htmlspecialchars($user->username).')</li>';
                }
                print '</ul>';
            }
            /*print '<form action="invite.php" method="get">'.$activityfields.'
             <input type="submit" value="'.get_string('continue').'" />
             </form>';*/

        } else {
            // This is just the confirmation page

            // Display the header...

            $strconfirmremove=get_string('confirmremove','refworks');
            $strcancel=get_string('cancel');

            print '<div class="sa_mainbit">';
            //print_heading($strconfirmremove);

            // Show the initial information
            print_string('removeconfirm_instructions','refworks');
            print '<ul>';
            foreach ($users as $user) {
                print '<li>'.fullname($user).
                    ' ('.htmlspecialchars($user->username).')</li>';
            }
            $email=get_email($info,fullname(reset($users)),'remove');

            print <<<END
</ul>
<pre>
            {$email->preview}
</pre>
<form method='post' action='collab_manage_users.php'><div>
            $activityfields
<input type='hidden' name='action' value='noreallyremove' />
            $removedatahtml
<input type='submit' value='$strconfirmremove' />
<input type='submit' name='cancel' value='$strcancel' />
</div></form>
END;
        }
        print '</div>';
        refworks_base::write_footer();
        exit;
    }

} else {
    // Display the main UI
    $donesomething=optional_param('donesomething',0,PARAM_INT);
    $add=stripslashes(optional_param('add','',PARAM_CLEAN));
    $failed=stripslashes(optional_param('failed','',PARAM_CLEAN));

    // Display the header...
    //$strsharedactivities=get_string('formatsharedactv','format_sharedactv');
    $strinvite=get_string('selectpersoninvites','refworks');
    /*$nav=array();
     $nav[]=array('name'=>$info->name,'type'=>'module','link'=>
     'go.php?to='.$cmid);
     $nav[]=array('name'=>$strinvite,'type'=>'misc');
     print_header($strinvite,$strinvite,build_navigation($nav));
     sharedactv_ui_viewotherbar($activityuser);*/
    print '<div class="sa_mainbit">';



    echo $OUTPUT->heading_with_help($strinvite, 'invite', 'refworks');



    // Display message if some action was already taken
    if ($donesomething) {

        //$strbacktocourse=get_string('backtocourse','format_sharedactv');
        $strdonesomething=get_string('donesomething'.$donesomething,'refworks');
        $failmessage=$failed ? get_string('failed','refworks',$failed) : '';
        //$backlink=$donesomething==1 ? "(<a href='./'>$strbacktocourse</a>.)" : '';
        $backlink='';
        print <<<END
<div class="sa_donesomething">
<p>
        $strdonesomething $failmessage $backlink
</p>
</div>
END;
    }

    // Get list of already existing people
    $existing=refworks_collab_lib::get_participants($accid);
    //$existing=$existing[SHAREDACTV_ROLE_PARTICIPANT];

    if (count($existing)>0) {
        $strexisting=get_string('existing','refworks');
        $strremove=get_string('remove');
        $strmakeowner=get_string('makeowner','refworks');
        $options='';
        foreach ($existing as $user) {
            $name=fullname($user).' ('.$user->username.')';
            $options.="<option value='{$user->id}'>$name</option>";
        }
        print <<<END
<div class="sa_existing">
<h3>$strexisting</h3>
<form method="post" action="collab_manage_users.php"><div>
        $activityfields
<input type='hidden' name='action' value='remove' />
<select name='remove[]' multiple='multiple' size='14'>
        $options
</select>
</div>
<div class='sa_block'>
<input type='submit' value='$strremove' />
<input type='submit' value='$strmakeowner' name='makeowner' />
</div>
</form>
</div>
END;
    }

    $strexplanation=get_string('invite_instructions','refworks',
    $justcreated ? get_string('invite_instructions_firsttime','refworks'):'');
    $strcancel=get_string('cancel');
    $strusernames=get_string('usernames','refworks');
    $extraclass=count($existing)==0 ? ' sa_nonames' : '';
    $strnew=get_string('new','refworks');
    $optionalheading=count($existing)===0 ? '' : '<h3>'.$strnew.'</h3>';
    $asownercheckbox = '<p><input type="checkbox" name="asowner" id="asowner" value="1"></input><label for="asowner">'.get_string('ownercheckbox','refworks').'</label></p>';


    //standard moodle
    $cancelurl = refworks_base::return_link('collab/collab_manage.php');
    print <<<END
<div class="sa_new$extraclass">
    $optionalheading
    $strexplanation
<form method="post" action="collab_manage_users.php"><div>
    $activityfields
<input type='hidden' name='cancelurl' value='{$cancelurl}' />
<input type='hidden' name='action' value='add' />
<div class="sa_block">
<label class="accesshide" for="add">$strusernames</label>
<textarea id="add" name='add' rows='5' cols='40'>$add</textarea>
</div>
<div>$asownercheckbox<input type='submit' value='$strnew' id="invite" /></div>
</div></form>
<form method="get" action="collab_manage.php"><div>
    $activityfields
<div><input type='submit' value='$strcancel' /></div>
</div></form>
</div>
<div class="clearer"></div>
END;


    // Link back to course
    /*$strreturn=get_string('backtocourse','format_sharedactv');


    print '<div class="sa_linkback">'.link_arrow_left($strreturn,$CFG->wwwroot.
    '/course/format/sharedactv/').'</div>';*/

    // ...and footer
    print '</div>';
    //print_footer();
}
?>