<?php
namespace org\opencomb\oauth\adapter ;

use am\abrah\oauth\OAuthRequest;
use am\abrah\oauth\OAuthSignatureMethod_HMAC_SHA1;
use am\abrah\oauth\OAuthUtil;
use am\abrah\oauth\OAuthConsumer;
use org\jecat\framework\util\DataSrc;
use org\jecat\framework\system\HttpRequest;

class AuthorizeAdapter {
	
	public function __construct(array $arrAdapteeConfig,$consumer_key,$consumer_secret,$oauth_token = NULL, $oauth_token_secret = NULL)
	{
		$this->arrAdapteeConfig = $arrAdapteeConfig ;
		
		$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
		
		if( $oauth_token and $oauth_token_secret )
		{echo $oauth_token,$oauth_token_secret ;
			$this->setOAuthToken(new OAuthConsumer($oauth_token, $oauth_token_secret)) ;
		}
	}
	
	public function setOAuthToken(OAuthConsumer $aToken)
	{
		$this->token = $aToken ;
	}
	
	/**
	 * Get the authorize URL
	 *
	 * @return string
	 */
	function tokenFetchUrl($sOAuthToken, $sType='authenticate' , $arrCallbackParams=array())
	{
		$sCallback = HttpRequest::singleton()->urlNoQuery() ;
		$sCallback.= '?c=org.opencomb.oauth.auth.AuthorizeCallback' ;

		if($arrCallbackParams)
		{
			$sCallback.= '&'.http_build_query($arrCallbackParams) ;
		}
		
		return $this->arrAdapteeConfig[$sType]."?oauth_token={$sOAuthToken}&oauth_callback=" . urlencode($sCallback);
	}
	
	/**
	 * Get a request_token from Weibo
	 *
	 * @return array a key/value array containing oauth_token and oauth_token_secret
	 */
	function fetchRequestToken($oauth_callback = NULL) {
		$parameters = array();
		if (!empty($oauth_callback)) {
			$parameters['oauth_callback'] = $oauth_callback;
		}
	
		$request = $this->oAuthRequest($this->arrAdapteeConfig['tokenUrl']['request'],'GET',$parameters);
		$token = OAuthUtil::parse_parameters($request);
		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
		
		return $token;
	}
	
	/**
	 * Exchange the request token and secret for an access token and
	 * secret, to sign API calls.
	 *
	 * @return array array("oauth_token" => the access token,
	 *                "oauth_token_secret" => the access secret)
	 */
	function fetchAccessToken($oauth_verifier = FALSE, $oauth_token = false) {
		$parameters = array();
		if (!empty($oauth_verifier)) {
			$parameters['oauth_verifier'] = $oauth_verifier;
		}
	
	
		$request = $this->oAuthRequest($this->arrAdapteeConfig['tokenUrl']['access'], 'GET', $parameters);
		$token = OAuthUtil::parse_parameters($request);
		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}
	
