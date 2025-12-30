<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\UserNotificationMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserEmailController extends Controller
{
    /**
     * Send email to a single user (synchronously)
     */
    public function sendSingle(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Only validate if user has an email
        if (!$user->email) {
            return response()->json([
                'message' => 'This user does not have an email address.'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        try {
            // Send immediately (no queue)
            Mail::to($user->email)->send(new UserNotificationMail(
                user: $user,
                subjectLine: $request->subject,
                messageBody: $request->message
            ));

            return response()->json([
                'message' => 'Email sent successfully!'
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Email send failed for user ' . $user->id . ': ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to send email. Please check your mail configuration.'
            ], 500);
        }
    }

    /**
     * Broadcast email to multiple users (synchronously)
     */
    public function broadcast(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userIds' => 'required|array|min:1',
            'userIds.*' => 'integer|exists:users,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $users = User::whereIn('id', $request->userIds)
                    ->whereNotNull('email') // Skip users without email
                    ->get();

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No users with valid email addresses found.'
            ], 400);
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            try {
                Mail::to($user->email)->send(new UserNotificationMail(
                    user: $user,
                    subjectLine: $request->subject,
                    messageBody: $request->message
                ));
                $successCount++;
            } catch (\Exception $e) {
                \Log::error("Failed to send email to user {$user->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        if ($successCount === 0) {
            return response()->json([
                'message' => 'All emails failed to send. Check mail settings.'
            ], 500);
        }

        $message = "Sent to {$successCount} user(s)";
        if ($failedCount > 0) {
            $message .= " ({$failedCount} failed)";
        }

        return response()->json([
            'message' => $message . '!'
        ], 200);
    }
}