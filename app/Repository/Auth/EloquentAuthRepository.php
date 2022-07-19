<?php

namespace App\Repository\Auth;

use App\Http\Requests\Auth\AuthUserRequest;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\ChangePassRequest;
use App\Models\User;
use App\Repository\Auth\AuthRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Exception;
use App\Traits\Auth;
use Illuminate\Support\Facades\DB;

/*
 * Parent Controller:-App\Http\Controller\UserController 
 * ----------------------------------------------------------------------------------------------
 * Author Name-Anshu Kumar
 * ----------------------------------------------------------------------------------------------
 * Start Date  - 24-06-2022
 * Finish Date - 24-06-2022
 * ----------------------------------------------------------------------------------------------
 * Coding Test
 * ----------------------------------------------------------------------------------------------
 * Code Tested By - Anil Sir
 * Code Testing Date - 24-06-2022
 * Feedback- 
 * 
 */

class EloquentAuthRepository implements AuthRepository
{
    use Auth;
    /**
     * -----------------------------------------------
     * Parent Controller- function Store()
     * -----------------------------------------------
     * @param App\Http\Requests\AuthUserRequest
     * @param App\Http\Requests\AuthUserRequest $request
     */

    public function store(AuthUserRequest $request)
    {
        try {
            // Validation---@source-App\Http\Requests\AuthUserRequest
            $user = new User;
            $this->saving($user, $request);                     // Storing data using Auth trait
            $user->save();
            return response()->json(["Registered Successfully", "Please Login to Continue"], 200);
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Editing Users
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     * @param user_id $id
     * --------------------------------------------------------------------------------------------
     * validate
     * Checking if the request email is already existing of not
     * update using AuthTrait
     */

    public function update(Request $request, $id)
    {
        $request->validate([
            'Name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'Password' => [
                'required',
                'min:6',
                'max:255',
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/'  // must contain a special character
            ]
        ]);
        try {
            $user = User::find($id);
            $stmt = $user->email == $request->email;
            if ($stmt) {
                $this->saving($user, $request);
                $this->savingExtras($user, $request);
                $user->save();
                Redis::del('user:' . $id);                                  //Deleting Key from Redis Database
                return response()->json('User Record Successfully Updated', 200);
            }
            if (!$stmt) {
                $check = User::where('email', '=', $request->email)->first();
                if ($check) {
                    return response()->json('Email is already existing', 400);
                }
                if (!$check) {
                    $this->saving($user, $request);
                    $this->savingExtras($user, $request);
                    $user->save();
                    Redis::del('user:' . $id);                               //Deleting Key from Redis Database
                    return response()->json('User Record Successfully Updated', 200);
                }
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * ------------------------------------------------
     * @Parent Controller- function loginAuth()
     * ------------------------------------------------
     * @param App\Http\Requests\LoginUserRequest
     * @param App\Http\Requests\LoginUserRequest $request
     * -------------------------------------------------
     * Function LoginAuth 
     * -------------------------------------------------
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
            if ($emailInfo->suspended == true) {
                $response = ['status' => 'Cant logged in!! You Have Been Suspended !'];
                return response()->json($response, 401);
            }

            // Redis Authentication if data already existing in redis database
            $redis = Redis::connection();
            /*   if email exists then the condition applies  */
            if ($emailInfo && $user = Redis::get('user:' . $emailInfo->id)) {
                $info = json_decode($user);
                // AUTHENTICATING PASSWORD IN HASH
                if (Hash::check($request->Password, $info->password)) {
                    $token = $emailInfo->createToken('my-app-token')->plainTextToken;
                    $emailInfo->remember_token = $token;
                    $emailInfo->save();

                    $this->redisStore($redis, $emailInfo, $request, $token);   // Trait for update Redis

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
                if (Hash::check($request->Password, $emailInfo->password)) {
                    $token = $emailInfo->createToken('my-app-token')->plainTextToken;
                    $emailInfo->remember_token = $token;
                    $emailInfo->save();

                    $redis = Redis::connection();                   // Redis Connection

                    $this->redisStore($redis, $emailInfo, $request, $token);   // Trait for update Redis

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
     * -----------------------------------------------
     * @function function LogOut
     * -----------------------------------------------
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

    /**
     * -----------------------------------------------
     * Parent @Controller- function changePass()
     * -----------------------------------------------
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
            $user->password = Hash::make($request->Password);
            $user->save();

            Redis::del('user:' . auth()->user()->id);   //DELETING REDIS KEY

            return response()->json(['Status' => 'True', 'Message' => 'Successfully Changed the Password'], 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Get All Users
     */
    public function getAllUsers()
    {
        $users = DB::select("select u.id,
                        u.user_name,
                        u.mobile,
                        u.email,
                        u.user_type,
                        u.roll_id,
                        r.role_name,
                        u.ulb_id,
                        um.ulb_name,
                        u.suspended,
                        u.super_user,
                        u.description,
                        u.workflow_participant,
                        u.created_at,
                        u.updated_at
                from users u
                left join role_masters r on r.id=u.roll_id
                left join ulb_masters um on um.id=u.ulb_id
                order by u.id desc
                        ");
        return $users;
    }

    /**
     * Get User by IDs
     * ----------------------------------------------------------------------------------------
     * @param user_id $id
     */
    public function getUser($id)
    {
        $user = DB::select("select u.id,
                                u.user_name,
                                u.mobile,
                                u.email,
                                u.user_type,
                                u.roll_id,
                                r.role_name,
                                u.ulb_id,
                                um.ulb_name,
                                u.suspended,
                                u.super_user,
                                u.description,
                                u.workflow_participant,
                                u.created_at,
                                u.updated_at
                            from users u
                            left join role_masters r on r.id=u.roll_id
                            left join ulb_masters um on um.id=u.ulb_id
                            where u.id=$id");
        if ($user) {
            return $user;
        } else {
            return response()->json('User Not Available for this Id', 404);
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
        Redis::connection();
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
