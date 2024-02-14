<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserExceptionResource;
use App\Models\UserException;
use App\Models\User;
use App\Models\Group;
use App\Models\UserGroup;
use App\Models\Week;
use Illuminate\Http\Request;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Models\ExceptionType;
use App\Models\Mark;
use App\Models\Thesis;
use App\Traits\MediaTraits;
use App\Traits\PathTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * UserExceptionController to create exception for user
 *
 * Methods:
 *  - CRU
 *  - revoke: Delete
 *  - getMonth
 *
 */

class UserExceptionController extends Controller
{
    use ResponseJson, PathTrait, MediaTraits;

    /*
        # Create Exception Request Endpoint Documentation

        This endpoint allows users to submit various types of exception requests, including freezing weeks, exam schedules, and other exceptional circumstances. It supports multiple exception types and handles each scenario with specific logic.

        ## Endpoint

        `POST /exceptions/create`

        ## Request Body

        The request body should include the following parameters:

        - `reason` (string, required): The reason for requesting the exception.
        - `type_id` (integer, required): The ID representing the type of exception being requested.
        - `end_at` (date, optional): The proposed end date for the exception, applicable for certain types of exceptions.
        - `desired_duration` (string, optional): Desired duration for the exception, if applicable.
        - `exam_media` (file, optional): Media file related to the exam exception request.

        ## Functionality

        - Validates the input to ensure required fields are present and properly formatted.
        - Processes the request based on the type of exception:
            - Freezing weeks (current or next week) with specific eligibility checks.
            - Exam-related exceptions with an option to upload exam media.
            - Exceptional freezing requests with additional logic for admins.
        - Automatically accepts or sets to pending based on user roles and request types.
        - Notifies the user and relevant authorities (like leaders or admins) about the request status.
        - Handles media uploads for exam-related requests.

        ## Response

        - Returns a success message indicating the status of the exception request (e.g., submitted, accepted, pending approval).

        ## Errors

        - `500 Internal Server Error`: If validation fails or if an unexpected error occurs during processing.
        - `NotFound`: If an invalid exception type is requested.
        - The error message will include details of the exception encountered.
    */

