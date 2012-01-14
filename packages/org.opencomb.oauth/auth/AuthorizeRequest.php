<?php
namespace org\opencomb\oauth\auth ;

use org\jecat\framework\system\HttpRequest;

use org\jecat\framework\session\Session;
use org\opencomb\oauth\adapter\AuthAdapterException;
use org\opencomb\oauth\adapter\AdapterManager;
use org\jecat\framework\message\Message;
use org\opencomb\platform\ext\Extension;
use org\jecat\framework\mvc\controller\Controller;

class AuthorizeRequest extends Controller
{
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
		
		// 取得 request token
		$sCallbackUrl = HttpRequest::singleton()->urlNoQuery().'?c=org.opencomb.oauth.auth.AuthoritionObtaining&act=form' ;
		$sCallbackUrl.= '&' . http_build_query(array(
			'operation'=>$this->params['operation'] ,
			'service'=>$this->params['service'] ,
		)) ;
		$arrRequestToken = $aAdapter->fetchRequestToken($sCallbackUrl) ;
		if(empty($arrRequestToken['oauth_token']))
		{
			$this->createMessage( Message::error,"从 %s 取得 request token 失败，请检查 oauth 配置", AdapterManager::singleton()->arrAdapteeConfigs[$this->params['service']]['name'] ) ;
			$this->messageQueue()->display() ;
			print_r($arrRequestToken) ;
			return ;			
		}

		Session::singleton()->addVariable($this->params['service'].'.RequestToken',$arrRequestToken) ;

		// 重定向引导用户授权
		$sRequestUrl = $aAdapter->tokenFetchUrl($arrRequestToken['oauth_token'],'authorize',$sCallbackUrl) ;
		$this->createMessage( Message::notice,"正在请求%s授权...", AdapterManager::singleton()->arrAdapteeConfigs[$this->params['service']]['name'] ) ;
		$this->location( $sRequestUrl ) ;
	}
}

?>