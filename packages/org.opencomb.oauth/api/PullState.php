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
                			'table' => 'oauth:user' ,
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
	        if($o->hasData('token') && $o->hasData('token_secret') && ($o->pulltime+$o->pullnexttime) < time() && $o->service == "t.qq.com" )
	        {
	            
	            //echo "<pre>";print_r("拉取:".$o->service);echo "</pre>";
	            try{
	                $aAdapter = AdapterManager::singleton()->createApiAdapter($o->service) ;
	                $aRs = @$aAdapter->createTimeLineMulti($o,json_decode($o->pulldata,true));
	            }catch(AuthAdapterException $e){
	                $this->createMessage(Message::error,$e->messageSentence(),$e->messageArgvs()) ;
	                $this->messageQueue()->display() ;
	                return ;
	            }
	        }else{
	            //echo "<pre>";print_r("时间未到:".$o->service);echo "</pre>";
	        }
	    }
	    
	    $OAuthCommon = new OAuthCommon("",  "");
	    $aRsT = $OAuthCommon -> multi_exec();
	    
// 	    echo "<pre>";print_r($aRsT['qzone.qq.com']);echo "</pre>";
// 	    echo "<pre>";print_r(json_decode($aRsT['weibo.com'],true));echo "</pre>";
// 	    echo "<pre>";print_r(json_decode($aRsT['163.com'],true));echo "</pre>";
//   	echo "<pre>";print_r(json_decode($aRsT['t.qq.com'],true));echo "</pre>";
// 	    echo "<pre>";print_r(json_decode($aRsT['renren.com'],true));echo "</pre>";
// 	    echo "<pre>";print_r(json_decode($aRsT['douban.com'],true));echo "</pre>";
// 	    echo "<pre>";print_r(json_decode($aRsT['sohu.com'],true));echo "</pre>";
	    
	    foreach($this->auser->childIterator() as $o)
	    {
	        if(!empty($aRsT[$o->service]))
	        {
	            $aAdapter = AdapterManager::singleton()->createApiAdapter($o->service) ;
	            
	            $aRs = @$aAdapter->filterTimeLine($o->token,$o->token_secret,$aRsT[$o->service],json_decode($o->pulldata,true));
	            
	            echo "<pre>";print_r($aRs);echo "</pre>";
	            
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
	                }
	            
	                //测试用户是否已经存在
	                $uid = $this->checkUid($aRs[$i],$o->service);
	            
	                $aRs[$i]['uid'] = $uid;
	                $aRs[$i]['forwardtid'] = '0';
	                $aRs[$i]['stid'] = $o->service."|".$aRs[$i]['id']."|".$uid;
	                $aRs[$i]['service'] = $o->service;

	                /**
	                 * add feed
	                 * @example new Controller
	                 */
	                if(!empty($aRs[$i]['source']))
	                {
	                    $sourceUid = $this->checkUid($aRs[$i]['source'],$o->service);
	                    $aRs[$i]['source']['forwardtid'] = '0';
	                    $aRs[$i]['source']['uid'] = $sourceUid;
	                    $aRs[$i]['source']['stid'] = $o->service."|".$aRs[$i]['source']['id']."|".$sourceUid;
	                    $aRs[$i]['source']['service'] = $o->service;
	            
	                    if($uid)
	                    {
    	                    $stateController = new CreateState($aRs[$i]['source']);
    	                    $stid = $stateController->process();
	                    }
	                    
	                    $aRs[$i]['forwardtid'] = $stid;
	                }
	                
	                if($uid)
	                {
	                    $stateController = new CreateState($aRs[$i]);
	                    $stateController->process();
	                }
	            }
	            
	            $o->save() ;
	        }
	    }
	    
	}
	
	/**
	 * 测试用户是否存在，不存在就创建
	 * @param unknown_type $aUserInfo
	 * @param unknown_type $service
	 */
	public function checkUid($aUserInfo,$service)
	{
	    if(empty($aUserInfo['username']))
	    {
	        return false;
	    }
	    
	    $aId = IdManager::singleton()->currentId() ;
	    $auserModelInfo = clone $this->auser->prototype()->criteria()->where();
	    $this->auser->clearData();
	    $auserModelInfo->eq('service',$service);
	    $auserModelInfo->eq('suid',$aUserInfo['username']);
	    $this->auser->load($auserModelInfo);
	    
	    if( $this->auser->isEmpty())
	    {
	        $this->user->clearData();
	        $this->user->setData("username",$service."#".$aUserInfo['username']);
	        $this->user->setData("password","") ;
	        $this->user->setData("registerTime",time()) ;
	    
	        $this->user->setData('auser.service',$service);
	        $this->user->setData('auser.suid',$aUserInfo['username']);
	    
	        $this->user->setData("info.nickname",$aUserInfo['username']);
	        $this->user->setData("info.avatar",$aUserInfo['avatar']);
	    
	        $this->user->child("friends")->createChild()
	        ->setData("from",$aId->userId());
	    
	        $this->user->save() ;
	        $uid = $this->user->uid;
	    }else{
	        foreach($this->auser->childIterator() as $oAuser){
	            $uid = $oAuser->uid;
	        }
	    }
	    return $uid;
	} 
	
}

?>