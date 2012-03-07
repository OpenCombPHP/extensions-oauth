<?php
namespace org\opencomb\oauth\aspect;

use org\opencomb\oauth\api\PushState;

use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\lang\aop\jointpoint\JointPointMethodDefine;

class WowneiPushStateAspect
{
	/**
	 * @pointcut
	 */
	public function pointcutWowneiPushState()
	{
		return array(
			new JointPointMethodDefine('org\\opencomb\\coresystem\\mvc\\controller\\ControlPanelFrame','process') ,
		) ;
	}
	
	/**
	 * @advice around
	 * @for pointcutWowneiPushState
	 */
	private function process()
	{
		// 调用原始原始函数
		aop_call_origin() ;
		
			
		/**
		 * push weibo
		 * @var unknown_type
		 */
		$aWeibo = $this->params['pushweibo'];
		if($aWeibo)
		{
		    $aParams = array(
		            'service'=>$aWeibo,
		            'title'=>$this->params['body'],
		    );
		    $oOauthPush = new PushState($aParams);
		    $oOauthPush->process();
		}
	}
}
?>