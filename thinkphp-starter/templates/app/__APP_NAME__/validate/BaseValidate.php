<?php
declare (strict_types = 1);

namespace app\__APP_NAME__\validate;

use think\Validate;
use function error;

/**
 * 验证器基类。
 * 子类只需声明 $rule、$message、$sceneRules，
 * 即可通过 XxxValidate::instance()->verify('scene') 按场景验证。
 * 验证失败统一通过 error() 抛出 ApiException，由全局异常处理器渲染成统一 JSON。
 */
abstract class BaseValidate extends Validate
{
    /** 场景规则：场景名 => 需要验证的字段，子类覆写。 */
    protected array $sceneRules = [];

    /** 每个子类独立的单例，避免父子类共享同一实例。 */
    protected static array $instances = [];

    /** 获取当前验证器单例。 */
    public static function instance(): static
    {
        return static::$instances[static::class] ??= new static();
    }

    /**
     * 按场景验证。
     * @param string $scene 场景名，对应子类 $sceneRules 的键
     * @param array  $data  待验证数据，不传则取 request()->param()
     * @return array 验证通过且只包含该场景字段的数据
     */
    public function verify(string $scene, array $data = []): array
    {
        if (!isset($this->sceneRules[$scene])) {
            error(['scene' => $scene], "验证场景 [{$scene}] 未定义", 422);
        }

        $fields = $this->sceneRules[$scene];
        $data   = $data ?: request()->param();

        // 只保留场景需要的字段，控制器拿到的是干净入参。
        $data = array_intersect_key($data, array_flip($fields));

        // 限定 check() 只校验当前场景字段，避免其他字段规则被触发。
        $this->only($fields);

        if (!$this->check($data)) {
            error(null, $this->getError(), 422);
        }

        return $data;
    }
}
