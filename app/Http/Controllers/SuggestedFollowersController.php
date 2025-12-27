<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuggestedFollowersController extends Controller
{
    /**
     * Get suggested users to follow with pagination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $currentUser = Auth::user();
            
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Get pagination parameters with defaults
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            
            // Validate pagination parameters
            $page = max(1, (int) $page);
            $limit = min(max(1, (int) $limit), 50); // Max limit 50 per page

            // Get users that the current user is NOT following
            // Exclude the current user themselves
            $alreadyFollowingIds = $currentUser->followings()->pluck('users.id')->toArray();
            $excludedIds = array_merge($alreadyFollowingIds, [$currentUser->id]);

            // Base query for suggested users
            // You can customize this algorithm based on your requirements
            $suggestedQuery = User::whereNotIn('users.id', $excludedIds)
                ->withCount(['followers as followersCount', 'followings as followingCount'])
                ->with(['profile' => function($query) {
                    $query->select('user_id', 'profile_image', 'bio', 'location');
                }]);

            // Option 1: Suggest users with similar roles
            if ($currentUser->role) {
                $suggestedQuery->where('role', $currentUser->role);
            }

            // Option 2: Suggest users with mutual connections
            // This is more advanced and requires a mutual connections algorithm

            // Option 3: Random suggestions (fallback)
            // $suggestedQuery->inRandomOrder();

            // Get total count for pagination
            $total = $suggestedQuery->count();

            // Apply pagination
            $suggestedUsers = $suggestedQuery
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($user) use ($currentUser) {
                    // Check if current user is following this user
                    $isFollowing = $currentUser->isFollowing($user);
                    
                    // Get mutual connections count (you'll need to implement this method)
                    $mutualConnections = $this->getMutualConnectionsCount($currentUser, $user);
                    
                    return [
                        'id' => $user->id,
                        'firstName' => $user->first_name,
                        'lastName' => $user->last_name,
                        'full_name' => $user->full_name,
                        'otherNames' => $user->other_names,
                        'email' => $user->email,
                        'role' => $user->role,
                        'avatar' => $user->avatar,
                        'profileImage' => $user->profile->profile_image ?? null,
                        'bio' => $user->profile->bio ?? null,
                        'location' => $user->profile->location ?? null,
                        'followersCount' => $user->followersCount ?? 0,
                        'followingCount' => $user->followingCount ?? 0,
                        'is_following' => $isFollowing,
                        'mutualConnections' => $mutualConnections,
                        'created_at' => $user->created_at,
                    ];
                });

            // Calculate pagination metadata
            $lastPage = ceil($total / $limit);
            $hasMore = $page < $lastPage;

            return response()->json([
                'success' => true,
                'message' => 'Suggested followers retrieved successfully',
                'data' => $suggestedUsers,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => $lastPage,
                    'has_more' => $hasMore,
                    'next_page' => $hasMore ? $page + 1 : null,
                    'prev_page' => $page > 1 ? $page - 1 : null,
                ],
                'meta' => [
                    'algorithm' => 'similar_roles', // or 'mutual_connections', 'random'
                    'total_suggestions' => $total,
                    'user_excluded' => count($excludedIds) - 1, // minus current user
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching suggested followers: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch suggested followers',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Alternative implementation using Laravel's built-in paginator
     */
    public function indexWithPaginator(Request $request)
    {
        try {
            $currentUser = Auth::user();
            
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Get pagination parameters with defaults
            $perPage = $request->query('limit', 10);
            $perPage = min(max(1, (int) $perPage), 50);

            // Get users that the current user is NOT following
            $alreadyFollowingIds = $currentUser->followings()->pluck('users.id')->toArray();
            $excludedIds = array_merge($alreadyFollowingIds, [$currentUser->id]);

            // Base query
            $suggestedQuery = User::whereNotIn('users.id', $excludedIds)
                ->withCount(['followers as followersCount', 'followings as followingCount'])
                ->with(['profile']);

            // Apply role-based suggestions if user has a role
            if ($currentUser->role) {
                $suggestedQuery->where('role', $currentUser->role);
            }

            // Use Laravel's paginator
            $paginator = $suggestedQuery->paginate($perPage);

            // Transform the results
            $transformedData = $paginator->getCollection()->map(function ($user) use ($currentUser) {
                $isFollowing = $currentUser->isFollowing($user);
                
                return [
                    'id' => $user->id,
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'full_name' => $user->full_name,
                    'otherNames' => $user->other_names,
                    'role' => $user->role,
                    'profileImage' => $user->profile->profile_image ?? null,
                    'bio' => $user->profile->bio ?? null,
                    'location' => $user->profile->location ?? null,
                    'followersCount' => $user->followersCount ?? 0,
                    'followingCount' => $user->followingCount ?? 0,
                    'is_following' => $isFollowing,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Suggested followers retrieved successfully',
                'data' => $transformedData,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more' => $paginator->hasMorePages(),
                    'next_page_url' => $paginator->nextPageUrl(),
                    'prev_page_url' => $paginator->previousPageUrl(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching suggested followers: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch suggested followers',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get mutual connections count between two users
     */
    private function getMutualConnectionsCount(User $user1, User $user2): int
    {
        // Get users that both users follow
        $user1FollowingIds = $user1->followings()->pluck('users.id')->toArray();
        $user2FollowingIds = $user2->followings()->pluck('users.id')->toArray();
        
        // Get intersection (mutual connections)
        $mutualIds = array_intersect($user1FollowingIds, $user2FollowingIds);
        
        return count($mutualIds);
    }

    /**
     * Advanced suggestion algorithm with multiple factors
     */
    public function advancedSuggestions(Request $request)
    {
        try {
            $currentUser = Auth::user();
            
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $page = max(1, (int) $request->query('page', 1));
            $limit = min(max(1, (int) $request->query('limit', 10)), 50);

            // Get excluded users
            $alreadyFollowingIds = $currentUser->followings()->pluck('users.id')->toArray();
            $excludedIds = array_merge($alreadyFollowingIds, [$currentUser->id]);

            // Start with users who have mutual connections
            $mutualConnectionsQuery = User::whereNotIn('users.id', $excludedIds)
                ->whereHas('followers', function ($query) use ($currentUser) {
                    // Users who follow people that the current user follows
                    $query->whereIn('follower_id', function ($subQuery) use ($currentUser) {
                        $subQuery->select('following_id')
                            ->from('user_followers')
                            ->where('follower_id', $currentUser->id);
                    });
                })
                ->withCount(['followers as followersCount'])
                ->with(['profile']);

            // Get count for pagination
            $mutualCount = $mutualConnectionsQuery->count();

            if ($mutualCount >= $limit) {
                // We have enough mutual connection suggestions
                $suggestedQuery = $mutualConnectionsQuery;
            } else {
                // Combine with role-based suggestions
                $roleBasedQuery = User::whereNotIn('users.id', $excludedIds)
                    ->where('role', $currentUser->role)
                    ->whereNotIn('users.id', function ($query) use ($mutualConnectionsQuery) {
                        $query->select('id')
                            ->fromSub($mutualConnectionsQuery, 'mutual_users');
                    })
                    ->withCount(['followers as followersCount'])
                    ->with(['profile']);

                // If still not enough, add random suggestions
                $remaining = $limit - $mutualCount;
                if ($roleBasedQuery->count() < $remaining) {
                    $randomQuery = User::whereNotIn('users.id', $excludedIds)
                        ->whereNotIn('users.id', function ($query) use ($mutualConnectionsQuery) {
                            $query->select('id')
                                ->fromSub($mutualConnectionsQuery, 'mutual_users');
                        })
                        ->whereNotIn('users.id', function ($query) use ($roleBasedQuery) {
                            $query->select('id')
                                ->fromSub($roleBasedQuery, 'role_users');
                        })
                        ->inRandomOrder()
                        ->withCount(['followers as followersCount'])
                        ->with(['profile']);

                    $suggestedQuery = $mutualConnectionsQuery
                        ->union($roleBasedQuery)
                        ->union($randomQuery);
                } else {
                    $suggestedQuery = $mutualConnectionsQuery->union($roleBasedQuery);
                }
            }

            // Apply pagination
            $suggestedUsers = $suggestedQuery
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($user) use ($currentUser) {
                    $isFollowing = $currentUser->isFollowing($user);
                    $mutualConnections = $this->getMutualConnectionsCount($currentUser, $user);
                    
                    return [
                        'id' => $user->id,
                        'firstName' => $user->first_name,
                        'lastName' => $user->last_name,
                        'full_name' => $user->full_name,
                        'role' => $user->role,
                        'profileImage' => $user->profile->profile_image ?? null,
                        'bio' => $user->profile->bio ?? null,
                        'location' => $user->profile->location ?? null,
                        'followersCount' => $user->followersCount ?? 0,
                        'followingCount' => $user->followingCount ?? 0,
                        'is_following' => $isFollowing,
                        'mutualConnections' => $mutualConnections,
                        'suggestion_type' => $mutualConnections > 0 ? 'mutual_connections' : ($user->role === $currentUser->role ? 'similar_role' : 'random'),
                    ];
                });

            $total = $mutualCount; // Approximate total

            return response()->json([
                'success' => true,
                'message' => 'Advanced suggestions retrieved successfully',
                'data' => $suggestedUsers,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'has_more' => $suggestedUsers->count() === $limit,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching advanced suggestions: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch suggestions',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }



    public function getFollowers(Request $request)
{
    $user = Auth::user();

    // Get all users who follow the current user
    $followers = Follow::where('followingId', $user->id)
        ->pluck('followerId');

    $users = User::whereIn('id', $followers)
        ->select('id', 'firstName', 'lastName', 'otherNames', 'profileImage', 'bio',
                 'location', 'followersCount', 'followingCount')
        ->get();

    // Check if current user follows each follower
    $isFollowingIds = Follow::where('followerId', $user->id)
        ->pluck('followingId')
        ->toArray();

    $formatted = $users->map(function ($u) use ($isFollowingIds) {
        return [
            'id' => $u->id,
            'firstName' => $u->firstName,
            'lastName' => $u->lastName,
            'otherNames' => $u->otherNames,
            'profileImage' => $u->profileImage,
            'bio' => $u->bio,
            'location' => $u->location,
            'followersCount' => $u->followersCount,
            'followingCount' => $u->followingCount,
            'is_following' => in_array($u->id, $isFollowingIds)
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $formatted
    ]);
}


public function getFollowing(Request $request)
{
    $user = Auth::user();

    // IDs of people user follows
    $following = Follow::where('followerId', $user->id)
        ->pluck('followingId');

    $users = User::whereIn('id', $following)
        ->select('id', 'firstName', 'lastName', 'otherNames', 'profileImage', 'bio',
                 'location', 'followersCount', 'followingCount')
        ->get();

    $formatted = $users->map(function ($u) {
        return [
            'id' => $u->id,
            'firstName' => $u->firstName,
            'lastName' => $u->lastName,
            'otherNames' => $u->otherNames,
            'profileImage' => $u->profileImage,
            'bio' => $u->bio,
            'location' => $u->location,
            'followersCount' => $u->followersCount,
            'followingCount' => $u->followingCount,
            'is_following' => true // Since this is "following" page
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $formatted
    ]);
}


}