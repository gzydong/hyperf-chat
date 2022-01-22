<?php
declare(strict_types=1);

namespace App\Helper;

/**
 * 字符串助手类
 * Class StringHelper
 *
 * @package App\Helper
 */
class StringHelper
{
    /**
     * 替换文本中的url 为 a标签
     *
     * @param string $str 字符串
     * @return null|string|string[]
     */
    public static function formatUrlLink(string $str)
    {
        $re = '@((https|http)?://([-\w\.]+)+(:\d+)?(/([\w/_\-.#%]*(\?\S+)?)?)?)@';
        return preg_replace_callback($re, function ($matches) {
            return sprintf('<a href="%s" target="_blank">%s</a>', trim($matches[0], '&quot;'), $matches[0]);
        }, $str);
    }

    /**
     * 从HTML文本中提取所有图片
     *
     * @param string $content HTML文本
     * @return array
     */
    public static function getHtmlImage(string $content): array
    {
        $pattern = "/<img.*?src=[\'|\"](.*?)[\'|\"].*?[\/]?>/";
        preg_match_all($pattern, htmlspecialchars_decode($content), $match);
        $data = [];
        if (!empty($match[1])) {
            foreach ($match[1] as $img) {
                if (!empty($img)) $data[] = $img;
            }
            return $data;
        }

        return $data;
    }
}
