<?php
declare(strict_types=1);

namespace App\Helper;

use Hashids\Hashids;

/**
 * ID加密辅助类
 *
 * @package App\Helper
 */
class HashIdsHelper
{
    /**
     * 长度
     *
     * @var int
     */
    public static $length = 10;

    /**
     * 为安全起见需要修改为自己的秘钥
     *
     * @var string
     */
    public static $secretKey = 'AqBU9zgA8EfGwVv3ghSj16n4vKS9gMtTbu';

    /**
     * @var Hashids
     */
    protected static $hashIds;

    /**
     * 加密
     *
     * @param mixed ...$numbers
     * @return string
     */
    public static function encode(...$numbers): string
    {
        return self::getHashIds()->encode(...$numbers);
    }

    /**
     * 解密
     *
     * @param string $hash
     * @return array|mixed
     * @throws \Exception
     */
    public static function decode(string $hash)
    {
        $data = self::getHashIds()->decode($hash);
        if (empty($data) || !is_array($data)) {
            throw new \Exception('解密失败');
        }

        return count($data) == 1 ? $data[0] : $data;
    }

    /**
     * 获取 HashIds 实例
     *
     * @return Hashids
     */
    private static function getHashIds(): Hashids
    {
        if (!self::$hashIds instanceof Hashids) {
            self::$hashIds = new Hashids(self::$secretKey, self::$length);
        }

        return self::$hashIds;
    }
}
