<?php 
namespace org\opencomb\oauth ;

use org\jecat\framework\lang\aop\AOP;
use org\opencomb\oauth\adapter\AdapterManager;
use org\opencomb\platform\service\ServiceSerializer;
use org\jecat\framework\ui\xhtml\weave\Patch;
use org\jecat\framework\ui\xhtml\weave\WeaveManager;
use org\opencomb\platform\ext\Extension ;
use org\opencomb\platform\mvc\view\widget\Menu;

class OAuth extends Extension 
{
	/**
	 * 载入扩展
	 */
	public function load()
	{
		$aWeaveMgr = WeaveManager::singleton() ;
		$aWeaveMgr->registerTemplate( "coresystem:Login.html", '/div@0/form@0/a@0', 'oauth:user/LoginForWeiboCom.html', Patch::appendAfter ) ;
		
		// --------------------------
		// 提供给系统序列化
		ServiceSerializer::singleton()->addSystemObject(AdapterManager::singleton()) ;
		
		

		// 注册菜单build事件的处理函数
		Menu::registerBuildHandle(
				'org\\opencomb\\coresystem\\mvc\\controller\\ControlPanelFrame'
				, 'frameView'
				, 'mainMenu'
				, array(__CLASS__,'buildControlPanelMenu')
		) ;
	}
	
	static public function buildControlPanelMenu(array & $arrConfig)
	{
		// 合并配置数组，增加菜单
		$arrConfig['item:system']['item:platform-manage']['item:oauth']=array (
		  		'title'=>'OAuth' ,
		  		'link' => '?c=org.opencomb.oauth.controlPanel.OAuthSetting' ,
		  		'query' => 'c=org.opencomb.oauth.controlPanel.OAuthSetting' ,
		) ;
	}
}