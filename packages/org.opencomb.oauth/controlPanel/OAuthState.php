<?php
namespace org\opencomb\oauth\controlPanel ;

use org\jecat\framework\auth\IdManager;

use org\jecat\framework\verifier\Length;

use org\opencomb\platform\ext\Extension;
use org\opencomb\oauth\adapter\AdapterManager;
use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\jecat\framework\message\Message;
use org\opencomb\oauth\controlPanel\OAuthItem;


class OAuthState extends ControlPanel
{
	public function createBeanConfig()
	{
	    $aId = IdManager::singleton()->currentId() ;
	    
		$arrBean = array(
            'frame' => array(
                	'class'=>'org\\opencomb\\coresystem\\mvc\\controller\\UserPanelFrame',
             ) ,
			'view' => array(
				'template' => 'oauth:OAuthState.html' ,
				'model' => 'auser' ,
			) ,
            'model:auser' => array(
            	'orm' => array(
            		'table' => 'oauth:user' ,
		            'keys'=>array('uid','suid'),
        			'where' => array(
        				array('eq','uid',$aId->userId()) ,
        			) ,
            	) ,
                'list' => true,
            ) ,
		);
		
		return $arrBean;
	}
	
	public function process()
	{	
	    $this->auser->load();
	}
	
}
