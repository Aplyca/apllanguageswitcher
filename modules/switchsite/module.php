<?php 

$Module = array('name' => 'GeoLocation', 'variable_params' => true);

$ViewList = array();

$ViewList["geoip"] = array(
    "script" => "geoip.php",
	'ui_component' => 'content',
    'default_navigation_part' => 'ezcontentnavigationpart',
	'functions' => array('region'),
	'single_post_actions' => array( 'UploadCSVButton' => 'UploadCSV',
                                    'UploadCancelButton' => 'UploadCancel' ),
    'params' => array() );

$SiteAccess = array('name'=> 'SiteAccess',
					'values'=> array(),
					'path' => 'classes/',
					'file' => 'ezsiteaccess.php',
					'class' => 'eZSiteAccess',
					'function' => 'siteAccessList',
					'parameter' => array());

$FunctionList['import'] = array( 'SiteAccess' => $SiteAccess );

?>