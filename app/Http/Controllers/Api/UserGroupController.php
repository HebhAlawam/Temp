<?php

namespace App\Http\Controllers\Api;

use App\Events\NotificationsEvent;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserGroupResource;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use App\Models\Group;
use App\Models\UserBook;
use Illuminate\Validation\Rule;




class UserGroupController extends Controller
{
    use ResponseJson;
    /**
     * Read all user groups in the system.
     *
     * @return jsonResponseWithoutMessage
     */
    public function index()
    {
        #####Asmaa####
        $userGroups = UserGroup::all();

        if ($userGroups->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(UserGroupResource::collection($userGroups), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Find an existing user group in the system by its id and display it.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function show(Request $request)
    {
        #####Asmaa####
        $validator = Validator::make($request->all(), ['user_group_id' => 'required']);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userGroup = UserGroup::find($request->user_group_id);

        if ($userGroup) {
            return $this->jsonResponseWithoutMessage(new UserGroupResource($userGroup), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Get all users in specific group.
     * 
     * @param  $group_id
     * @return jsonResponseWithoutMessage;
     */
    public function usersByGroupID($group_id)
    {
        $users = Group::with('users')->where('id', $group_id)->first();
        if ($users) {
            return $this->jsonResponseWithoutMessage($users, 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    /**
     * Assign role to specific user with add him/her to group.
     * after that,this user will receive a new notification about his/her new role and group(“assgin role” permission is required).
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */



    public function create(Request $request)
    {
        // Validate the input
        $validatedData = $request->validate([
            'email' => 'required',
            'group_id' => 'required',
            'user_type' => 'required',
        ]);


        $user = User::where('email', '=', $validatedData['email']);
        if (!$user) {
            return $this->jsonResponseWithoutMessage('email not found', 'data', 404);
        } else if (!is_null($user->parent_id)) {
            $user->parent_id = Auth::id();
        } else if (!$user->hasRole(validatedData['uesr_type'])) {
            return $this->jsonResponseWithoutMessage('User does not have the required role', 'data', 401);
        }



        $user->save();
        $userGroup = UserGroup::create(['user_id' => $user->id, 'group_id' =>  $validatedData['group_id'], validatedData['uesr_type']]);

        $userGroup->save();


        return response()->json([
            'status' => 'success',
            'message' => 'User added successfully',
            'data' => $user,
        ]);
    }



    /**
     * Add user to group with specific role 
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */

    public function addMember(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'group_id' => 'required',
                'email' => ['required',],
                'role_id' => 'required',
            ]
        );

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $group = Group::with('groupLeader')->where('id', $request->group_id)->first();

            if ($group) {
                $role = Role::find($request->role_id);

                if ($user->hasRole($role->name)) {
                    if ($role->name == 'ambassador' && $group->type->type='followup') {
                        if ($group->groupLeader->isEmpty())
                            return $this->jsonResponseWithoutMessage("لا يوجد قائد للمجموعة", 'data', 200);
                        else
                            $user->parent_id = $group->groupLeader[0]->id;
                    }
                    $role_in_arabic = [
                        'ambassador' => "سفير",
                        'leader' => "قائد",
                        'supervisor' => "مراقب",
                        'advisor' => 'موجه',
                        'consultant' => 'مستشار',
                        'admin' => 'ادارة'
                    ];

                    $userGroup = UserGroup::updateOrCreate(
                        ['user_id' => $user->id, 'group_id' => $group->id],
                        ['user_type' => $role->name]
                    );

                    $msg = "أنت الأن " . $role_in_arabic[$role->name] . " في المجموعة:  " . $group->name;
                    (new NotificationController)->sendNotification($user->id, $msg, 'roles');
                    //event(new NotificationsEvent($msg,$user));

                    return $this->jsonResponseWithoutMessage('تمت الاضافة', 'data', 202);
                } else {
                    return $this->jsonResponseWithoutMessage("قم بترقية السفير أولاً", 'data', 200);
                }
            } else {
                return $this->jsonResponseWithoutMessage("المجموعة غير موجودة", 'data', 200);
            }
        } else {
            return $this->jsonResponseWithoutMessage("المستخدم غير موجود", 'data', 200);
        }
    }

    public function assign_role(Request $request)
    {
        #####Asmaa####

        $validator = Validator::make(
            $request->all(),
            [
                'group_id' => 'required',
                'user_id' => [
                    'required',
                    Rule::unique('user_groups')->where(fn ($query) => $query->where('group_id', $request->group_id))
                ],
                'user_type' => 'required',
            ]
        );



        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->can('assign role')) {
            $user = User::find($request->user_id);
            $role = Role::where('name', $request->user_type)->first();
            $group = Group::where('id', $request->group_id)->first();

            if ($user && $role && $group) {
                $user->assignRole($role);

                $msg = "Now, you are " . $role->name . " in " . $group->name . " group";
                (new NotificationController)->sendNotification($request->user_id, $msg, 'roles');

                $userGroup = UserGroup::create($request->all());

                return $this->jsonResponse(new UserGroupResource($userGroup), 'data', 200, 'User Group Created Successfully');
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }
    /**
     * remove role to specific user with create group to him/her.
     * after that,this user will receive a new notification about termination reason(update role” permission is required).
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update_role(Request $request)
    {
        #####Asmaa####
        $validator = Validator::make(
            $request->all(),
            [
                'group_id' => 'required',
                'user_type' => 'required',
                'user_group_id' => 'required',
                'termination_reason' => 'required',
                'user_id' => [
                    'required',
                    Rule::unique('user_groups')->where(fn ($query) => $query->where('group_id', $request->group_id))->ignore(request('user_id'), 'user_id')
                ],
            ]
        );

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userGroup = UserGroup::find($request->user_group_id);

        if ($userGroup) {
            if (Auth::user()->can('update role')) {

                $user = User::find($request->user_id);
                $role = Role::where('name', $request->user_type)->first();
                $group = Group::where('id', $request->group_id)->first();

                if ($user && $role && $group) {
                    $user->removeRole($role);

                    $msg = "You are not a " . $role->name . " in " . $group->name . " group anymore, because you " . $request->termination_reason;
                    (new NotificationController)->sendNotification($request->user_id, $msg, 'roles');

                    $userGroup->update($request->all());

                    return $this->jsonResponse(new UserGroupResource($userGroup), 'data', 200, 'User Group Updated Successfully');
                } else {
                    throw new NotFound;
                }
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }
    /**
     * Read all user groups by its id in the system.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function list_user_group(Request $request)
    {
        #####Asmaa####

        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => 'required',
            ]
        );

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userGroups = UserGroup::where('user_id', $request->user_id)->get();

        if ($userGroups) {
            return $this->jsonResponseWithoutMessage(UserGroupResource::collection($userGroups), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
}
