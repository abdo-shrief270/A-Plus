<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\CheckOTPRequest;
use App\Http\Requests\Auth\CheckUserNameRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\Register;
use App\Http\Requests\Auth\resetPasswordRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Models\Student;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use function PHPUnit\Framework\isEmpty;

class AuthController extends Controller
{
    use ApiResponse;
    public function register(Register $request)
    {
        DB::beginTransaction();

        try {
            if(isset($request->user_name)){
                $user_name =$request->user_name;
            }else{
                $user_name =$this->generateUniqueUserName($request->name);
            }

            $user = User::create([
                'name'      => $request->name,
                'user_name' => $user_name,
                'phone'     => $request->phone,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'type'      => $request->type,
                'gender'    => $request->gender,
            ]);

            $studentData = null;

            if ($request->type === 'student') {
                $student = Student::create([
                    'user_id'   => $user->id,
                    'exam_id'   => $request->exam_id,
                    'exam_date' => $request->exam_date,
                    'id_number' => $request->id_number,
                ]);
                $studentData = $student->makeHidden(['id','created_at', 'updated_at','user_id'])->toArray();
            } elseif ($request->type !== 'parent') {
                DB::rollBack();
                return $this->apiResponse(401, 'Invalid User Type');
            }

            $token = auth('api')->login($user);

            DB::commit();


            $userData = $user->makeHidden(['id','created_at', 'updated_at','password'])->toArray();

            if ($studentData) {
                $userData = array_merge($userData,$studentData);
            }

            return $this->apiResponse(200, 'User Registered Successfully', null, [
                'token'   => $token,
                'user'    => array_merge(['type' => $request->type], $userData)
            ]);

        } catch (JWTException $e) {
            DB::rollBack();
            return $this->apiResponse(500, 'Could not create token');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->apiResponse(500, 'Registration failed: ' . $e->getMessage());
        }
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('user_name', 'password');
        try {
            if ($token = auth('api')->attempt($credentials)) {
                return $this->apiResponse(200,'User Logged In Successfully',null,[
                    'token' => $token,
                    'type' => \auth('api')->user()->type,
                    'expires_in' => auth('api')->factory()->getTTL() * 60,
                ]);
            }elseif($token = auth('schools')->attempt($credentials)){
                return $this->apiResponse(200,'User Logged In Successfully',null,[
                    'token' => $token,
                    'type' => 'school',
                    'expires_in' => auth('api')->factory()->getTTL() * 60,
                ]);
            }else{
                return $this->apiResponse(401,'Invalid credentials');
            }
        } catch (JWTException $e) {
            return $this->apiResponse(500,'Could not create token');
        }
    }


    public function getUser()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->apiResponse(404,'User not found');
            }
            return $this->apiResponse(200,'User Data Retrieved Successfully',null,[
                'data' => $user->makeHidden(['id','created_at', 'updated_at','remember_token','password'])->toArray(),
            ]);
        } catch (JWTException $e) {
            return $this->apiResponse(404,'Failed to fetch user profile');
        }
    }

    public function updateUser(UpdateUserRequest $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->apiResponse(404,'User not found');
            }
            if (!Hash::check($request->old_password, $user->password)) {
                return $this->apiResponse(403,'Validation Error',['old_password' => 'كلمة المرور القديمة غير صحيحة.']);
            }

            $user->update($request->all());
            return $this->apiResponse(200,'User Profile Updated Successfully',null,[
                'data' => $user->makeHidden(['id','created_at', 'updated_at','remember_token','password'])->toArray(),
            ]);
        } catch (JWTException $e) {
            return $this->apiResponse(500,'Failed to update user profile');
        }
    }


    public function checkUserName(CheckUserNameRequest $request)
    {
        try {
            $user = User::where('user_name', $request->user_name)->first();
            $available = !$user;
        } catch (\Exception $e) {
            return $this->apiResponse(500, 'Could not check UserName');
        }

        return $this->apiResponse(200, 'Username availability checked', null, [
            'available' => $available,
        ]);
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
            if(isset($request->user_name)){
                $user=User::where('user_name',$request->user_name)->first();
                if(isset($user->phone)){
                    return $this->apiResponse(200,'OTP is sent check your sms inbox!',null,['otpMethod'=>'phone']);
                }elseif(isset($user->email)){
                    return $this->apiResponse(200,'OTP is sent check your email inbox!',null,['otpMethod'=>'email']);
                }else{
                    return $this->apiResponse(404, 'لم يتم العثور على المستخدم بهذه البيانات');
                }
            }
            elseif(isset($request->phone)){
                $user=User::where('phone',$request->phone)->first();
                return $this->apiResponse(200,'OTP is sent check your sms inbox!',null,['otpMethod'=>'phone']);
            }
            elseif(isset($request->whatsapp)){
                $user=User::where('phone',$request->whatsapp)->first();
                return $this->apiResponse(200,'OTP is sent check your whatsapp inbox!',null,['otpMethod'=>'whatsapp']);
            }
            elseif(isset($request->email)){
                $user=User::where('email',$request->email)->first();
                return $this->apiResponse(200,'OTP is sent check your email inbox!',null,['otpMethod'=>'email']);
            }else{
                return $this->apiResponse(404, 'لم يتم العثور على المستخدم بهذه البيانات');
            }

            # TODO : Send OTP depend on the method


        } catch (\Exception $e) {
            return $this->apiResponse(500,'Failed to reset user password');
        }
    }

    public function checkOTP(CheckOTPRequest $request)
    {
        try {
            if(isset($request->user_name)){
                $user=User::where('user_name',$request->user_name)->first();
                if(isset($user->phone)){
                    return $this->apiResponse(200,'OTP is sent check your sms inbox!',null,['otpMethod'=>'phone']);
                }elseif(isset($user->email)){
                    return $this->apiResponse(200,'OTP is sent check your email inbox!',null,['otpMethod'=>'email']);
                }else{
                    return $this->apiResponse(404, 'لم يتم العثور على المستخدم بهذه البيانات');
                }
            }
            elseif(isset($request->phone)){
                $user=User::where('phone',$request->phone)->first();
                return $this->apiResponse(200,'OTP is sent check your sms inbox!',null,['otpMethod'=>'phone']);
            }
            elseif(isset($request->whatsapp)){
                $user=User::where('phone',$request->whatsapp)->first();
                return $this->apiResponse(200,'OTP is sent check your whatsapp inbox!',null,['otpMethod'=>'whatsapp']);
            }
            elseif(isset($request->email)){
                $user=User::where('email',$request->email)->first();
                return $this->apiResponse(200,'OTP is sent check your email inbox!',null,['otpMethod'=>'email']);
            }else{
                return $this->apiResponse(404, 'لم يتم العثور على المستخدم بهذه البيانات');
            }

            # TODO : Check OTP depend on the method


        } catch (\Exception $e) {
            return $this->apiResponse(500,'Failed to check user otp');
        }
    }
}
