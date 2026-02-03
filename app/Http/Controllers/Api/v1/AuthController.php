<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\CheckOTPRequest;
use App\Http\Requests\Auth\CheckUserNameRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\Register;
use App\Http\Requests\Auth\ResendOTPRequest;
use App\Http\Requests\Auth\resetPasswordRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Http\Requests\LoginOTPRequest;
use App\Models\Student;
use App\Models\User;
// use App\Traits\ApiResponse; // Removed
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use function PHPUnit\Framework\isEmpty;

class AuthController extends BaseApiController
{
    // use ApiResponse; // Removed
    public function register(Register $request)
    {
        DB::beginTransaction();

        try {
            if (isset($request->user_name)) {
                $user_name = $request->user_name;
            } else {
                $user_name = $this->generateUniqueUserName($request->name);
            }

            $user = User::create([
                'name' => $request->name,
                'user_name' => $user_name,
                'phone' => $request->country_code . $request->phone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'type' => $request->type,
                'gender' => $request->gender,
            ]);

            $studentData = null;

            if ($request->type === 'student') {
                $student = Student::create([
                    'user_id' => $user->id,
                    'exam_id' => $request->exam_id,
                    'exam_date' => $request->exam_date,
                    'id_number' => $request->id_number,
                ]);
                $studentData = $student->makeHidden(['id', 'created_at', 'updated_at', 'user_id'])->toArray();
            } elseif ($request->type !== 'parent') {
                DB::rollBack();
                return $this->errorResponse('Invalid User Type', 401);
            }

            /** @var \Tymon\JWTAuth\JWTGuard $apiGuard */
            $apiGuard = auth('api');
            $token = $apiGuard->login($user);

            DB::commit();


            $userData = $user->makeHidden(['id', 'created_at', 'updated_at', 'password'])->toArray();

            if ($studentData) {
                $userData = array_merge($userData, $studentData);
            }

            return $this->successResponse([
                'token' => $token,
                'user' => array_merge(['type' => $request->type], $userData)
            ], 'User Registered Successfully');

        } catch (JWTException $e) {
            DB::rollBack();
            return $this->errorResponse('Could not create token', 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('user_name', 'password');
        try {
            /** @var \Tymon\JWTAuth\JWTGuard $apiGuard */
            $apiGuard = auth('api');
            /** @var \Tymon\JWTAuth\JWTGuard $schoolGuard */
            $schoolGuard = auth('schools');

            if ($token = $apiGuard->attempt($credentials)) {
                return $this->successResponse([
                    'token' => $token,
                    'type' => $apiGuard->user()->type,
                    'expires_in' => $apiGuard->factory()->getTTL() * 60,
                ], 'User Logged In Successfully');
            } elseif ($token = $schoolGuard->attempt($credentials)) {
                return $this->successResponse([
                    'token' => $token,
                    'type' => 'school',
                    'expires_in' => $schoolGuard->factory()->getTTL() * 60,
                ], 'User Logged In Successfully');
            } else {
                return $this->errorResponse('Invalid credentials', 401);
            }
        } catch (JWTException $e) {
            return $this->errorResponse('Could not create token', 500);
        }
    }
    //
//
//    public function loginOTP(LoginOTPRequest $request)
//    {
//        $phone = $request->only('phone');
//        try {
//            if ($token = auth('api')->attempt($credentials)) {
//                return $this->apiResponse(200,'User Logged In Successfully',null,[
//                    'token' => $token,
//                    'type' => \auth('api')->user()->type,
//                    'expires_in' => auth('api')->factory()->getTTL() * 60,
//                ]);
//            }elseif($token = auth('schools')->attempt($credentials)){
//                return $this->apiResponse(200,'User Logged In Successfully',null,[
//                    'token' => $token,
//                    'type' => 'school',
//                    'expires_in' => auth('api')->factory()->getTTL() * 60,
//                ]);
//            }else{
//                return $this->apiResponse(401,'Invalid credentials');
//            }
//        } catch (JWTException $e) {
//            return $this->apiResponse(500,'Could not create token');
//        }
//    }


    public function getUser()
    {
        try {
            $user = auth('api')->user() ?: auth('schools')->user();
            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }
            return $this->successResponse([
                'data' => $user->makeHidden(['id', 'created_at', 'updated_at', 'remember_token', 'password'])->toArray(),
            ], 'User Data Retrieved Successfully');
        } catch (JWTException $e) {
            return $this->errorResponse('Failed to fetch user profile', 404);
        }
    }

    public function updateUser(UpdateUserRequest $request)
    {
        try {
            $user = auth('api')->user() ?: auth('schools')->user();
            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }
            if ($request->password) {
                if (!Hash::check($request->old_password, $user->password)) {
                    return $this->errorResponse('Validation Error', 403, ['old_password' => 'كلمة المرور القديمة غير صحيحة.']);
                }
            }

            $user->update($request->validated());
            return $this->successResponse([
                'data' => $user->makeHidden(['id', 'created_at', 'updated_at', 'remember_token', 'password'])->toArray(),
            ], 'User Profile Updated Successfully');
        } catch (JWTException $e) {
            return $this->errorResponse('Failed to update user profile', 500);
        }
    }


