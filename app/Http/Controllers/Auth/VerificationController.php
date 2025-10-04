<?php
// app/Http/Controllers/Auth/VerificationController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;

class VerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        $user = \App\Models\User::find($id);

        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'رابط التفعيل غير صالح'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'البريد الإلكتروني مفعل بالفعل'], 200);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => 'تم تفعيل البريد الإلكتروني بنجاح'], 200);
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'البريد الإلكتروني مفعل بالفعل'], 200);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'تم إرسال رابط التفعيل إلى بريدك الإلكتروني'], 200);
    }
}