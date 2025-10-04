<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ], [
            'email.exists' => 'لا يوجد حساب مرتبط بهذا البريد الإلكتروني.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'البيانات غير صالحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // إنشاء رمز تحقق بسيط (6 أرقام)
            $verificationCode = Str::random(6);
            
            // حفظ الرمز في الكاش لمدة 10 دقائق
            Cache::put('password_reset_' . $request->email, $verificationCode, 600); // 10 دقائق
            
            // هنا يمكنك إرسال الإيميل بالرمز
            // سنستخدم نظام الإشعارات العادي لكن مع الرمز
            $status = Password::sendResetLink(
                $request->only('email')
            );

            // إرجاع الرمز للـ frontend (للتجربة فقط - إزالة هذا في الإنتاج)
            return response()->json([
                'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني',
                'code' => app()->environment('local') ? $verificationCode : null // إرجاع الرمز فقط في البيئة المحلية
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Password reset error: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'حدث خطأ في الخادم. يرجى المحاولة مرة أخرى لاحقاً.'
            ], 500);
        }
    }


    public function verifyCode(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'code' => 'required'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'البيانات غير صالحة',
            'errors' => $validator->errors()
        ], 422);
    }

    $cachedCode = Cache::get('password_reset_' . $request->email);
    
    if ($cachedCode && $cachedCode === $request->code) {
        return response()->json([
            'message' => 'الرمز صحيح',
            'valid' => true
        ], 200);
    }

    return response()->json([
        'message' => 'الرمز غير صحيح',
        'valid' => false
    ], 400);
}
}