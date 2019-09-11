<?php
/**
 * File containing the ezpLanguageSwitcher class
 *
 * @copyright Copyright (C) 1999-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 *
 */

/**
* Utility class for transforming URLs between siteaccesses.
*
* This class will generate URLs for various siteaccess, and translate
* URL-aliases into other languages as necessary.
*/
class AplLanguageSwitcher implements ezpLanguageSwitcherCapable
{
    protected $origUrl;
    protected $userParamString;
    protected $destinationSiteAccess;
    protected $destinationLocale;
    protected $baseDestinationUrl;
    protected $destinationSiteAccessIni;

    function __construct( $params = null )
    {
        if ( $params === null )
        {
            return $this;
        }

        $this->origUrl = $params['Parameters'][1];

        $this->userParamString = '';
        $userParams = $params['UserParameters'];
        foreach ( $userParams as $key => $value )
        {
            $this->userParamString .= "/($key)/$value";
        }
    }

    /**
     * Get instance siteaccess specific site.ini
     *
     * @param string $sa
     * @return void
     */
    protected function getSiteAccessIni()
    {
        if ( $this->destinationSiteAccessIni === null )
        {
            $this->destinationSiteAccessIni = eZSiteAccess::getIni( $this->destinationSiteAccess, 'site.ini' );
        }
        return $this->destinationSiteAccessIni;
    }

    /**
    * Get instance siteaccess specific site.ini
    *
    * @param string $sa
    * @return void
    */
    protected function isCurrentSiteAccess($siteAccessName)
    {
        if ( $GLOBALS['eZCurrentAccess']['name'] == $siteAccessName)
        {
           return true;
        }
        return false;
    }

	/**
	 * Checks if the given $url points to a module.
	 *
	 * We use this method to check whether we should pass on the original URL
	 * to the destination translation siteaccess.
	 *
	 * @param string $url
	 * @return bool
	 */
    protected function isUrlPointingToModule( $url )
    {
        // Grab the first URL element, representing the possible module name
        $urlElements = explode( '/', $url );
        $moduleName = $urlElements[0];

        // Look up for a match in the module list
        $moduleIni = eZINI::instance( 'module.ini' );
        $availableModules = $moduleIni->variable( 'ModuleSettings', 'ModuleList' );

        return in_array( $moduleName, $availableModules, true );
    }

    /**
     * Checks if the current content object locale is available in destination siteaccess.
     *
     * This is used to check whether we should pass on the original URL to the
     * destination translation siteaccess, when no translation of an object
     * exists in the destination locale.
     *
     * If the current content object locale exists as a fallback in the
     * destination siteaccess, the original URL should be available there as
     * well.
     *
     * @return bool
     */
    protected function isLocaleAvailableAsFallback()
    {
        $currentContentObjectLocale = eZINI::instance()->variable( 'RegionalSettings', 'ContentObjectLocale' );
        $saIni = $this->getSiteAccessIni();
        $siteLanguageList = $saIni->variable( 'RegionalSettings', 'SiteLanguageList' );
        return in_array( $currentContentObjectLocale, $siteLanguageList, true );
    }

