<?php
namespace org\opencomb\oauth\adapter ;

use org\jecat\framework\session\Session;

use net\daichen\oauth\Http;

use net\daichen\oauth\OAuthCommon;

if (!session_id()) session_start();

class Oauth10Adapter{
    private $oauth;
    private $arrAdapteeConfigs = array() ;
    
    public function __construct($aSiteConfig,$aKey) {
        
        if(empty($aSiteConfig) || empty($aKey))
        {
            throw new Exception("尚不能绑定此网站");
        }else{
            $this->arrAdapteeConfigs = $aSiteConfig;
        }
        
        $this->oauth = new OAuthCommon($aKey["appkey"],  $aKey["appsecret"],  $this->arrAdapteeConfigs['auth']['tokenUrl']['request'],  $this->arrAdapteeConfigs['auth']['authorize'],  $this->arrAdapteeConfigs['auth']['tokenUrl']['access']);
    }
    
    public function fetchRequestTokenUrl($call_back_uri){
        $para = array();
        $responseData = $this->oauth->RequestToken($call_back_uri, $para);
        $http =new Http();
        $array = $http->GetQueryParameters($responseData);
        
        Session::singleton()->addVariable($this->arrAdapteeConfigs['url'].'.RequestToken',$array) ;
        return $this->oauth->AuthorizationURL($array["oauth_token"]);
    }
    
    public function fetchAccessToken($verifier){
        
         $arrRequestToken = Session::singleton()->variable($this->arrAdapteeConfigs['url'].'.RequestToken') ;
         
         $responseData = $this->oauth->GetAccessToken($verifier, $arrRequestToken["oauth_token"] , $arrRequestToken["oauth_token_secret"]);
         $http =new Http();
         
         $array = $http->GetQueryParameters($responseData);
         
         // 统一参数
         $sIdKey = $this->arrAdapteeConfigs['auth']['accessRspn']['keyId'] ;
         $array['id'] = $array[$sIdKey] ;
         
         
        return  $array;
    }
    
    public function AuthUser(){
         $url = $this->arrAdapteeConfigs[$this->sSiteUrl]['app']['userinfo']['url'];
         $params = $this->arrAdapteeConfigs[$this->sSiteUrl]['app']['userinfo']['params'];
         
         $responseData = $this->oauth->SignRequest($url, "get", $params, $_SESSION[$this->arrAdapteeConfigs['url']."_access_token"], $_SESSION[$this->arrAdapteeConfigs['url']."_access_token_secret"]);
         return $responseData;
    }
    
    public function Add($context){
         $uri = $this->arrAdapteeConfigs[$this->sSiteUrl]['app']['add']['url'];
         $params = $this->arrAdapteeConfigs[$this->sSiteUrl]['app']['add']['params'];
         
         if($params['format'] == "json" || empty($params['format']))
         {
             $params['content'] = $params['status'] = $context;
             $responseData = $this->oauth->SignRequest($uri, "post", $params, $_SESSION[$this->arrAdapteeConfigs['url']."_access_token"], $_SESSION[$this->arrAdapteeConfigs['url']."_access_token_secret"]);
         }
         
         if($params['format'] == "xml")
         {
             $content =  str_replace("{content}",  $context ,$params['html']);
             $responseData = $this->oauth->SignXMLRequest($uri, "post", $content, $_SESSION[$this->arrAdapteeConfigs['url']."_access_token"], $_SESSION[$this->arrAdapteeConfigs['url']."_access_token_secret"]);
         }
         
         return $responseData;
    }
}

?>
