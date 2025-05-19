<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\CheckUserNameRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\Register;
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

class AuthController extends Controller
{
    use ApiResponse;
    public function register(Register $request)
    {
        DB::beginTransaction();

        try {
            $user_name =$this->generateUniqueUserName($request->name);
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


            $userData = $user->makeHidden(['id','created_at', 'updated_at'])->toArray();

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

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to logout, please try again'], 500);
        }

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function getUser()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
            return response()->json($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to fetch user profile'], 500);
        }
    }

    public function updateUser(Request $request)
    {
        try {
            $user = Auth::user();
            $user->update($request->only(['name', 'email']));
            return response()->json($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to update user'], 500);
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
}
