<?php


namespace App\Controller\Common;
use Qiniu\Auth;
use ZYProSoft\Controller\AbstractController;
use ZYProSoft\Http\AuthedRequest;
use App\Service\PostService;
use  Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;

/**
 * @AutoController (prefix="/common/post")
 * Class PostController
 * @package App\Controller\Common
 */
class PostController extends AbstractController
{
    /**
     * @Inject
     * @var PostService
     */
    private PostService $service;

    public function create(AuthedRequest $request)
    {
        $this->validate([
            'title' => 'string|required|min:1|max:40|sensitive',
            'content' => 'string|required|min:10|max:5000|sensitive',
            'imageList' => 'array|min:1|max:4',
            'link' => 'string|min:1|max:500',
            'vote' => 'array|min:1',
            'vote.subject' => 'string|required_with:vote|min:1|max:32|sensitive',
            'vote.items.*.content' => 'string|required_with:vote|min:1|max:32|sensitive'
        ]);
        $params = $request->getParams();
        $result = $this->service->create($params);
        return $this->success($result);
    }

    public function update(AuthedRequest $request)
    {
        $this->validate([
            'postId' => 'integer|required|exists:post,post_id',
            'title' => 'string|min:1|max:32|sensitive',
            'content' => 'string|min:10|max:5000|sensitive',
            'imageList' => 'array|min:1|max:4',
            'link' => 'string|min:1|max:500|sensitive',
        ]);
        $params = $request->getParams();
        $postId = $request->param('postId');
        $result = $this->service->update($postId, $params);
        return $this->success($result);
    }

    public function vote(AuthedRequest $request)
    {
        $this->validate([
            'postId' => 'integer|required|exists:post,post_id',
            'voteId' => 'integer|required|exists:vote,vote_id',
            'voteItemId' => 'integer|required|exists:vote_item,vote_item_id'
        ]);
        $postId = $request->param('postId');
        $voteItemId = $request->param('voteItemId');
        $voteId = $request->param('voteId');
        $result = $this->service->vote($voteItemId, $postId, $voteId);
        return $this->success($result);
    }

    public function voteDetail()
    {
        $this->validate([
            'voteId' => 'integer|required|exists:vote,vote_id',
        ]);
        $voteId = $this->request->param('voteId');
        $result = $this->service->voteDetail($voteId);
        return $this->success($result);
    }

    public function detail()
    {
        $this->validate([
            'postId' => 'integer|required|exists:post,post_id',
        ]);
        $postId = $this->request->param('postId');
        $result = $this->service->detail($postId);
        return $this->success($result);
    }

    public function list()
    {
        $this->validate([
            'pageIndex' => 'integer|required|min:0',
            'pageSize' => 'integer|required|min:10|max:30',
            'type' => 'integer|required|in:1,2,3',
        ]);
        $pageIndex = $this->request->param('pageIndex');
        $pageSize = $this->request->param('pageSize');
        $type = $this->request->param('type');
        $result = $this->service->getList($type, $pageIndex, $pageSize);
        return $this->success($result);
    }

    public function listByUser(AuthedRequest $request)
    {
        $this->validate([
            'pageIndex' => 'integer|required|min:0',
            'pageSize' => 'integer|required|min:10|max:30',
        ]);
        $pageIndex = $request->param('pageIndex');
        $pageSize = $request->param('pageSize');
        $result = $this->service->getUserPostList($pageIndex, $pageSize);
        return $this->success($result);
    }

    public function otherUserPostList()
    {
        $this->validate([
            'pageIndex' => 'integer|required|min:0',
            'pageSize' => 'integer|required|min:10|max:30',
            'userId' => 'integer|required|exists:user,user_id',
        ]);
        $pageIndex = $this->request->param('pageIndex');
        $pageSize = $this->request->param('pageSize');
        $userId = $this->request->param('userId');
        $result = $this->service->getUserPostList($pageIndex, $pageSize, $userId);
        return $this->success($result);
    }

    public function favoriteList(AuthedRequest $request)
    {
        $this->validate([
            'pageIndex' => 'integer|required|min:0',
            'pageSize' => 'integer|required|min:10|max:30',
        ]);
        $pageIndex = $request->param('pageIndex');
        $pageSize = $request->param('pageSize');
        $result = $this->service->getUserFavoriteList($pageIndex, $pageSize);
        return $this->success($result);
    }

    public function otherUserFavoriteList()
    {
        $this->validate([
            'pageIndex' => 'integer|required|min:0',
            'pageSize' => 'integer|required|min:10|max:30',
            'userId' => 'integer|required|exists:user,user_id',
        ]);
        $pageIndex = $this->request->param('pageIndex');
        $pageSize = $this->request->param('pageSize');
        $userId = $this->request->param('userId');
        $result = $this->service->getUserFavoriteList($pageIndex, $pageSize, $userId);
        return $this->success($result);
    }

    public function favorite(AuthedRequest $request)
    {
        $this->validate([
            'postId' => 'integer|required|exists:post,post_id',
        ]);
        $postId = $request->param('postId');
        $result = $this->service->favorite($postId);
        return $this->success($result);
    }

    public function report(AuthedRequest $request)
    {
        $this->validate([
            'postId' => 'integer|required|exists:post,post_id',
            'content' => 'string|required|min:1|max:500'
        ]);
        $postId = $request->param('postId');
        $content = $request->param('content');
        $result = $this->service->reportPost($postId, $content);
        return $this->success($result);
    }

    public function markRead(AuthedRequest $request)
    {
        $this->validate([
            'postId' => 'integer|required|exists:post,post_id',
        ]);
        $postId = $request->param('postId');
        $result = $this->service->markRead($postId);
        return $this->success($result);
    }

    public function increaseForward()
    {
        $this->validate([
            'postId' => 'integer|required|exists:post,post_id',
        ]);
        $postId = $this->request->param('postId');
        $result = $this->service->increaseForward($postId);
        return $this->success($result);
    }
}