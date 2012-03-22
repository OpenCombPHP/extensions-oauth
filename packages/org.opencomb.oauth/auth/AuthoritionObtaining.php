<?php
namespace org\opencomb\oauth\auth ;

use org\jecat\framework\db\ExecuteException;
use org\opencomb\coresystem\auth\Id;
use org\opencomb\coresystem\auth\Authenticate;
use org\jecat\framework\auth\IdManager;
use org\jecat\framework\bean\BeanFactory;
use org\opencomb\oauth\adapter\AuthAdapterException;
use org\opencomb\oauth\adapter\AdapterManager;
use org\jecat\framework\session\Session;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\Controller ;

class AuthoritionObtaining extends Controller
{
	public function createBeanConfig()
	{
		return array(
			'view' => array(
					'template'=>'oauth:auth/AuthoritionObtaining.html' ,
					'model' => 'user' ,
					'class' => 'form' ,
					'hideForm' => true ,
			) ,
			'model:user' => array(
					'orm'=>array(
							'table' => 'coresystem:user' ,
							'hasOne:info' => array(
								'table' => 'coresystem:userinfo'
							) ,
							'hasOne:token' => array(
								'table' => 'oauth:user' ,
								'keys' => array('service','suid') ,
								'fromkeys' => 'uid' ,
								'tokeys' => 'uid' ,
							) ,
					)
			) ,
			'model:token' => array(
					'orm'=>array(
							'table' => 'oauth:user' ,
							'keys' => array('service','suid') ,
					)
			) ,
		) ;
	}
	
	public function process()
	{
		// 执行 action 
		if( empty($this->params['act']) )
		{
			$this->params['act'] = 'form' ;
		}
		$this->doActions() ;
	}
	
	protected function actionForm()
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
		
		
		// 检查 token 是否存在
		$this->user->load(
				array($this->params['service'],$this->arrAccessToken['id'])
				, array('token.service','token.suid')
		) ;
		
		if( $this->params['operation']=='login' )
		{
			// 直接登录
			if( !$this->user->isEmpty() )
			{
				if( !IdManager::singleton()->id($this->user->uid) )
				{
					IdManager::singleton()->setCurrentId(
						new Id($this->user)
					) ;
				}
				
				
				if($this->user->child('token')->token && $this->user->child('token')->token_secret)
				{
				    $this->createMessage( Message::success, '已经以 %s 的身份登录', $this->user->username ) ;
				}else{
				    
				    $this->user->setData("token.token",$this->arrAccessToken['oauth_token']);
				    $this->user->setData("token.token_secret",$this->arrAccessToken['oauth_token_secret']);
				    
				    $this->user->save();
				    $this->createMessage( Message::success, '已经以 %s 的身份绑定并登录', $this->user->username ) ;
				}
				
				
				return ;
			}
			
			//
			$this->view->variables()->set('bAlong',true) ;
		}
		
		$this->view->hideForm(false) ;
		$this->view->variables()->set('sServiceName',$this->params['service']) ;
		$this->view->variables()->set('sCode',$this->params['code']) ;
		
		Session::singleton()->addVariable($this->params['service'].'.AccessToken',$this->arrAccessToken) ;
		
		// 绑定的默认用户名
		if( $aId = IdManager::singleton()->currentId() )
		{
			$this->view->variables()->set('sBindUser',$aId->username()) ;
		}
	}
	
	/**
	 * 绑定已有的帐号
	 */
	protected function actionBindExists()
	{
	    
		$arrAccessToken = Session::singleton()->variable($this->params['service'].'.AccessToken') ;
		
		$this->params['user'] = trim($this->params['user']) ;
		if( empty($this->params['user']) or empty($this->params['password']) )
		{
			$this->createMessage(Message::error,"请输入用户名和密码") ;
			return ;
		}
		
		
		if( !$this->user->load( $this->params['user'],'username') )
		{
			$this->createMessage(Message::error,"用户名不存在，无法完成绑定") ;
			return ;
		}
		
		if( $this->user['password']!=Authenticate::encryptPassword($this->user,$this->params['user'],$this->params['password']) )
		{
			$this->createMessage(Message::error,"密码错误，无法完成绑定") ;
			return ;
		}
		
		// 解除以前的绑定
		$aBind = $this->user->child('token')->prototype()->createModel() ;
		if( $aBind->load(
				array($this->params['service'],$arrAccessToken['id'])
				/* , array('service','suid') */
		) )
		{
			try{
				$aBind->delete() ;
				$this->createMessage(Message::success,"原有的帐号绑定已经解除。") ;
			} catch(\Exception $e) {
				$this->createMessage(Message::error,"系统在解除原有的帐号绑定时遇到错误。") ;
				return ;
			}
		}
		
		$this->user['token.service'] = $this->params['service'] ;
		$this->user['token.suid'] = $arrAccessToken['id'] ;
		$this->user['token.token'] = $arrAccessToken['oauth_token'] ;
		$this->user['token.token_secret'] = $arrAccessToken['oauth_token_secret'] ;
		
		
		try{
			$this->user->save() ;
			$this->createMessage(Message::success,"帐号绑定成功") ;
		} catch (\Exception $e) {
			$this->createMessage(Message::error,"帐号绑定失败:%s",$e->getMessage()) ;
			return ;
		}
	}
	
	/**
	 * 绑定一个新帐号
	 */
	protected function actionBindAlong()
	{
		$arrAccessToken = Session::singleton()->variable($this->params['service'].'.AccessToken') ;
		
		// 创建一个新用户
		$this->user->username = "{$arrAccessToken['id']}@{$this->params['service']}" ;
		$this->user->password = md5( $this->user->username . time() );
		$this->user->lastLoginTime = time() ;
		$this->user->lastLoginIp = $_SERVER['REMOTE_ADDR'] ;
		$this->user->registerTime = time() ;
		$this->user->registerIp = $_SERVER['REMOTE_ADDR'] ;
		$this->user->activeTime = time() ;
		$this->user->activeIp = $_SERVER['REMOTE_ADDR'] ;
		$this->user['info.nickname'] = $this->user->username ;
		$this->user['token.service'] = $this->params['service'] ;
		$this->user['token.suid'] = $arrAccessToken['id'] ;
		$this->user['token.token'] = $arrAccessToken['oauth_token'] ;
		$this->user['token.token_secret'] = $arrAccessToken['oauth_token_secret'] ;
		
		try{
			$this->user->save() ;
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