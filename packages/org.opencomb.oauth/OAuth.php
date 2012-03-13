<?php 
namespace org\opencomb\oauth ;

use org\jecat\framework\lang\aop\AOP;
use org\opencomb\oauth\adapter\AdapterManager;
use org\opencomb\platform\system\PlatformSerializer;
use org\jecat\framework\ui\xhtml\weave\Patch;
use org\jecat\framework\ui\xhtml\weave\WeaveManager;
use org\opencomb\platform\ext\Extension ;

class OAuth extends Extension 
{
	/**
	 * 载入扩展
	 */
	public function load()
	{
		$aWeaveMgr = WeaveManager::singleton() ;
		$aWeaveMgr->registerTemplate( "coresystem:Login.html", '/form@0/a@0', 'oauth:user/LoginForWeiboCom.html', Patch::appendAfter ) ;
		
		// --------------------------
		// 提供给系统序列化
		PlatformSerializer::singleton()->addSystemObject(AdapterManager::singleton()) ;
		
		AOP::singleton()->register('org\\opencomb\\oauth\\aspect\\MainMenuAspect') ;
		
		//发布消息同步到weibo
		AOP::singleton()->register('org\\opencomb\\oauth\\aspect\\UserStatePushStateAspect') ;
		$aWeaveMgr->registerTemplate( 'userstate:CreateState.html', "/form@0/div@0/div@0/div@0/div@1/div@0", 'oauth:api/pushState.html', Patch::appendAfter ) ;
		
		//获取最新记录数然时候先拉取
		AOP::singleton()->register('org\\opencomb\\oauth\\aspect\\UserStatePullStateAspect') ;
		
		//转发
		$aWeaveMgr->registerTemplate( 'userstate:UserState.html', "/div@0/model:foreach@0/dl@0/dd@0/div@3/textarea@0", 'oauth:api/ForwardState.html', Patch::appendAfter ) ;
	}
}