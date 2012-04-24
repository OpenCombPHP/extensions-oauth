<?php

namespace org\opencomb\oauth\adapter ;

use org\jecat\framework\session\Session;
use net\daichen\oauth\OAuthCommon;

class Oauth20Adapter
{
    public $authRequestUrl;
    public $oauthCommon;
    public $arrAdapteeConfigs = array() ;
    public $appKey = array();
    
    public function __construct($aSiteConfig,$aKey) {
        
        if(empty($aSiteConfig) || empty($aKey))
        {
            throw new Exception("尚不能绑定此网站");
        }else{
            $this->arrAdapteeConfigs = $aSiteConfig;
            $this->appKey = $aKey;
        }
        
        $this->oauthCommon = new OAuthCommon($aKey["appkey"],  $aKey["appsecret"],  $this->arrAdapteeConfigs['auth']['authorize'],  $this->arrAdapteeConfigs['auth']['authorize'],  $this->arrAdapteeConfigs['auth']['tokenUrl']['access_token_uri']);
    }
    
    public function fetchRequestTokenUrl($call_back_uri){
        Session::singleton()->addVariable($this->arrAdapteeConfigs['url'].'.Callback_Uri',$call_back_uri) ;
        return $this->authRequestUrl = $this->oauthCommon->GetAuthorizationCode($call_back_uri, $this->arrAdapteeConfigs['auth']['tokenUrl']['scope']);
    }
    
    public function fetchAccessToken($code){
        
        $call_back_uri = Session::singleton()->variable($this->arrAdapteeConfigs['url'].'.Callback_Uri') ;
        
        $rs = $this->oauthCommon->Get2AccessToken($code, $call_back_uri);
        $rs = json_decode($rs,true);
        
        
        // 统一参数
        $sIdKey = $this->arrAdapteeConfigs['auth']['accessRspn']['keyId'] ;
        $aIdKey = explode(".", $sIdKey);
        if(count($aIdKey) == 2)
        {
            $rs['id'] = $rs[$aIdKey[0]][$aIdKey[1]] ;
        }else{
            $rs['id'] = $rs[$sIdKey] ;
        }
        
        $rs['oauth_token'] = $rs["access_token"] ;
        $rs['oauth_token_secret'] = $rs["refresh_token"] ;
        
        if( !empty($rs['error']) )
        {
            $rs['error_code'] = $rs['error'];
            $rs['error_CN'] = $rs['error_description'];
        }
        return $rs;
    }
    
}
