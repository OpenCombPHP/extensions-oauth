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

		return new AuthorizeAdapter($this->arrAdapteeConfigs[$sServiceName]['auth'],$sAppKey,$sAppSecret,$sOAuthToken,$sOAuthTokenSecret) ;
	}
	
	public $arrAdapteeConfigs = array(
			
		// 新浪微博
		'weibo' => array(
			'name' => '新浪微博' ,
				
			// 授权
			'auth' => array(
				'adapter' => 'org\\opencomb\\webopenapi\\adapter\\oauth\\AuthorizerRequest' ,
				'authenticate' => 'http://api.t.sina.com.cn/oauth/authenticate' ,	
				'authorize' => 'http://api.t.sina.com.cn/oauth/authorize' ,	
				'tokenUrl' => array(
					'request' => 'http://api.t.sina.com.cn/oauth/request_token' ,
					'access' => 'http://api.t.sina.com.cn/oauth/access_token' ,	
				)	
			)
		) ,
		
		// QQ
		'qq' => array(
		) ,
			
	) ;
}
