<?php

namespace org\opencomb\oauth\adapter ;

use org\jecat\framework\session\Session;

use net\daichen\oauth\OAuthCommon;

if (!session_id()) session_start();

class Oauth20Adapter{
    private $authRequestUrl;
    private $oauth;
    private $arrAdapteeConfigs = array() ;
    
    public function __construct($aSiteConfig,$aKey) {
        
        if(empty($aSiteConfig) || empty($aKey))
        {
            throw new Exception("尚不能绑定此网站");
        }else{
            $this->arrAdapteeConfigs = $aSiteConfig;
        }
        
        $this->oauth = new OAuthCommon($aKey["appkey"],  $aKey["appsecret"],  $this->arrAdapteeConfigs['auth']['authorize'],  $this->arrAdapteeConfigs['auth']['authorize'],  $this->arrAdapteeConfigs['auth']['tokenUrl']['access_token_uri']);
    }
    
    public function fetchRequestTokenUrl($call_back_uri){
        Session::singleton()->addVariable($this->arrAdapteeConfigs['url'].'.Callback_Uri',$call_back_uri) ;
        return $this->authRequestUrl = $this->oauth->GetAuthorizationCode($call_back_uri, $this->arrAdapteeConfigs['auth']['tokenUrl']['scope']);
    }
    
    public function fetchAccessToken($code){
        $call_back_uri = Session::singleton()->variable($this->arrAdapteeConfigs['url'].'.Callback_Uri') ;
        return $this->oauth->Get2AccessToken($code, $call_back_uri);
    }
    
    public function AuthUser(){
        $responseData = $this->oauth->SignRequest($this->arrAdapteeConfigs['app']['userinfo']['uri'], "post", $this->arrAdapteeConfigs['app']['userinfo']['params'], $_SESSION[$this->arrAdapteeConfigs['url']."_access_token"]);
        return $responseData;
    }
}

?>
