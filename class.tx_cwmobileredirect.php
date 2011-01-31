<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2011 Carsten Windler (info@windler-consulting.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
* Simple user_func that uses tx_mobileredirect to determine if the 
* page should be displayed in mobile mode, regardless of the used device
* (i.e. the Cookie and/or the GET parameters are taken into account)
*
* Examples:
*
* [userFunc = user_isMobileForced]
*    page.config.headerComment (
       Thanks to cwmobileredirect we know that you want this page to be displayed in mobile mode!
*    )
* [end]
*
* @return   boolean true mobile mode is forced by GET parameter or by Cookie
*
*/
function user_isMobileForced()                                        
{                                                                   
    return tx_cwmobileredirect::getInstance()->isMobileForced();        
}                                                                   
    
    
    
/**
* Simple user_func that uses tx_mobileredirect to determine if the 
* page should be displayed in standard mode, regardless of the used device
* (i.e. the Cookie and/or the GET parameters are taken into account)
*
* Examples:
*
* [userFunc = user_isStandardForced]
*    page.config.headerComment (
       Thanks to cwmobileredirect we know that you want this page to be displayed in mobile mode!
*    )
* [end]
*
* @return   boolean true mobile mode is forced by GET parameter or by Cookie
*
*/                                                                
function user_isStandardForced()                            
{                                                                   
    return tx_cwmobileredirect::getInstance()->isStandardForced(); 
}  



/**
* Simple user_func that uses tx_mobileredirect to determine if mobile
* browser is used or not, or to detect if a special browser is used
*
* Examples:
*
* [userFunc = user_isMobile]
*    page.config.headerComment (
       Thanks to cwmobileredirect we know that you called this page using a mobile!
*    )
* [end]
*
* [userFunc = user_isMobile(Safari)]
*    page.config.headerComment (
*      Thanks to cwmobileredirect we know that you called this page using a Safari mobile!
*    )
* [end]
*
* Pls see the constants MOBILEREDIRECT_USERAGENT_* below to find out which Ids are recognized!
*
* @param    string  $browserId  (Optional) if set, it is checked if the detected browser equals the given Id
*
* @return   boolean true if current browser is detected as a mobile, false otherwise
*
*/
function user_isMobile($browserId = null)
{
    if(!empty($browserId))
        return (tx_cwmobileredirect::getInstance()->getDetectedMobileBrowser() == $browserId);
    else
        return tx_cwmobileredirect::getInstance()->isMobile();
}



/**
 * Detect mobile device and redirect
 *
 * This class is the main part of the 'cwmobileredirect' extension.
 *
 * @author  Carsten Windler (info@windler-consulting.de)
 *
 */
class tx_cwmobileredirect
{
    /**
     * User agent constants
     */
    const MOBILEREDIRECT_USERAGENT_SAFARI       = 'Safari';
    const MOBILEREDIRECT_USERAGENT_OPERA        = 'Opera';
    const MOBILEREDIRECT_USERAGENT_OPERA_MINI   = 'Opera Mini';
    const MOBILEREDIRECT_USERAGENT_MSIE         = 'MSIE';
    const MOBILEREDIRECT_USERAGENT_BLACKBERRY   = 'Blackberry';
    const MOBILEREDIRECT_USERAGENT_BOLT         = 'BOLT';
    const MOBILEREDIRECT_USERAGENT_NETFRONT     = 'NetFront';
    
    /**
    * Cookie values
    */
    const MOBILEREDIRECT_COOKIE_STANDARD        = 'standard';
    const MOBILEREDIRECT_COOKIE_MOBILE          = 'mobile';

    /**
     * The extension key
     * @var string
     */
    protected $extKey                       = 'cwmobileredirect';

    /**
    * Instance
    * @var tx_mobileredirect
    */
    protected static $_instance             = null;

    /**
    * The onfiguration array
    * @var array
    */
    protected $_conf                        = null;
    
    /**
    * The requests URL
    * @var string
    */
    protected $selfUrl                      = null;
    
    /**
    * Protocol (http/https)
    * @var string
    */
    protected $protocol                     = '';
    
    /**
    * HTTP status to use for the redirect
    * @var string
    */
    protected $httpStatus                   = '';

    /**
     * Whether mobile is used or not
     * @var boolean
     */
    protected $isMobileStatus               = null;

    /**
     * Stores the detected browser (if detection is active) or false
     * @var string|boolean
     */
    protected $detectedMobileBrowser        = null;

    /**
     * An array with the user agent (key) and names (value) of all supported browsers
     * (known by this extension ;-)
     * @var array
     */
    protected $knownMobileBrowsersArr       = array(
                                                self::MOBILEREDIRECT_USERAGENT_SAFARI        => 'Safari Mobile',
                                                self::MOBILEREDIRECT_USERAGENT_OPERA         => 'Opera Mobile',
                                                self::MOBILEREDIRECT_USERAGENT_OPERA_MINI    => 'Opera Mini',
                                                self::MOBILEREDIRECT_USERAGENT_MSIE          => 'Internet Explorer Mobile',
                                                self::MOBILEREDIRECT_USERAGENT_BLACKBERRY    => 'Blackberry',
                                                self::MOBILEREDIRECT_USERAGENT_BOLT          => 'BOLT',
                                                self::MOBILEREDIRECT_USERAGENT_NETFRONT      => 'NetFront'
                                                 );



    /**
    * Returns instance of this model
    *
    * @return tx_mobileredirect
    *
    */
    public static function getInstance()
    {
        if (!isset(self::$_instance))
        {
            $c = __CLASS__;
            self::$_instance = new $c;
        }

        return self::$_instance;
    }



    /**
     * Constructor
     *
     * @return void
     *
     */
    public function __construct()
    {
        global $TYPO3_CONF_VARS;

        self::$_instance    = $this;
        
        $this->selfUrl      = $this->getSelfUrl();     
        $this->_conf        = unserialize($TYPO3_CONF_VARS['EXT']['extConf'][$this->extKey]);

        if(strpos($this->selfUrl, "/") !== FALSE)
            $this->_conf['standard_url'] .= "/";
    }

   
    
    /**
    * First entry point - is always called by preprocessRequest hook to check usage of Typo Script
    * 
    * @return   void
    * 
    */
    public function firstEntryPoint()
    {
        global $TYPO3_CONF_VARS;
        
        // Check if TypoScript usage is inactive
        if(empty($this->_conf['use_typoscript']))
        {
            // Remove hook to second entry point because we don't want to parse TS
            unset($TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc']['tx_mobileredirect']);
            $this->checkRedirect();      
        }
        
        // if active, do nothing here - the action continues in secondEntryPoint then!
    }

    
    
    /**
    * Second entry point - is called by configArrayPostProc hook if TypoScript usage is active
    * 
    * We merge the TypoScript setup with the configuration array here
    * 
    * @param    array   $params     the params from the configArrayPostProc hook
    * @param    object  $ref        a reference to the parent object 
    * 
    * @return   void
    * 
    */
    public function secondEntryPoint(&$params, &$ref)
    {
        // Merge TS configuration with other configuration, if available
        if(isset($params['config']['tx_cwmobileredirect.']))
            $this->_conf = array_merge($this->_conf, $params['config']['tx_cwmobileredirect.']);    

        $this->checkRedirect();
    }    
    
    

    /**
    * Check if redirect conditions apply
    *
    * @return   void
    *
    */
    public function checkRedirect()
    {
        $this->setHttpStatus();        

        // check if mobile version is forced
        if($this->isMobileForced())
        {
            $this->setExtensionCookie(self::MOBILEREDIRECT_COOKIE_MOBILE);                    
            
            // Check if we need to redirect to the mobile page
            if(!$this->isMobileUrlRequested())
                $this->redirectToMobileUrl();
            
            return;
        }
        
        // check if standard version is forced
        if($this->isStandardForced())
        {
            $this->setExtensionCookie(self::MOBILEREDIRECT_COOKIE_STANDARD);

            // Check if we need to redirect to the standard page
            if(!$this->isStandardUrlRequested())
                $this->redirectToStandardUrl();
            
            return;
        }

        // end here if mobile detection disabled or mobile URL is already used
        if(!$this->_conf['detection_enabled'] || $this->isMobileUrlRequested())
            return;

        // here the real detection begins
        if($this->detectMobile() && $this->_conf['redirection_enabled'])
            $this->redirectToMobileUrl(false);
    }
    
    
    
    /**
    * Redirect to mobile URL                           
    * 
    * @param    boolean     $addParam   If true, is_mobile_name will be added to mobile_url
    * 
    * @return   void
    * 
    */
    public function redirectToMobileUrl($addParam = true)
    {
        if($addParam)
            $this->redirectTo($this->_conf['mobile_url'], $this->_conf['is_mobile_name']);
        else
            $this->redirectTo($this->_conf['mobile_url']);
    }

    
    
    /**
    * Redirect to standard URL
    * 
    * @param    boolean     $addParam   If true, no_mobile_name will be added to standard_url
    * 
    * @return   void
    * 
    */
    public function redirectToStandardUrl($addParam = true)
    {
        if($addParam)    
            $this->redirectTo($this->_conf['standard_url'], $this->_conf['no_mobile_name']); 
        else                           
            $this->redirectTo($this->_conf['standard_url']);                            
    }
    
    
        
    /**
    * Sets the header location to redirect to given URL and exits directly afterwards
    * 
    * @param    string     $addParam    If set, this param will be added (e.g. www.url.com?paramName)
    *                                   Considers add_value_to_params settings
    * 
    * @return   void
    * 
    */ 
    protected function redirectTo($url, $addParam = false)
    {
        // add =1 to param if needed to solve problems with RealUrl and pageHandling
        $urlParam = ($addParam && !empty($this->_conf['add_value_to_params'])) ? '?' . $addParam . '=1' : '';  
           
        t3lib_utility_Http::redirect($this->protocol . $url . $urlParam, $this->_conf['httpStatus']);
    }
    
    
    
    /**
    * Set the HTTP status used for redirects
    * 
    * @return   void
    * 
    */
    protected function setHttpStatus()
    {
        // set default HTTP Status code, if not defined
        if ('' == $this->_conf['httpStatus'] || NULL === @constant('t3lib_utility_Http::'. $this->_conf['httpStatus'])) {
            $this->_conf['httpStatus'] = t3lib_utility_Http::HTTP_STATUS_303;
        } else {
            $this->_conf['httpStatus'] = constant('t3lib_utility_Http::'. $this->_conf['httpStatus']);
        }
    }
    
    
    
    /**
    * Set the extension cookie
    * 
    * @param    $cookieValue        The cookie value to be set
    * 
    * @return   boolean
    * 
    */
    protected function setExtensionCookie($cookieValue)
    {
        if($this->_conf['use_cookie'])
            return setcookie($this->_conf['cookie_name'], $cookieValue, time()+$this->_conf['cookie_lifetime'], "/");
        else
            return false;   
    }
    
    
    
    /**
    * Determine if the requested URL is the mobile one
    * 
    * @return   boolean
    * 
    */
    public function isMobileUrlRequested()
    {
        //return (strpos($this->selfUrl, $this->_conf['mobile_url']) === FALSE);   
        return (strpos($this->selfUrl, $this->_conf['mobile_url']) !== FALSE);   
    }
    
    
    
    /**
    * Determine if the requested URL is the standard one
    * 
    * @return   boolean
    * 
    */
    public function isStandardUrlRequested()
    {
        //return (strpos($this->selfUrl, $this->_conf['standard_url']) === FALSE);   
        return (strpos($this->selfUrl, $this->_conf['standard_url']) !== FALSE);   
    }
    
    
    
    /**
    * Determine if the standard mode is forced
    * (checks Cookie and GET params)
    * 
    * @return   boolean     true if standard mode is forced, false otherwise
    * 
    */
    public function isStandardForced()
    {                                                                                                                          
        return ((isset($_COOKIE[$this->_conf['cookie_name']]) && $_COOKIE[$this->_conf['cookie_name']] == self::MOBILEREDIRECT_COOKIE_STANDARD && !isset($_GET[$this->_conf['is_mobile_name']])) || 
                (!empty($this->_conf['no_mobile_name']) && isset($_GET[$this->_conf['no_mobile_name']])))
                ? true
                : false;    
    }
    
    
    
    /**
    * Determine if the mobile mode is forced
    * (checks Cookie and GET params)
    * 
    * @return   boolean     true if mobile mode is forced, false otherwise
    * 
    */
    public function isMobileForced()
    {
        return ((isset($_COOKIE[$this->_conf['cookie_name']]) && $_COOKIE[$this->_conf['cookie_name']] == self::MOBILEREDIRECT_COOKIE_MOBILE && !isset($_GET[$this->_conf['no_mobile_name']])) || 
                (!empty($this->_conf['is_mobile_name']) && isset($_GET[$this->_conf['is_mobile_name']])))
                ? true
                : false;   
    }



    /**
    * Retrieve the requested URI
    *
    * @param    boolean     $prependProtocol    If true, the used protocol is added
    *
    * @return   string
    *
    */
    private function getSelfUrl($prependProtocol = false)
    {
        if(!isset($_SERVER['REQUEST_URI']))
            $serverrequri = $_SERVER['PHP_SELF'];
        else
            $serverrequri = $_SERVER['REQUEST_URI'];

        // store used protocol for later use
        $s              = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $this->protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s . "://";
        
        if($prependProtocol)
            return $this->protocol . $_SERVER['SERVER_NAME'] . $serverrequri;
        else
            return $_SERVER['SERVER_NAME'] . $serverrequri;
    }



    /**
    * Parse useragent to detect mobile
    *
    * @param    string      $useragent      (optional) string to parse, if not given HTTP_USER_AGENT will be used
    *
    * @return   boolean     true if mobile detect, false otherwise
    *
    */
    protected function detectMobile($useragent = NULL)
    {
        // Regular expressions for mobile detection by
        // http://detectmobilebrowser.com/
        // Thanks a lot!

        if(!$useragent)
            $useragent = t3lib_div::getIndpEnv('HTTP_USER_AGENT');

        // Run detection - if true, store status
        $rx1 = '/'. $this->_conf['regexp1'] .'/i';
        $rx2 = '/'. $this->_conf['regexp2'] .'/i';

        if((preg_match($rx1, $useragent) || preg_match($rx2, substr($useragent, 0, 4)))) 
        {
            $this->setIsMobile(true);

            return true;
        }
        else
        {
            $this->setIsMobile(false);

            return false;
        }
    }



    /**
    * Try to detect the used browser
    *
    * The result is also stored in $this->detectedBrowser
    *
    * @param    string              $useragent      (optional) string to parse, if not given HTTP_USER_AGENT will be used
    *
    * @return   string|boolean      The browser name or false
    *
    */
    protected function detectMobileBrowser($useragent = NULL)
    {
        if(!$useragent)
            $useragent = t3lib_div::getIndpEnv('HTTP_USER_AGENT');

        // go through the array of known mobile browsers and check
        // if the current useragent is recognized
        foreach($this->knownMobileBrowsersArr as $browserId => $browserName)
        {
            if(stripos($useragent, $browserId) !== FALSE)
            {
                $this->setDetectedMobileBrowser($browserId);

                return $browserId;
            }
        }

        return false;
    }



    /**
     * Returns the detected browser name
     *
     * @return  string|boolean    The detected browser name or false
     *
     */
    public function getDetectedMobileBrowserName()
    {
        // Check if there is a name available for the detected user agent
        if(isset($this->knownMobileBrowsersArr[$this->getDetectedMobileBrowser()]))
            return $this->knownMobileBrowsersArr[$this->getDetectedMobileBrowser()];

        return false;
    }



    /**
     * Returns the detected browser (mainly just a part of the user agent)
     *
     * @see     Constants of this class
     *
     * @return  string|boolean    The detected browser or false
     *
     */
    public function getDetectedMobileBrowser()
    {
        // Run detection once, if not done already
        if($this->detectedMobileBrowser === NULL)
            $this->detectMobileBrowser();

        return $this->detectedMobileBrowser;
    }



    /**
    * Setter for $detectedMobileBrowser
    *
    * @param    string  $detectedMobileBrowser      Detected mobile browswer
    *
    * @return   void
    *
    */
    protected function setDetectedMobileBrowser($detectedMobileBrowser)
    {
        $this->detectedMobileBrowser = $detectedMobileBrowser;
    }



    /**
    * Setter for $isMobileStatus
    *
    * @param    boolean     $isMobile
    *
    * @return   void
    *
    */
    protected function setIsMobile($isMobile)
    {
        $this->isMobileStatus = $isMobile;
    }



    /**
    * Getter for $isMobile
    *
    * Calls $this->detectMobile(), if not done already
    *
    * @return   boolean     true if current browser was detected as mobile, otherwise false
    *
    */
    public function isMobile()
    {
        if($this->isMobileStatus === null)
            $this->detectMobile();

        return $this->isMobileStatus;
    }
}