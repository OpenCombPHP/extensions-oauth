<?php 
namespace org\opencomb\oauth ;

use org\opencomb\coresystem\mvc\controller\ControlPanel;

use org\jecat\framework\lang\aop\AOP;

use org\opencomb\oauth\adapter\AdapterManager;
use org\opencomb\platform\service\ServiceSerializer;
use org\jecat\framework\ui\xhtml\weave\Patch;
use org\jecat\framework\ui\xhtml\weave\WeaveManager;
use org\opencomb\platform\ext\Extension;
use org\opencomb\platform\mvc\view\widget\Menu;

class OAuth extends Extension 
{
	/**
	 * 载入扩展
	 */
	public function load()
	{
		// --------------------------
		// 提供给系统序列化
		ServiceSerializer::singleton()->addSystemObject(AdapterManager::singleton()) ;

		// 注册菜单build事件的处理函数
		ControlPanel::registerMenuHandler( array(__CLASS__,'buildControlPanelMenu') ) ;
	}

	public function initRegisterUITemplateWeave(WeaveManager $aWeaveMgr)
	{
		$aWeaveMgr->registerTemplate( "coresystem:user/Login.html", '/div@0', 'oauth:user/LoginOAuth.html', Patch::appendAfter ) ;
	}
	
	static public function buildControlPanelMenu(array & $arrConfig)
	{
		// 合并配置数组，增加菜单
		$arrConfig['item']['system']['item']['platform-manage']['item']['oauth']=array (
		  		'title'=>'OAuth设置' ,
		  		'controller' => 'org.opencomb.oauth.controlPanel.OAuthSetting' ,
		) ;
	}
}
