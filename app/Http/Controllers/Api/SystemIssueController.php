<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SystemIssueResource;
use App\Models\SystemIssue;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;

class SystemIssueController extends Controller
{
    use ResponseJson;

    public function index()
    {
        if(Auth::user()->can('list systemIssue')){
            $issues = SystemIssue::all();
            if($issues){
                return $this->jsonResponseWithoutMessage(SystemIssueResource::collection($issues), 'data',200);
            }
            else {
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;
        }
    }

    public function create(Request $request){

        $validator = Validator::make($request->all(), [
            'reporter_id' => 'required|integer',
            'reporter_description' => 'required',
            'reviewer_id' => 'integer|nullable',
            'reviewer_note' => 'string|nullable',
            'solved' => 'date|nullable',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        //Anyone can create system issue
        SystemIssue::create($request->all());
        return $this->jsonResponseWithoutMessage("System Issue Created Successfully", 'data', 200);
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'issue_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $issue = SystemIssue::find($request->issue_id);
        if($issue){
            return $this->jsonResponseWithoutMessage(new SystemIssueResource($issue), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }


    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reporter_id' => 'required|integer',
            'reporter_description' => 'required',
            'reviewer_id' => 'required|integer', // required because only reviewer will update it
            'reviewer_note' => 'required|string', // required because only reviewer will update it
            'solved' => 'required|date', // required because only reviewer will update it
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('update systemIssue')){
            $issue = SystemIssue::find($request->issue_id);
            if ($issue){
                $issue->update($request->all());
                return $this->jsonResponseWithoutMessage("System Issue Updated Successfully", 'data', 200);
            }
            else {
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;
        }
    }
}
