<?php
namespace org\opencomb\oauth\auth ;

use org\jecat\framework\message\Message;
use org\opencomb\oauth\adapter\AuthAdapterException;
use org\opencomb\oauth\adapter\AdapterManager;
use org\jecat\framework\mvc\controller\Controller;

class RefreshBind extends Controller
{
	public function process()
	{
	    $this->model('oauth:user');
	    
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
		
		$this->model('oauth:user')->where("service = '{$this->params["service"]}' AND suid = '{$this->params["id"]}'");
		$this->model('oauth:user')->load();
		
        try{
            $aAdapter = AdapterManager::singleton()->createApiAdapter($this->model('oauth:user')->service) ;
            $aRs = $aAdapter->refreshTtoken($this->model('oauth:user')->token,$this->model('oauth:user')->token_secret);
        }catch(AuthAdapterException $e){
            $this->createMessage(Message::error,$e->messageSentence(),$e->messageArgvs()) ;
            $this->messageQueue()->display() ;
            return ;
        }
		
		$aRs = json_decode($aRs,true);
		
		$this->model('oauth:user')->insert(
            array(
                    'token' => $aRs['access_token'],
                    'token_secret' => $aRs['refresh_token'],
            )		        
        );
		
		$this->location( "/?c=org.opencomb.oauth.controlPanel.OAuthState" ) ;
	}
}
