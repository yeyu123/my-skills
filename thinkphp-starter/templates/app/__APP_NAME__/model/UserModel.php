<?php
declare (strict_types = 1);

namespace app\__APP_NAME__\model;

/** 用户模型：只负责数据库查询、写入和 ORM 关系。 */
class UserModel extends BaseModel
{
    protected $name = 'user';
    protected $pk   = 'id';
}
