<?php
namespace org\opencomb\oauth\controlPanel;

use org\opencomb\coresystem\mvc\controller\ControlPanel;

use org\opencomb\coresystem\mvc\controller\Controller;
use org\opencomb\platform\ext\Extension;

/**
 * 设置appkey
 * @author gaojun
 *
 */

class OAuthItem extends ControlPanel
{
	protected $arrConfig = array(
			'view'=>array(
				'template' => 'OAuthItem.html' ,
			),
	) ;	
	
	public function process()
	{
		$aSetting = Extension::flyweight('oauth')->setting();
		
		$aOauthAppkey = array(
		    array('name'=>'163.com'),     
		    array('name'=>'douban.com'),     
		    array('name'=>'renren.com'),     
		    array('name'=>'sohu.com'),     
		    array('name'=>'t.qq.com'),     
		    array('name'=>'weibo.com'),     
        );
		
		for($i = 0; $i < sizeof($aOauthAppkey); $i++)
		{	
			if($aSetting->hasItem( $aOauthAppkey[$i]['name'], 'appKey'))
			{
				$aOauthAppkey[$i]['appKey'] = $aSetting->key( $aOauthAppkey[$i]['name'] )->item('appKey');
				$aOauthAppkey[$i]['appSecret'] = $aSetting->key( $aOauthAppkey[$i]['name'] )->item('appSecret');
				$aOauthAppkey[$i]['flag'] = $aSetting->key( $aOauthAppkey[$i]['name'] )->item('flag');
				$aOauthAppkey[$i]['display'] = $aSetting->key( $aOauthAppkey[$i]['name'] )->item('display');
			}else{
				$aOauthAppkey[$i]['appKey'] = '';
				$aOauthAppkey[$i]['appSecret'] = '';
				$aOauthAppkey[$i]['flag'] = '';
				$aOauthAppkey[$i]['display'] = '';
			}

		}
		
		$this->view()->variables()->set('aOauthAppkey',$aOauthAppkey ) ;
		
		if ($_POST)
		{
		    for($i = 0; $i < sizeof($aOauthAppkey); $i++)
		    {
		        $aSetting->key( $aOauthAppkey[$i]['name'] )->setItem('appKey',trim($_POST['appKey_text'][$aOauthAppkey[$i]['name']]));
			    $aSetting->key( $aOauthAppkey[$i]['name'] )->setItem('appSecret',trim($_POST['appSecret_text'][$aOauthAppkey[$i]['name']]));
			    $aSetting->key( $aOauthAppkey[$i]['name'] )->setItem('display', !empty($_POST['display'][$aOauthAppkey[$i]['name']]) ? :'false');
		    }
		    
		    $this->location('?c=org.opencomb.oauth.controlPanel.OAuthItem');
		};
	}
}