    public function create(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'reason' => 'required|string',
            'type_id' => 'required|int',
            'end_at' => 'date|after:yesterday',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $current_week = Week::latest()->first();
        $exception['reason'] = $request->reason;
        $exception['type_id'] =  $request->type_id;
        $exception['user_id'] = Auth::id();

        if ($request->has('desired_duration')) {
            $exception['desired_duration'] =  $request->desired_duration;
        }


        //get types of exceptions
        $freezCurrentWeek = ExceptionType::where('type', config("constants.FREEZE_THIS_WEEK_TYPE"))->first();
        $freezNextWeek = ExceptionType::where('type', config("constants.FREEZE_NEXT_WEEK_TYPE"))->first();
        $exceptionalFreez = ExceptionType::where('type', config("constants.EXCEPTIONAL_FREEZING_TYPE"))->first();
        $monthlyExam = ExceptionType::where('type', config("constants.EXAMS_MONTHLY_TYPE"))->first();
        $FinalExam = ExceptionType::where('type', config("constants.EXAMS_SEASONAL_TYPE"))->first();
        $withdrawn = ExceptionType::where('type', config("constants.WITHDRAWN"))->first();

        $group = UserGroup::where('user_id', Auth::id())->where('user_type', 'ambassador')->first()->group;
        $leader_id = Auth::user()->parent_id;
        $authID = Auth::id();

        if ($request->type_id == $freezCurrentWeek->id || $request->type_id == $freezNextWeek->id) { // تجميد عادي - الاسبوع الحالي أو القادم
            if (!Auth::user()->hasRole(['leader', 'supervisor', 'advisor', 'consultant', 'admin'])) {


                // check if user is within his first month
                if (Auth::user()->created_at >=  Carbon::now()->subMonth()) {
                    return $this->jsonResponseWithoutMessage("عذرًا لا يمكنك استخدام نظام التجميد إلا بعد 4 أسابيع من انضمامك للمشروع", 'data', 200);
                }

                $currentDate = Carbon::now()->format('Y-m-d');

                $laseFreezing = UserException::where('user_id', $authID)
                    ->whereIn('status', ['finished', 'accepted'])
                    ->whereHas('type', function ($query) {
                        $query->where('type', config('constants.FREEZE_THIS_WEEK_TYPE'))
                            ->orWhere('type', config('constants.FREEZE_NEXT_WEEK_TYPE'));
                    })->latest()->pluck('created_at')->first();

                $dateAfter4Weeks = Carbon::parse($laseFreezing)->addWeeks(4)->format('Y-m-d');

                //check if user freezed in last 4 weeks
                if ($laseFreezing && ($dateAfter4Weeks > $currentDate)) {
                    return $this->jsonResponseWithoutMessage("عذرًا لا يمكنك استخدام نظام التجميد إلا مرة كل 4 أسابيع", 'data', 200);
                }

                $exception['status'] = 'accepted';
                $exception['desired_duration'] =  'أسبوع واحد';

                if ($request->type_id == $freezCurrentWeek->id) {
                    /**
                     * @todo: slow query - asmaa
                     */
                    $this->updateUserMarksToFreez($current_week->id, $authID);

                    $exception['week_id'] =  $current_week->id;
                    $exception['start_at'] = $current_week->created_at;
                    $exception['end_at'] = Carbon::parse($current_week->created_at->addDays(7))->format('Y-m-d');
                } else {
                    $exception['week_id'] =  $current_week->id;
                    $exception['start_at'] = Carbon::parse($current_week->created_at->addDays(7))->format('Y-m-d');
                    $exception['end_at'] = Carbon::parse($current_week->created_at->addDays(14))->format('Y-m-d');
                }

                $userException = UserException::create($exception);

                //Notify User
                $userToNotify = User::find($authID);
                $userToNotify->notify(new \App\Notifications\FreezException($userException->start_at, $userException->end_at));

                //Notify Leader
                $msg = "قام السفير " . Auth::user()->name . " باستخدام نظام التجميد";
                (new NotificationController)->sendNotification($leader_id, $msg, LEADER_EXCEPTIONS, $this->getExceptionPath($userException->id));

                return $this->jsonResponseWithoutMessage("تم قبول طلب التجميد", 'data', 200);
            } else {
                return $this->jsonResponseWithoutMessage("عذرًا لا يمكنك استخدام نظام التجميد", 'data', 200);
            }
        } elseif ($request->type_id == $monthlyExam->id || $request->type_id == $FinalExam->id) { // نظام امتحانات - شهري أو فصلي
            $exception['status'] = 'pending';
            $userException = UserException::create($exception);

            //if there is Media
            if ($request->hasFile('exam_media')) {
                //exam_media/user_id/
                $folder_path = 'exam_media/' . $authID;

                $this->createMedia($request->exam_media, $userException->id, 'user_exception', $folder_path);
            }


            //Notify User
            $userToNotify = User::find(Auth::id());
            $userToNotify->notify(new \App\Notifications\ExamException());

            //Notify Leader
            $msg = "قام السفير " . Auth::user()->name . " بطلب نظام امتحانات";
            (new NotificationController)->sendNotification($leader_id, $msg, LEADER_EXCEPTIONS, $this->getExceptionPath($userException->id));

            return $this->jsonResponseWithoutMessage("تم رفع طلبك لنظام الامتحانات، انتظر موافقة القائد", 'data', 200);
        } elseif ($request->type_id == $exceptionalFreez->id) { // تجميد استثنائي

            $successMessage = "";
            if (Auth::user()->hasRole('admin')) {
                $exception['status'] = 'accepted';
                $exception['end_at'] = Carbon::parse($request->end_at)->format('Y-m-d');
                $successMessage = "تم تجميدك لغاية " . $exception['end_at'];
            } else {
                $exception['status'] = 'pending';
                $successMessage = "تم رفع طلبك للتجميد الاستثنائي انتظر الموافقة";
            }

            $userException = UserException::create($exception);
            $userException->fresh();

            //Notify User
            $userToNotify = User::find(Auth::id());

            if (Auth::user()->hasRole('admin')) {
                $userToNotify->notify(new \App\Notifications\FreezException($userException->start_at, $userException->end_at));
            } else {

                $userToNotify->notify(new \App\Notifications\ExceptionalException());
            }

            //if not admin
            if (!Auth::user()->hasRole('admin')) {
                //if advisor or consultant, notify admin
                $msg = "قام السفير " . Auth::user()->name . " بطلب نظام تجميد استثنائي";
                if (Auth::user()->hasRole(['advisor', 'supervisor'])) {

                    $admin_id = $group->admin()->first()->id;
                    (new NotificationController)->sendNotification($admin_id, $msg, ADMIN_EXCEPTIONS, $this->getExceptionPath($userException->id));
                } else {
                    (new NotificationController)->sendNotification($leader_id, $msg, LEADER_EXCEPTIONS, $this->getExceptionPath($userException->id));
                }
            }
            return $this->jsonResponseWithoutMessage($successMessage, 'data', 200);
        } elseif ($request->type_id == $withdrawn->id) { // انسحاب مؤقت
            $successMessage = "";

            if (!Auth::user()->hasRole(['leader', 'supervisor', 'advisor', 'consultant', 'admin'])) {

                $exception['status'] = 'pending';
                $successMessage = "تم رفع طلبك للانسحاب انتظر الموافقة";

                $userException = UserException::create($exception);
                $userException->fresh();
            } else {
                return $this->jsonResponseWithoutMessage('يرجى مراجعة المسؤول عنك', 'data', 200);
            }
            return $this->jsonResponseWithoutMessage($successMessage, 'data', 200);
        } else {
            throw new NotFound;
        }
    }


