<?php
// app/Http/Controllers/FollowController.php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Follow;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FollowController extends Controller
{
    /**
     * Follow a user
     */
    public function follow($userId)
    {
        try {
            $currentUser = Auth::user();
            $userToFollow = User::findOrFail($userId);

            if ($currentUser->id === $userToFollow->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot follow yourself'
                ], 400);
            }

            $alreadyFollowing = Follow::where('followerId', $currentUser->id)
                ->where('followingId', $userId)
                ->exists();

            if ($alreadyFollowing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already following this user'
                ], 400);
            }

            DB::beginTransaction();

            // Create follow relationship
            $follow = Follow::create([
                'followerId' => $currentUser->id,
                'followingId' => $userId,
                'followedAt' => now()
            ]);

            // Update counters
            $currentUser->increment('followingCount');
            $userToFollow->increment('followersCount');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully followed user',
                'data' => [
                    'follow' => $follow,
                    'stats' => [
                        'following_count' => $currentUser->fresh()->followingCount,
                        'followers_count' => $userToFollow->fresh()->followersCount
                    ]
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Follow error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to follow user',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Unfollow a user
     */
    public function unfollow($userId)
    {
        try {
            $currentUser = Auth::user();
            $userToUnfollow = User::findOrFail($userId);

            $follow = Follow::where('followerId', $currentUser->id)
                ->where('followingId', $userId)
                ->first();

            if (!$follow) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not following this user'
                ], 400);
            }

            DB::beginTransaction();

            // Delete follow relationship
            $follow->delete();

            // Update counters
            $currentUser->decrement('following_count');
            $userToUnfollow->decrement('followers_count');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully unfollowed user',
                'data' => [
                    'stats' => [
                        'following_count' => $currentUser->fresh()->following_count,
                        'followers_count' => $userToUnfollow->fresh()->followers_count
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Unfollow error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to unfollow user',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove a follower
     */
    public function removeFollower($userId, $followerId)
    {
        try {
            $currentUser = Auth::user();

            // Check if current user is the one being followed
            if ($currentUser->id != $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to remove follower'
                ], 403);
            }

            $follower = User::findOrFail($followerId);

            $follow = Follow::where('followerId', $followerId)
                ->where('followingId', $userId)
                ->first();

            if (!$follow) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user is not following you'
                ], 400);
            }

            DB::beginTransaction();

            // Delete follow relationship
            $follow->delete();

            // Update counters
            $currentUser->decrement('followers_count');
            $follower->decrement('following_count');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully removed follower',
                'data' => [
                    'stats' => [
                        'followers_count' => $currentUser->fresh()->followers_count,
                        'following_count' => $follower->fresh()->following_count
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Remove follower error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove follower',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get followers of a user
     */
    public function getFollowers($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $currentUser = Auth::user();

            $followers = Follow::with(['follower' => function ($query) {
                $query->select([
                    'id',
                    'firstName',
                    'lastName',
                    'otherNames',
                    'avatar',
                    'bio',
                    'location',
                    'followers_count',
                    'following_count'
                ]);
            }])
                ->where('followingId', $userId)
                ->orderBy('followed_at', 'desc')
                ->paginate(20);

            // Add isFollowing flag for each follower
            $followersData = $followers->getCollection()->map(function ($follow) use ($currentUser) {
                $follower = $follow->follower;
                $follower->isFollowing = $currentUser->isFollowing($follower);
                $follower->followId = $follow->id;
                return $follower;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $followersData,
                    'pagination' => [
                        'current_page' => $followers->currentPage(),
                        'last_page' => $followers->lastPage(),
                        'per_page' => $followers->perPage(),
                        'total' => $followers->total(),
                        'has_more' => $followers->hasMorePages()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get followers error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load followers',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get users followed by a user
     */
    public function getFollowing($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $currentUser = Auth::user();

            $following = Follow::with(['following' => function ($query) {
                $query->select([
                    'id',
                    'firstName',
                    'lastName',
                    'otherNames',
                    'avatar',
                    'bio',
                    'location',
                    'followers_count',
                    'following_count'
                ]);
            }])
                ->where('followerId', $userId)
                ->orderBy('followed_at', 'desc')
                ->paginate(20);

            // Add isFollowing flag (always true for following list)
            $followingData = $following->getCollection()->map(function ($follow) use ($currentUser) {
                $followingUser = $follow->following;
                $followingUser->isFollowing = true; // Always true since they're in the following list
                $followingUser->followId = $follow->id;
                return $followingUser;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $followingData,
                    'pagination' => [
                        'current_page' => $following->currentPage(),
                        'last_page' => $following->lastPage(),
                        'per_page' => $following->perPage(),
                        'total' => $following->total(),
                        'has_more' => $following->hasMorePages()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get following error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load following',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get follow statistics for a user
     */
    public function getFollowStats($userId)
    {
        try {
            $user = User::findOrFail($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'followers_count' => $user->followers_count,
                    'following_count' => $user->following_count,
                    'user_id' => $user->id
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get follow stats error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load follow statistics',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check follow status between users
     */
    public function getFollowStatus($userId)
    {
        try {
            $currentUser = Auth::user();
            $targetUser = User::findOrFail($userId);

            $isFollowing = Follow::where('followerId', $currentUser->id)
                ->where('followingId', $userId)
                ->exists();

            $isFollowedBy = Follow::where('followerId', $userId)
                ->where('followingId', $currentUser->id)
                ->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'is_following' => $isFollowing,
                    'is_followed_by' => $isFollowedBy,
                    'is_mutual' => $isFollowing && $isFollowedBy,
                    'target_user' => [
                        'id' => $targetUser->id,
                        'name' => $targetUser->fullName,
                        'followers_count' => $targetUser->followers_count,
                        'following_count' => $targetUser->following_count
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get follow status error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check follow status',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Search followers/following
     */
    public function searchFollowers(Request $request, $userId)
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2',
                'type' => 'required|in:followers,following'
            ]);

            $user = User::findOrFail($userId);
            $currentUser = Auth::user();

            $query = $request->query;
            $type = $request->type;

            if ($type === 'followers') {
                $usersQuery = Follow::where('followingId', $userId)
                    ->with(['follower' => function ($q) use ($query) {
                        $q->where(function ($q2) use ($query) {
                            $q2->where('firstName', 'LIKE', "%{$query}%")
                                ->orWhere('lastName', 'LIKE', "%{$query}%")
                                ->orWhere('otherNames', 'LIKE', "%{$query}%")
                                ->orWhereRaw("CONCAT(firstName, ' ', lastName) LIKE ?", ["%{$query}%"]);
                        })
                            ->select([
                                'id',
                                'firstName',
                                'lastName',
                                'otherNames',
                                'avatar',
                                'bio',
                                'location',
                                'followers_count',
                                'following_count'
                            ]);
                    }]);
            } else {
                $usersQuery = Follow::where('followerId', $userId)
                    ->with(['following' => function ($q) use ($query) {
                        $q->where(function ($q2) use ($query) {
                            $q2->where('firstName', 'LIKE', "%{$query}%")
                                ->orWhere('lastName', 'LIKE', "%{$query}%")
                                ->orWhere('otherNames', 'LIKE', "%{$query}%")
                                ->orWhereRaw("CONCAT(firstName, ' ', lastName) LIKE ?", ["%{$query}%"]);
                        })
                            ->select([
                                'id',
                                'firstName',
                                'lastName',
                                'otherNames',
                                'avatar',
                                'bio',
                                'location',
                                'followers_count',
                                'following_count'
                            ]);
                    }]);
            }

            $results = $usersQuery->paginate(20);

            // Process results
            $usersData = $results->getCollection()->map(function ($follow) use ($type, $currentUser) {
                $user = $type === 'followers' ? $follow->follower : $follow->following;

                if (!$user) return null;

                $user->isFollowing = $type === 'following' ? true : $currentUser->isFollowing($user);
                $user->followId = $follow->id;
                return $user;
            })->filter();

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $usersData,
                    'pagination' => [
                        'current_page' => $results->currentPage(),
                        'last_page' => $results->lastPage(),
                        'per_page' => $results->perPage(),
                        'total' => $results->total(),
                        'has_more' => $results->hasMorePages()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Search followers error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to search',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    public function suggestedUsers(Request $request)
    {
        try {
            $currentUser = Auth::user();

            // Get IDs of users the current user is already following
            $followingIds = Follow::where('followerId', $currentUser->id)
                ->pluck('followingId')
                ->toArray();

            // Include current user's ID to exclude from suggestions
            $followingIds[] = $currentUser->id;

            // Fetch users not followed by the current user
            $suggestedUsers = User::whereNotIn('id', $followingIds)
                ->inRandomOrder()
                ->limit(10)
                ->get(['id', 'firstName', 'lastName', 'otherNames', 'avatar', 'profileImage', 'bio', 'location', 'followersCount', 'followingCount']);

            return response()->json([
                'success' => true,
                'data' => $suggestedUsers
            ]);
        } catch (\Exception $e) {
            Log::error('Suggested users error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load suggested users',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function suggestedUsersPaginated(Request $request)
    {
        try {
            $currentUser = Auth::user();

            // Pagination parameters
            $perPage = $request->get('per_page', 10); // default 10
            $page = $request->get('page', 1);

            // Get IDs the user already follows
            $followingIds = Follow::where('followerId', $currentUser->id)
                ->pluck('followingId')
                ->toArray();

            // Exclude the current user too
            $followingIds[] = $currentUser->id;

            // Query users NOT followed by current user
            $suggestedQuery = User::whereNotIn('id', $followingIds)
                ->inRandomOrder()
                ->select('id', 'firstName', 'lastName', 'otherNames', 'avatar', 'profileImage', 'bio', 'location', 'followersCount', 'followingCount');

            // Apply pagination
            $suggestedUsers = $suggestedQuery->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $suggestedUsers
            ]);
        } catch (\Exception $e) {
            Log::error('Suggested users error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load suggested users',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }



    public function toggleFollow(Request $request)
    {
        $request->validate([
            'follow_user_id' => 'required|exists:users,id'
        ]);

        try {
            $user = auth()->user();
            $targetUser = User::findOrFail($request->follow_user_id);

            if ($user->id == $targetUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot follow yourself'
                ], 400);
            }

            $isFollowing = $user->isFollowing($targetUser->id);

            DB::beginTransaction();

            if ($isFollowing) {
                // Unfollow
                $user->unfollow($targetUser->id);
                $message = 'Unfollowed successfully';
            } else {
                $user->follow($targetUser->id);
                NotificationService::notifyNewFollower($user, $targetUser);
                $message = 'Followed successfully';
            }

            // Refresh user data
            $user->refresh();
            $targetUser->refresh();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'is_following' => !$isFollowing,
                    'followers_count' => $targetUser->followers_count,
                    'following_count' => $user->following_count,
                    'target_user' => [
                        'id' => $targetUser->id,
                        'followers_count' => $targetUser->followers_count
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Follow error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request'
            ], 500);
        }
    }
}
