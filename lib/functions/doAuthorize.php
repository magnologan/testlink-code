<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 * 
 * @filesource $RCSfile: doAuthorize.php,v $
 * @version $Revision: 1.17 $
 * @modified $Date: 2007/12/19 20:27:19 $ by $Author: schlundus $
 * @author Chad Rosen, Martin Havlat
 *
 * This file handles the initial login and creates all user session variables.
 *
 * @todo Setting up cookies so that the user can automatically login next time
 * 
 * Revision:
 *           20070130 - jbarchibald -
 *           $_SESSION['filter_tp_by_product'] should always default to = 1;
 *
 *           20060507 - franciscom - 
 *           added bare bones LDAP authentication using mantis code
 *                                  
 *
 */
require_once("users.inc.php");
require_once("roles.inc.php");

/** authorization function verifies login & password and set user session data */
function doAuthorize(&$db,$login,$pwd)
{
    $bSuccess = false;
	$sProblem = 'wrong'; // default problem attribute value
	
	$_SESSION['locale'] = TL_DEFAULT_LOCALE; 

	if (!is_null($pwd) && !is_null($login))
	{
		$user = new tlUser();
		$user->login = $login;
		$login_exists = ($user->readFromDB($db,tlUser::USER_O_SEARCH_BYLOGIN) == OK); 
		tLog("Account exist = " . $login_exists);
	    if ($login_exists)
	    {
			$password_check = auth_does_password_match($user,$pwd);
			if ($password_check->status_ok && $user->bActive)
			{
				// 20051007 MHT Solved  0000024 Session confusion 
				// Disallow two sessions within one browser
				if (isset($_SESSION['user']) && strlen($_SESSION['user']))
				{
					$sProblem = 'sessionExists';
					tLog("Session exists. No second login is allowed", 'INFO');
				}
				else
				{ 
					$_SESSION['filter_tp_by_product'] = 1;
					$userProductRoles = getUserTestProjectRoles($db,$user->dbID);
					$userTestPlanRoles = getUserTestPlanRoles($db,$user->dbID);
				  
					//Setting user's session information
					setUserSession($db,$user->login, $user->dbID,$user->globalRoleID, 
						$user->emailAddress, $user->locale,null,$userProductRoles,
						$userTestPlanRoles);
					$bSuccess = true;
				}
			}
		}
	}
	if ($bSuccess)
	{
	    tLog("Login ok. (Timing: " . tlTimingCurrent() . ')', 'INFO');
	    //forwarding user to the mainpage
	    redirect($_SESSION['basehref'] ."index.php");
	}
	else
	{
		tLog("Account ".$login." doesn't exist or used wrong password.",'INFO');
		// not authorized
		tLog("Login '$login' fails. (Timing: " . tlTimingCurrent() . ')', 'INFO');
		redirect($_SESSION['basehref'] . "login.php?note=" . $sProblem);
	}
}


// 20060507 - franciscom - based on mantis function
//
//
// returns:
//         obj->status_ok = true/false
//         obj->msg = message to explain what has happened to a human being.
//
function auth_does_password_match(&$user,$cleartext_password)
{
	$login_method = config_get('login_method');
	$ret->status_ok = true;
	$ret->msg = 'ok';
	if ('LDAP' == $login_method) 
	{
		$msg[ERROR_LDAP_AUTH_FAILED] = lang_get('error_ldap_auth_failed');
		$msg[ERROR_LDAP_SERVER_CONNECT_FAILED] = lang_get('error_ldap_server_connect_failed');
		$msg[ERROR_LDAP_UPDATE_FAILED] = lang_get('error_ldap_update_failed');
		$msg[ERROR_LDAP_USER_NOT_FOUND] = lang_get('error_ldap_user_not_found');
		$msg[ERROR_LDAP_BIND_FAILED] = lang_get('error_ldap_bind_failed');
		
		$xx = ldap_authenticate($user->login, $cleartext_password);
		$ret->status_ok = $xx->status_ok;
		$ret->msg = $msg[$xx->status_code];	
	}
	else if ($user->comparePassword($cleartext_password) != OK)
		$ret->status_ok = false;      
	
	return $ret;
}
?>
