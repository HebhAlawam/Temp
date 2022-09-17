<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Media;
use App\Models\Timeline;
use App\Models\UserGroup;
use App\Models\Group;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\PostResource;


class PostController extends Controller
{
    use ResponseJson, MediaTraits;
    /**
     * Read all information about all posts of auth user in the system.
     * 
     * @return jsonResponseWithoutMessage
     */
    public function index()
    {
        //$posts = Post::all();
        $posts = Post::where('user_id', Auth::id())->get();
        //$posts = Post::where('timeline_id', $timeline_id)->get();

        if ($posts->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
     /**
     * Add a new post to the system (“create post” permission is required) 
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function create(Request $request)
    {
        //validate requested data
        $validator = Validator::make($request->all(), [
            'body' => 'required_without:image',
            'type' => 'required',
            'timeline_id' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048 required_without:body',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $user_id = Auth::id();

        if (Auth::user()->can('create post')) {
            $input = $request->all();
            $timeline = Timeline::find($request->timeline_id);

            if (!empty($timeline)) {
                if ($timeline->type == "group") {
                    $group = Group::where('timeline_id', $timeline->id)->first();
                    $user = UserGroup::where([
                        ['group_id', $group->id],
                        ['user_id', Auth::id()]
                    ])->first();
                    if ($user->user_type != "advisor" || $user->user_type != "supervisor" || $user->user_type != "leader") {
                        $input['is_approved'] = null;
                        echo 'waiting for the leader approval';

                        $leader = UserGroup::where([
                            ['group_id', $group->id],
                            ['user_type', "leader"]
                        ])->first();
                        $msg = "There are new posts need approval";
                        (new NotificationController)->sendNotification($leader->user_id, $msg);
                    }
                }

                if ($request->has('tag')) {
                    $input['tag'] = serialize($request->tag);
                }

                if ($request->has('vote')) {
                    $input['vote'] = serialize($request->vote);
                }

                $input['user_id'] = Auth::id();

                $post = Post::create($input);

                if ($request->hasFile('image')) {
                    // if post has media
                    // upload media
                    $this->createMedia($request->file('image'), $post->id, 'post');
                }
                return $this->jsonResponseWithoutMessage("Post Craeted Successfully", 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }
    /**
     * Find an existing post in the system by its id.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $post = Post::find($request->post_id);
        if ($post) {
            return $this->jsonResponseWithoutMessage(new PostResource($post), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Update an existing post in the system by the auth user.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required_without:image',
            'user_id' => 'required',
            'type' => 'required',
            //'allow_comments' => 'required',
            //'tag' => 'required',
            //'vote' => 'required',
            //'is_approved' => 'required',
            //'is_pinned' => 'required',
            'timeline_id' => 'required',
            //'post_id' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048 required_without:body'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $post = Post::find($request->post_id);
        if ($post) {
            if (Auth::id() == $post->user_id) {
                $input = $request->all();
                if ($request->has('tag')) {
                    $input['tag'] = serialize($request->tag);
                }

                if ($request->has('vote')) {
                    $input['vote'] = serialize($request->vote);
                }

                if ($request->hasFile('image')) {
                    // if post has media
                    //check Media
                    $currentMedia = Media::where('post_id', $post->id)->first();
                    // if exists, update
                    if ($currentMedia) {
                        $this->updateMedia($request->file('image'), $currentMedia->id);
                    }
                    //else create new one
                    else {
                        // upload media
                        $this->createMedia($request->file('image'), $post->id, 'post');
                    }
                }
                $post->update($input);
                return $this->jsonResponseWithoutMessage("Post Updated Successfully", 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }        
    /**
     * Delete an existing post in the system by auth user or with “delete post” permission.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $post = Post::find($request->post_id);
        if ($post) {
            if (Auth::user()->can('delete post') || Auth::id() == $post->user_id) {
                //check Media
                $currentMedia = Media::where('post_id', $post->id)->first();
                // if exist, delete
                if ($currentMedia) {
                    $this->deleteMedia($currentMedia->id);
                }
                $post->delete();
                return $this->jsonResponseWithoutMessage("Post Deleted Successfully", 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }
    /**
     * Return all posts that match requested timeline_id.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function postByTimelineId(Request $request)
    {
        $timeline_id = $request->timeline_id;

        //find posts belong to timeline_id
        $posts = Post::where('timeline_id', $timeline_id)->get();

        if ($posts->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data', 200);
        } else {
            throw new NotFound();
        }
    }
    /**
     * Return all posts that match requested user_id.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function postByUserId(Request $request)
    {
        $user_id = $request->user_id;
        //find posts belong to user_id
        $posts = Post::where('user_id', $user_id)->get();

        if ($posts->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data', 200);
        } else {
            throw new NotFound();
        }
    }
    /**
     *Return all posts that match requested timeline_id where is_approved is null.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function listPostsToAccept (Request $request)
    {
        $validator = Validator::make($request->all(), [
            'timeline_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $posts = Post::where([
            ['timeline_id', $request->timeline_id],
            ['is_approved', Null]
        ])->get();
        if ($posts->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Accept post that matches the required post_id where is_approved = null,
     * give date for this approval and send notification to user
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function AcceptPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $post = Post::find($request->post_id);
        if ($post->is_approved == Null) {
            $post->is_approved = now();
            $post->update();

            $msg = "Your post is approved successfully";
            (new NotificationController)->sendNotification($post->user_id, $msg);
            return $this->jsonResponseWithoutMessage("The post is approved successfully", 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage("The post is already approved ", 'data', 200);
        }
    }
    /**
     * Decline post that matches the required post_id where is_approved = null,
     * delete post and send notification to user
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function declinePost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $post = Post::find($request->post_id);
        if ($post->is_approved == Null) {
            $post->delete();
            $msg = "Your post is declined";
            (new NotificationController)->sendNotification($post->user_id, $msg);
            return $this->jsonResponseWithoutMessage("The post is deleted successfully", 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage("The post is already approved ", 'data', 200);
        }
    }
    /**
     * user can control comments in the system (“control comments” permission is required)
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function controllComments(Request $request){
        // user can controll comments [allowed or not]  if he is the owner or has a controll comments permission
        $validator = Validator::make($request->all(), [
            'allow_comments' => 'required',
            'post_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        
        $post = Post::find($request->post_id);
        if($post){
            if(Auth::id() == $post->user_id || Auth::user()->can('controll comments')){
                $post->allow_comments=$request->allow_comments;
                $post->save();

                if($request->allow_comments == 0 ){
                    $msg = "Comments Closed Successfully";
                }
                else{
                    $msg = "Comments Opend Successfully";
                }
                return $this->jsonResponseWithoutMessage($msg, 'data', 200);

            }    
            else{
                throw new NotAuthorized;   
            }
        }
        else{
            throw new NotFound;   
        } 

    }
    /**
     * User can pin post on his profile or if user has a pin post permission.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function pinnPost(Request $request){
        // user can pin post on his profile or if he has a pin post permission
        $validator = Validator::make($request->all(), [
            'is_pinned' => 'required',
            'post_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        
        $post = Post::find($request->post_id);
        if($post){
            if(Auth::user()->userProfile->timeline_id == $post->timeline_id || Auth::user()->can('pin ')){
               
                Post::where('id',$request->post_id)->update(['is_pinned'=>$request->is_pinned]);
                if($request->is_pinned == 0 ){
                    $msg = "Post Unpinned Successfully";
                }
                else{
                    $msg = "Post Pinned Successfully";
                }
                return $this->jsonResponseWithoutMessage($msg, 'data', 200);
            }    
            else{
                throw new NotAuthorized;   
            }
        }
        else{
            throw new NotFound;   
        } 
    }    
}
