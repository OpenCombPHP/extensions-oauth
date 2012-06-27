<?php
namespace org\opencomb\oauth\controlPanel ;

use org\jecat\framework\mvc\model\Model;

use org\opencomb\coresystem\user\UserPanel;

class OAuthState extends UserPanel
{
	protected $arrConfig = array(
	        'frame' => array(
	                'class'=>'org\\opencomb\\coresystem\\mvc\\controller\\UserPanelFrame',
	        ) ,
	        'view' => array(
	                'template' => 'oauth:OAuthState.html' ,
	        ) ,
	) ;	
	
	public function process()
	{
		$aId = $this->requireLogined() ;
		
		$model = Model::create('oauth:user');
		$model->where("uid='{$aId->userId()}'");
	    $model->load();
	    
	    if(!$model){
	    	return;
	    }
	    
	    $arrServices= array('t.qq.com', 'sohu.com' , '163.com' , 'weibo.com' ,'renren.com' ,'douban.com');
	    $arrServiceModels = array();
	    foreach($arrServices as $sService){
	    	foreach($model as $aModel){
	    		if($aModel['service'] == $sService && $aModel['token'] !== ''){
	    			$arrServiceModels[$sService] = $aModel;
	    			break;
	    		}
	    	}
	    	
	    	if(!isset($arrServiceModels[$sService])){
	    		$arrServiceModels[$sService] = null;
	    	}
	    }
	    
	    $this->view()->setModel($model);
	    $this->viewOauth->variables()->set('arrServiceModels',$arrServiceModels) ;
	}
}