    /*
        # Set Exceptional Freezing Request Endpoint Documentation

        This endpoint allows administrators or authorized users to set an exceptional freezing request for a user. This type of request typically applies to an exceptional circumstance that requires halting a user's active participation for a specified week.

        ## Endpoint

        `POST /exceptions/exceptional-freezing`

        ## Request Body

        The request body should include the following parameters:

        - `reason` (string, required): The reason for the exceptional freezing request.
        - `user_id` (integer, required): The ID of the user for whom the exceptional freeze is being requested.
        - `week_id` (integer, required): The ID of the week during which the freeze is to be applied.

        ## Functionality

        - Validates the input to ensure required fields are present and correctly formatted.
        - Begins a database transaction to ensure data integrity.
        - Retrieves the exceptional freezing type ID from the `ExceptionType` model.
        - Creates or updates a `UserException` record with the specified user ID, week ID, reason, and sets the status to 'pending'.
        - Sends a notification to the parent or leader of the authenticated user, informing them of the exceptional freeze request.
        - Commits the transaction upon successful creation or update of the freeze request.

        ## Response

        - Returns a success message indicating that the exceptional freeze request has been submitted and is pending approval.

        ## Errors

        - `500 Internal Server Error`: If validation fails, if there are issues with the database transaction, or if an unexpected error occurs during processing. The error message will include details of the exception encountered.
    */

