<?php


namespace App\Job;
use App\Model\Comment;
use Hyperf\AsyncQueue\Job;
use ZYProSoft\Facade\Cache;
use ZYProSoft\Log\Log;

class CommentHotStatusCheckJob extends Job
{
    private string $cacheKey;

    public int $postId;

    public int $starBaseCount = 6;

    public int $replyBaseCount = 5;

    public int $baseTopCount = 15;

    public function __construct(string $cacheKey, int $postId)
    {
        $this->cacheKey = $cacheKey;
        $this->postId = $postId;
    }

    /**
     * @inheritDoc
     */
    public function handle()
    {
        //删除队列标记位
        Cache::delete($this->cacheKey);

        Log::info("开始执行热门评论判定任务");

        //需要设置成热门评论的条件: 2.评论的点赞数前5名 3.评论最少有2个点赞
        // 4.回复数达到10条以上可单独成立 5. 点赞数达到10个以上可单独成立
        $topStarList = Comment::query()->where('post_id',$this->postId)
                                       ->where('praise_count','>=',2)
                                       ->orderByDesc('praise_count')
                                       ->limit($this->baseTopCount)
                                       ->get();
        $topReplyList = Comment::query()->where('post_id',$this->postId)
                                        ->where('reply_count','>=',2)
                                        ->orderByDesc('reply_count')
                                        ->limit($this->baseTopCount)
                                        ->get();
        if ($topStarList->isEmpty() && $topReplyList->isEmpty()) {
            return;
        }
        $hotCommentIds = collect();
        if (! $topStarList->isEmpty()) {
            //有没有超过6个点赞的
            $topStarList->map(function (Comment $comment) use (&$hotCommentIds) {
                if ($comment->praise_count >= $this->starBaseCount) {
                    $hotCommentIds->push($comment->comment_id);
                }
            });
            //如果都没有超过10个点赞的，那么取前两名点赞数量的评论作为热评
            $chooseCount = $topStarList->count() >= 2? 2:$topStarList->count();
            if ($hotCommentIds->isEmpty()) {
                $hotCommentIds->union($topStarList->slice(0,$chooseCount)->pluck('comment_id'));
            }
        }
        if (! $topReplyList->isEmpty()) {
            //有没有超过5个回复的
            $topReplyList->map(function (Comment $comment) use (&$hotCommentIds) {
                if ($comment->reply_count >= $this->replyBaseCount) {
                    $hotCommentIds->push($comment->comment_id);
                }
            });
            //如果都没有超过5个回复的，那么取前两名回复数量的评论作为热评
            $chooseCount = $topReplyList->count() >= 2? 2:$topReplyList->count();
            if ($hotCommentIds->isEmpty()) {
                $hotCommentIds->union($topReplyList->slice(0,$chooseCount)->pluck('comment_id'));
            }
        }
        //设置热评
        if (!$hotCommentIds->isEmpty()) {
            Comment::query()->whereIn('comment_id',$hotCommentIds->toArray())
                ->update(['is_hot'=>1]);
            $hotCommentIdsLabel = $hotCommentIds->toJson();
            Log::info("已经将($hotCommentIdsLabel)设置成热门评论");
            return;
        }
        Log::info("帖子($this->postId)暂无热门评论");
    }
}