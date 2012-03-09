<?php
namespace org\opencomb\oauth\aspect;

use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\lang\aop\jointpoint\JointPointMethodDefine;

class MainMenuAspect
{
	/**
	 * @pointcut
	 */
	public function pointcutCreateBeanConfig()
	{
		return array(
			new JointPointMethodDefine('org\\opencomb\\coresystem\\mvc\\controller\\ControlPanelFrame','createBeanConfig') ,
		) ;
	}
	
	/**
	 * @advice around
	 * @for pointcutCreateBeanConfig
	 */
	private function createBeanConfig()
	{
		// 调用原始原始函数
		$arrConfig = aop_call_origin() ;
		/*
		// 合并配置数组，增加菜单
		BeanFactory::mergeConfig(
				$arrConfig['frameview:frameView']['widget:mainMenu']['items']['system']['menu']['items']['platform-manage']
				['menu']['items'],$arrMenus
		) ;
		*/
		$arrConfig['frameview:frameView']['widget:mainMenu']['item:system']['item:platform-manage']['item:oauth']=array (
					  		'title'=>'OAuth' ,
					  		'link' => '?c=org.opencomb.oauth.controlPanel.OAuthSetting' ,
					  		'query' => 'c=org.opencomb.oauth.controlPanel.OAuthSetting' ,
					  );
		return $arrConfig ;
	}
}
?>