    public function setExceptionalFreez(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'reason' => 'required|string',
            'user_id' => 'required|int',
            'week_id' => 'required|int',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        try {
            DB::beginTransaction();

            //get exceptional freez type id
            $exceptionalFreez = ExceptionType::where('type', config("constants.EXCEPTIONAL_FREEZING_TYPE"))->first();

            $week = Week::find($request->week_id);
            $exception['reason'] = $request->reason;
            $exception['type_id'] =  $exceptionalFreez->id;
            $exception['user_id'] = $request->user_id;

            $parentOfAuth = Auth::user()->parent_id;

            $successMessage = "";
            $exception['status'] = 'pending';
            $exception['week_id'] =  $week->id;
            $exception['start_at'] = $week->created_at;
            $exception['end_at'] = Carbon::parse($week->created_at->addDays(7))->format('Y-m-d');

            $userException = UserException::updateOrCreate(
                ['user_id' => $request->user_id, 'week_id' => $week->id],
                $exception
            );
            $userException->fresh();

            // response msg
            $successMessage = "تم طلب التجميد لغاية " . $exception['end_at'];


            //Notify User
            $userToNotify = User::find($request->user_id);

            //notify parent of Auth
            $msg = "قام  " . Auth::user()->name . " بطلب نظام تجميد استثنائي للسفير  "  . $userToNotify->name;
            (new NotificationController)->sendNotification($parentOfAuth, $msg, ADMIN_EXCEPTIONS, $this->getExceptionPath($userException->id));

            DB::commit();

            return $this->jsonResponseWithoutMessage($successMessage, 'data', 200);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }

    /*
        # Set New User Exception Endpoint Documentation

        This endpoint allows administrators or authorized users to set an exception for a new user, typically exempting them from specific requirements or considerations for a designated week.

        ## Endpoint

        `POST /exceptions/new-user`

        ## Request Body

        The request body should include the following parameters:

        - `reason` (string, required): The reason for setting the user as a new user.
        - `user_id` (integer, required): The ID of the user who is being set as a new user.
        - `week_id` (integer, required): The ID of the week during which the user is being considered new.

        ## Functionality

        - Validates the input to ensure required fields are present and correctly formatted.
        - Begins a database transaction to ensure data integrity.
        - Retrieves the exceptional freezing type ID from the `ExceptionType` model, specific to new user exceptions.
        - Creates or updates a `UserException` record with the specified user ID, week ID, and reason, setting the status to 'accepted'.
        - Sends notifications to both the specified user and the parent or leader of the authenticated user, informing them of the new user status.
        - Commits the transaction upon successful creation or update of the new user exception.

        ## Response

        - Returns a success message indicating that the new user exception has been successfully set.

        ## Errors

        - `500 Internal Server Error`: If validation fails, if there are issues with the database transaction, or if an unexpected error occurs during processing. The error message will include details of the exception encountered.
    */

    public function setNewUser(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'reason' => 'required|string',
            'user_id' => 'required|int',
            'week_id' => 'required|int',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        try {
            DB::beginTransaction();

            //get exceptional freez type id
            $exceptionalFreez = ExceptionType::where('type', config("constants.EXCEPTIONAL_FREEZING_TYPE"))->first();

            $week = Week::find($request->week_id);
            $exception['reason'] = $request->reason;
            $exception['type_id'] =  $exceptionalFreez->id;
            $exception['user_id'] = $request->user_id;

            $parentOfAuth = Auth::user()->parent_id;

            $successMessage = "";
            $exception['status'] = 'accepted';
            $exception['week_id'] =  $week->id;
            $exception['start_at'] = $week->created_at;
            $exception['end_at'] = Carbon::parse($week->created_at->addDays(7))->format('Y-m-d');

            $userException = UserException::updateOrCreate(
                ['user_id' => $request->user_id, 'week_id' => $week->id],
                $exception
            );
            $userException->fresh();
            /**
             * @todo: slow query - asmaa
             */
            $this->updateUserMarksToFreez($week->id, $request->user_id);

            // response msg
            $successMessage = "تم تعيين السفير كعضو جديد ";


            //Notify User
            $userToNotify = User::find($request->user_id);
            $msg = "قام  " . Auth::user()->name . " بتعيينك كعضو جديد، وعدم احتساب علامتك للأسبوع الحالي ";
            (new NotificationController)->sendNotification($userToNotify->id, $msg, ADMIN_EXCEPTIONS, $this->getExceptionPath($userException->id));

            //notify parent of Auth
            $msg = "قام  " . Auth::user()->name . " بتعيين " . $userToNotify->name . " كـ عضو جديد";
            (new NotificationController)->sendNotification($parentOfAuth, $msg, ADMIN_EXCEPTIONS, $this->getExceptionPath($userException->id));

            DB::commit();

            return $this->jsonResponseWithoutMessage($successMessage, 'data', 200);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }

    /**
     * Find an existing user exception in the system by its id display it.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function show($exception_id)
    {
        $userException = UserException::find($exception_id);

        if ($userException) {
            if (Auth::id() == $userException->user_id || Auth::user()->hasRole(['leader', 'supervisor', 'advisor', 'consultant', 'admin'])) {
                $group_id = UserGroup::where('user_id', $userException->user_id)->where('user_type', 'ambassador')->pluck('group_id')->first();
                $response['authInGroup'] = UserGroup::where('user_id', Auth::id())->where('group_id', $group_id)
                    ->latest() //asmaa
                    ->first();
                $response['user_exception'] = $userException;
                //weeks [current - last]
                $response['weeks'] = Week::orderBy('created_at', 'desc')->take(2)->get();

                //last freez
                $response['last_freez'] = UserException::where('user_id', $userException->user_id)->where(function ($q) {
                    $q->where('status', 'accepted')
                        ->orWhere('status', 'finished');
                })
                    ->whereHas('type', function ($query) {
                        $query->where('type', config('constants.FREEZE_THIS_WEEK_TYPE'))
                            ->orWhere('type', config('constants.FREEZE_NEXT_WEEK_TYPE'));
                    })
                    ->first();

                //last exam
                $response['last_exam'] = UserException::where('user_id', $userException->user_id)->where(function ($q) {
                    $q->where('status', 'accepted')
                        ->orWhere('status', 'finished');
                })
                    ->whereHas('type', function ($query) {
                        $query->where('type', config('constants.EXAMS_MONTHLY_TYPE'))
                            ->orWhere('type', config('constants.EXAMS_SEASONAL_TYPE'));
                    })
                    ->first();
                //last exceptional freez
                $response['last_exceptional_freez'] = UserException::where('user_id', $userException->user_id)->where(function ($q) {
                    $q->where('status', 'accepted')
                        ->orWhere('status', 'finished');
                })
                    ->whereHas('type', function ($query) {
                        $query->where('type', config('constants.EXCEPTIONAL_FREEZING_TYPE'));
                    })
                    ->first();

                return $this->jsonResponseWithoutMessage($response, 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } //end if $userexception

        else {
            throw new NotFound;
        }
    }

    /**
     * Update an existing user exception’s details by its id( “update exception” permission is required).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'exception_id' => 'required',
            'reason' => 'required|string',
            'end_at' => 'required|date|after:yesterday',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userException = UserException::find($request->exception_id);
        if ($userException) {
            if (Auth::id() == $userException->user_id && $userException->status == 'pending') {
                $input['reason'] = $request->reason;
                $input['end_at'] = Carbon::parse($request->end_at)->format('Y-m-d');
                $userException->update($input);
                return $this->jsonResponseWithoutMessage("User Exception Updated", 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound();
        }
    }

    /**
     * Cancel existing user exception in the system by its id.
     * An exception can be cancelled if:
     * 1 - The id of auth user matches the user_id for the specified user exception.
     * 2 - exception status is not finished or rejected.
     *
     * @param  $exception_id
     * @return jsonResponseWithoutMessage;
     */
    public function cancelException($exception_id)
    {
        $userException = UserException::find($exception_id);

        if ($userException) {
            if ((Auth::id() == $userException->user_id) && ($userException->status == 'accepted' || $userException->status == 'pending')) {
                $userException->status = 'cancelled';
                $userException->save();
                $current_week = Week::latest()->first();

                /**
                 * @todo remove the update mark since it will no longer be there
                 */
                Mark::where('week_id', $current_week->id)
                    ->where('user_id', Auth::id())
                    ->update(['is_freezed' => 0]);
                return $this->jsonResponseWithoutMessage("تم الالغاء بنجاح", 'data', 200);
            } else {
                throw new NotAuthorized;
            } //end if Auth
        } else {
            throw new NotFound();
        }
    }

    /**
     * Delete an existing user exception in the system by its id.
     * A user exception can’t be deleted unless:
     * 1 - The id of auth user matches the user_id for the specified user exception.
     * 2 - exception status is not pending.
     *
     * @param   $exception_id
     * @return jsonResponseWithoutMessage;
     */
    public function delete($exception_id)
    {
        $userException = UserException::find($exception_id);

        if ($userException) {
            if ((Auth::id() == $userException->user_id) && $userException->status == 'pending') {
                $userException->delete();
                return $this->jsonResponseWithoutMessage("تم الحذف بنجاح", 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }
    /**
     * return the current month.
     *
     * @return currentMonth;
     */
    public function getMonth()
    {
        $currentMonth = Carbon::now();
        return $currentMonth->month;
    }

    /**
     * Accept and Reject Exceptions
     * This action affects Marks
     *
     * @param $exception_id, Request  $request contains decision
     * @return jsonResponseWithoutMessage;
     */

    public function updateStatus($exception_id, Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'decision' => 'required',
            'week_id' => 'required|int',
            'note' => 'string',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $userException = UserException::find($exception_id);
        if ($userException && $userException->status == 'pending') {

            //get types of exceptions
            $exceptionalFreez = ExceptionType::where('type', config('constants.EXCEPTIONAL_FREEZING_TYPE'))->first();
            $monthlyExam = ExceptionType::where('type', config('constants.EXAMS_MONTHLY_TYPE'))->first();
            $FinalExam = ExceptionType::where('type', config('constants.EXAMS_SEASONAL_TYPE'))->first();
            $withdrawn = ExceptionType::where('type', config("constants.WITHDRAWN"))->first();

            $owner_of_exception = User::find($userException->user_id);
            $user_group = UserGroup::with("group")->where('user_id', $userException->user_id)->where('user_type', 'ambassador')->first();

            $group = $user_group->group;
            //the head of owner_of_exception
            $leader_id = $owner_of_exception->parent_id;
            $authID = Auth::id();

            $desired_week = Week::find($request->week_id);

            if (in_array(Auth::id(), $group->groupAdministrators->pluck('id')->toArray())) {
                if ($userException->type_id == $exceptionalFreez->id) { //exceptional freezing
                    $this->handleExceptionalFreezing($userException, $authID, $owner_of_exception, $leader_id,  $request->note, $request->decision, $desired_week);
                } elseif ($userException->type_id == $monthlyExam->id || $userException->type_id == $FinalExam->id) { // exam exception
                    $this->handleExamException($userException, $authID, $owner_of_exception, $leader_id,  $request->note, $request->decision, $desired_week);
                } elseif ($userException->type_id == $withdrawn->id) { // withdrawn exception
                    $this->handleWithdrawnException($userException, $authID, $owner_of_exception, $leader_id,  $request->note, $request->decision, $desired_week);
                }
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound();
        }
    }




    public function handleExceptionalFreezing($userException, $authID, $owner_of_exception, $leader_id,  $note, $decision, $desired_week)
    {

        if (Auth::user()->hasanyrole('admin|consultant|advisor')) {

            $userException->note = $note;
            $userException->reviewer_id = $authID;

            if (in_array($decision, [1, 2, 3, 4])) {


                $userException->status = 'accepted';
                $status = 'مقبول';


                switch ($decision) {
                        //اعفاء الأسبوع الحالي
                    case 1:
                        $this->updateUserMarksToFreez($desired_week->id, $owner_of_exception->id);
                        $userException->week_id =  $desired_week->id;
                        $userException->start_at = $desired_week->created_at;
                        $userException->end_at = Carbon::parse($desired_week->created_at->addDays(7))->format('Y-m-d');
                        break;
                        //اعفاء الأسبوع القادم
                    case 2:
                        $userException->week_id =  $desired_week->id;
                        $userException->start_at = Carbon::parse($desired_week->created_at->addDays(7))->format('Y-m-d');
                        $userException->end_at = Carbon::parse($desired_week->created_at->addDays(14))->format('Y-m-d');
                        break;
                        //اعفاء لأسبوعين الحالي و القادم
                    case 3:
                        $this->updateUserMarksToFreez($desired_week->id, $owner_of_exception->id);
                        $userException->week_id =  $desired_week->id;
                        $userException->start_at = $desired_week->created_at;
                        $userException->end_at = Carbon::parse($desired_week->created_at->addDays(14))->format('Y-m-d');

                        break;
                        //اعفاء لثلاثة أسابيع الحالي - القام - الذي يليه

                    case 4:
                        $this->updateUserMarksToFreez($desired_week->id, $owner_of_exception->id);
                        $userException->week_id =  $desired_week->id;
                        $userException->start_at = $desired_week->created_at;
                        $userException->end_at = Carbon::parse($desired_week->created_at->addDays(21))->format('Y-m-d');
                        break;
                }

                //notify leader
                $msg = "السفير:  " . $owner_of_exception->name . " تحت التجميد الاستثنائي لغاية:  " . $userException->end_at;
                (new NotificationController)->sendNotification($leader_id, $msg, LEADER_EXCEPTIONS, $this->getExceptionPath($userException->id));
            } else {
                // رفض
                $userException->status = 'rejected';
                $status = 'مرفوض';
            }

            //update
            $userException->update();

            //notify ambassador
            $userToNotify = User::find($userException->user_id);
            $userToNotify->notify(
                (new \App\Notifications\UpdateExceptionStatus($status, $userException->note, $userException->start_at, $userException->end_at))
                    ->delay(now()->addMinutes(2))
            );

            $msg = "حالة طلبك للتجميد الاستثنائي هي " . $status;
            (new NotificationController)->sendNotification($userToNotify->id, $msg, USER_EXCEPTIONS, $this->getExceptionPath($userException->id));

            return $this->jsonResponseWithoutMessage("تم التعديل بنجاح", 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

    public function handleExamException($userException, $authID, $owner_of_exception, $leader_id,  $note, $decision, $desired_week)
    {

        $userException->note = $note;
        $userException->reviewer_id = $authID;

        if (in_array($decision, [1, 2, 3])) {
            //مقبول
            $userException->status = 'accepted';
            $status = 'مقبول';

            switch ($decision) {
                    //اعفاء الأسبوع الحالي
                case 1:
                    $userException->week_id =  $desired_week->id;
                    $userException->start_at = $desired_week->created_at;
                    $userException->end_at = Carbon::parse($desired_week->created_at->addDays(7))->format('Y-m-d');

                    break;
                    //اعفاء الأسبوع القادم
                case 2:
                    $userException->week_id =  $desired_week->id;
                    $userException->start_at = Carbon::parse($desired_week->created_at->addDays(7))->format('Y-m-d');
                    $userException->end_at = Carbon::parse($desired_week->created_at->addDays(14))->format('Y-m-d');
                    break;
                    //اعفاء لأسبوعين الحالي و القادم
                case 3:
                    $userException->week_id =  $desired_week->id;
                    $userException->start_at = $desired_week->created_at;
                    $userException->end_at = Carbon::parse($desired_week->created_at->addDays(14))->format('Y-m-d');
                    break;
            }
            $this->calculate_mark_for_exam($owner_of_exception, $desired_week);

            //notify leader
            $msg = "السفير:  " . $owner_of_exception->name . " تحت نظام الامتحانات لغاية:  " . $userException->end_at;
            (new NotificationController)->sendNotification($leader_id, $msg, LEADER_EXCEPTIONS, $this->getExceptionPath($userException->id));
        } else {
            // رفض
            $userException->status = 'rejected';
            $status = 'مرفوض';
        }

        //update
        $userException->update();

        //notify ambassador
        $userToNotify = User::find($userException->user_id);
        $userToNotify->notify(new \App\Notifications\UpdateExceptionStatus($status, $userException->note, $userException->start_at, $userException->end_at));

        $msg = "حالة طلبك لنظام الامتحانات هي " . $status;
        (new NotificationController)->sendNotification($userToNotify->id, $msg, USER_EXCEPTIONS, $this->getExceptionPath($userException->id));

        return $this->jsonResponseWithoutMessage("تم التعديل بنجاح", 'data', 200);
    }

    public function handleWithdrawnException($userException, $authID, $owner_of_exception, $leader_id,  $note, $decision, $desired_week)
    {

        if (Auth::user()->hasanyrole('admin|consultant|advisor')) {

            $userException->note = $note;
            $userException->reviewer_id = $authID;
            $userException->status = 'accepted';
            $status = 'مقبول';

            //notify leader
            $msg = "السفير:  " . $owner_of_exception->name . " طلب انسحاب مؤقت ";
            (new NotificationController)->sendNotification($leader_id, $msg, LEADER_EXCEPTIONS, $this->getExceptionPath($userException->id));

            //update
            $userException->update();

            //notify ambassador By Email
            $userToNotify = User::find($userException->user_id);
            $userToNotify->notify(
                (new \App\Notifications\UpdateExceptionStatus($status, $userException->note, $userException->start_at, $userException->end_at))
                    ->delay(now()->addMinutes(2))
            );

            return $this->jsonResponseWithoutMessage("تم التعديل بنجاح", 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

    private function updateUserMarksToFreez($weekId, $userId)
    {
        Mark::updateOrCreate(
            ['week_id' => $weekId, 'user_id' => $userId],
            [
                'reading_mark' => 0,
                'writing_mark' => 0,
                'total_pages' => 0,
                'support' => 0,
                'total_thesis' => 0,
                'total_screenshot' => 0,
                'is_freezed' => 1

            ]
        );
    }


    public function calculate_mark_for_exam($owner_of_exception, $current_week)
    {
        $thisWeekMark = Mark::where('week_id', $current_week->id)
            ->where('user_id', $owner_of_exception->id)->first();
        if ($thisWeekMark) {
            $thesesLength = Thesis::where('mark_id', $thisWeekMark->id)
                ->select(
                    DB::raw('sum(max_length) as max_length'),
                )->first()->max_length;

            if ($thisWeekMark->total_pages >= 10 && ($thesesLength >= config('constants.COMPLETE_THESIS_LENGTH') || $thisWeekMark->total_screenshots >= 2)) {
                $thisWeekMark->reading_mark = config('constants.FULL_READING_MARK');
                $thisWeekMark->writing_mark = config('constants.FULL_WRITING_MARK');
                $thisWeekMark->save();
            }
        }
    }

    public function listPindigExceptions()
    {
        if (Auth::user()->can('list pending exception')) {
            $userExceptions = UserException::where('status', 'pending')->get();
            if ($userExceptions) {
                return $this->jsonResponseWithoutMessage(UserExceptionResource::collection($userExceptions), 'data', 200);
            } else {
                throw new NotFound();
            }
        } else {
            throw new NotAuthorized;
        }
    }

    public function addExceptions(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'user_email' => 'required|email',
            'reason' => 'required|string',
            'type_id' => 'required|int',
            'end_at' => 'required|date|after:yesterday',
            'note' => 'nullable'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->hasRole(['admin', 'advisor'])) {
            $user = User::where('email', $request->user_email)->pluck('id')->first();
            if ($user) {
                $current_week = Week::latest()->pluck('id')->first();
                $input['week_id'] =  $current_week;
                $input['user_id'] = $user;
                $input['status'] = 'accepted';
                $input['reviewer_id'] = Auth::id();
                $input['end_at'] = Carbon::parse($request->end_at)->format('Y-m-d');

                $userException = UserException::create($input);

                $msg = "لديك إعفاء استثنائي لغاية " . $userException->end_at;
                (new NotificationController)->sendNotification($user, $msg, USER_EXCEPTIONS, $this->getExceptionPath($userException->id));

                return $this->jsonResponseWithoutMessage('User Exception created', 'data', 200);
            } else {
                throw new NotFound();
            }
        } else {
            throw new NotAuthorized;
        }
    }

    public function finishedException()
    {
        $userExceptions = UserException::where('status', 'accepted')->whereDate('end_at', '<', Carbon::now())->get();
        if (!$userExceptions->isEmpty()) {
            foreach ($userExceptions as $userException) {
                $userException['status'] = 'finished';
                $userException->update();
            }
            return $this->jsonResponseWithoutMessage('Done', 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage('all exception are alrady finished', 'data', 200);
        }
    }

    /**
     * List all exceptions for one user
     *
     * @param $user_id
     * @return jsonResponseWithoutMessage
     */

    public function userExceptions($user_id)
    {

        $response['week'] = Week::latest()->first();
        $response['exceptions'] = UserException::where('user_id', $user_id)->latest()->get();
        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    /**
     * Filter user exceptions.
     *
     * @param  exception filter , user_id
     * @return jsonResponseWithoutMessage
     */
    public function exceptionsFilter($filter, $user_id)
    {

        if ($filter == 'oldest') {
            $exceptions = UserException::where('user_id', $user_id)->get();
        } else if ($filter == 'latest') {
            $exceptions = UserException::where('user_id', $user_id)->latest()->get();
        } else if ($filter == 'freez') {
            $exceptions = UserException::whereHas('type', function ($query) {
                $query->where('type', 'تجميد الأسبوع الحالي')
                    ->orWhere('type', 'تجميد الأسبوع القادم');
            })->where('user_id', $user_id)->latest()->get();
        } else if ($filter == 'exceptional_freez') {
            $exceptions = UserException::whereHas('type', function ($query) {
                $query->where('type', 'تجميد استثنائي');
            })->where('user_id', $user_id)->latest()->get();
        } else if ($filter == 'exams') {
            $exceptions = UserException::whereHas('type', function ($query) {
                $query->where('type', 'نظام امتحانات - شهري')
                    ->orWhere('type', 'نظام امتحانات - فصلي');
            })->where('user_id', $user_id)->latest()->get();
        } else if ($filter == 'accepted') {
            $exceptions = UserException::where('status', 'accepted')->where('user_id', $user_id)->latest()->get();
        } else if ($filter == 'pending') {
            $exceptions = UserException::where('status', 'pending')->where('user_id', $user_id)->latest()->get();
        } else if ($filter == 'rejected') {
            $exceptions = UserException::where('status', 'rejected')->where('user_id', $user_id)->latest()->get();
        } else if ($filter == 'finished') {
            $exceptions = UserException::where('status', 'finished')->where('user_id', $user_id)->latest()->get();
        }

        return $this->jsonResponseWithoutMessage($exceptions, 'data', 200);
    }

    public function searchByEmail($email)
    {
        $response['user'] = User::where('email', $email)->first();
        if ($response['user']) {
            $response['exceptions'] = UserException::where('user_id', $response['user']->id)->latest()->limit(10)->get();
            $response['followup_team'] = UserGroup::with('group')->where('user_id', $response['user']->id)->where('user_type', 'ambassador')->whereNull('termination_reason')->first();
            return $this->jsonResponseWithoutMessage($response, "data", 200);
        } else {
            return $this->jsonResponseWithoutMessage(null, "data", 200);
        }
    }

    public function listForAdvisor($advisor_id)
    {
        $advisingGroup = UserGroup::where('user_id', $advisor_id)->where('user_type', 'advisor')
            ->whereHas('group.type', function ($q) {
                $q->where('type', '=', 'advising');
            })->first();
        $response['advisingGroup'] = $advisingGroup->group->name;

        $advisorGroups = UserGroup::where('user_id', $advisor_id)->where('user_type', 'advisor')->whereNull('termination_reason')->pluck('group_id');

        $ambassadorsInGroups = UserGroup::whereIn('group_id', $advisorGroups)->where('user_type', 'ambassador')->whereNull('termination_reason')
            ->pluck('user_id');
        $response['exceptions'] = UserException::with('user.followupTeam.group')->whereIn('user_id', $ambassadorsInGroups)
            ->whereHas('type', function ($q) {
                $q->where('type', '=', config('constants.EXCEPTIONAL_FREEZING_TYPE'));
            })
            ->where('status', 'pending')
            ->latest()->get();
        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }
}
