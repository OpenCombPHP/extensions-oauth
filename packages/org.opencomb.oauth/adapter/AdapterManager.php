<?php
namespace org\opencomb\oauth\adapter ;

use org\opencomb\platform\ext\Extension;
use org\jecat\framework\lang\Object;

class AdapterManager extends Object
{
	/**
	 * @throws AuthAdapterException
	 * @return org\opencomb\oauth\adapter\AuthorizeAdapter
	 */
	public function createAuthAdapter($sServiceName,$sOAuthToken=null,$sOAuthTokenSecret=null)
	{
		if( !isset($this->arrAdapteeConfigs[$sServiceName]) )
		{
			throw new AuthAdapterException("无效的服务名称:%s",$sServiceName,null,AuthAdapterException::invalid_service) ;
		}
		if( !isset($this->arrAdapteeConfigs[$sServiceName]['auth']) )
		{
			throw new AuthAdapterException("服务:%s不支持认证/授权操作",$sServiceName,null,AuthAdapterException::not_support_feature) ;
		}
		$aSetting = Extension::flyweight('oauth')->setting() ;
		$sAppKey = $aSetting->item('/'.$sServiceName,'appKey') ;
		$sAppSecret = $aSetting->item('/'.$sServiceName,'appSecret') ;
		
		if( !$sAppKey or !$sAppSecret )
		{
			throw new AuthAdapterException("服务:%s尚未配置正确的 app key/secret",$sServiceName,null,AuthAdapterException::not_setup_appkey) ;
		}

		
		if($this->arrAdapteeConfigs[$sServiceName]['OAuthVersion'] == "1.0" || empty($this->arrAdapteeConfigs[$sServiceName]['OAuthVersion']))
		{
		    $oOauth = new Oauth10Adapter($this->arrAdapteeConfigs[$sServiceName],array('appkey'=>$sAppKey,'appsecret'=>$sAppSecret));
		}
		
		if($this->arrAdapteeConfigs[$sServiceName]['OAuthVersion'] == "2.0")
		{
		    $oOauth = new Oauth20Adapter($this->arrAdapteeConfigs[$sServiceName],array('appkey'=>$sAppKey,'appsecret'=>$sAppSecret));
		}
		return $oOauth;
		//return new AuthorizeAdapter($this->arrAdapteeConfigs[$sServiceName]['auth'],$sAppKey,$sAppSecret,$sOAuthToken,$sOAuthTokenSecret) ;
	}
	