	/**
	 * Format and sign an OAuth / API request
	 *
	 * @return string
	 */
	function oAuthRequest($url, $method, $parameters , $multi = false) {
	
		if (strrpos($url, 'http://') !== 0 && strrpos($url, 'http://') !== 0) {
			$url = "{$this->host}{$url}.{$this->format}";
		}
	
		// echo $url ;
		$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
		$request->sign_request($this->sha1_method, $this->consumer, $this->token);
		switch ($method) {
			case 'GET':
				//echo $request->to_url();
				return $this->http($request->to_url(), 'GET');
			default:
				return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata($multi) , $multi );
		}
	}
	
	/**
	 * Contains the last HTTP status code returned.
	 *
	 * @ignore
	 */
	private $http_code;
	/**
	 * Contains the last API call.
	 *
	 * @ignore
	 */
	private $url;
	/**
	 * Set timeout default.
	 *
	 * @ignore
	 */
	private $timeout = 30;
	/**
	 * Set connect timeout.
	 *
	 * @ignore
	 */
	private $connecttimeout = 30;
	/**
	 * Verify SSL Cert.
	 *
	 * @ignore
	 */
	private $ssl_verifypeer = FALSE;
	/**
	 * Respons format.
	 *
	 * @ignore
	 */
	private $format = 'json';
	/**
	 * Decode returned json data.
	 *
	 * @ignore
	 */
	private $decode_json = TRUE;
	/**
	 * Contains the last HTTP headers returned.
	 *
	 * @ignore
	 */
	private $http_info;
	/**
	 * Set the useragnet.
	 *
	 * @ignore
	 */
	private $useragent = 'Sae T OAuth v0.2.0-beta2';
	/* Immediately retry the API call if the response was not successful. */
	//public $retry = TRUE;
	
	private $token ;
	
	private $arrAdapteeConfig ;
	
	
	/**
	 * Set API URLS
	 */
	/**
	 * @ignore
	 */
	function accessTokenURL()  {
		return 'http://api.t.sina.com.cn/oauth/access_token';
	}
	/**
	 * @ignore
	 */
	function authenticateURL() {
		return 'http://api.t.sina.com.cn/oauth/authenticate';
	}
	/**
	 * @ignore
	 */
	function authorizeURL()    {
		return 'http://api.t.sina.com.cn/oauth/authorize';
	}
	/**
	 * @ignore
	 */
	function requestTokenURL() {
		return 'http://api.t.sina.com.cn/oauth/request_token';
	}
	
	
	/**
	 * Debug helpers
	 */
	/**
	 * @ignore
	 */
	function lastStatusCode() {
		return $this->http_status;
	}
	/**
	 * @ignore
	 */
	function lastAPICall() {
		return $this->last_api_call;
	}
	
	
	
	
	/**
	 * GET wrappwer for oAuthRequest.
	 *
	 * @return mixed
	 */
	function get($url, $parameters = array()) {
		$response = $this->oAuthRequest($url, 'GET', $parameters);
		if ($this->format === 'json' && $this->decode_json) {
			return json_decode($response, true);
		}
		return $response;
	}
	
	/**
	 * POST wreapper for oAuthRequest.
	 *
	 * @return mixed
	 */
	function post($url, $parameters = array() , $multi = false) {
	
		$response = $this->oAuthRequest($url, 'POST', $parameters , $multi );
		if ($this->format === 'json' && $this->decode_json) {
			return json_decode($response, true);
		}
		return $response;
	}
	
	/**
	 * DELTE wrapper for oAuthReqeust.
	 *
	 * @return mixed
	 */
	function delete($url, $parameters = array()) {
		$response = $this->oAuthRequest($url, 'DELETE', $parameters);
		if ($this->format === 'json' && $this->decode_json) {
			return json_decode($response, true);
		}
		return $response;
	}
	
	
	/**
	 * Make an HTTP request
	 *
	 * @return string API results
	 */
	function http($url, $method, $postfields = NULL , $multi = false) {
		$this->http_info = array();
		$ci = curl_init();
		/* Curl settings */
		curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
		curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
	
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
	
		curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
	
		curl_setopt($ci, CURLOPT_HEADER, FALSE);
	
		switch ($method) {
			case 'POST':
				curl_setopt($ci, CURLOPT_POST, TRUE);
				if (!empty($postfields)) {
					curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
					//echo "=====post data======\r\n";
					//echo $postfields;
				}
				break;
			case 'DELETE':
				curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
				if (!empty($postfields)) {
					$url = "{$url}?{$postfields}";
				}
		}
	
		$header_array = array();
	
		/*
		 $header_array["FetchUrl"] = $url;
		$header_array['TimeStamp'] = date('Y-m-d H:i:s');
		$header_array['AccessKey'] = SAE_ACCESSKEY;
	
	
		$content="FetchUrl";
	
		$content.=$header_array["FetchUrl"];
	
		$content.="TimeStamp";
	
		$content.=$header_array['TimeStamp'];
	
		$content.="AccessKey";
	
		$content.=$header_array['AccessKey'];
	
		$header_array['Signature'] = base64_encode(hash_hmac('sha256',$content, SAE_SECRETKEY ,true));
		*/
		//curl_setopt($ci, CURLOPT_URL, SAE_FETCHURL_SERVICE_ADDRESS );
	
		//print_r( $header_array );
		$header_array2=array();
		if( $multi )
			$header_array2 = array("Content-Type: multipart/form-data; boundary=" . OAuthUtil::$boundary , "Expect: ");
		foreach($header_array as $k => $v)
			array_push($header_array2,$k.': '.$v);
	
		curl_setopt($ci, CURLOPT_HTTPHEADER, $header_array2 );
		curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
	
		//echo $url."<hr/>";
	
		curl_setopt($ci, CURLOPT_URL, $url);
	
		$response = curl_exec($ci);
		$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ci));
		$this->url = $url;
	
		//echo '=====info====='."\r\n";
		//print_r( curl_getinfo($ci) );
	
		//echo '=====$response====='."\r\n";
		//print_r( $response );
	
		curl_close ($ci);
		return $response;
	}
	
	/**
	 * Get the header info to store.
	 *
	 * @return int
	 */
	function getHeader($ch, $header) {
		$i = strpos($header, ':');
		if (!empty($i)) {
			$key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
			$value = trim(substr($header, $i + 2));
			$this->http_header[$key] = $value;
		}
		return strlen($header);
	}
}

?>