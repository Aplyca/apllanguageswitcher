<?php
//
// Created on: <13-Aug-2010 18:12:39 msanchez>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Publish
// SOFTWARE RELEASE: 4.3.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2010 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

$module = $Params['Module'];
$http = eZHTTPTool::instance();

$geoip_ini = eZINI::instance( 'geoip.ini' );
$ini = eZINI::instance();

$country_code = AplGeoLocation::getCountry(  AplISO3166::getRealIpAddr() );
eZDebug::writeDebug( AplISO3166::getRealIpAddr(), 'REMOTE IP ADDRESS' );
$site_access = AplGeoLocation::getCountrySiteAccess( $country_code,  $ini->variable( 'RegionalSettings', 'TranslationSA' ) );



if (!$site_access)
{
    $site_access = $ini -> variable('SiteSettings', 'DefaultAccess');
}

$request_uri = eZSys::serverVariable( 'REQUEST_URI' );
$module_url = '/' . $Params['ModuleName'] . '/' . $Params['FunctionName'];
$from_url = str_replace($module_url, '', $request_uri);
 
$handlerOptions = new ezpExtensionOptions();
$handlerOptions->iniFile = 'site.ini';
$handlerOptions->iniSection = 'RegionalSettings';
$handlerOptions->iniVariable = 'LanguageSwitcherClass';
$handlerOptions->handlerParams = array( array('Parameters' => array($site_access, $from_url), 'UserParameters' => array()) );
			
$langSwitch = eZExtension::getHandlerClass( $handlerOptions );
$langSwitch->setDestinationSiteAccess( $site_access );
$langSwitch->process();

$destinationUrl = $langSwitch->destinationUrl(); 

if ($geoip_ini -> variable('Settings', 'AutomaticRedirect') == "enabled")
{
    $module->redirectTo( $destinationUrl, false );
}
else
{
    include_once( 'kernel/common/template.php' );
    $tpl = templateInit();

    //$tpl->setVariable( 'error_list', $error_list );

    $Result = array();
    $Result['content'] = $tpl->fetch( "design:switchsite/geoip.tpl" );
    $Result['path'] = array( array( 'url' => 'switchsite/geoip'),
                         array( 'url' => false) );
}

?>
