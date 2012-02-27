<?php
namespace org\opencomb\oauth\auth ;

use org\jecat\framework\message\Message;

use org\opencomb\oauth\adapter\AuthAdapterException;
use org\opencomb\oauth\adapter\AdapterManager;
use org\opencomb\platform\ext\Extension;
use org\jecat\framework\mvc\controller\Controller;

class RemoveBind extends Controller
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
		
		$this->auser->prototype()->criteria()->where()->eq('service',$this->params["service"]);
		$this->auser->prototype()->criteria()->where()->eq('suid',$this->params["id"]);
		$this->auser->load();
		
		$this->auser->setData('token',"");
		$this->auser->setData('token_secret',"");
		$this->auser->save();
		
		$this->location( "/?c=org.opencomb.oauth.controlPanel.OAuthState" ) ;
	}
}

?>