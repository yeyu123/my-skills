<?php
declare (strict_types = 1);

namespace app\__APP_NAME__\model;

use think\Model;
use think\model\concern\SoftDelete;

/** 模型基类：统一启用软删除。 */
class BaseModel extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}