	public $arrAdapteeConfigs = array(
	
	// 新浪微博
	        'weibo.com' => array(
	                'name' => '新浪微博' ,
	                'url' => 'weibo.com' ,
	                'OAuthVersion'=>'1.0',
	                // 授权
	                'auth' => array(
	                        'adapter' => 'org\\opencomb\\webopenapi\\adapter\\oauth\\AuthorizerRequest' ,
	                        'authorize' => 'http://api.t.sina.com.cn/oauth/authenticate' ,
	                        'tokenUrl' => array(
	                                'request' => 'http://api.t.sina.com.cn/oauth/request_token' ,
	                                'access' => 'http://api.t.sina.com.cn/oauth/access_token' ,
	                        ) ,
	                        'accessRspn' => array(
	                                'keyId' => 'user_id' ,
	                        ) ,
	                        // 应用
	                        'app' => array(
	                                'userinfo'=>array(
	                                        'uri'=>'http://api.t.sina.com.cn/account/verify_credentials.json',
	                                        'params'=>array('format'=>'json'),
	                                ),
	                                'add'=>array(
	                                        'uri'=>'http://api.t.sina.com.cn/statuses/update.json',
	                                        'params'=>array('format'=>'json'),
	                                ),
	                        ),
	                )
	        ) ,
	
	        // 腾讯微博
	        't.qq.com' => array(
	                'name' => '腾讯微博' ,
	                'url' => 't.qq.com' ,
	                'OAuthVersion'=>'1.0',
	                // 授权
	                'auth' => array(
	                        'adapter' => 'org\\opencomb\\webopenapi\\adapter\\oauth\\AuthorizerRequest' ,
	                        'authorize' => 'https://open.t.qq.com/cgi-bin/authorize' ,
	                        'tokenUrl' => array(
	                                'request' => 'https://open.t.qq.com/cgi-bin/request_token' ,
	                                'access' => 'https://open.t.qq.com/cgi-bin/access_token' ,
	                        ) ,
	                        'accessRspn' => array(
	                                'keyId' => 'name' ,
	                        ) ,
	                        // 应用
	                        'app' => array(
	                                'userinfo'=>array(
	                                        'uri'=>'http://open.t.qq.com/api/user/info',
	                                        'params'=>array('format'=>'json'),
	                                ),
	                                'add'=>array(
	                                        'uri'=>'http://open.t.qq.com/api/t/add',
	                                        'params'=>array('format'=>'json','clientip'=>'123.119.32.211'),
	                                ),
	                        ),
	                )
	        ) ,
	
	        // douban
	        'douban.com' => array(
	                'name' => '豆瓣社区' ,
	                'url' => 'douban.com' ,
	                'OAuthVersion'=>'1.0',
	                // 授权
	                'auth' => array(
	                        'adapter' => 'org\\opencomb\\webopenapi\\adapter\\oauth\\AuthorizerRequest' ,
	                        'authorize' => 'http://www.douban.com/service/auth/authorize' ,
	                        'tokenUrl' => array(
	                                'request' => 'http://www.douban.com/service/auth/request_token' ,
	                                'access' => 'http://www.douban.com/service/auth/access_token' ,
	                        ),
	                        // 应用
	                        'app' => array(
	                                'userinfo'=>array(
	                                        'uri'=>'http://api.douban.com/people/%40me?alt=json',
	                                        'params'=>array(),
	                                ),
	                                'add'=>array(
	                                        'uri'=>'http://api.douban.com/miniblog/saying',
	                                        'params'=>array('format'=>'xml','html'=>"<?xml version='1.0' encoding='UTF-8'?><entry xmlns:ns0=\"http://www.w3.org/2005/Atom\" xmlns:db=\"http://www.douban.com/xmlns/\"><content>{content}</content></entry>"),
	                                ),
	                        ),
	
	                )
	        ) ,
	
	        // sohu
	        'sohu.com' => array(
	                'name' => '搜狐' ,
	                'OAuthVersion'=>'1.0',
	                'url'=>'sohu.com',
	                // 授权
	                'auth' => array(
	                        'adapter' => 'org\\opencomb\\webopenapi\\adapter\\oauth\\AuthorizerRequest' ,
	                        'authorize' => 'http://api.t.sohu.com/oauth/authorize' ,
	                        'tokenUrl' => array(
	                                'request' => 'http://api.t.sohu.com/oauth/request_token' ,
	                                'access' => 'http://api.t.sohu.com/oauth/access_token' ,
	                        ),
	                        // 应用
	                        'app' => array(
	                                'userinfo'=>array(
	                                        'uri'=>'http://api.t.sohu.com/account/verify_credentials.json',
	                                        'params'=>array(),
	                                ),
	                                'add'=>array(
	                                        'uri'=>'http://api.t.sohu.com/statuses/update.json',
	                                        'params'=>array('format'=>'json'),
	                                ),
	                        ),
	                )
	        ) ,
	
	        // 163
	        '163.com' => array(
	                'name' => '网易' ,
	                'OAuthVersion'=>'1.0',
	                'url'=>'163.com',
	                // 授权
	                'auth' => array(
	                        'adapter' => 'org\\opencomb\\webopenapi\\adapter\\oauth\\AuthorizerRequest' ,
	                        'authorize' => 'http://api.t.163.com/oauth/authenticate' ,
	                        'tokenUrl' => array(
	                                'request' => 'http://api.t.163.com/oauth/request_token' ,
	                                'access' => 'http://api.t.163.com/oauth/access_token' ,
	                        ),
	                        // 应用
	                        'app' => array(
	                                'userinfo'=>array(
	                                        'uri'=>'http://api.t.163.com/account/verify_credentials.json',
	                                        'params'=>array("format"=>"json"),
	                                ),
	                                'add'=>array(
	                                        'uri'=>'http://api.t.163.com/statuses/update.json',
	                                        'params'=>array('format'=>'json'),
	                                ),
	                        ),
	                )
	        ) ,
	
	        // renren
	        'renren.com' => array(
	                'name' => '人人' ,
	                'OAuthVersion'=>'2.0',
	                'url'=>'renren.com',
	                // 授权
	                'auth' => array(
	                        'adapter' => 'org\\opencomb\\webopenapi\\adapter\\oauth\\AuthorizerRequest' ,
	                        'authorize' => 'https://graph.renren.com/oauth/authorize' ,
	                        'tokenUrl' => array(
	                                'access_token_uri' => 'https://graph.renren.com/oauth/token' ,
	                                'scope' => 'status_update' ,
	                        ),
	                        // 应用
	                        'app' => array(
	                                'userinfo'=>array(
	                                        'uri'=>'http://api.renren.com/restserver.do',
	                                        'params'=>array('mode'=>'users.getInfo','method'=>'users.getInfo','fields'=>'uid,name,sex,star,zidou,vip,birthday,email_hash,tinyurl,headurl,mainurl,hometown_location,work_history,university_history'),
	                                ),
	                        ),
	                )
	        ) ,
	) ;
}
