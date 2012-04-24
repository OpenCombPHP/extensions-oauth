<?php
namespace org\opencomb\oauth\controlPanel ;

// use org\jecat\framework\message\Message;
// use org\jecat\framework\setting\Setting;
// use org\opencomb\platform\service\Service;
use org\jecat\framework\verifier\Length;

use org\opencomb\platform\ext\Extension;
use org\opencomb\oauth\adapter\AdapterManager;
use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\jecat\framework\message\Message;


class OAuthSetting extends ControlPanel
{
	public function createBeanConfig()
	{
		return array(
			'view:authSetting' => array(
				'template' => 'OAuthSetting.html' ,
				'class' => 'form' ,
			)
		) ;
	}
	
	public function process()
	{
		$aSetting = Extension::flyweight('oauth')->setting();//->key("/merge/uiweave/{$sNamespace}/{$sTemplate}",true) ;
// 		$arrPatchs = $aKey->item('arrPatchs','ddd'array()) ;
		$bIsSubmiting = $this->authSetting->isSubmit() ;
		$arrl=array();
		/*
		echo "<pre>";
		var_dump($this->params['stat1']);
		echo "<pre/>";
		*/
		if($bIsSubmiting) {
			foreach ($this->params['stat1'] as $key=>$value)
			{
			
				$aSetting->setItem($key,'appKey',trim($this->verification($value,$key)));
		
			}
			foreach ($this->params['stat2'] as $key=>$value)
			{
				
				$aSetting->setItem($key,'appSecret',trim($this->verification($value,$key)));
		
			}
			foreach ($this->params['stat3'] as $key=>$value)
			{
				$aSetting->setItem($key,'flag',$value);
		
			}
			/*
			 echo "<pre>";
			echo "sssssss";
			print_r($this->params['stat']);
			print_r($this->params['stat1']);
			//print_r($this->params['stat']);
			echo "<pre/>";
			*/
		}
		foreach(AdapterManager::singleton()->arrAdapteeConfigs as $value)
		{
			$arrb = array();
			$arrb['name']=$value['name'];
			$arrb['domain']=$value['url'];
			$akey=$aSetting->key('/'.$value['url'],true);
			$arrb['appkey'] = $akey->item('appKey',"1");
			$arrb['appSecret'] = $akey->item('appSecret',"1");
			$arrb['flag'] = $akey->item('flag',"1");
			$arrl[] = $arrb;
		}
		
		//print_r($arrl);
	
		//var_dump($arr);
		
// 		$aSetting = Setting::singleton() ;
// 		$arrStats = $this->stats() ;
		
// 		$bIsSubmiting = $this->authSetting->isSubmit() ;
		
		
// 		foreach($arrStats as $sPath=>&$arrItemList)
// 		{
// 			foreach($arrItemList as $sItemName=>&$arrItem)
// 			{
// 				// 加载状态
// 				$arrItem['setting'] = (bool)$aSetting->item($sPath,$sItemName) ;
				
// 				// 保存内状态
// 				if($bIsSubmiting)
// 				{
// 					$bSubmitItemValue = empty($this->params['stat'][$sPath][$sItemName])? (!$arrItem['value']): $arrItem['value'] ; 
					
// 					if($arrItem['setting']!=$bSubmitItemValue)
// 					{
// 						$arrItem['setting'] = $bSubmitItemValue ;
// 						$aSetting->setItem($sPath,$sItemName,$bSubmitItemValue) ;
						
// 						$this->authSetting->createMessage(Message::success,"系统状态 %s 已经保存",array($arrItem['title'])) ;
// 					}
// 				}
				
// 				$arrItem['checked'] = ($arrItem['setting'] == $arrItem['value']) ;
// 			}
// 		}
		//建立网页和数组之间的链接关系
 		$this->authSetting->variables()->set('arrStats',$arrl) ;
 		
	}
	
	//页面数值验证
	public function verification($value,$key) {
		echo $value;
		if(empty($value))
		{
			$this->authSetting->createMessage(Message::error,"%s key不能为空",$key) ;
			return ;
			echo !$value;
			//exit(0);
		}
		else if(strlen($value)<2 || strlen($value)>25) {
			$this->authSetting->createMessage(Message::error,"%s 输入key值不足",$key) ;
			return ;
			echo !$value;
			//exit(0);
		}
		else {
// 			var_dump($value);
			echo $value;
			return $value;
			}
		}
}
