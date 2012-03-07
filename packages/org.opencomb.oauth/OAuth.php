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
		AOP::singleton()->register('org\\opencomb\\oauth\\aspect\\WowneiPushStateAspect') ;
		$aWeaveMgr->registerTemplate( 'userstate:CreateState.html', "/form@0/div@0/div@0/div@0/div@1/div@0", 'oauth:api/pushState.html', Patch::appendAfter ) ;
	}
}