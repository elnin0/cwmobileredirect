<?php

if (!defined ("TYPO3_MODE"))     die ("Access denied.");

$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'][] = t3lib_extMgm::extPath($_EXTKEY) . 'class.tx_mobileredirect.php:&tx_mobileredirect->checkRedirect';
