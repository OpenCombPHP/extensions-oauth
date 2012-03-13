<?php
namespace org\opencomb\oauth\aspect;

use org\opencomb\oauth\api\PullState;

use org\opencomb\oauth\api\PushState;

use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\lang\aop\jointpoint\JointPointMethodDefine;

class UserStatePullStateAspect
{
	/**
	 * @pointcut
	 */
	public function pointcutUserStatePullStateAspect()
	{
		return array(
			new JointPointMethodDefine('org\\opencomb\\userstate\\NewStateNumber','process') ,
		) ;
	}
	
	/**
	 * @advice around
	 * @for pointcutUserStatePullStateAspect
	 */
	private function process()
	{
        $oAuth = new PullState();
        $oAuth->process();
		
		// 调用原始原始函数
		aop_call_origin() ;
	}
}
?>