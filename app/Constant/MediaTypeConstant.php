<?php
declare(strict_types=1);

namespace App\Constant;

/**
 * Class MediaTypeConstant
 *
 * @package App\Constants
 */
class MediaTypeConstant
{
    const FILE_IMAGE = 1; //图片文件
    const FILE_VIDEO = 2; //视频文件
    const FILE_AUDIO = 3; //音频文件
    const FILE_OTHER = 4; //其它文件

    const FILE_TYPES = [
        'gif'  => self::FILE_IMAGE,
        'jpg'  => self::FILE_IMAGE,
        'jpeg' => self::FILE_IMAGE,
        'png'  => self::FILE_IMAGE,
        'webp' => self::FILE_IMAGE,
        'ogg'  => self::FILE_VIDEO,
        'mp3'  => self::FILE_VIDEO,
        'wav'  => self::FILE_VIDEO,
        'mp4'  => self::FILE_AUDIO,
        'webm' => self::FILE_AUDIO,
    ];

    /**
     * 获取媒体文件的类型
     *
     * @param string $ext 文件后缀
     * @return int
     */
    public static function getMediaType(string $ext): int
    {
        return self::FILE_TYPES[$ext] ?? self::FILE_OTHER;
    }
}
