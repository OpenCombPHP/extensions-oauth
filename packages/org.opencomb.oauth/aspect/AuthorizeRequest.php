<?php
namespace org\opencomb\oauth\auth ;

use org\opencomb\coresystem\mvc\controller\UserSpace;

use org\jecat\framework\mvc\controller\HttpRequest;

use org\jecat\framework\session\Session;
use org\opencomb\oauth\adapter\AuthAdapterException;
use org\opencomb\oauth\adapter\AdapterManager;
use org\jecat\framework\message\Message;
use org\opencomb\platform\ext\Extension;
use org\jecat\framework\mvc\controller\Controller;

class AuthorizeRequest extends UserSpace
{
	public function createBeanConfig()
	{
	    return array(
			// 视图
			'view' => array(
				'template' => 'oauth:auth/AuthorizeRequest.html' ,
			) ,
		) ;
	}
	
	public function process()
	{
		if( empty($this->params['service']) )
		{
			$this->createMessage(Message::error,"缺少参数 service") ;
			$this->messageQueue()->display() ;
			return ;
		}
		if( empty($this->params['operation']) )
		{
			$this->createMessage(Message::error,"缺少参数 operation") ;
			$this->messageQueue()->display() ;
			return ;
		}
		
		try{
			$aAdapter = AdapterManager::singleton()->createAuthAdapter($this->params['service']) ;
		}catch(AuthAdapterException $e){
			$this->createMessage(Message::error,$e->messageSentence(),$e->messageArgvs()) ;
			$this->messageQueue()->display() ;
			return ;
		}
		
		// 取得未授权 request token
		$sCallbackUrl = HttpRequest::singleton()->urlNoQuery().'?c=org.opencomb.oauth.auth.AuthoritionObtaining&act=form' ;
		$sCallbackUrl.= '&' . http_build_query(array(
			'operation'=>$this->params['operation'] ,
			'service'=>$this->params['service'] ,
		)) ;
		
		if(AdapterManager::singleton()->getCallbackCode($this->params['service']) == "urlencode")
		{
		    $sCallbackUrl = urlencode($sCallbackUrl);
		}
		
		$sRequestUrl = $aAdapter->fetchRequestTokenUrl($sCallbackUrl) ;
		
		
		if(empty($sRequestUrl))
		{
			$this->createMessage( Message::error,"从 %s 取得 request token 失败，请检查 oauth 配置", AdapterManager::singleton()->arrAdapteeConfigs[$this->params['service']]['name'] ) ;
			$this->messageQueue()->display() ;
			return ;			
		}

		// 重定向引导用户授权
		$this->createMessage( Message::notice,"正在请求%s授权...", AdapterManager::singleton()->arrAdapteeConfigs[$this->params['service']]['name'] ) ;
		$this->location( $sRequestUrl ) ;
	}
}

?>