<?php
namespace org\opencomb\oauth\api ;

use net\daichen\oauth\OAuthCommon;

use net\daichen\oauth\Http;

use org\opencomb\userstate\CreateState;

use org\opencomb\oauth\adapter\AuthAdapterException;

use org\opencomb\platform\ext\Extension;
use org\opencomb\oauth\adapter\AdapterManager;

use org\jecat\framework\db\DB;
use org\jecat\framework\auth\IdManager;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\Controller ;

class PushState extends Controller
{
	public function createBeanConfig()
	{
	    $aOrm = array(
		
	        /**
	         * 用来快速获取，判断认证信息
	         */
            'model:auser' => array(
                	'orm' => array(
                		'table' => 'oauth:user' ,
    		            'keys'=>array('uid','suid'),
                	) ,
                    'list' => true,
            ) ,
	            
		) ;
	    
	    return  $aOrm;
	}
	public function process()
	{
	    
	    $aService = $this->params['service'];
	    $sTitle = $this->params['title'];
	    
	    $aId = IdManager::singleton()->currentId() ;
	    
	    /**
	     * 克隆MODEL-Where，只用来获得用户KEY
	     * @var unknown_type
	     */
	    $auserModelWhere = clone $this->auser->prototype()->criteria()->where();
	    $auserModelWhere->eq('uid',$aId->userId());
	    //$this->auser->prototype()->criteria()->where()->eq('service',$this->params["service"]);
	    $this->auser->load($auserModelWhere) ;
	    
	    foreach($this->auser->childIterator() as $o)
	    {
	        if($o->hasData('token') && $o->hasData('token_secret') && ($o->pulltime+$o->pullnexttime) < time() && in_array($o->service, $aService) )
	        {
	            try{
	                $aAdapter = AdapterManager::singleton()->createApiAdapter($o->service) ;
	                $aRs = @$aAdapter->createPushMulti($o,$sTitle);
	            }catch(AuthAdapterException $e){
	                $this->createMessage(Message::error,$e->messageSentence(),$e->messageArgvs()) ;
	                $this->messageQueue()->display() ;
	                return ;
	            }
	        }
	    }
	    
	    $OAuthCommon = new OAuthCommon("",  "");
	    $aRsT = $OAuthCommon -> multi_exec();
	}
}

?>