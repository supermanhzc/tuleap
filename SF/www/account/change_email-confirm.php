<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');   
require_once('common/include/Mail.class');

$Language->loadLanguageMsg('account/account');

$confirm_hash = substr(md5($session_hash . time()),0,16);

$res_user = db_query("SELECT * FROM user WHERE user_id=".user_getid());
if (db_numrows($res_user) < 1) exit_error("Invalid User","That user does not exist.");
$row_user = db_fetch_array($res_user);

db_query("UPDATE user SET confirm_hash='$confirm_hash',email_new='$form_newemail' "
	. "WHERE user_id=$row_user[user_id]");

list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		

$message = stripcslashes($Language->getText('account_change_email-confirm', 'message', array($GLOBALS['sys_name'], get_server_url()."/account/change_email-complete.php?confirm_hash=$confirm_hash")));

$mail =& new Mail();
$mail->setTo($form_newemail);
$mail->setSubject($GLOBALS['sys_name'].': '.$Language->getText('account_change_email-confirm', 'title'));
$mail->setBody($message);
$mail->setFrom("noreply@".$host);
$mail_is_sent = $mail->send();
site_header(array('title'=>$Language->getText('account_change_email-confirm', 'title'))); ?>


<P><B><?php if ($mail_is_sent) { echo $Language->getText('account_change_email-confirm', 'title'); ?></B>

<P><?php echo $Language->getText('account_change_email-confirm', 'mailsent'); ?>.

<P><A href="/">[ <?php echo $Language->getText('global', 'back_home'); ?> ]</A>

<?php
} else {
    $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']));
}
site_footer(array());

?>
