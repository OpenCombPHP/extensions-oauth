<?php
namespace org\opencomb\oauth\auth ;

use com\weibo\sdk\WeiboOAuth;

use org\opencomb\platform\ext\Extension;

use org\jecat\framework\session\Session;
use org\jecat\framework\message\Message;
use org\jecat\framework\mvc\controller\Controller ;

class AuthorizeCallback extends Controller
{
	public function process()
	{
		// 检查参数 ----------
		if( empty($this->params['oauth_token']) or empty($this->params['oauth_verifier']) )
		{
			$this->createMessage(Message::notice,"缺少参数：oauth_token, oauth_verifier") ;
			$this->messageQueue()->display() ;
			return ;
		}
		
		$arrKeys =& Session::singleton()->variable('weiboAuthKeys') ;
		if( empty($arrKeys['oauth_token']) or empty($arrKeys['oauth_token_secret']) )
		{
			$this->createMessage(Message::notice,"缺少参数：oauth_token, oauth_token_secret") ;
			$this->messageQueue()->display() ;
			return ;
		}
		
		$aSetting = Extension::flyweight('webopenapi')->setting() ;
		$sAppKey = $aSetting->item('/weibo.com','appKey') ;
		$sAppSecret = $aSetting->item('/weibo.com','appSecret') ;
		
		if( !$sAppKey or !$sAppSecret )
		{
			$this->createMessage(Message::error,"尚未配置新浪微博API的 AppKey/AppSecret") ;
			$this->messageQueue()->display() ;
			return ;
		}
		
		// 取得授权信息 --------
		$aWeiboOAuth = new WeiboOAuth( $sAppKey, $sAppSecret, $arrKeys['oauth_token'] , $arrKeys['oauth_token_secret']  );
		
		$this->arrAccessToken = $aWeiboOAuth->getAccessToken($this->params['oauth_verifier']) ;
		print_r($this->arrAccessToken) ;
		
		// 执行 action 
		$this->doActions() ;
		
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