<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2010 Carsten Windler (info@windler-consulting.de)
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
    return tx_mobileredirect::getInstance()->isMobileForced();        
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
    return tx_mobileredirect::getInstance()->isStandardForced(); 
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
        return (tx_mobileredirect::getInstance()->getDetectedMobileBrowser() == $browserId);
    else
        return tx_mobileredirect::getInstance()->isMobile();
}



/**
 * Detect mobile device and redirect
 *
 * This class is the main part of the 'cwmobileredirect' extension.
 *
 * @author  Carsten Windler (info@windler-consulting.de)
 *
 */
class tx_mobileredirect
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
    * Check if redirect conditions apply
    *
    * @return   void
    *
    */
    public function checkRedirect()
    {
        // used for development - unset the cookie
        if(isset($_GET['deleteCookie']))
        {
            setcookie($this->_conf['cookie_name'], false, time()-$this->_conf['cookie_lifetime'], "/");
            unset($_COOKIE[$this->_conf['cookie_name']]);
        }         

        // check if mobile version is forced
        if($this->isMobileForced())
        {
            $this->setExtensionCookie(self::MOBILEREDIRECT_COOKIE_MOBILE);                    
            
            // Check if we need to redirect to the mobile page
            if(!$this->isMobileUrlRequested())
                $this->redirectToMobileUrl();
            
            return;
        }
        
        // check if standard version is forced by Cookie
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
        {
            header("Location: http://" . $this->_conf['mobile_url']);
            exit;
        }
    }
    
    
    
    /**
    * Redirect to mobile URL                           
    * 
    * @return   void
    * 
    */
    public function redirectToMobileUrl()
    {
        $this->redirectTo($this->_conf['mobile_url']. "?" . $this->_conf['is_mobile_name']);
    }

    
    
    /**
    * Redirect to standard URL
    * 
    * @return   void
    * 
    */
    public function redirectToStandardUrl()
    {
        $this->redirectTo($this->_conf['standard_url'] . "?" . $this->_conf['no_mobile_name']);                            
    }
    
    
        
    /**
    * Sets the header location to redirect to given URL and exits directly afterwards
    * 
    * @return   void
    * 
    */ 
    protected function redirectTo($url)
    {
        // add =1 to param if needed to solve problems with RealUrl and pageHandling
        $value = (!empty($this->_conf['add_value_to_params'])) ? '=1' : '';     
        
        header("Location: http://" . $url . $value);
        exit;                        
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
        {
            $serverrequri = $_SERVER['PHP_SELF'];
        }
        else
        {
            $serverrequri = $_SERVER['REQUEST_URI'];
        }

        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";

        if($prependProtocol)
            return substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s . "://" . $_SERVER['SERVER_NAME'] . $serverrequri;
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
        // Mobile detection by
        // http://detectmobilebrowser.com/
        // Last Updated: 30 June 2010
        // Thanks a lot!

        if(!$useragent)
            $useragent = t3lib_div::getIndpEnv('HTTP_USER_AGENT');

        // Run detection - if true, store status
        if((preg_match('/android|avantgo|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))))
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