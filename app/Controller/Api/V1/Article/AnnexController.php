<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Article;

use App\Constant\FileDriveConstant;
use App\Controller\Api\V1\CController;
use App\Helper\DateHelper;
use App\Model\Article\ArticleAnnex;
use App\Service\ArticleService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use League\Flysystem\Filesystem;
use Psr\Http\Message\ResponseInterface;

/**
 * Class AnnexController
 *
 * @Controller(prefix="/api/v1/note/annex")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1\Article
 */
class AnnexController extends CController
{
    /**
     * @var ArticleService
     */
    private $articleService;

    public function __construct(ArticleService $service)
    {
        parent::__construct();

        $this->articleService = $service;
    }

    /**
     * 上传附件
     *
     * @RequestMapping(path="upload", methods="post")
     */
    public function upload(Filesystem $filesystem): ResponseInterface
    {
        $params = $this->request->inputs(['article_id']);

        $this->validate($params, [
            'article_id' => 'required|integer|min:0'
        ]);

        $file = $this->request->file('annex');
        if (!$file || !$file->isValid()) {
            return $this->response->fail('上传文件验证失败！');
        }

        $annex = [
            'suffix'        => pathinfo($file->getClientFilename(), PATHINFO_EXTENSION),
            'size'          => $file->getSize(),
            'drive'         => FileDriveConstant::Local,
            'original_name' => $file->getClientFilename()
        ];

        $annex['path'] = 'private/files/notes/' . date('Ymd') . '/' . "[{$annex['suffix']}]" . create_random_filename('tmp');

        try {
            $filesystem->write($annex['path'], file_get_contents($file->getRealPath()));
        } catch (\Exception $e) {
            return $this->response->fail();
        }

        $annex['id'] = $this->articleService->insertArticleAnnex($this->uid(), (int)$params['article_id'], $annex);

        if (!$annex['id']) {
            return $this->response->fail('附件上传失败，请稍后再试！');
        }

        return $this->response->success($annex);
    }

    /**
     * 删除附件
     *
     * @RequestMapping(path="delete", methods="post")
     */
    public function delete(): ResponseInterface
    {
        $params = $this->request->inputs(['annex_id']);

        $this->validate($params, [
            'annex_id' => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->updateArticleAnnexStatus($this->uid(), (int)$params['annex_id'], 2);

        return $isTrue ? $this->response->success() : $this->response->fail();
    }

    /**
     * 恢复已删除附件
     *
     * @RequestMapping(path="recover", methods="post")
     */
    public function recover(): ResponseInterface
    {
        $params = $this->request->inputs(['annex_id']);

        $this->validate($params, [
            'annex_id' => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->updateArticleAnnexStatus($this->uid(), (int)$params['annex_id'], 1);

        return $isTrue ? $this->response->success() : $this->response->fail();
    }

    /**
     * 附件回收站列表
     *
     * @RequestMapping(path="recover/list", methods="get")
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function recoverList(): ResponseInterface
    {
        $rows = $this->articleService->recoverAnnexList($this->uid());

        if ($rows) {
            $getDay = function ($delete_at) {
                $last_time = strtotime('+30 days', strtotime($delete_at));
                return (time() > $last_time) ? 0 : DateHelper::diff(date('Y-m-d', $last_time), date('Y-m-d'));
            };

            array_walk($rows, function (&$item) use ($getDay) {
                $item['day']     = $getDay($item['deleted_at']);
                $item['visible'] = false;
            });
        }

        return $this->response->success(['rows' => $rows]);
    }

    /**
     * 永久删除附件
     *
     * @RequestMapping(path="forever/delete", methods="post")
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function foreverDelete(): ResponseInterface
    {
        $params = $this->request->inputs(['annex_id']);

        $this->validate($params, [
            'annex_id' => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->foreverDelAnnex($this->uid(), (int)$params['annex_id']);

        return $isTrue ? $this->response->success() : $this->response->fail();
    }

    /**
     * 下载附件
     *
     * @RequestMapping(path="download", methods="get")
     */
    public function download(\Hyperf\HttpServer\Contract\ResponseInterface $response, Filesystem $filesystem)
    {
        $params = $this->request->inputs(['annex_id']);

        $this->validate($params, [
            'annex_id' => 'required|integer'
        ]);

        /** @var ArticleAnnex $info */
        $info = ArticleAnnex::select(['path', 'original_name', 'drive'])->where('id', $params['annex_id'])->where('user_id', $this->uid())->first();

        if (!$info || !$filesystem->has($info->path)) {
            return $this->response->fail('文件不存在或没有下载权限！');
        }

        switch ($info->drive) {
            case FileDriveConstant::Local:
                return $response->download($this->getFilePath($info->path), $info->original_name);
            case FileDriveConstant::Cos:
                return $this->response->fail('文件驱动不存在！');
            default:
                break;
        }
    }

    private function getFilePath(string $path)
    {
        return di()->get(Filesystem::class)->getConfig()->get('root') . '/' . $path;
    }
}
