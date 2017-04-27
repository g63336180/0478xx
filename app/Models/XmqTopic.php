<?php

namespace App\Models;

use App\Models\Relations\BelongsToCategoryTrait;
use App\Models\Relations\BelongsToUserTrait;
use App\Models\Relations\MorphManyCommentsTrait;
use App\Models\Relations\MorphManyTagsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class XmqTopic extends Model
{
    use BelongsToUserTrait,MorphManyCommentsTrait,MorphManyTagsTrait,BelongsToCategoryTrait;
    protected $table = 'xmq_topic';
    protected $fillable = ['topic_id', 'group_id','type','owner','content','images','created_at'];


    public static function boot()
    {
        parent::boot();

    }

    /*获取相关问题*/
    public static function correlations($tagIds,$size=6)
    {
        $questions = self::whereHas('tags', function($query) use ($tagIds) {
            $query->whereIn('tag_id', $tagIds);
        })->orderBy('created_at','DESC')->take($size)->get();
        return $questions;
    }




    /*热门问题*/
    public static function hottest($categoryId = 0,$pageSize=20)
    {
        $query = self::with('user');
        if( $categoryId > 0 ){
            $query->where('category_id','=',$categoryId);
        }
        $list = $query->where('status','>',0)->orderBy('views','DESC')->orderBy('answers','DESC')->orderBy('created_at','DESC')->paginate($pageSize);
        return $list;

    }

    /*最新问题*/
    public static function newest($group_id=0 , $pageSize=20)
    {
        $query = self::with('user');
        if( $group_id > 0 ){
            $query->where('group_id','=',$group_id);
        }
        $list = $query->orderBy('created_at','DESC')->paginate($pageSize);
        return $list;
    }

    /*未回答的*/
    public static function unAnswered($categoryId=0 , $pageSize=20)
    {
        $query = self::query();
        if( $categoryId > 0 ){
            $query->where('category_id','=',$categoryId);
        }
        $list = $query->where('status','>',0)->where('answers','=',0)->orderBy('created_at','DESC')->paginate($pageSize);
        return $list;
    }

    /*悬赏问题*/
    public static function reward($categoryId=0 , $pageSize=20)
    {
        $query = self::query();
        if( $categoryId > 0 ){
            $query->where('category_id','=',$categoryId);
        }
        $list = $query->where('status','>',0)->where('price','>',0)->orderBy('created_at','DESC')->paginate($pageSize);
        return $list;
    }

    /*最近热门问题*/
    public static function recent()
    {
        $list = Cache::remember('recent_questions',300, function() {
            return self::where('status','>',0)->where('created_at','>',Carbon::today()->subWeek())->orderBy('views','DESC')->orderBy('answers','DESC')->orderBy('created_at','DESC')->take(12)->get();
        });

        return $list;
    }

    /*是否已经邀请用户回答了*/
    public function isInvited($sendTo,$fromUserId){
        return $this->invitations()->where("send_to","=",$sendTo)->where("from_user_id","=",$fromUserId)->count();
    }


    /*问题搜索*/
    public static function search($word,$size=16)
    {
        $list = self::where('title','like',"$word%")->paginate($size);
        return $list;
    }


    /*问题所有回答*/
    public function answers()
    {
        return $this->hasMany('App\Models\XmqComment','topic_id', 'topic_id');
    }


    /*问题所有邀请*/
    public function invitations()
    {
        return $this->hasMany('App\Models\QuestionInvitation','question_id');
    }


}
