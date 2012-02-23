<?php
namespace net\daichen\oauth ;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class OAuthCommon extends OAuthBase{
    private $appkey;
    private $appkeysercert;
    private $request_token_uri;
    private $authorize_uri;
    private $access_token_uri;
    private static $http;
    
    public function __construct($appkey,$appkeysercert,$request_token_uri = "",$authorize_uri = "",$access_token_uri = ""){
        
        $this->appkey = $appkey;
        $this->appkeysercert = $appkeysercert;
        $this->request_token_uri = $request_token_uri;
        $this->authorize_uri = $authorize_uri;
        $this->access_token_uri = $access_token_uri;
    }
    
    static function http(){
        if(empty(self::$http))
        {
            self::$http = new Http();
        }
        return self::$http;
    }
    
    public function multi_exec(){
        return self::http()->multi_exec();
    }
    
    public function  GetAppKey()
    {
        return $this->appkey;
    }
    
    public function GetAppKeySercert()
    {
        return $this->appkeysercert;
    }
    
    public function GetRequestTokenUri()
    {
        return $this->request_token_uri;
    }
    
    public function GetAuthorizeUri(){
        return $this->authorize_uri;
    }
    
    public function GetAccessTokenUri()
    {
        return $this->access_token_uri;
    }
    
    public function RequestToken($callback_uri,$para)
    {
        $uri = $this->GetOauthUrl($this->GetRequestTokenUri(), "Get",$this->GetAppKey(),  $this->GetAppKeySercert(),"","","",$callback_uri,$para);
        $http = new Http();
        if(is_array($uri)){
            $url = $uri[0];
            if($uri[1] !=""){
                $url.="?".$uri[1];
                
                return $http->fetch_page($url,false,"Get");
            }
        }else{
            return "";
        }
    }
    
    public function AuthorizationURL($oauth_token,$call_back_uri="") 
    {
         $getUserAuthorizationURL =$this->GetAuthorizeUri(). "?oauth_token=" .$oauth_token."&oauth_callback=".urlencode($call_back_uri);
         return $getUserAuthorizationURL;
    }
    
    public function GetAccessToken($verifier,$oauth_token,$oauth_token_sercet)
    {
        $parameters = array();
        $uri = $this->GetOauthUrl($this->GetAccessTokenUri(), "Get",$this->GetAppKey() , $this->GetAppKeySercert(), $oauth_token,$oauth_token_sercet,$verifier, "", $parameters);
        $http = new Http();
        if(is_array($uri)){
            $url = $uri[0];
            if($uri[1] !=""){
                $url.="?".$uri[1];
                return $http->fetch_page($url,false,"Get");
            }
        }else{
            return "";
        }
    }
    
    public function SignRequest($uri, $HttpMode, $postData, $access_token, $access_token_sercert="" , $isMulti = ""){
        $postUri = $this->GetOauthUrl($uri, $HttpMode, $this->GetAppKey() ,$this->GetAppKeySercert(),  $access_token, $access_token_sercert,"" , "", $postData);
        
        if(is_array($postUri)){
            if(strtoupper($HttpMode) == "GET"){
                $url = $postUri[0]."?".$postUri[1];
                
                if(!empty($isMulti))
                {
                    return self::http()->createMultiParams($url,false,$HttpMode,$isMulti);
                }else{
                    
                    return self::http()->fetch_page($url,false,$HttpMode);
                }
                
            }  
            else if(strtoupper($HttpMode) == "POST") {
                $url = $postUri[0];
                
                
                if(!empty($isMulti))
                {
                    return self::http()->createMultiParams($url,$postUri[1],$HttpMode,$isMulti);
                }else{
                    return self::http()->fetch_page($url,$postUri[1],$HttpMode);
                }
                
                
            }  else {
                return "";
            }           
        }else{
            return "";
        }
    }
    
    public function SignXMLRequest($uri, $HttpMode, $postData, $access_token, $access_token_sercert){
        $para = array();
        $postUri = $this->GetOauthUrl($uri, $HttpMode, $this->GetAppKey() ,$this->GetAppKeySercert(),  $access_token, $access_token_sercert,"" , "",  $para);
        $http = new Http();
        if(is_array($postUri)){
            $para = $http->GetQueryParameters($postUri[1]);
            $header = $this->getAuthorizationHeader($para, "");
            if(strtoupper($HttpMode) == "POST") {
                $url = $postUri[0];
                $header = array('Content-Type: application/atom+xml',$header);
                return $http->fetch_header_page($url, $header, $postData);
            }  else {
                return "";
            }
        }  else {
            return "";
        }
      
    }
    
    public function getAuthorizationHeader($para,$realm){
        $header = "Authorization: OAuth realm=\"" .$realm. "\"";
        foreach ($para as $key=>$value){
             if(strpos($key,"oauth_") >- 1){
                  $header .= ",".$key."=".$value;
             } 
        }
        return $header;
    }
    
    public function Sign($para,$session_sercert=false){
        if(is_array($para)){
            uksort($para, 'strcmp');
            $sbList="";
            foreach ($para as $k=>$v){
                $sbList.=$k."=".$v;
            }
            if($session_sercert){
                $sbList.=$session_sercert;
            }else{
                $sbList.=$this->appkeysercert;
            }
            return md5($sbList);
        }else{
            return "";
        }
    }
    
    public function GetAuthorizationCode($callback_uri,$scope){
        $para=array("client_id"=>  $this->appkey,"response_type"=>"code","redirect_uri"=>$callback_uri);
        if($scope !=""){
          $para["scope"] = $scope;
        }
        
        $AuthorizationUri = $this->request_token_uri."?".$this->NormalizeRequestParameters($para);
        return $AuthorizationUri;
    }
    
    public function Get2AccessToken($AuthorizationCode,$callback_uri){
        
        $para = array("grant_type"=>"authorization_code");
        $para["code"] = $AuthorizationCode;
        $para["client_id"] = $this->appkey;
        $para["client_secret"] = $this->appkeysercert;
        $para["redirect_uri"] = $callback_uri;
        $AccessUrl = $this->access_token_uri."?".$this->NormalizeRequestParameters($para);
        
        
        $http = new Http();
        
        return $http->fetch_page($AccessUrl,"","POST");
    }
    
    public function GetSessionKey($access_token){
         $para = array("oauth_token"=>$access_token);
         $SessionUrl = $this->access_token_uri."?".$this->NormalizeRequestParameters($para);
         $http = new Http();
         return $http->fetch_page($SessionUrl,"","POST");
    }
    
    public function CallRequest($uri, $paras, $format,$token,$isMulti=""){
        $strSign="";
        if(is_array($paras)){
            $paras["access_token"] = $token;
            $paras["format"] = $format!=""?$format:"json";
            $paras["call_id"] = floor(microtime()*1000);
            $paras["v"] = "1.0";
            $strSign = $this->Sign($paras,false); 
            $paras["sig"] =$strSign;
            $call_uri = $uri."?".$this->NormalizeRequestParameters($paras);
            
            
            if(!empty($isMulti))
            {
                return self::http()->createMultiParams($call_uri, $paras, "post",$isMulti);
            }else{
                return self::http()->fetch_page($call_uri, $paras, "post");
            }
            
            
        }else{
            return $strSign;
        }
    }
}
?>
