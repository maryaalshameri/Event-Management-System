<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        
        // التصفية حسب النوع
        if ($request->has('role') && in_array($request->role, ['admin', 'organizer', 'attendee'])) {
            $query->where('role', $request->role);
        }
        
        // البحث
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // الترتيب
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }



   public function store(Request $request)
 {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'phone' => 'nullable|string|max:20',
        'role' => 'required|in:admin,organizer,attendee',
        'password' => 'required|string|min:8|confirmed' // تأكد من وجود confirmed
    ]);

    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'phone' => $validated['phone'] ?? null,
        'role' => $validated['role'],
        'password' => Hash::make($validated['password'])
    ]);

    return response()->json([
        'message' => 'تم إضافة المستخدم بنجاح',
        'user' => $user
    ], 201);
}

public function update(Request $request, User $user)
{
    $validated = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'email' => [
            'sometimes',
            'required',
            'email',
            Rule::unique('users')->ignore($user->id)
        ],
        'phone' => 'nullable|string|max:20',
        'role' => 'sometimes|required|in:admin,organizer,attendee',
        'password' => 'sometimes|nullable|string|min:8|confirmed' // جعله اختيارياً عند التحديث
    ]);

    $data = $validated;

    // إذا كانت كلمة المرور موجودة، قم بتشفيرها
    if (isset($data['password']) && !empty($data['password'])) {
        $data['password'] = Hash::make($data['password']);
    } else {
        unset($data['password']); // إزالة كلمة المرور إذا كانت فارغة
    }

    $user->update($data);

    return response()->json([
        'message' => 'تم تحديث المستخدم بنجاح',
        'user' => $user
    ]);
}

    public function updatePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed'
        ]);

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message' => 'تم تحديث كلمة المرور بنجاح'
        ]);
    }


       public function destroy(User $user)
    {
        // منع حذف المدير الرئيسي
        if ($user->id === 1) {
            return response()->json([
                'message' => 'لا يمكن حذف المدير الرئيسي'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'تم حذف المستخدم بنجاح'
        ]);
    }

    public function toggleStatus(User $user)
    {
        // يمكن إضافة حقل is_active إذا أردت تعطيل الحسابات
        return response()->json([
            'message' => 'ميزة تعطيل الحساب قيد التطوير'
        ]);
    }
}