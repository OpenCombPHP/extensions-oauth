<?php
namespace org\opencomb\oauth\auth ;

use org\opencomb\oauth\adapter\AuthAdapterException;
use org\opencomb\oauth\adapter\AdapterManager;
use org\jecat\framework\session\Session;
use org\jecat\framework\message\Message;
use org\jecat\framework\mvc\controller\Controller ;

class AuthorizeCallback extends Controller
{
	public function createBeanConfig()
	{
		
	}
	
	public function process()
	{
		// 检查参数 ----------
		if( empty($this->params['oauth_token']) or empty($this->params['oauth_verifier']) or empty($this->params['service']) )
		{
			$this->createMessage(Message::notice,"缺少参数：oauth_token, oauth_verifier, service") ;
			$this->messageQueue()->display() ;
			return ;
		}
		
		$arrKeys =& Session::singleton()->variable('weiboAuthKeys') ;
		$arrRequestToken = Session::singleton()->variable($this->params['service'].'.RequestToken') ;
		if( empty($arrRequestToken['oauth_token_secret']) )
		{
			$this->createMessage(Message::notice,"request oauth_token_secret 丢失") ;
			$this->messageQueue()->display() ;
			return ;
		}
		if( $arrRequestToken['oauth_token']!=$this->params['oauth_token'] )
		{
			$this->createMessage(Message::notice,"request oauth_token 不匹配") ;
			$this->messageQueue()->display() ;
			return ;
		}
				print_r($arrRequestToken) ;
		// 取得授权信息 --------
		try{
			
			$aAdapter = AdapterManager::singleton()->createAuthAdapter($this->params['service'],$arrRequestToken['oauth_token'],$arrRequestToken['oauth_token_secret']) ;
			
			$arrAccessToken = $aAdapter->fetchAccessToken($this->params['oauth_verifier']) ;
			
		}catch(AuthAdapterException $e){
			$this->createMessage(Message::error,$e->messageSentence(),$e->messageArgvs()) ;
			$this->messageQueue()->display() ;
			return ;
		}
		
		print_r($arrAccessToken) ;
		
		// 执行 action 
		
		// 绑定用户授权
		
		//$c = new WeiboClient( WB_AKEY , WB_SKEY , $_SESSION['last_key']['oauth_token'] , $_SESSION['last_key']['oauth_token_secret']  );
		// mentions
		//$_SESSION['last_key'] = $last_key;
		//print_r($_REQUEST) ;
	}
	
	protected function actionNewUser()
	{
		// create new
	}
	
	private $arrAccessToken ;
}