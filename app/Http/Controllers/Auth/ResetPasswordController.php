<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class ResetPasswordController extends Controller
{
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'البيانات غير صالحة',
                'errors' => $validator->errors()
            ], 422);
        }

        // التحقق من صحة الرمز
        $cachedToken = Cache::get('password_reset_' . $request->email);
        
        if (!$cachedToken || $cachedToken !== $request->token) {
            return response()->json([
                'message' => 'رمز التحقق غير صالح أو منتهي الصلاحية'
            ], 400);
        }

        // البحث عن المستخدم
        $user = \App\Models\User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'message' => 'لا يوجد حساب مرتبط بهذا البريد الإلكتروني'
            ], 400);
        }

        // تحديث كلمة المرور
        $user->forceFill([
            'password' => Hash::make($request->password)
        ])->setRememberToken(Str::random(60));

        $user->save();

        // مسح الرمز من الكاش بعد الاستخدام
        Cache::forget('password_reset_' . $request->email);

        return response()->json([
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح'
        ], 200);
    }
}