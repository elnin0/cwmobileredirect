<?php

########################################################################
# Extension Manager/Repository config file for ext "cwmobileredirect".
#
# Auto generated 18-04-2011 22:28
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Mobile Redirect',
	'description' => 'Your all-in-one mobile device detection and redirection solution! Detects mobile browsers and redirects to other Typo3 sites in your setup (most likely optimized for mobiles). Allows to easily switch back to the normal version, with Cookie support to remember the users choice. The browser detection can be access via TypoScript or in your own extension.',
	'category' => 'fe',
	'shy' => 0,
	'version' => '1.3.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Carsten Windler',
	'author_email' => 'info@windler-consulting.de',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.3.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:6:{s:9:"ChangeLog";s:4:"5a5b";s:29:"class.tx_cwmobileredirect.php";s:4:"b337";s:21:"ext_conf_template.txt";s:4:"dcd8";s:12:"ext_icon.gif";s:4:"4904";s:17:"ext_localconf.php";s:4:"51fd";s:14:"doc/manual.sxw";s:4:"765b";}',
	'suggests' => array(
	),
);

?>