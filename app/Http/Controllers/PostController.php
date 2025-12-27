<?php

namespace App\Http\Controllers;

use App\Models\PostComments;
use App\Models\PostLikes;
use Illuminate\Http\Request;

use App\Models\Posts;
use App\Models\PostShares;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PostController extends Controller
{
    
    public function index()
    {
        $posts = Posts::with(['user', 'likes', 'shares'])->get();
        return response()->json($posts);
    }

   public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            // 'userId' => 'required|exists:users,id',
            // 'title' => 'required|string|max:255',
            // 'body' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // 2MB max
            'status' => 'nullable|string|in:draft,published',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only([ 'title', 'body', 'status']);
            $uploadUrl = null;
            $data['userId'] = auth()->id();
            // Handle image upload
       if ($request->hasFile('image') && $request->file('image')->isValid()) {
    $image = $request->file('image');

    // Generate unique filename
    $filename = 'post_' . time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();

    // Store in storage/app/public/posts
    $path = $image->storeAs('posts', $filename, 'public');

    // Return relative path like: /posts/filename.png
    $data['uploadUrl'] = '/' . $path;
}


            // Create the post
            $post = Posts::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Post created successfully',
                'post' => $post
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Post creation failed: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $post = Posts::with(['user', 'likes', 'shares'])->findOrFail($id);
        return response()->json($post);
    }

    public function update(Request $request, $id)
    {
        $post = Posts::findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'body' => 'sometimes|required|string',
            'uploadUrl' => 'sometimes|nullable|string|max:255',
            'status' => 'sometimes|nullable|string|in:draft,published',
        ]);

        $post->update($data);

        return response()->json($post);
    }

    public function destroy($id)
    {
        $post = Posts::findOrFail($id);
        $post->delete();

        return response()->json(null, 204);
    }


    public function likePost($id)
    {
         $user = auth()->user();
        // Logic to like a post
        $like = PostLikes::create([
            'postId' => $id,
            'userId' => auth()->id(),
        ]);
        if ($like->userId !== $user->id) {
        NotificationService::notifyPostLike($user, $like->post);
    }
    }

    public function unlikePost($id)
    {
        // Logic to unlike a post
        $like = PostLikes::where('postId', $id)
            ->where('userId', auth()->id())
            ->first();

        if ($like) {
            $like->delete();
        }
    }


    public function sharePost($id)
    {
        // Logic to share a post
        $share = PostShares::create([
            'postId' => $id,
            'userId' => auth()->id(),
        ]);
    }

    public function unsharePost($id)
    {
        // Logic to unshare a post
        $share = PostShares::where('postId', $id)
            ->where('userId', auth()->id())
            ->first();

        if ($share) {
            $share->delete();
        }
    }

    public function commentPost(Request $request, $id)
    {
        $user = auth()->user();
        // Logic to comment on a post
        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $comment = PostComments::create([
            'postId' => $id,
            'userId' => auth()->id(),
            'comment' => $request->comment,
        ]);
        $post = Posts::findOrFail($id);

         if ($comment->userId !== $user->id) {
        NotificationService::notifyPostComment($user, $post, $comment);
    }
        return response()->json($comment, 201);
    }

    public function uncommentPost(Request $request, $id)
    {
        // Logic to remove comment from a post
        $comment = PostComments::where('postId', $id)
            ->where('userId', auth()->id())
            ->where('comment', $request->comment)
            ->first();

        if ($comment) {
            $comment->delete();
            return response()->json(null, 204);
        }

        return response()->json(['message' => 'Comment not found'], 404);
    }


    public function getComments($id)
    {
        // Logic to get comments for a post
        $comments = PostComments::where('postId', $id)->with('user')->get();
        return response()->json($comments);
    }


    public function getShares($id)
    {
        // Logic to get shares for a post
        $shares = PostShares::where('postId', $id)->with('user')->get();
        return response()->json($shares);
    }

    public function getLikes($id)
    {
        // Logic to get likes for a post
        $likes = PostLikes::where('postId', $id)->with('user')->get();
        return response()->json($likes);
    }

}
