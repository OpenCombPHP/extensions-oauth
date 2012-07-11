<?php
namespace org\opencomb\oauth\auth ;

use org\jecat\framework\mvc\model\Model;

use org\jecat\framework\message\Message;
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
	    $aModel = Model::create('oauth:user');
	    
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
		
		$aModel->delete("service = '{$this->params["service"]}' AND suid = '{$this->params["id"]}'");
		
		$this->location( "?c=org.opencomb.oauth.controlPanel.OAuthState" ) ;
	}
}
