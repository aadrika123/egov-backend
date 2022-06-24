<?php

namespace App\Repository\Auth;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Repository\Auth\AuthRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

/*
* @Source Controller:-App\Http\Controller\UserController 

 * Author Name-Anshu Kumar
 * 
 * Start Date  - 24-06-2022
 * Finish Date - 24-06-2022
 * 
 * * Coding Test **
 * Code Tested By - Anil Sir
 * Code Testing Date - 24-06-2022
 * Feedback- 
 * 
 * * Function LoginAuth **
 * Check if the user Existing or Not    (OK)
 * Check if the User is Suspended or Not    (OK)
 * Check Data Present On Redis Database -> Check Password (OK)
 * If Data not Present on Redis Database -> Authentication Check by SQL Database    (OK)
 *
 */


class EloquentAuthRepository implements AuthRepository
{
    // Parent Controller- function Store()
    public function store(Request $request)
    {
        try {
            $rules = array(
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => [
                    'required',
                    'min:6',
                    'max:255',
                    'regex:/[a-z]/',      // must contain at least one lowercase letter
                    'regex:/[A-Z]/',      // must contain at least one uppercase letter
                    'regex:/[0-9]/',      // must contain at least one digit
                    'regex:/[@$!%*#?&]/'  // must contain a special character
                ]
            );

            $validator = Validator::make($request->input(), $rules);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $user = new User;
            $user->UserName = $request->name;
            $user->Mobile = $request->mobile;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $token = Str::random(80);       //Generating Random Token for Initial
            $user->remember_token = $token;
            $user->save();
            return response()->json("Saved Successfully", 201);
        } catch (Exception $e) {
            return $e;
        }
    }

    // Parent Controller- function loginAuth()
    public function loginAuth(Request $request)
    {
        try {
            $rules = array(
                'email' => ['required', 'string', 'email'],
                'password' => [
                    'required'
                ]
            );

            $validator = Validator::make($request->input(), $rules);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            // checking user is existing or not
            $emailInfo = User::where('email', $request->email)->first();

            // Redis Authentication if data already existing in redis database
            $redis = Redis::connection();

            // check if suspended user
            if ($emailInfo->Suspended == -1) {
                $response = ['status' => 'Cant logged in!! You Have Been Suspended !'];
                return response()->json($response, 401);
            }

            /*   if email exists then the condition applies  */
            if ($emailInfo && $user = Redis::get('user:' . $emailInfo->id)) {
                $info = json_decode($user);
                // AUTHENTICATING PASSWORD IN HASH
                if (Hash::check($request->password, $info->password)) {

                    $token = $emailInfo->createToken('my-app-token')->plainTextToken;
                    $emailInfo->remember_token = $token;
                    $emailInfo->save();

                    $redis->set(
                        'user:' . $emailInfo->id,
                        json_encode([
                            'id' => $emailInfo->id,
                            'email' => $request->email,
                            'password' => Hash::make($request->password),
                            'remember_token' => $token
                        ])
                    );
                    Redis::expire('user:' . $emailInfo->id, 18000);         // EXPIRE KEY AFTER 5 HOURS
                    $response = ['status' => 'You Have Logged In!', 'token' => $token];
                    return response($response, 200);
                }
                // AUTHENTICATING PASSWORD IN HASH
                else {
                    $response = ['status' => false, 'message' => 'Incorrect Password'];
                    return response($response, 401);
                }
            }
            /*  End if email exists then the condition applies  */

            // End Redis Authentication if data already existing in redis database 

            // Authentication Using Sql Database
            else {
                if (!$emailInfo) {
                    $response = ['status' => false, 'message' => 'Oops! Given email does not exist'];
                    return response($response, 401);
                } else {
                    // Authenticating Password
                    if (Hash::check($request->password, $emailInfo->password)) {
                        $token = $emailInfo->createToken('my-app-token')->plainTextToken;
                        $emailInfo->remember_token = $token;
                        $emailInfo->save();


                        $redis->set(
                            'user:' . $emailInfo->id,
                            json_encode([
                                'id' => $emailInfo->id,
                                'email' => $request->email,
                                'password' => Hash::make($request->password),
                                'remember_token' => $token
                            ])
                        );
                        Redis::expire('user:' . $emailInfo->id, 18000);     //EXPIRE KEY IN AFTER 5 HOURS

                        $response = ['status' => true, 'token' => $token];
                        return response($response, 200);
                    } else {
                        $response = ['status' => false, 'message' => 'Incorrect Password'];
                        return response($response, 401);
                    }
                }
            }
            // Authentication Using Sql Database
        } catch (Exception $e) {
            return $e;
        }
    }

    // Parent Controller- function logOut()
    public function logOut()
    {
        try {
            $id = auth()->user()->id;
            $user = User::where('id', $id)->first();
            $user->remember_token = null;
            $user->save();

            $user->tokens()->delete();

            Redis::connection();
            $redis = Redis::del('user:' . $id);     //Deleting Key from Redis Database

            if ($redis) {
                return response()->json(['Token' => $user->remember_token ?? '', 'status' => $redis], 200);
            } else {
                return response()->json(['message' => 'Already Deleted'], 400);
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    // Parent Controller- function changePass()
    public function changePass(Request $request)
    {
        try {
            $rules = array(
                'password' => [
                    'required',
                    'min:6',
                    'max:255',
                    'regex:/[a-z]/',      // must contain at least one lowercase letter
                    'regex:/[A-Z]/',      // must contain at least one uppercase letter
                    'regex:/[0-9]/',      // must contain at least one digit
                    'regex:/[@$!%*#?&]/'  // must contain a special character
                ]
            );

            $validator = Validator::make($request->input(), $rules);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $id = auth()->user()->id;
            $user = User::find($id);
            $user->password = Hash::make($request->password);
            $user->save();

            Redis::del('user:' . auth()->user()->id);   //DELETING REDIS KEY

            return response()->json(['Status' => 'True', 'Message' => 'Successfully Changed the Password'], 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    // Redis expiry, set, update testing
    public function testing()
    {
        // $user = auth()->user()->UserName;
        // $redis = Redis::set('UserName', $user);
        // return Redis::get('UserName');

        // Redis::pipeline(function ($pipe) {
        //     for ($i = 0; $i < 1000; $i++) {
        //         $pipe->set("key:$i", $i);
        //     }
        // });
        // $user = User::all();
        // $store = Redis::set('key', $user);

        // $store = Redis::get('user:' . auth()->user()->email);
        // $manage = json_decode($store);
        // return response()->json($manage->remember_token, 200);

        // if (Redis::del('user:' . auth()->user()->id)) {
        //     return response()->json('Deleted');
        // } else {
        //     return response()->json('already Deleted');
        // }

        return response()->json('Accessed');
    }
}
