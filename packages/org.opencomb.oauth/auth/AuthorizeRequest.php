<?php
namespace org\opencomb\oauth\auth ;

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
		$arrRequestToken = $aAdapter->fetchRequestToken() ;
		Session::singleton()->addVariable($this->params['service'].'.RequestToken',$arrRequestToken) ;

		// 重定向引导用户授权
		$sRequestUrl = $aAdapter->tokenFetchUrl($arrRequestToken['oauth_token'],'authenticate',array(
				'operation'=>$this->params['operation'] ,
				'service'=>$this->params['service'] ,
		)) ;
		$this->createMessage(Message::notice,"正在请求新浪微博授权...") ;
		$this->location( $sRequestUrl ) ;
	}
}

?>