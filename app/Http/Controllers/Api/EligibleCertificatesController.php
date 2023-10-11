<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\EligibleCertificates;
use App\Models\EligibleGeneralInformations;
use App\Models\Question;
use App\Models\EligibleThesis;
use App\Models\User;
use App\Models\EligibleUserBook;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class EligibleCertificatesController extends Controller
{
    public function index()
    {

        $certificate = Certificates::all();
        return $this->jsonResponseWithoutMessage($certificate, 'certificate', 200);

    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'eligible_user_book_id ' => 'required',

        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $input = $request->all();

        $all_avareges = UserBook::
        join('general_informations', 'user_book.id', '=', 'general_informations.eligible_user_book_id ')
        ->join('questions', 'user_book.id', '=', 'questions.eligible_user_book_id ')
        ->join('thesis', 'user_book.id', '=', 'thesis.eligible_user_book_id ')
        ->select(DB::raw('avg(general_informations.degree) as general_informations_degree,avg(questions.degree) as questions_degree,avg(thesis.degree) as thesises_degree'))
        ->where('user_book.id', $input['eligible_user_book_id '])
        ->get();
        $thesisDegree = $all_avareges[0]['thesises_degree'];
        $generalInformationsDegree = $all_avareges[0]['general_informations_degree'];
        $questionsDegree = $all_avareges[0]['questions_degree'];
        $finalDegree = ($questionsDegree+$generalInformationsDegree+$thesisDegree) /3 ;
        $certificate = new Certificates();
        $certificate->eligible_user_book_id  = $input['eligible_user_book_id '];
        $certificate->thesis_grade = $questionsDegree;
        $certificate->check_reading_grade = $questionsDegree;
        $certificate->general_summary_grade = $generalInformationsDegree;
        $certificate->final_grade = $finalDegree;
        try {
            $certificate->save();
            $userBook = UserBook::find($input['eligible_user_book_id ']);
            $user=User::find($userBook->user_id);
            $userBook->status = 'finished';
            $userBook->save();
            $user->notify(
                (new \App\Notifications\Certificate())->delay(now()->addMinutes(2))
            );

        } catch (\Illuminate\Database\QueryException $e) {
            echo($e);
            return $this->sendError('User Book does not exist');
        }

        return $this->jsonResponseWithoutMessage($certificate, 'certificate created', 200);

    }


    public function show($id)
    {
        $certificate = Certificates::find($id);

        if (is_null($certificate)) {

            return $this->sendError('Certificate does not exist');
        }
        return $this->jsonResponseWithoutMessage($certificate, 'certificate ', 200);

    }


    public function update(Request $request,  $id)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), []);
        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors());
        }


        $certificate = Certificates::find($id);

        $updateParam = [];
        try {
            $certificate->update($updateParam);
        } catch (\Error $e) {
            return $this->sendError('Certificate does not exist');
        }
        return $this->jsonResponseWithoutMessage($certificate, 'certificate updated', 200);

    }

    public function destroy($id)
    {

        $result = Certificates::destroy($id);

        if ($result == 0) {

            return $this->sendError('Certificate does not exist');
        }
        return $this->jsonResponseWithoutMessage($certificate, 'certificate deleted', 200);

    }

    public function fullCertificate($eligible_user_book_id )
    {
        $fullCertificate=UserBook::where('id',$eligible_user_book_id )->with('questions')->with('generalInformation')->get();
        $all_avareges = UserBook::
        join('general_informations', 'user_book.id', '=', 'general_informations.eligible_user_book_id ')
        ->join('questions', 'user_book.id', '=', 'questions.eligible_user_book_id ')
        ->join('thesis', 'user_book.id', '=', 'thesis.eligible_user_book_id ')
        ->select(DB::raw('avg(general_informations.degree) as general_informations_degree,avg(questions.degree) as questions_degree,avg(thesis.degree) as thesises_degree'))
        ->where('user_book.id', $eligible_user_book_id )
        ->get();
        $thesisDegree = $all_avareges[0]['thesises_degree'];
        $generalInformationsDegree = $all_avareges[0]['general_informations_degree'];
        $questionsDegree = $all_avareges[0]['questions_degree'];
        $finalDegree = ($questionsDegree+$generalInformationsDegree+$thesisDegree) /3 ;
         $certificate = new Certificates();

        $certificate->thesis_grade = $thesisDegree;
        $certificate->check_reading_grade = $questionsDegree;
        $certificate->general_summary_grade = $generalInformationsDegree;
        $certificate->final_grade = $finalDegree;
        $response = ["degrees" => $certificate,"information" => $fullCertificate];
        return $this->jsonResponseWithoutMessage($response, 'certificate deleted', 200);

    }

    public function getUserCertificates(){
        $id = Auth::id();
        $certificates = UserBook::join('certificates',"user_book.id","=","certificates.eligible_user_book_id ")->where('user_id',$id)->get();

        return $this->sendResponse($certificates, 'Certificates');
        return $this->jsonResponseWithoutMessage($certificates, 'certificate', 200);

    }


}
