<?php
namespace org\opencomb\oauth\api ;

use org\opencomb\oauth\adapter\AuthAdapterException;

use org\opencomb\platform\ext\Extension;
use org\opencomb\oauth\adapter\AdapterManager;

use org\jecat\framework\db\DB;
use org\jecat\framework\auth\IdManager;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\Controller ;

class PullState extends Controller
{
	public function createBeanConfig()
	{
	    $aId = IdManager::singleton()->currentId() ;
	    $aOrm = array(
		
    		/**
    		 * 模型
    		 * list = true 返回多条记录
    		 */
            'model:user' => array(
            		'orm'=>array(
							'table' => 'user' ,
            		        'keys'=>array('uid','suid'),
            		        'where' => array(
            		        // 'logic' => 'and' , (可省略)
            		                array('eq','uid',$aId->userId()) ,
            		                array('eq','service',$this->params["service"]) ,
            		        ) ,
					)
            ) 
		) ;
	    
	    return  $aOrm;
	}
	public function process()
	{
	    $this->user->load() ;
	    try{
	        $aAdapter = AdapterManager::singleton()->createApiAdapter($this->params['service']) ;
	    }catch(AuthAdapterException $e){
	        $this->createMessage(Message::error,$e->messageSentence(),$e->messageArgvs()) ;
	        $this->messageQueue()->display() ;
	        return ;
	    }
	    
	    
	    $json = $aAdapter->TimeLine($this->user->token,$this->user->token_secret);
	    
	    
	    $json=preg_replace("||",'',$json );
	    echo "<pre>";print_r(json_decode ($json,true));echo "</pre>";
	}
}

?>