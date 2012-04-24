<?php
namespace org\opencomb\oauth\auth ;

use org\jecat\framework\message\Message;
use org\opencomb\oauth\adapter\AuthAdapterException;
use org\opencomb\oauth\adapter\AdapterManager;
use org\jecat\framework\mvc\controller\Controller;

class RefreshBind extends Controller
{
	public function createBeanConfig()
	{
		$arrBean = array(
            'model:auser' => array(
            	'orm' => array(
            		'table' => 'oauth:user' ,
		            'keys'=>array('uid','suid'),
            	) ,
            ) ,
		);
		
		return $arrBean;
	}
	
	public function process()
	{
		if( empty($this->params['service']) )
		{
			$this->createMessage(Message::error,"缺少参数 service") ;
			$this->messageQueue()->display() ;
			return ;
		}
		if( empty($this->params['id']) )
		{
			$this->createMessage(Message::error,"缺少参数 id") ;
			$this->messageQueue()->display() ;
			return ;
		}
		
		$this->auser->loadSql("service = @1 AND suid = @2" , $this->params["service"] , $this->params["id"]);
		
        try{
            $aAdapter = AdapterManager::singleton()->createApiAdapter($this->auser->service) ;
            $aRs = $aAdapter->refreshTtoken($this->auser->token,$this->auser->token_secret);
        }catch(AuthAdapterException $e){
            $this->createMessage(Message::error,$e->messageSentence(),$e->messageArgvs()) ;
            $this->messageQueue()->display() ;
            return ;
        }
		
		$aRs = json_decode($aRs,true);
		
		$this->auser->setData('token',$aRs['access_token']);
		$this->auser->setData('token_secret',$aRs['refresh_token']);
		$this->auser->save();
		
		$this->location( "/?c=org.opencomb.oauth.controlPanel.OAuthState" ) ;
	}
}