    /**
     * Returns URL alias for the specified <var>$locale</var>
     *
     * @param string $url
     * @param string $locale
     * @return void
     */
    public function destinationUrl()
    {
        $nodeId = $this->origUrl;
        if ( !is_numeric( $this->origUrl ) )
        {
            $nodeId = eZURLAliasML::fetchNodeIDByPath( $this->origUrl );
        }

        $destinationElement = eZURLAliasML::fetchByAction( 'eznode', $nodeId, $this->destinationLocale, false );
        $saIni = $this->getSiteAccessIni();

        if ( empty( $destinationElement ) || ( !isset( $destinationElement[0] ) && !( $destinationElement[0] instanceof eZURLAliasML ) ) )
        {
            // If the return of fetchByAction is empty, it can mean a couple
            // of different things:
            // Either we are looking at a module, and we should pass the
            // original URL on
            //
            // Or we are looking at URL which does not exist in the
            // destination siteaccess, for instance an untranslated object. In
            // which case we will point to the root of the site, unless it is
            // available as a fallback.
            if ( $this->isUrlPointingToModule( $this->origUrl ))
            {
                // We have a module, we're keeping the orignal url.
                $urlAlias = $this->origUrl;
            }
            elseif ((eZContentObjectTreeNode::fetch($nodeId) instanceof eZContentObjectTreeNode) and eZContentObjectTreeNode::fetch($nodeId) -> object() -> isAlwaysAvailable () )
            {
                $urlAlias = $this->origUrl;
            }
            else
            {
                // We probably have an untranslated object, which is not
                // available with SiteLanguageList setting, we direct to root.

                $urlAlias = '';
            }
        }
        else
        {
            // Translated object found, forwarding to new URL.
            $siteLanguageList = $saIni->variable( 'RegionalSettings', 'SiteLanguageList' );

            $urlAlias = $destinationElement[0]->getPath( $this->destinationLocale, $siteLanguageList );
            $urlAlias .= $this->userParamString;
        }

        $saPathPrefix = $saIni->variable( 'SiteAccessSettings', 'PathPrefix' );
        $trailingSlash = "/";
        if ( $saPathPrefix == $urlAlias)
        {
            $trailingSlash = "";
        }
        $urlAlias = preg_replace('/^' . preg_quote($saPathPrefix . $trailingSlash, '/') . '/', '', $urlAlias);


        $this->baseDestinationUrl = rtrim( $this->baseDestinationUrl, '/' );

        if ( $GLOBALS['eZCurrentAccess']['type'] === eZSiteAccess::TYPE_URI )
        {
            $siteIni=eZINI::instance();
            $URIMatchMapItems=$siteIni->variable( 'SiteAccessSettings', 'URIMatchMapItems' );
            $uriPrefix=false;
            foreach ($URIMatchMapItems as $URIMatchMapItem)
            {
                $uriArray=explode(';', $URIMatchMapItem);
                if ($uriArray[1] ==  $this->destinationSiteAccess)
                {
                    $uriPrefix=$uriArray[0];
                }
            }

            if ($uriPrefix)
            {
                $uriPrefix = '/' . $uriPrefix;
            }

            if ($urlAlias)
            {
                $urlAlias = '/' . $urlAlias;
            }

            $finalUrl = $uriPrefix . $urlAlias;
        }
        else
        {
            $finalUrl = '/' . $urlAlias;
        }

        return $this->baseDestinationUrl  . $finalUrl;
    }

    /**
     * Sets the siteaccess name, $saName, we want to redirect to.
     *
     * @param string $saName
     * @return void
     */
    public function setDestinationSiteAccess( $saName )
    {
        $this->destinationSiteAccess = $saName;
    }

    /**
     * This is a hook which is called by the language switcher module on
     * implementation classes.
     *
     * In this implementation it is doing initialisation as an example.
     *
     * @return void
     */
    public function process()
    {
        $saIni = $this->getSiteAccessIni();
        $this->destinationLocale = $saIni->variable( 'RegionalSettings', 'ContentObjectLocale' );

        // Detect the type of siteaccess we are dealing with. Initially URI and Host are supported.
        // We don't want the siteaccess part here, since we are inserting our siteaccess name.
        $indexFile = trim( eZSys::indexFile( false ), '/' );
        switch ( $GLOBALS['eZCurrentAccess']['type'] )
        {
            case eZSiteAccess::TYPE_URI:
                eZURI::transformURI( $host, true, 'full' );
                break;

            default:
                $host = $saIni->variable( 'SiteSettings', 'SiteURL' );
                $host = eZSys::serverProtocol()."://".$host;
                break;
        }
        $this->baseDestinationUrl = "{$host}{$indexFile}";
    }

    /**
     * Creates an array of corresponding language switcher links and logical names.
     *
     * This mapping is set up in site.ini.[RegionalSettings].TranslationSA.
     * The purpose of this method is to assist creation of language switcher
     * links into the available translation siteaccesses on the system.
     *
     * This is used by the language_switcher template operator.
     *
     * @param string $url
     * @return void
     */
    public static function setupTranslationSAList( $url = null )
    {
        $ini = eZINI::instance();
        if ( !$ini->hasVariable( 'RegionalSettings', 'TranslationSA' ) )
        {
            return array();
        }

        $ret = array();
        $translationSiteAccesses = $ini->variable( 'RegionalSettings', 'TranslationSA' );

        foreach ( $translationSiteAccesses as $siteAccessName => $translationName )
        {
            $handlerOptions = new ezpExtensionOptions();
			$handlerOptions->iniFile = 'site.ini';
			$handlerOptions->iniSection = 'RegionalSettings';
			$handlerOptions->iniVariable = 'LanguageSwitcherClass';
			$handlerOptions->handlerParams = array( array('Parameters' => array($siteAccessName, $url), 'UserParameters' => array()) );
			$langSwitch = eZExtension::getHandlerClass( $handlerOptions );
			$langSwitch->setDestinationSiteAccess( $siteAccessName );
			$langSwitch->process();

			$destinationUrl = $langSwitch->destinationUrl();

			$labels = explode(';', $translationName);
            $ret[] = array( 'url' => $destinationUrl,
                            'siteaccess' => $siteAccessName,
                            'text' => array('desktop' => $labels[0], "mobile" => $labels[1]),
					        'currentsiteaccess' => $langSwitch->isCurrentSiteAccess($siteAccessName)
                         );
        }

        return $ret;
    }
}