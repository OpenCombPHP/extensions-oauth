<?php
namespace org\opencomb\oauth\aspect;

use org\jecat\framework\mvc\controller\Request;

use org\opencomb\oauth\api\PushState;

use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\lang\aop\jointpoint\JointPointMethodDefine;

class UserStateForwardStateAspect
{
	/**
	 * @pointcut
	 */
	public function pointcutUserStateForwardStateAspect()
	{
		return array(
			new JointPointMethodDefine('org\\opencomb\\userstate\\CreateState','process') ,
		) ;
	}
	
	/**
	 * @advice around
	 * @for pointcutUserStateForwardStateAspect
	 */
	private function process()
	{
		
		// 调用原始原始函数
		$stid = aop_call_origin() ;
		
		
		/**
		 * push weibo
		 * @var unknown_type
		 */
		
	    if( Request::isUserRequest($this->params) )
	    {
	        if(is_array($this->params['pushweibo']))
	        {
	            $aWeibo = $this->params['pushweibo'];
	        }else{
	            $aWeibo = explode(",", $this->params['pushweibo']);
	        }
	        
	        
	        if($aWeibo)
	        {
	            $aParams = array(
	                    'service'=>$aWeibo,
	                    'title'=>$this->params['body'],
	                    'stid'=>$stid,
	            );
	            $oOauthPush = new PushState($aParams);
	            $oOauthPush->process();
	        }
	    }
		
	}
}
?>