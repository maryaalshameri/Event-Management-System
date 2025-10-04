<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage; 
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Notifications\UserRegistered;
use App\Notifications\UserLoggedIn;

class AuthController extends Controller
{
    public function register(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8|confirmed',
        'phone' => 'nullable|regex:/^[0-9]{9,15}$/',
        'address' => 'nullable|string|max:500',
        'role' => 'required|in:attendee,organizer',
        'terms' => 'accepted'
    ]);

    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'phone' => $validated['phone'] ?? null,
        'address' => $validated['address'] ?? null,
        'role' => $validated['role'],
        'email_verified_at' => null, // تأكد من أن البريد غير مفعل
    ]);

    // إرسال إشعار تفعيل البريد
    $user->sendEmailVerificationNotification();
    
    // إرسال الإشعارات الأخرى
    $user->notify(new UserRegistered($user));
    
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'تم إنشاء الحساب بنجاح. يرجى تفعيل بريدك الإلكتروني.',
        'user' => new UserResource($user),
        'token' => $token
    ], 201);
  }
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'بيانات الدخول غير صحيحة'
            ], 401);
        }

        // إرسال إشعار تسجيل الدخول
        $user->notify(new UserLoggedIn($user));
        
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'user' => new UserResource($user),
            'token' => $token
        ], 200);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load(['events', 'orders', 'reviews']));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح.']);
    }

    public function updateProfile(Request $request)
{
    $user = $request->user();
       
    $validated = $request->validate([
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:users,email,' . $user->id,
        'phone' => 'nullable|regex:/^[0-9]{9,15}$/',
        'address' => 'nullable|string|max:500',
        'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    // معالجة رفع الصورة
    if ($request->hasFile('avatar')) {
        // حذف الصورة القديمة إذا كانت موجودة
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        // حفظ الصورة الجديدة
        $avatarPath = $request->file('avatar')->store('avatars', 'public');
        $validated['avatar'] = $avatarPath;
    }

    // تحديث بيانات المستخدم
    $user->update($validated);

    return response()->json([
        'message' => 'تم تحديث الملف الشخصي بنجاح',
        'user' => new UserResource($user)
    ]);
}

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'كلمة المرور الحالية غير صحيحة'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json([
            'message' => 'تم تحديث كلمة المرور بنجاح'
        ]);
    }
}