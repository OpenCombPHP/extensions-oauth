<?php
namespace org\opencomb\oauth\auth ;

use org\opencomb\coresystem\mvc\controller\UserSpace;

use org\opencomb\coresystem\user\UserPanel;
use org\jecat\framework\db\ExecuteException;
use org\opencomb\coresystem\auth\Id;
use org\opencomb\coresystem\auth\Authenticate;
use org\jecat\framework\auth\IdManager;
use org\opencomb\oauth\adapter\AuthAdapterException;
use org\opencomb\oauth\adapter\AdapterManager;
use org\jecat\framework\session\Session;
use org\jecat\framework\message\Message;

class AuthoritionObtaining extends UserSpace
{
	protected $arrConfig = array(
			'view' => array(
					'template'=>'oauth:auth/AuthoritionObtaining.html' ,
			) ,
	) ;	
	
	public function process()
	{
		// 执行 action 
		if( empty($this->params['act']) )
		{
			$this->params['act'] = 'form' ;
		}
		
		$this->model('coresystem:user')
		     ->hasOne('coresystem:userinfo','uid','uid')
		     ->hasOne('oauth:user','uid','uid','token');
		
		$this->model('oauth:user');
		
		$this->doActions() ;
	}
	
	protected function form()
	{
		// 检查参数 ----------
		if( empty($this->params['service']) or (empty($this->params['oauth_token']) and (empty($this->params['code']))) )
		{
			$this->createMessage(Message::notice,"缺少参数：oauth_token, oauth_verifier, service") ;
			return ;
		}
		
		$arrKeys =& Session::singleton()->variable('weiboAuthKeys') ;
		$arrRequestToken = Session::singleton()->variable($this->params['service'].'.RequestToken') ;
		
		if( empty($arrRequestToken['oauth_token_secret']) && empty($this->params['code']))
		{
			$this->createMessage(Message::notice,"request oauth_token_secret 丢失") ;
			return ;
		}
		if( $arrRequestToken['oauth_token']!=$this->params['oauth_token'] && empty($this->params['code']))
		{
			$this->createMessage(Message::notice,"request oauth_token 不匹配") ;
			return ;
		}

		// 取得授权信息 --------
		try{
			$aAdapter = AdapterManager::singleton()->createAuthAdapter($this->params['service'],$arrRequestToken['oauth_token'],$arrRequestToken['oauth_token_secret']) ;
			
			$token = $this->params[AdapterManager::singleton()->getAccessParam($this->params['service'])];
			
			$this->arrAccessToken = $aAdapter->fetchAccessToken($token) ;
			
		}catch(AuthAdapterException $e){
			$this->createMessage(Message::error,$e->messageSentence(),$e->messageArgvs()) ;
			return ;
		}
		
		
		if( !empty($this->arrAccessToken['error']) )
		{
			$this->createMessage(
					Message::error
					, '[%d]%s (%s)'
					, array($this->arrAccessToken['error_code'],$this->arrAccessToken['error_CN'],$this->arrAccessToken['error'])
			) ;
			return ;
		}
		
		/**
		 * usernickname
		 */
		if(empty($this->arrAccessToken['nickname']))
		{
		    $apiAdapter = AdapterManager::singleton()->createApiAdapter($this->params['service']) ;
		    $aUser = $apiAdapter->getUser($this->arrAccessToken['oauth_token'],$this->arrAccessToken['oauth_token_secret']);
		    $this->arrAccessToken['nickname'] = $aUser['nickname'];
		    $this->arrAccessToken['username'] = $aUser['username'];
		}
		
		// 检查 token 是否存在
		$this->model('coresystem:user')->where("token.service = '{$this->params['service']}'");
		$this->model('coresystem:user')->where("token.suid = '{$this->arrAccessToken['id']}'");
		$this->model('coresystem:user')->load() ;
		
		if( $this->params['operation']=='login' )
		{
			// 直接登录
			if( $this->model('coresystem:user')->rowNum() > 0 )
			{
				if( !IdManager::singleton()->id($this->model('coresystem:user')->uid) )
				{
					IdManager::singleton()->setCurrentId(
						new Id($this->model('coresystem:user'))
					) ;
				}
				
				
				if($this->model('coresystem:user')->data('token.token') && $this->model('coresystem:user')->data('token.token_secret'))
				{
				    $this->createMessage( Message::success, '已经以 %s 的身份登录', $this->model('coresystem:user')->data('username') ) ;
				}else{
				    
				    $this->model('coresystem:user')->setData("token.token",$this->arrAccessToken['oauth_token']);
				    $this->model('coresystem:user')->setData("token.token_secret",$this->arrAccessToken['oauth_token_secret']);
				    
				    $this->model('coresystem:user')->insert(
				            array(
				                    "token.token" => $this->arrAccessToken['oauth_token'],
				                    "token.token_secret" => $this->arrAccessToken['oauth_token_secret']
		                    )
		            );
				    $this->createMessage( Message::success, '已经以 %s 的身份绑定并登录', $this->model('coresystem:user')->username ) ;
				}
				
				
				return ;
			}
			
			//
			$this->view->variables()->set('bAlong',true) ;
		}
		
		$this->view->hideForm(false) ;
		$this->view->variables()->set('sServiceName',$this->params['service']) ;
		$this->view->variables()->set('sServiceTitle',AdapterManager::singleton()->serviceTitle($this->params['service'])) ;
		$this->view->variables()->set('sCode',$this->params['code']) ;
		
		Session::singleton()->addVariable($this->params['service'].'.AccessToken',$this->arrAccessToken) ;
		
		// 绑定的默认用户名
		if( $aId = IdManager::singleton()->currentId() )
		{
			$this->view->variables()->set('sBindUser',$aId->username()) ;
		}
		
		$this->view()->setModel($this->model('coresystem:user'));
	}
	
