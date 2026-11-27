<?php
declare (strict_types = 1);

namespace app\__APP_NAME__\controller;

use app\BaseController;
use think\App;

/**
 * __APP_NAME__ 应用的控制器基类。
 * 负责登录人信息、请求上下文等初始化，不写具体业务。
 */
class __APP_NAME__Base extends BaseController
{
    /** 当前登录人信息。 */
    protected $userInfo;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->verify();
    }

    /** 请求上下文初始化，可在此记录日志或补充登录人信息。 */
    private function verify(): void
    {
        $header     = $this->request->header();
        $controller = $this->request->controller();
        $action     = $this->request->action();
    }
}
