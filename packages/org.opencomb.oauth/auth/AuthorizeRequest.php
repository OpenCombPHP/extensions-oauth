<?php
namespace org\opencomb\oauth\auth ;

use org\opencomb\coresystem\mvc\controller\UserSpace;

use org\opencomb\coresystem\user\UserPanel;
use org\jecat\framework\mvc\controller\HttpRequest;
use org\opencomb\oauth\adapter\AuthAdapterException;
use org\opencomb\oauth\adapter\AdapterManager;
use org\jecat\framework\message\Message;

class AuthorizeRequest extends UserSpace
{
	protected $arrConfig = array(
			'view'=>array(
				'template' => 'auth/AuthorizeRequest.html' ,
			),
	) ;	
	
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
		$sCallbackUrl = HttpRequest::singleton()->urlNoQuery().'?c=org.opencomb.oauth.auth.AuthoritionObtaining&a=auth.AuthoritionObtaining::form' ;
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
		
		echo "<pre>";print_r($sRequestUrl);echo "</pre>";exit;
		
		$this->createMessage( Message::notice,"正在请求%s授权...", AdapterManager::singleton()->arrAdapteeConfigs[$this->params['service']]['name'] ) ;
		$this->location( $sRequestUrl , 3) ;
	}
}
