<?php
namespace org\opencomb\oauth\adapter ;

use org\jecat\framework\session\Session;

use net\daichen\oauth\Http;

use net\daichen\oauth\OAuthCommon;

class Oauth10Adapter
{
    public $oauthCommon;
    public $arrAdapteeConfigs = array() ;
    
    public function __construct($aSiteConfig,$aKey) {
        
        if(empty($aSiteConfig) || empty($aKey))
        {
            throw new Exception("尚不能绑定此网站");
        }else{
            $this->arrAdapteeConfigs = $aSiteConfig;
        }
        
        $this->oauthCommon = new OAuthCommon($aKey["appkey"],  $aKey["appsecret"],  $this->arrAdapteeConfigs['auth']['tokenUrl']['request'],  $this->arrAdapteeConfigs['auth']['authorize'],  $this->arrAdapteeConfigs['auth']['tokenUrl']['access']);
    }
    
    public function fetchRequestTokenUrl($call_back_uri){
        $para = array();
        
        //腾讯登网站的call_back是第一次申请RequestToken的有效
        $responseData = $this->oauthCommon->RequestToken($call_back_uri, $para);
        
        $http =new Http();
        $array = $http->GetQueryParameters($responseData);
        
        Session::singleton()->addVariable($this->arrAdapteeConfigs['url'].'.RequestToken',$array) ;
        
        //搜狐，网易等网站的call_back是用RequestToken组装RequestTokenUrl时候然有效
        return $this->oauthCommon->AuthorizationURL($array["oauth_token"],$call_back_uri);
    }
    
    public function fetchAccessToken($verifier){
        
         $arrRequestToken = Session::singleton()->variable($this->arrAdapteeConfigs['url'].'.RequestToken') ;
         
         $responseData = $this->oauthCommon->GetAccessToken($verifier, $arrRequestToken["oauth_token"] , $arrRequestToken["oauth_token_secret"]);
         $http =new Http();
         
         $array = $http->GetQueryParameters($responseData);
         
         // 统一参数

         /**
          * 认证信息里不包含用户信息的情况
          */
         if(empty($array[$this->arrAdapteeConfigs['auth']['accessRspn']['keyId']]))
         {
             $userinfoUrl = $this->arrAdapteeConfigs['api']['userinfo']['uri'];
             $params = $this->arrAdapteeConfigs['api']['userinfo']['params'];
             
             $responseData = $this->oauthCommon->SignRequest($userinfoUrl, "get", $params, $array['oauth_token'],$array['oauth_token_secret']);
             
             
             $aRs = json_decode($responseData,true);
             $array['id'] = $aRs[$this->arrAdapteeConfigs['auth']['accessRspn']['keyId']];
         }else{
             $array['id'] = $array[$this->arrAdapteeConfigs['auth']['accessRspn']['keyId']] ;
         }
         
        return  $array;
    }
    
    public function Create($context){
         $uri = $this->arrAdapteeConfigs['api']['add']['url'];
         $params = $this->arrAdapteeConfigs['api']['add']['params'];
         
         if($params['format'] == "json" || empty($params['format']))
         {
             $params['content'] = $params['status'] = $context;
             $responseData = $this->oauthCommon->SignRequest($uri, "post", $params, $_SESSION[$this->arrAdapteeConfigs['url']."_access_token"], $_SESSION[$this->arrAdapteeConfigs['url']."_access_token_secret"]);
         }
         
         if($params['format'] == "xml")
         {
             $content =  str_replace("{content}",  $context ,$params['html']);
             $responseData = $this->oauthCommon->SignXMLRequest($uri, "post", $content, $_SESSION[$this->arrAdapteeConfigs['url']."_access_token"], $_SESSION[$this->arrAdapteeConfigs['url']."_access_token_secret"]);
         }
         
         return $responseData;
    }
    
    public function TimeLine($token,$token_secret ){
    
    
        $url = $this->arrAdapteeConfigs['api']['timeline']['uri'];
        $params = $this->arrAdapteeConfigs['api']['timeline']['params'];
    
    
        $params["access_token"] = $token;
    
        $responseData = $this->oauthCommon->SignRequest($url, "get", $params, $token, $token_secret);
        return $responseData;
    }
}

?>
