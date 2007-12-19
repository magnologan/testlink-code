<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Filename $RCSfile: lostPassword.php,v $
 *
 * @version $Revision: 1.20 $
 * @modified $Date: 2007/12/19 20:27:18 $ $Author: schlundus $
 *
**/
require_once('config.inc.php');
require_once('common.php');
require_once('users.inc.php');
require_once('email_api.php');

$_POST = strings_stripSlashes($_POST);
$login = isset($_POST['login']) ? $_POST['login']: null;

$op = doDBConnect($db);
if ($op['status'] == 0)
{
	$smarty = new TLSmarty();
	$smarty->assign('title', lang_get('fatal_page_title'));
	$smarty->assign('msg', $op['dbms_msg']);
	$smarty->display('fatal_error.tpl');
	exit();
}

$note = lang_get('your_info_for_passwd');
if (strlen($login))
{
	$userID = tlUser::doesUserExist($db,$login);
	if (!$userID)
		$note = lang_get('bad_user');
	else
	{
		$result = resetPassword($db,$userID,$note);
		if ($result == OK)
		{
			redirect(TL_BASE_HREF ."login.php?note=lost");
			exit();
		}
		else if ($result == tlUser::USER_E_EMAILLENGTH)
			$note = lang_get('mail_empty_address');
		else if (!strlen($note))
			$note = getUserErrorMessage($result);
	}
}

$smarty = new TLSmarty();
$smarty->assign('login_logo', LOGO_LOGIN_PAGE);
$smarty->assign('css', TL_BASE_HREF . TL_LOGIN_CSS);
$smarty->assign('note',$note);
$smarty->assign('page_title',lang_get('page_title_lost_passwd'));
$smarty->display('loginLost.tpl');
?>
