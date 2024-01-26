<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Notifications;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class QueriesController extends Controller
{

    public function index()
    {

        if (Session::get('userType') == 'T') {
            return view('studentQueries');
        } else {
            return view('queries');
        }
    }

    public function getAllCourses()
    {

        $allCourses =  DB::table('Courses')->select('courseID', 'courseName')->get();

        return json_encode($allCourses);
    }

    public function getAllTeachers()
    {

        $allTeachers =  DB::table('Teachers')->select('teacherID', 'teacherLastName', 'teacherFirstName', 'specialty')->get();

        return json_encode($allTeachers);
    }

    public function getAllSpecialties()
    {

        // $allTeachers =  DB::table('Teachers')->select('teacherID', 'teacherLastName', 'teacherFirstName', 'specialty')->get();

        $allSpecialties = DB::table('Specialties')->pluck('specialty');

        return json_encode($allSpecialties);
    }



    public function getStudentsOfSelectedCourse(Request $request)
    {


        $students = DB::table('Students')->select('studentID', 'lastName', 'firstName', 'fatherFirstName')->where('courseID', '=', $request->courseID)->get();

        return json_encode($students);
    }

    public function getCourseTimetable(Request $request)
    {

        $courseName = DB::table('Courses')->select('courseName')->where('courseID', '=', $request->courseID);


        $result =  DB::table('Teachers')->join('Timetable', 'Teachers.teacherID', '=', 'Timetable.teacherID')
            ->whereIn('courseName', $courseName)->where('Day', '=', $request->Day)
            ->where('teachingPeriod', '=', $request->teachingPeriod)
            ->get();



        return json_encode($result);
    }

    public function studentTimetable()
    {
    }

    public function getAllSubjects(Request $request)
    {


        $subjects = DB::table('Subjects')->select('subjectID', 'subjectName')->get();

        return json_encode($subjects);
    }


    public function getSubjectTimetable(Request $request)
    {


        $courseName = DB::table('Courses')->select('courseName')->where('courseID', '=', $request->courseID);



        $result =  DB::table('Teachers')->join('Timetable', 'Teachers.teacherID', '=', 'Timetable.teacherID')
            ->whereIn('courseName', $courseName)
            ->where('subjectName', '=', $request->subjectName)
            ->get();



        return json_encode($result);
    }


    public function getTeacherTimetable(Request $request)
    {


        $result =  DB::table('Timetable')->where('teacherID', '=', $request->teacherID)
            ->where('Day', '=', $request->day)
            ->where('teachingPeriod', '=', $request->period)
            ->get();



        return json_encode($result);
    }

    public function getFreeTeacher(Request $request)
    {

        //timetable array for the specified day and teaching period
        $temp_timetable =  DB::table('Timetable')->select('teacherID')
            ->where('Day', '=', $request->day)
            ->where('teachingPeriod', '=', $request->period);


        if ($request->specialty == 0) {
            // no specialty speciafied so the result is: all teachers that do not 
            // have a teaching session on this specific deay and teaching period
            $result =  DB::table('Teachers')->join('users', 'Teachers.teacherID', '=', 'users.teacherID')
                ->select('Teachers.teacherID', 'Teachers.teacherFirstName', 'Teachers.teacherLastName', 'users.username', 'users.id')
                ->whereNotIn('Teachers.teacherID', $temp_timetable)->get();
        } else {

            $result =  DB::table('Teachers')->join('users', 'Teachers.teacherID', '=', 'users.teacherID')
                ->select('Teachers.teacherID', 'Teachers.teacherFirstName', 'Teachers.teacherLastName', 'users.username', 'users.id')
                ->where('specialty', '=', $request->specialty)
                ->whereNotIn('Teachers.teacherID', $temp_timetable)
                ->get();
        }

        return json_encode($result);
    }


    //this is the query when I search what subject has a student on a specific day and period
    public function getTimetableOfSpecificStudentByPeriod(Request $request)
    {

        $studentID = $request->studentID;

        $allJoinsIdsOfThisStudent = DB::table('Subjects')->join('Student_Subject', 'Subjects.subjectID', '=', 'Student_Subject.subjectID')
            ->select('Subjects.joinID')->where('Student_Subject.studentID', '=', $request->studentID);

        $allJoinsCodesOfThisStudent = DB::table('Joins')->select('joinCode')->whereIn('joinID', $allJoinsIdsOfThisStudent);


        $result =  DB::table('Timetable')->join('Joins', 'Timetable.joinCode', '=', 'Joins.joinCode')
            ->where('teachingPeriod', '=', $request->teachingPeriod)
            ->where('Day', '=', $request->Day)
            ->whereIn('Timetable.joinCode', $allJoinsCodesOfThisStudent)
            ->join('Teachers', 'Timetable.teacherID', '=', 'Teachers.teacherID')->get();


        return json_encode($result);
    }

    //this is to get all the subjects for a specific course by Timatable TABLE
    public function getSubjectsOfSelectedCourse(Request $request)
    {

        $result =  DB::table('Timetable')->select('subjectName')
            ->where('courseName', '=', $request->courseName)
            ->orderByDesc('subjectName')->get();

        return json_encode($result);

    }

    

    //this is to get all the subjects for a specific student 
    public function getSubjectsOfSelectedStudent(Request $request)
    {

        $allSubjectIds =  DB::table('Student_Subject')->where('studentID', '=', $request->studentID)
                            ->join('Subjects', 'Student_Subject.subjectID', '=', 'Subjects.subjectID')->select('Subjects.subjectName')->get();

        return json_encode($allSubjectIds);

    }

    

    //this is to get all the subjects for a specific student 
    public function getSpecificSubjectTimetableForStudent(Request $request)
    {

        $allSubjectIds =  DB::table('Student_Subject')->where('studentID', '=', $request->studentID)
                            ->join('Subjects', 'Student_Subject.subjectID', '=', 'Subjects.subjectID')->where('subjectName', '=', $request->subjectName)
                            ->join('Joins', 'Subjects.joinID', '=', 'Joins.joinID')->select('joinCode');



        $result =  DB::table('Timetable')->whereIn('joinCode', $allSubjectIds)->join('Teachers', 'Timetable.teacherID', '=', 'Teachers.teacherID')->get();

        return json_encode( $result);

    }
}