    public function checkUserName(CheckUserNameRequest $request)
    {
        try {
            $user = User::where('user_name', $request->user_name)->first();
            $available = !$user;
        } catch (\Exception $e) {
            return $this->errorResponse('Could not check UserName', 500);
        }

        return $this->successResponse([
            'available' => $available,
        ], 'Username availability checked');
    }
    protected function generateUniqueUserName($name)
    {
        $base = Str::slug($name);

        do {
            $random = Str::lower(Str::random(5));
            $username = "{$base}-{$random}";
        } while (User::where('user_name', $username)->exists());

        return $username;
    }

    public function resetPassword(resetPasswordRequest $request)
    {
        try {
            if (isset($request->user_name)) {
                $user = User::where('user_name', $request->user_name)->first();
                $token = Str::random(100);

                DB::table('password_reset_tokens')->insert([
                    'user_id' => $user->id,
                    'token' => $token,
                    'created_at' => now(),
                ]);
                if (isset($user->phone)) {
                    return $this->successResponse(['token' => $token, 'type' => 'phone', 'phone' => $user->phone], 'OTP is sent check your sms inbox!');
                } elseif (isset($user->email)) {
                    return $this->successResponse(['token' => $token, 'type' => 'email', 'email' => $user->email], 'OTP is sent check your email inbox!');
                } else {
                    return $this->errorResponse('لم يتم العثور على المستخدم بهذه البيانات', 404);
                }
            } elseif (isset($request->phone)) {
                $user = User::where('phone', $request->country_code . $request->phone)->first();
                $token = Str::random(100);
                DB::table('password_reset_tokens')->insert([
                    'user_id' => $user->id,
                    'token' => $token,
                    'created_at' => now(),
                ]);
                return $this->successResponse(['token' => $token, 'type' => 'phone', 'phone' => $user->phone], 'OTP is sent check your sms inbox!');
            } elseif (isset($request->whatsapp)) {
                $user = User::where('phone', $request->country_code . $request->whatsapp)->first();
                $token = Str::random(100);
                DB::table('password_reset_tokens')->insert([
                    'user_id' => $user->id,
                    'token' => $token,
                    'created_at' => now(),
                ]);
                return $this->successResponse(['token' => $token, 'type' => 'whatsapp', 'whatsapp' => $user->phone], 'OTP is sent check your whatsapp inbox!');
            } elseif (isset($request->email)) {
                $user = User::where('email', $request->email)->first();
                $token = Str::random(100);
                DB::table('password_reset_tokens')->insert([
                    'user_id' => $user->id,
                    'token' => $token,
                    'created_at' => now(),
                ]);
                return $this->successResponse(['token' => $token, 'type' => 'email', 'email' => $user->email], 'OTP is sent check your email inbox!');
            } else {
                return $this->errorResponse('لم يتم العثور على المستخدم بهذه البيانات', 404);
            }

            # TODO : Send OTP depend on the method also delete all previous tokens


        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function checkOTP(CheckOTPRequest $request)
    {
        try {
            $user = User::find(DB::table('password_reset_tokens')->where('token', $request->token)->first()?->user_id);
            if (!$user) {
                return $this->errorResponse('لم يتم العثور على المستخدم بهذه البيانات', 404);
            }
            $code = 1234;
            # TODO : Delete Token
            $checkOTP = ($code == $request->otp);
            if ($checkOTP) {
                return $this->successResponse(null, 'الكود المدخل صحيح');
            } else {
                return $this->errorResponse('الكود المدخل غير صحيح', 403);
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to check user otp', 500);
        }
    }
    //    public function resendOTP(ResendOTPRequest $request)
//    {
//        try {
//
//            // find reset record
//            $resetRecord = DB::table('password_reset_tokens')->where('token', $request->token)->first();
//
//            // find user
//
//
//            // delete old token
//            DB::table('password_reset_tokens')->where('user_id', $user->id)->delete();
//
//            // generate new token + otp
//            $newToken = Str::random(100);
//            $otp = rand(1000, 9999); // generate 4-digit OTP
//
//            DB::table('password_reset_tokens')->insert([
//                'user_id' => $user->id,
//                'token' => $newToken,
//                'otp' => $otp, // you should add this column to your table if not already there
//                'created_at' => now(),
//            ]);
//
//            // Send OTP (SMS, WhatsApp, Email, etc. depending on user info)
//            if ($user->phone) {
//                // integrate with SMS service here
//                return $this->apiResponse(200, 'تم إرسال OTP جديد عبر SMS', null, [
//                    'token' => $newToken,
//                ]);
//            } elseif ($user->email) {
//                // integrate with mail here
//                return $this->apiResponse(200, 'تم إرسال OTP جديد عبر البريد الإلكتروني', null, [
//                    'token' => $newToken,
//                ]);
//            }
//
//            return $this->apiResponse(400, 'لا يوجد وسيلة اتصال لإرسال OTP');
//
//        } catch (\Exception $e) {
//            return $this->apiResponse(500, 'فشل في إعادة إرسال OTP: ' . $e->getMessage());
//        }
//    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $user = User::find(DB::table('password_reset_tokens')->where('token', $request->token)->first()?->user_id);
            if (!$user) {
                return $this->errorResponse('هذا المستخدم غير موجود', 404);
            }

            if ($user) {
                $user->update([
                    'password' => Hash::make($request->password),
                ]);
                return $this->successResponse(null, 'تم تغيير باسورد المستخدم.');
            } else {
                return $this->errorResponse('هذا المستخدم غير موجود', 404);
            }

            # TODO : Send OTP depend on the method


        } catch (\Exception $e) {
            return $this->errorResponse('Failed to change user password', 500);
        }
    }

}
