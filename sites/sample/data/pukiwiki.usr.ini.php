<?php

// adminpass
$adminpass = '{x-php-md5}1a1dc91c907325c69271ddf0c944bc72';	// 'pass'

// use monobook (old style)
define('SKIN_DIR', WWW_HOME . 'skin/');
define('SKIN_FILE_DEFAULT', SKIN_DIR . 'monobook/monobook.skin.php');

// override from pukiwiki.php.ini
$page_title = 'Sample Wiki Site';
//$modifier = '';
//$modifierlink = '';

// enable AutoBaseAlias
$autobasealias = 1;

// enable paraedit
$fixed_heading_anchor = 1;
$fixed_heading_edited = 1;

// disable trackback
$trackback = 0;

// disable referer
$referer = 0;

// always create backup
//$cycle = 0;

// acl
defined('PKWK_USE_REDIRECT') or define('PKWK_USE_REDIRECT', 1);

// auto_template
$auto_template_func = 1;
$auto_template_rules = array(
        '(.+)\/.+'              => '\1/template',
        '([^\/]+?)\/[^\/]+'     => 'templates/nonSucceeded/\1',
        '([^\/]+?)\/.+'         => 'templates/succeeded/\1',
        '.+'                    => 'templates/all'                                                                                                                       
);

?>
