<?php

namespace App\Repository\Auth;

use App\Http\Requests\Auth\AuthUserRequest;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\ChangePassRequest;
use App\Models\User;
use Exception;
use Illuminate\Support\Str;
use App\Repository\Auth\AuthRepository;
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
 *
 */


class EloquentAuthRepository implements AuthRepository
{
    // Parent Controller- function Store()
    /**
     * @param App\Http\Requests\AuthUserRequest
     * @param App\Http\Requests\AuthUserRequest $request
     */

    public function store(AuthUserRequest $request)
    {
        try {
            // Validation---@source-App\Http\Requests\AuthUserRequest
            $validator = $request->validated();

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

    /**
     * @Parent Controller- function loginAuth()
     * @param App\Http\Requests\LoginUserRequest
     * @param App\Http\Requests\LoginUserRequest $request
     * 
     * * Function LoginAuth **
     * validate email password
     * Check if the user Existing or Not    (OK)
     * Check if the User is Suspended or Not    (OK)
     * Check UserExist && Data Present On Redis Database -> Check Password (OK)
     * If Data not Present on Redis Database -> Authentication Check by SQL Database    (OK)
     */

    public function loginAuth(LoginUserRequest $request)
    {
        try {
            // Validation
            $validator = $request->validated();

            // checking user is existing or not
            $emailInfo = User::where('email', $request->email)->first();
            if (!$emailInfo) {
                $response = ['status' => false, 'message' => 'Oops! Given email does not exist'];
                return response($response, 401);
            }

            // check if suspended user
            if ($emailInfo->Suspended == -1) {
                $response = ['status' => 'Cant logged in!! You Have Been Suspended !'];
                return response()->json($response, 401);
            }

            // Redis Authentication if data already existing in redis database
            $redis = Redis::connection();
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
                            'remember_token' => $token,
                            'created_at' => $emailInfo->created_at,
                            'updated_at' => $emailInfo->updated_at
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
            if ($emailInfo) {
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
                            'remember_token' => $token,
                            'created_at' => $emailInfo->created_at,
                            'updated_at' => $emailInfo->updated_at
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
        catch (Exception $e) {
            return $e;
        }
    }

    /**
     * @function function LogOut
     * Save null on remember_token in users table 
     * delete token
     * delete user key in redis database
     * @return message
     */
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
    /**
     * Parent @Controller- function changePass()
     * @param App\Http\Requests\Request 
     * @param App\Http\Requests\Request $request 
     * 
     * 
     */
    public function changePass(ChangePassRequest $request)
    {
        try {
            $validator = $request->validated();

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

        $store = Redis::get('user:' . auth()->user()->id);
        $manage = json_decode($store);
        return response()->json([
            'id' => $manage->id,
            'email' => $manage->email,
            'password' => $manage->password,
            'token' => $manage->remember_token,
            'created_at' => getFormattedDate($manage->created_at, 'd-M-Y h:i'),
            'updated_at' => getFormattedDate($manage->updated_at, 'd-M-Y h:i')
        ]);

        // if (Redis::del('user:' . auth()->user()->id)) {
        //     return response()->json('Deleted');
        // } else {
        //     return response()->json('already Deleted');
        // }

    }
}
