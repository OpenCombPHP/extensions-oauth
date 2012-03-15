<?php
namespace org\opencomb\oauth\api ;

use org\jecat\framework\system\Application;

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

class PullComment extends Controller
{
//     private $minNextTime = 4;
//     private $maxNextTime = 20;
    
	public function createBeanConfig()
	{
	    $aOrm = array(
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
            ) ,
	    		
            'model:state' => array(
                	'orm' => array(
                		'table' => 'userstate:state' ,
    		            'keys'=>array('stid'),
                		'hasMany:ostate'=>array(
								'fromkeys'=>'stid',
								'tokeys'=>'stid',
								'table'=>'oauth:state',
                				'keys'=>array('stid','service'),
							),
                		
                	) ,
            ) ,
    		'model:stateauther' => array(
    				'orm' => array(
    						'table' => 'oauth:user' ,
    						'keys'=>array('uid','suid'),
    				) ,
    		) ,
		) ;
	    
	    return  $aOrm;
	}
	public function process()
	{
		
	    if(!$aId =IdManager::singleton()->currentId())
	    {
	    	$this->messageQueue ()->create ( Message::error, "请先登录" );
	        return;
	    }
	    if(!$this->params->has('tid')){
	    	$this->messageQueue ()->create ( Message::error, "缺少信息,无法找到评论" );
	    	return;
	    }
	    
	    $this->state->load($this->params->get('tid'));

	    $aSetting = Application::singleton()->extensions()->extension('comment')->setting() ;
	    $nWaitTime = (int)$aSetting->item('/commentFromOtherWeb','commentTime',300) ;
	    //有几个网站存在这个state就拉几个网站的评论
	    foreach($this->state->child('ostate')->childIterator() as $ostate){
	    	//需要新的评论,结果却没有到该拉评论的时间限制
    		if( !$this->params->has('oldcommentes') && ($ostate['pullcommenttime'] + $nWaitTime) > time() )
    		{
    			continue;
    		}
    		
    		//state的作者信息,包括在本站的和在对方网站上的,目前只有renren需要
    		$auther = null ;
    		if($ostate['service'] == 'renren.com'){
    			$aWhere = clone $this->stateauther->prototype()->criteria()->where();
    			$aWhere->eq('uid',$this->state['uid']);
    			$aWhere->eq('service',$ostate['service']);
    			$this->stateauther->load($aWhere);
    			$auther = $this->stateauther;
    		}
    		
    		//取一个用户的认证来拉取评论,挑最早用过的,避免超对方网站限制
    		$auserModelWhere = clone $this->auser->prototype()->criteria()->where();
    		$auserModelWhere->eq('service',$ostate['service']);
    		$auserModelWhere->eq('valid',1);
    		$auserModelWhere->ne('token','');
    		if(!$this->auser->load($auserModelWhere)){
    			continue;
    		}
			try{
				
				$aAdapter = AdapterManager::singleton()->createApiAdapter($ostate['service']) ;
				//$this->params->has('oldcommentes')请求最近的评论还是旧评论
				$aRs = @$aAdapter->createPullCommentMulti($this->auser, $ostate ,array(), $auther);

			}catch(AuthAdapterException $e){
				$this->createMessage(Message::error,$e->messageSentence(),$e->messageArgvs()) ;
				$this->messageQueue()->display() ;
				return ;
			}
    		
		    $OAuthCommon = new OAuthCommon("",  "");
		    $aRsT = $OAuthCommon -> multi_exec();
		    
		    print_r($aRsT);exit;
		    $this->saveComment($aRsT);
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
	        $this->user->setData("password",md5($service."#".$aUserInfo['username'])) ;
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
	
	static public function commentCount(){
		try{
			$aAdapter = AdapterManager::singleton()->createApiAdapter($ostate['service']) ;
			//$this->params->has('oldcommentes')请求最近的评论还是旧评论
			$aRs = @$aAdapter->createCommentCountMulti($this->auser, $ostate );
		}catch(AuthAdapterException $e){
			$this->createMessage(Message::error,$e->messageSentence(),$e->messageArgvs()) ;
			$this->messageQueue()->display() ;
			return ;
		}
		
		$OAuthCommon = new OAuthCommon("",  "");
		$aRsT = $OAuthCommon -> multi_exec();
	}
	
	public function saveComment($aRsT){
		foreach($this->auser->childIterator() as $o)
		{
			if(!empty($aRsT[$o->service]))
			{
				$aAdapter = AdapterManager::singleton()->createApiAdapter($o->service) ;
		
				$aRs = @$aAdapter->filterTimeLine($o->token,$o->token_secret,$aRsT[$o->service],json_decode($o->pulldata,true));
		
				//echo "<pre>";print_r($aRs);echo "</pre>";
				/**
				 * 最新一条记录的时间
				 */
				$o->setData("pulltime",time());
				if(empty($aRs))
				{
					/**
					 * 如果没有更新到数据下次更新时间增加
					 * @var unknown_type
					 */
					$nextTime = $o->pullnexttime +4;
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
}
?>