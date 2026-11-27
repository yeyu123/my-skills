<?php
declare (strict_types = 1);

namespace app\__APP_NAME__\service;

use app\__APP_NAME__\model\UserModel;
use app\__APP_NAME__\validate\UserValidate;
use function error;

/** 用户服务：负责业务规则、参数校验与流程编排，数据读写统一经由 UserModel。 */
class UserService extends BaseService
{
    /** 构造参数保留为 protected；构造函数必须为 public，否则容器反射无法创建实例。 */
    protected array $params = [];

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    /** 用户列表：按 id 倒序分页，返回列表、总数与分页信息。 */
    public function paginate(array $data): array
    {
        $page  = max(1, (int) ($data['page'] ?? 1));
        $limit = min(100, max(1, (int) ($data['limit'] ?? 15)));

        $paginator = UserModel::order('id', 'desc')->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return [
            'list'  => array_map(fn (UserModel $user): array => $this->safe($user), $paginator->items()),
            'total' => $paginator->total(),
            'page'  => $paginator->currentPage(),
            'limit' => $paginator->listRows(),
        ];
    }

    /** 用户详情。 */
    public function detail(array $data): array
    {
        $data = UserValidate::instance()->verify('read', $data);
        return $this->safe($this->findOrFail((int) $data['id']));
    }

    /** 创建用户：密码使用 password_hash 存储，不返回密码字段。 */
    public function create(array $data): array
    {
        $data = UserValidate::instance()->verify('add', $data);

        $user = new UserModel();
        $user->username = $data['username'];
        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $user->email    = $data['email'];
        $user->save();

        return $this->detail(['id' => $user->id]);
    }

    /** 修改用户：使用 edit 场景校验；密码需通过独立流程重置。 */
    public function update(array $data): array
    {
        $data = UserValidate::instance()->verify('edit', $data);

        $user = $this->findOrFail((int) $data['id']);
        $user->username = $data['username'];
        $user->email    = $data['email'];
        $user->save();

        return $this->detail(['id' => $user->id]);
    }

    /** 删除用户：BaseModel 启用软删除，delete() 只写 delete_time。 */
    public function delete(array $data): array
    {
        $data = UserValidate::instance()->verify('read', $data);
        $this->findOrFail((int) $data['id'])->delete();

        return ['id' => (int) $data['id'], 'deleted' => true];
    }

    /** 登录校验：返回可用于签发 Token 的用户信息；密码错误不区分用户是否存在。 */
    public function login(array $data): array
    {
        $data = UserValidate::instance()->verify('login', $data);

        $user = UserModel::where('username', $data['username'])->find();
        if ($user === null || !password_verify($data['password'], (string) $user->password)) {
            error(['username' => $data['username']], '用户名或密码错误', 401);
        }
        if ((int) $user->status !== 1) {
            error(['username' => $data['username']], '账号已被禁用', 403);
        }

        return [
            'id'       => (int) $user->id,
            'username' => (string) $user->username,
            'email'    => (string) $user->email,
            'status'   => (int) $user->status,
        ];
    }

    /** 按主键查询，不存在时抛出 404。 */
    protected function findOrFail(int $id): UserModel
    {
        $user = UserModel::find($id);
        if ($user === null) {
            error(['id' => $id], '用户不存在', 404);
        }

        return $user;
    }

    /** 输出前移除敏感字段。 */
    protected function safe(UserModel $user): array
    {
        $data = $user->toArray();
        unset($data['password'], $data['delete_time']);

        return $data;
    }
}
