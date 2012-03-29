<?php
namespace org\opencomb\oauth\controlPanel ;

use org\opencomb\coresystem\user\UserPanel;
use org\jecat\framework\auth\IdManager;
use org\jecat\framework\message\Message;
use org\opencomb\oauth\controlPanel\OAuthItem;

class OAuthState extends UserPanel
{
	public function createBeanConfig()
	{
		$aId = $this->requireLogined() ;
	    
		$arrBean = array(
            'frame' => array(
                	'class'=>'org\\opencomb\\coresystem\\mvc\\controller\\UserPanelFrame',
             ) ,
			'view:oauth' => array(
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
		$aId = $this->requireLogined() ;
		
	    $this->auser->load();
	    
	    if(!$this->auser){
	    	return;
	    }
	    $arrServices= array('t.qq.com', 'sohu.com' , '163.com' , 'weibo.com' ,'renren.com' ,'douban.com');
	    $arrServiceModels = array();
	    foreach($arrServices as $sService){
	    	foreach($this->auser->childIterator() as $aModel){
	    		if($aModel['service'] == $sService && $aModel['token'] !== ''){
	    			$arrServiceModels[$sService] = $aModel;
	    			break;
	    		}
	    	}
	    	
	    	if(!isset($arrServiceModels[$sService])){
	    		$arrServiceModels[$sService] = null;
	    	}
	    }
	    
	    $this->viewOauth->variables()->set('arrServiceModels',$arrServiceModels) ;
	}
}