	/**
	 * 绑定已有的帐号
	 */
	protected function BindExists()
	{
		$arrAccessToken = Session::singleton()->variable($this->params['service'].'.AccessToken') ;
		
		$this->params['user'] = trim($this->params['user']) ;
		if( empty($this->params['user']) or empty($this->params['password']) )
		{
			$this->createMessage(Message::error,"请输入用户名和密码") ;
			return ;
		}
		
		
		if( !$this->model('coresystem:user')->load( $this->params['user'],'username') )
		{
			$this->createMessage(Message::error,"用户名不存在，无法完成绑定") ;
			return ;
		}
		
		if( $this->model('coresystem:user')->data('password')!=Authenticate::encryptPassword($this->model('coresystem:user'),$this->params['user'],$this->params['password']) )
		{
			$this->createMessage(Message::error,"密码错误，无法完成绑定") ;
			return ;
		}
		
		// 解除以前的绑定
		$aBind = $this->model('oauth:user') ;
		$aBind->where("service = '{$this->params['service']}'");
		$aBind->where("suid = '{$arrAccessToken['id']}'");
		
		if( $aBind->load()->rowNum() > 0 )
		{
			try{
				$aBind->delete("suid = '" . $arrAccessToken['id'] . "' and service = '". $this->params['service']."'") ;
				$this->createMessage(Message::success,"原有的帐号绑定已经解除。") ;
			} catch(\Exception $e) {
				$this->createMessage(Message::error,"系统在解除原有的帐号绑定时遇到错误。") ;
				return ;
			}
		}
		
		try{
			$this->model('oauth:user')->insert(
	            array(
	                    'uid' =>$this->model('coresystem:user')->uid ,
	                    'service' =>$this->params['service'] ,
	                    'suid' =>$arrAccessToken['id'] ,
	                    'nickname' =>$arrAccessToken['nickname'] ,
	                    'username' =>$arrAccessToken['username'] ,
	                    'token' =>$arrAccessToken['oauth_token'] ,
	                    'token_secret' =>$arrAccessToken['oauth_token_secret'] ,
                )
	        ) ;
			
			$this->createMessage(Message::success,"帐号绑定成功") ;			
		} catch (\Exception $e) {
			$this->createMessage(Message::error,"帐号绑定失败:%s",$e->getMessage()) ;
			return ;
		}
		$this->location("?c=org.opencomb.oauth.controlPanel.OAuthState") ;
	}
	
	/**
	 * 绑定一个新帐号
	 */
	protected function BindAlong()
	{
		$arrAccessToken = Session::singleton()->variable($this->params['service'].'.AccessToken') ;
		
		// 创建一个新用户
		
		try{
			$this->model('coresystem:user')->insert(
			        array(
			                'username' => "{$arrAccessToken['id']}@{$this->params['service']}" ,
			                'password' => md5( $this->model('coresystem:user')->username . time() ),
			                'lastLoginTime' => time() ,
			                'lastLoginIp' => $_SERVER['REMOTE_ADDR'] ,
			                'registerTime' => time() ,
			                'registerIp' => $_SERVER['REMOTE_ADDR'] ,
			                'activeTime' => time() ,
			                'activeIp' => $_SERVER['REMOTE_ADDR'] ,
			                'info.nickname' => $this->model('coresystem:user')->username ,
			                'token.service' => $this->params['service'] ,
			                'token.suid' => $arrAccessToken['id'] ,
			                'token.nickname' => $arrAccessToken['nickname'] ,
			                'token.username' => $arrAccessToken['username'] ,
			                'token.token' => $arrAccessToken['oauth_token'] ,
			                'token.token_secret' => $arrAccessToken['oauth_token_secret'] ,
	                )
	        ) ;
		} catch (\Exception $e) {
			if( $e instanceof ExecuteException and $e->isDuplicate() )
			{
				$this->createMessage(Message::error,"操作已经完成，无法重复绑定帐号。") ;
			}
			else
			{
				$this->createMessage(Message::error,"保存用户信息失败") ;
			}
			return ;
		}
		
		$this->createMessage(Message::success,"用户信息已经保存") ;
		
	}
	
	private $arrAccessToken ;
}
