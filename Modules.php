<?php
error_reporting(E_ALL ^ E_NOTICE);
include('Warehouse.php');

function array_rwalk(&$array, $function)
{
	//modify loop: use for instead of foreach
	$key = array_keys($array);
	$size = sizeOf($key);
	for ($i=0; $i<$size; $i++)
		if (is_array($array[$key[$i]]))
			array_rwalk($array[$key[$i]], $function);
		else
			$array[$key[$i]] = $function($array[$key[$i]]);
	
	/*foreach($array as $key => $value)
	{
		if(is_array($value))
		{
			array_rwalk($value, $function);
			$array[$key] = $value;
		}
		else
			$array[$key] = $function($value);
	}*/
}

array_rwalk($_REQUEST,'DBEscapeString');

if(isset($_REQUEST['modname']))
{
	$modname = $_REQUEST['modname'];
	
	//modif Francois: add TinyMCE to the textarea (see modules/Students/Letters.php & modules/Grades/HonorRollSubject.php & modules/Grades/HonorRoll.php)
	if (($modname=='Students/Letters.php' && isset($_REQUEST['letter_text'])) || (($modname=='Grades/HonorRollSubject.php' || $modname=='Grades/HonorRoll.php') && isset($_REQUEST['honor_roll_text'])))
	{
		$REQUEST_letter_text = $_REQUEST['letter_text'];
		$REQUEST_honor_roll_text = $_REQUEST['honor_roll_text'];
	}
	array_rwalk($_REQUEST,'strip_tags');

	if(!isset($_REQUEST['_ROSARIO_PDF']))
	{
		$_ROSARIO['is_popup'] = $_ROSARIO['not_ajax'] = false;
		
		//modif Francois: security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
		if (in_array($modname, array('misc/ChooseRequest.php', 'misc/ChooseCourse.php', 'misc/ViewContact.php')) || ($modname == 'School_Setup/Calendar.php' && $_REQUEST['modfunc'] == 'detail') || (in_array($modname, array('Scheduling/MassDrops.php', 'Scheduling/Schedule.php', 'Scheduling/MassSchedule.php', 'Scheduling/MassRequests.php', 'Scheduling/Courses.php')) && $_REQUEST['modfunc'] == 'choose_course')) //popups
		{
			$_ROSARIO['is_popup'] = true;
		}
		elseif (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') //AJAX check //change URL after AJAX
		{
			$_ROSARIO['not_ajax'] = true;
		}
		
		if ($_ROSARIO['is_popup'] || $_ROSARIO['not_ajax'])
			Warehouse('header');
	}

	if(empty($_REQUEST['LO_save']) && !isset($_REQUEST['_ROSARIO_PDF']) && (mb_strpos($modname,'misc/')===false || $modname=='misc/Registration.php' || $modname=='misc/Export.php' || $modname=='misc/Portal.php'))
		$_SESSION['_REQUEST_vars'] = $_REQUEST;

	$allowed = false;
	
	//modif Francois: security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
	//allow PHP scripts in misc/ one by one in place of the whole folder
	//if(mb_substr($_REQUEST['modname'],0,5)=='misc/')
	if (in_array($modname, array('misc/ChooseRequest.php', 'misc/ChooseCourse.php', 'misc/Portal.php', 'misc/ViewContact.php')))
		$allowed = true;
	else
	{
		include 'Menu.php';
		foreach($_ROSARIO['Menu'] as $modcat=>$programs)
		{
			foreach($programs as $program=>$title)
			{
	//modif Francois: fix bug URL Modules.php?modname=Student_Billing/Statements.php&_ROSARIO_PDF
				if($modname==$program || (mb_strpos($program, $modname)=== 0 && mb_strpos($_SERVER['QUERY_STRING'], $program)=== 8))
				{
					$allowed = true;
					$_ROSARIO['Program_loaded'] = $program; //eg: "Student_Billing/Statements.php&_ROSARIO_PDF"
				}
			}
			if ($allowed)
				break;
		}
	}
	
	if($allowed)
	{
		if(Preferences('SEARCH')!='Y')
			$_REQUEST['search_modfunc'] = 'list';
		include('modules/'.$modname);
	}
	elseif(User('USERNAME'))
			//modif Francois: create HackingLog function to centralize code
			HackingLog();

	if(isset($_SESSION['unset_student']))
		unset($_SESSION['unset_student']);

	if(!isset($_REQUEST['_ROSARIO_PDF']))
		Warehouse('footer');
}
?>