<?php
declare (strict_types = 1);

namespace app\__APP_NAME__\controller;

use app\__APP_NAME__\common\RedisManager;
use app\__APP_NAME__\service\UserService;
use think\response\Json;

/** 用户接口：只读取参数、调用 Service 并返回统一响应。 */
class Users extends __APP_NAME__Base
{
    /** 用户列表及分页。 */
    public function index(): Json
    {
        return success(UserService::instance()->paginate($this->request->param()));
    }

    /** 用户详情。 */
    public function read(): Json
    {
        return success(UserService::instance()->detail($this->request->param()));
    }

    /** 创建用户。 */
    public function create(): Json
    {
        return success(UserService::instance()->create($this->request->param()), '创建成功');
    }

    /** 修改用户。 */
    public function update(): Json
    {
        return success(UserService::instance()->update($this->request->param()), '更新成功');
    }

    /** 删除用户。 */
    public function delete(): Json
    {
        return success(UserService::instance()->delete($this->request->param()), '删除成功');
    }

    /** 防抖示例：同一用户 5 秒内只允许执行一次。 */
    public function debounce(): Json
    {
        $userId = (int) $this->request->param('user_id', 1);
        $key    = 'user:debounce:' . $userId;
        $redis  = new RedisManager();

        if (!$redis->setNxEx($key, 1, 5)) {
            return success(['debounced' => true], '操作过于频繁，请稍后再试', 429);
        }

        return success(['debounced' => false, 'user_id' => $userId], '防抖校验通过');
    }
}
