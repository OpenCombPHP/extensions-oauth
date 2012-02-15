<?php
namespace org\opencomb\oauth\api ;

use org\opencomb\userstate\CreateState;

use org\opencomb\oauth\adapter\AuthAdapterException;

use org\opencomb\platform\ext\Extension;
use org\opencomb\oauth\adapter\AdapterManager;

use org\jecat\framework\db\DB;
use org\jecat\framework\auth\IdManager;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\Controller ;

class PullState extends Controller
{
    private $minNextTime = 2;
    private $maxNextTime = 10;
    
	public function createBeanConfig()
	{
	    $aOrm = array(
		
    		/**
    		 * 模型
    		 * list = true 返回多条记录
    		 * 用来存数据
    		 */
            'model:user' => array(
                	'orm' => array(
                		'table' => 'coresystem:user' ,
                		'hasOne:info' => array(
                			'table' => 'coresystem:userinfo' ,
                		) ,
                		'hasOne:auser' => array(
                			'table' => 'user' ,
        		            'keys'=>array('uid','suid'),
            				'fromkeys'=>'uid',
            				'tokeys'=>'uid',
                		) ,
                		'hasMany:friends'=>array(    //一对多
                				'fromkeys'=>'uid',
                				'tokeys'=>'to',
                		        'table'=>'friends:subscription',
        		                'keys'=>array('from','to'),
                		),
                	) ,
            ) ,
	        /**
	         * 用来快速获取，判断认证信息
	         */
            'model:auser' => array(
                	'orm' => array(
                		'table' => 'user' ,
    		            'keys'=>array('uid','suid'),
                	) ,
                    'list' => true,
            ) ,
	            
		) ;
	    
	    return  $aOrm;
	}
	public function process()
	{
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
	        if($o->hasData('token') && $o->hasData('token_secret') && $o->service == "163.com" && ($o->pulltime+$o->pullnexttime) < time())
	        {
	            try{
	                $aAdapter = AdapterManager::singleton()->createApiAdapter($o->service) ;
	                $aRs = @$aAdapter->TimeLine($o->token,$o->token_secret,json_decode($o->pulldata,true));
	            }catch(AuthAdapterException $e){
	                $this->createMessage(Message::error,$e->messageSentence(),$e->messageArgvs()) ;
	                $this->messageQueue()->display() ;
	                return ;
	            }
	            
	            /**
	             * 最新一条记录的时间
	             */
	            $o->setData("pulltime",time());
	            if(empty($aRs))
	            {
	                /**
	                 * 如果没有更新到数据下次更新时间增加20%
	                 * @var unknown_type
	                 */
	                $nextTime = $o->pullnexttime +2;
	                if($nextTime > $this->maxNextTime)
	                {
	                    $nextTime = $this->maxNextTime;
	                }
	                $o->setData("pullnexttime",$nextTime);
	            }else{
	                $o->setData("pullnexttime",$this->minNextTime);
	            }
	            
	            
	            /**
	             * 插入
	             */
	            
	            for($i = 0; $i < sizeof($aRs); $i++){
	                
	                
	                /**
	                 * 把最新一条记录的数据存到oauth表中
	                 */
	                if($i == 0)
	                {
	                    $o->setData("pulldata",json_encode($aRs[$i]));
	                    $o->save() ;
	                }
	                
	                
	                //测试用户是否已经存在
	                
	                /*
	                $uid = "0";
	                $auserModelInfo = clone $this->auser->prototype()->criteria()->where();
	                $this->auser->clearData();
	                $auserModelInfo->eq('service',$o->service);
	                $auserModelInfo->eq('suid',$aRs[$i]['username']);
	                $this->auser->load($auserModelInfo);
	                
	                
	                if( $this->auser->isEmpty())
	                {
	                    $this->user->clearData();
	                    $this->user->setData("username",$aRs[$i]['username']);
	                    $this->user->setData("password",md5($aRs[$i]['username'])) ;
	                    $this->user->setData("registerTime",time()) ;
	            
	                    $this->user->setData('auser.service',$o->service);
	                    $this->user->setData('auser.suid',$aRs[$i]['username']);
	            
	                    $this->user->setData("info.nickname",$aRs[$i]['nickname']);
	                    $this->user->setData("info.avatar",md5($aRs[$i]['avatar']));
	            
	                    $this->user->child("friends")->createChild()
	                        ->setData("from",$aId->userId());
	            
	                    $this->user->save() ;
	                    $uid = $this->user->uid;
	                }else{
	                    foreach($this->auser->childIterator() as $oAuser){
	                        $uid = $oAuser->uid;
	                    }
	                }
	            
	                $aRs[$i]['uid'] = $uid;
	                $aRs[$i]['fstid'] = '0';
	                */
	                
	                /**
	                 * add feed
	                 * @example new Controller
	                 */
	            
	                /*
	                if(!empty($aRs[$i]['source']))
	                {
	                    $aRs[$i]['source']['fstid'] = '0';
	                    $aRs[$i]['source']['uid'] = $uid;
	            
	            
	                    $stateController = new CreateState($aRs[$i]['source']);
	                    $stid = $stateController->process();
	                    $aRs[$i]['fstid'] = $stid; 
	                }
	                $stateController = new CreateState($aRs[$i]);
	                $stateController->process();
	                */
	            }
	            
	        }
	    }
	    
	    
	    
	}
}

?>