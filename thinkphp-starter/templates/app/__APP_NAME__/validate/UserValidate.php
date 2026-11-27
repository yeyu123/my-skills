<?php
declare (strict_types = 1);

namespace app\__APP_NAME__\validate;

class UserValidate extends BaseValidate
{
    /** 验证规则（字段 => 规则）。 */
    protected $rule = [
        'username' => 'require|max:25',
        'password' => 'require|min:6',
        'email'    => 'require|email',
        'id'       => 'require|integer|gt:0',
    ];

    /** 错误提示。 */
    protected $message = [
        'username.require' => '用户名不能为空',
        'username.max'     => '用户名最多25个字符',
        'password.require' => '密码不能为空',
        'password.min'     => '密码至少6位',
        'email.require'    => '邮箱不能为空',
        'email.email'      => '邮箱格式不正确',
        'id.require'       => 'ID不能为空',
        'id.integer'       => 'ID必须是整数',
        'id.gt'            => 'ID必须大于0',
    ];

    /** 场景规则：场景名 => 需要验证的字段。 */
    protected array $sceneRules = [
        'add'    => ['username', 'password', 'email'],
        'edit'   => ['id', 'username', 'email'],
        'login'  => ['username', 'password'],
        'read'   => ['id'],
    ];
}
