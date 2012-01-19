<?php 
namespace net\daichen\oauth ;
class OAuthBase{
    private  static  $OAuthVersion = "1.0";
    private  static  $OAuthParameterPrefix = "oauth_";
    private  static  $OAuthConsumerKeyKey = "oauth_consumer_key";
    private  static  $OAuthCallbackKey = "oauth_callback";
    private  static  $OAuthVersionKey = "oauth_version";
    private  static  $OAuthSignatureMethodKey = "oauth_signature_method";
    private  static  $OAuthSignatureKey = "oauth_signature";
    private  static  $OAuthTimestampKey = "oauth_timestamp";
    private  static  $OAuthNonceKey = "oauth_nonce";
    private  static  $OAuthTokenKey = "oauth_token";
    private  static  $oAauthVerifier = "oauth_verifier";
    private  static  $OAuthTokenSecretKey = "oauth_token_secret";
    private  static  $HMACSHA1SignatureType = "HMAC-SHA1";


    public static function generate_timestamp() {
        return time();
    }
 
    public static function generate_nonce() {
        $mt = microtime();
        $rand = mt_rand();
        return md5($mt . $rand); // md5s look nicer than numbers
    }

    public static function NormalizeRequestParameters($parameters){
        $plist = "";
        if(empty($parameters)) {
            return $plist;
        }
        if(count($parameters)==0){
            return $plist;
        }
        $i = 0;
        foreach($parameters as $key=>$value){
                $plist .=$key."=".$value;
                if($i < count($parameters)-1){
                        $plist .= "&";
                }
                $i++;
        }
        return $plist;
    }

    public static function GetOauthUrl($url,$httpMethod,$customerKey,$customSecrect,$tokenKey,$tokenSecrect,$verify,$callbackUrl,$parameters)
    {
        $parameterString =self::NormalizeRequestParameters($parameters);
        $urlWithParameter = $url;
        if($parameterString !=""){
            $urlWithParameter.="?".$parameterString;
        }
        $nonce = self::generate_nonce();
        $timeStamp =  self::generate_timestamp();
        $parameters[self::$OAuthVersionKey] = self::$OAuthVersion;
        $parameters[self::$OAuthNonceKey] =$nonce;
        $parameters[self::$OAuthTimestampKey] = $timeStamp;
        $parameters[self::$OAuthSignatureMethodKey] = self::$HMACSHA1SignatureType;
        $parameters[self::$OAuthConsumerKeyKey] =$customerKey;

        if ($tokenKey != "")
        {
            $parameters[self::$OAuthTokenKey] =$tokenKey;
        }

        if ($verify !="")
        {
            $parameters[self::$oAauthVerifier] =$verify;
        }

        if ($callbackUrl != "")
        {
             $parameters[self::$OAuthCallbackKey] =$callbackUrl;
        }

        $normalizedUrl =  self::get_normalized_http_url($urlWithParameter);
       
        $sign = self::GenerateSignature($url, $httpMethod, $parameters, $customSecrect, $tokenSecrect);
        
        $queryString = self::FormEncodeParameters($parameters)."&oauth_signature=".  self::urlEncode_Str($sign);
        return array($normalizedUrl,$queryString);

    }

    public static function GenerateSignature($url, $httpMethod, $paramters,$consumerSecret, $tokenSecret){
         $signbase = self::GenerateSignatureBase($url, $httpMethod, $paramters);
         $key_parts = array(self::urlEncode_Str($consumerSecret), $tokenSecret==""?"":self::urlEncode_Str($tokenSecret));
         $key = implode('&', $key_parts);
         return base64_encode(hash_hmac('sha1', $signbase,$key, true));
    }
    public static function urlEncode_Str($input)
    {  
        if (is_scalar($input))
        {
            return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($input)));
        }
        else
        { 
            return '';
        }
    }
    
    public static function get_normalized_http_url($url)
    {
        $parts = parse_url($url);
        $port = @$parts['port'];
        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $path = @$parts['path'];
        $port or $port = ($scheme == 'https') ? '443' : '80'; 
        
        if (($scheme == 'https' && $port != '443') 
            || ($scheme == 'http' && $port != '80'))
        { 
                $host = "$host:$port"; 
        }
        return "$scheme://$host$path"; 
    }
    
    public static function get_normalized_http_method($http_method)
    {
        return strtoupper($http_method);
    }
    
    public static function FormEncodeParameters($parameters)
    {
        $encodeParams = array();
        foreach ($parameters as $key=>$value)
        {
            $encodeParams[$key] = self::urlEncode_Str($value);
        }
        uksort($encodeParams, 'strcmp');
        return self::NormalizeRequestParameters($encodeParams);
    }
    
    public static function GenerateSignatureBase($url,$httpMethod,$paramters)
    {
        uksort($paramters, 'strcmp');
        $parts = array( 
           self::get_normalized_http_method($httpMethod), 
           self::urlEncode_Str(self::get_normalized_http_url($url)), 
           self::urlEncode_Str(self::FormEncodeParameters($paramters)) 
        ); 
        return implode('&', $parts); 
    }
   
}
?>