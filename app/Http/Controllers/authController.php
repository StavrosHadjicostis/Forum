<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Semester;
use App\Models\Notifications;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class authController extends Controller
{
    public function login()
    {
        return view('login');
    }



    public function registerUser(Request $request)
    {

        $request->validate([
            'username' => 'required',
            'name' => 'required',
            'userType' => 'required',
            'password' => 'required'
        ]);

        $tmps = User::where('username', '=', $request->username)->get();

        //check if unique

        foreach ($tmps as $tmp) {
            return back()->with('fail-register', 'Μη επιτυχής εγγραφή χρήστη! Αυτό το όνομα χρήστη δεν είναι διαθέσιμο!');
        }



        if ((strlen($request->password) >= 8 && strlen($request->password) <= 15)) {

            $user = new User();
            $user->username = $request->username;
            $user->name = $request->name;
            $user->userType = $request->userType;
            $user->teacherId = $request->teacherId;
            //encrypted password
            $user->password = Hash::make($request->password);

            //save the data in database (table users)
            $res = $user->save();

            if ($res) {
                return back()->with('success-register', 'Ο νέος χρήστης εχει εγγραφεί επιτυχώς!');
            } else {
                return back()->with('fail-register', 'Μη επιτυχής εγγραφή χρήστη!');
            };
        } else {

            return back()->with('fail-register', 'Μη επιτυχής εγγραφή χρήστη! Ο κωδικός χρήστη πρέπει να είναι απο 8 εώς 15 χαρακτήρες');
        }
    }

    public function deleteUser(Request $request)
    {


        $thisUser =  User::where('id', '=', $request->deleteUserSelect)->first();

        if (!$thisUser) {

            return back()->with('fail-delete-user', 'Κάτι πήγε στραβά! Η διαγραφή χρήστη δεν ήταν επιτυχής!');
            //passing the 404 for 'not found' exception

        }

        //save the data in database (table users)
        $res = $thisUser->delete();

        if ($res) {
            return back()->with('success-delete-user', 'Ο χρήστης διαγράφηκε επιτυχώς!');
        } else {
            return back()->with('fail-delete-user', 'Κάτι πήγε στραβά! Η διαγραφή χρήστη δεν ήταν επιτυχής!');
        };
    }

    public function loginUser(Request $request)
    {

        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', '=', $request->username)->first();

        if ($user) {

            //if the given username exists in database
            //check ig the given password is right
            //if the password given (from $request) is the same with the password of username in DB ($user)
            if (Hash::check($request->password, $user->password)) {
                $request->session()->put('loginId', $user->id);
                $request->session()->put('userType', $user->userType);
                $request->session()->put('teacherID', $user->teacherID);
                $request->session()->put('username', $user->username);
                $request->session()->put('fullname', $user->name);
                $request->session()->put('remember_token', $user->remember_token);


                //every time a user logins - we call this stored procedure to delete any calendar events older than 2 years
                DB::select('call Delete_CalendarEvents_After_2Years()');

                //every time a user logins - we call this stored procedure to delete any booking older than 2 years
                DB::select('call Delete_Bookings_After_2Years()');

                //every time a user logins - we call this stored procedure to delete any notification older than 2 years
                DB::select('call Delete_Notifications_After_2Years()');


                return redirect('dashboard');
            } else {
                return back()->with('fail', 'Λανθασμένος κωδικός πρόσβασης.');
            }
        } else {
            //if the user is not found then we have a fail msg
            return back()->with('fail', 'Αυτό το όνομα χρήστη δεν είναι καταχωρημένο.');
        }
    }

    public function updateUser(Request $request)
    {
        $user = User::find($request->idEditUser);

        if (!$user) {

            return back()->with('fail-edit-user', 'Μη επιτυχής ενημέρωση χρήστη! ');
        }

        if ($request->idEditUser == Session::get('loginId')) {

            $res = $user->update([
                'username' =>  $request->usernameEditUser,
                'name' =>  $request->nameEditUser,
                'teacherID' =>  $request->teacherIdEditUser
            ]);
        } else {

            $res = $user->update([
                'username' =>  $request->usernameEditUser,
                'name' =>  $request->nameEditUser,
                'userType' =>  $request->userTypeEditUser,
                'teacherID' =>  $request->teacherIdEditUser
            ]);
        }

        if ($res) {

            if ($request->idEditUser == Session::get('loginId')) {

                $request->session()->put('loginId', $user->id);
                $request->session()->put('teacherID', $user->teacherID);
                $request->session()->put('username', $user->username);
                $request->session()->put('fullname', $user->name);
            }

            return back()->with('success-edit-user', 'Ο χρήστης εχει ενημερωθεί επιτυχώς!');

        } else {
            return back()->with('fail-edit-user', 'Μη επιτυχής ενημέρωση χρήστη!');
        };


        // return response()->json('User updated');
    }

    public function updatePassword(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'error' => 'Unable to locate the user'
            ], 404);
        }

        $user->update([
            'password' =>  Hash::make($request->newPassword),
            'remember_token' =>  '1'
        ]);


        return response()->json('Password updated');
    }

    public function updatePasswordFromAdmin(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'error' => 'Unable to locate the user'
            ], 404);
        }

        $user->update([
            'password' =>  Hash::make($request->newPassword),
            'remember_token' =>  null
        ]);


        return response()->json('Password updated');
    }



    public function dashboard()
    {
        //create empty array
        $data = array();

        $myNotifications = Notifications::where('toUser', '=', Session::get('loginId'))->orderBy('id', 'desc')->get();


        $data = $this->getTodayCalEvents();

        $semesters = DB::table('semesters')->get();


        foreach ($semesters as $semester) {

            $firstDate = date_create($semester->start_date);
            $lastDay = date_create($semester->end_date);

            $data[] = [
                'type' => 'semester',
                'firstDay' => date_format($firstDate, "dd/mm/YYYY"),
                'lastDay' => date_format($lastDay, "dd/mm/YYYY"),
                'title' => $semester->semesterTitle
            ];

            $data[] = [
                'type' => 'semester_string',
                'lastDayOfSemester' => $semester->end_date,

            ];
        }


        $data[] = [
            'type' => 'myData',
            'myid' => Session::get('loginId'),
            'myUsername' => Session::get('username'),
            'myUserType' => Session::get('userType'),
            'myTeacherId' => Session::get('teacherID'),
            'remember_token' => Session::get('remember_token'),
            'myName' => Session::get('fullname')

        ];

        $allUsers = User::where('id', '!=', Session::get('loginId'))->get();

        foreach ($allUsers as $allUser) {

            $data[] = [
                'type' => 'user',
                'id' => $allUser->id,
                'name' => $allUser->name,
                'username' => $allUser->username,
                'teacherID' =>  $allUser->teacherID,
                'userType' => $allUser->userType

            ];
        }

        if (Session::get('userType') == 'A') {

            $today = date_format(date_create(), "Y-m-d");


            $allBookings = DB::table('bookings')->where('start_date', '>=', $today)->get();


            foreach ($allBookings as $allBooking) {

                $data[] = [
                    'type' => 'booking',
                    'id' => $allBooking->id,
                    'userid' => $allBooking->userid,
                    'room' => $allBooking->room,
                    'start_date' =>  $allBooking->start_date,
                    'end_date' => $allBooking->end_date,
                    'today' => $today,
                    'description' => $allBooking->description

                ];
            }


            $timePeriods = DB::table('TimePeriods')->get();

            foreach ($timePeriods as $timePeriod) {

                $data[] = [
                    'type' => 'timePeriod',
                    'period' => $timePeriod->period,
                    'startTime' => $timePeriod->startTime,
                    'stopTime' => $timePeriod->stopTime

                ];
            }

            $teacherTempArray = DB::table('users')->select('teacherID')->where('teacherID', '!=', NULL);

            $teachersWithoutUser = DB::table('Teachers')->select('teacherID', 'teacherLastName', 'teacherFirstName', 'specialty')->whereNotIn('teacherID', $teacherTempArray)->get();

            foreach ($teachersWithoutUser as $teacherWithoutUser) {

                $data[] = [
                    'type' => 'userFormTeacher',
                    'teacherid' => $teacherWithoutUser->teacherID,
                    'teacherLastName' => $teacherWithoutUser->teacherLastName,
                    'teacherFirstName' => $teacherWithoutUser->teacherFirstName,
                    'specialty' => $teacherWithoutUser->specialty

                ];
            }

            foreach ($timePeriods as $timePeriod) {

                $data[] = [
                    'type' => 'timePeriod',
                    'period' => $timePeriod->period,
                    'startTime' => $timePeriod->startTime,
                    'stopTime' => $timePeriod->stopTime

                ];
            }
        }




        //using compact we can pass any data to the dashboard view
        if (Session::get('userType') == 'T' || Session::get('userType') == 'M') {
            return view('parentDashboard',  ['myNotifications' => $myNotifications],  ['data' => $data]);
        } else {
            return view('adminDashboard', ['myNotifications' => $myNotifications],  ['data' => $data]);
        }
    }


    public function createNotification(Request $request)
    {

        $request->validate([
            'notText' => 'required',
            'notTeacherSelect' => 'required'
        ]);

        $length = count($request->notTeacherSelect);

        $curr = 0;

        while ($curr < $length) {

            $notif = new Notifications();
            $notif->fromUser = Session::get('loginId');
            $notif->text = $request->notText;
            $notif->toUser = $request->notTeacherSelect[$curr];
            $notif->openedStatus = 0;

            $res = $notif->save();

            $curr = $curr + 1;
        }

        if ($res) {
            return response()->json('Done');
        } else {
            return response()->json('Fail');
        }
    }

    public function getUserNotification(Request $request)
    {


        $request = DB::table('Notifications')->select('courseName')->where('courseID', '=', $request->courseID);
    }

    public function changeSemesterDates(Request $request,  $id)
    {
        // $date =  DB::table('semester')->where('start_date', '=', $request->semesterFirstDay)->get();

        $date = Semester::find($id);


        if (!$date) {
            return response()->json([
                'error' => 'Unable to locate the event'
            ], 404);
        }


        $date->update([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);


        return response()->json("done");
    }



    public function getTodayCalEvents()
    {

        $data = [];

        // get calendar events for today

        $userid = Session::get('loginId');
        $today =  date_format(date_create(), "Y-m-d");
        $tomorrow =  date_format((date_create()->modify('+1 day')), "Y-m-d");

        $counter = 0;


        $calendarEvents = DB::table('calendar_events')->where([
            ['userid', '=',  $userid],
            ['start_date', '>=', $today],
            ['end_date', '<', $tomorrow],
        ])->get();

        foreach ($calendarEvents as $calendarEvent) {

            if ($calendarEvent->managerEvent == NULL) {

                $data[] = [
                    'type' => 'calEvent',
                    'id' => $counter,
                    'event_id' => $calendarEvent->id,
                    'title' => $calendarEvent->title,
                    'place' => $calendarEvent->place,
                    'start' => $calendarEvent->start_date,
                    'end' => $calendarEvent->end_date,
                    'color' => $calendarEvent->color,
                    'thisDay' => $today,
                    'typeEvent' => 'plainEvent', //changeable - event that can be edited,
                ];
            } else {

                $data[] = [
                    'type' => 'calEvent',
                    'id' => $counter,
                    'event_id' => $calendarEvent->id,
                    'title' => $calendarEvent->title,
                    'place' => $calendarEvent->place,
                    'start' => $calendarEvent->start_date,
                    'end' => $calendarEvent->end_date,
                    'color' => $calendarEvent->color,
                    'thisDay' => $today,
                    'fromManager' => $calendarEvent->managerEvent,
                    'typeEvent' => 'managerEvent', //changeable - event that can be edited,
                ];
            }

            $counter = $counter + 1;
        }


        $thisTeacherID = Session::get('teacherID');

        //date TODAY
        $tempStartDate = date_create();

        $tempStopDate = date_create();



        if ($thisTeacherID != NULL) {


            $day_of_week_enlish = date_format($tempStartDate, "l");

            if ($day_of_week_enlish == "Monday") {
                $day_of_week = "Δευτέρα";
            } else if ($day_of_week_enlish == "Tuesday") {
                $day_of_week = "Τρίτη";
            } elseif ($day_of_week_enlish == "Wednesday") {
                $day_of_week = "Τετάρτη";
            } else if ($day_of_week_enlish == "Thursday") {
                $day_of_week = "Πέμπτη";
            } else if ($day_of_week_enlish == "Friday") {
                $day_of_week = "Παρασκεύη";
            } else if ($day_of_week_enlish == "Saturday") {
                $day_of_week = "Σάββατο";
            } else {
                $day_of_week = "Κυριακή";
            }


            $timetableEvents = DB::table('Timetable')->where([
                ['teacherID', '=', $thisTeacherID],
                ['Day', '=', $day_of_week]
            ])->get();
            //echo date_format($startDate,"Y/m/d");

            foreach ($timetableEvents as $timetableEvent) {
                // red color for timatable events
                $color  = '#D2001A';






                // eventplace = premiseID
                $eventplace = $timetableEvent->premiseID;
                //echo "<br>" .$eventTitle; 

                $periodTables = DB::table('TimePeriods')->where('period', '=', $timetableEvent->teachingPeriod)->get();


                foreach ($periodTables as $periodTable) {
                    $get_start_time = $periodTable->startTime;
                    $get_stop_time = $periodTable->stopTime;
                }

                $tempStartDate->setTime(intval(substr($get_start_time, 0, 2)), intval(substr($get_start_time, 3, 5)), intval(substr($get_start_time, 6, 8)));


                $tempStopDate->setTime(intval(substr($get_stop_time, 0, 2)), intval(substr($get_stop_time, 3, 5)), intval(substr($get_stop_time, 6, 8)));

                if ($timetableEvent->courseName != NULL) {

                    $eventTitle = "{$timetableEvent->subjectName} {$timetableEvent->level} για {$timetableEvent->courseName}";

                    $data[] = [
                        'type' => 'calEvent',
                        'id' => $counter,
                        'title' => $eventTitle,
                        'place' => $eventplace,
                        'start' => date_format(clone ($tempStartDate), "Y-m-d H:i:s"), //clone( $tempStartDate),
                        'end' => date_format(clone ($tempStopDate), "Y-m-d H:i:s"), //clone ($tempStopDate),
                        'color' => $color,
                        'timetable_id' => $timetableEvent->id_timetable,
                        'period' => $timetableEvent->teachingPeriod,
                        'typeEvent' => 'timetable', //fixed - cannot change,
                        'subjectName' => $timetableEvent->subjectName,
                        'courseName' => $timetableEvent->courseName

                    ];
                } else {

                    $eventTitle = "{$timetableEvent->subjectName} {$timetableEvent->level} για {$timetableEvent->joinCode}";

                    $data[] = [
                        'type' => 'calEvent',
                        'id' => $counter,
                        'title' => $eventTitle,
                        'place' => $eventplace,
                        'start' => date_format(clone ($tempStartDate), "Y-m-d H:i:s"), //clone( $tempStartDate),
                        'end' => date_format(clone ($tempStopDate), "Y-m-d H:i:s"), //clone ($tempStopDate),
                        'color' => $color,
                        'timetable_id' => $timetableEvent->id_timetable,
                        'period' => $timetableEvent->teachingPeriod,
                        'typeEvent' => 'timetable', //fixed - cannot change,
                        'subjectName' => $timetableEvent->subjectName,
                        'courseName' => $timetableEvent->joinCode

                    ];
                }






                $counter = $counter + 1;
            }
        }

        $roomBookings =  DB::table('bookings')->where([
            ['userid', '=',  $userid],
            ['start_date', '>=', $today],
            ['end_date', '<', $tomorrow],
        ])->get();




        foreach ($roomBookings as $roomBooking) {

            $tempArrays = DB::table('SchoolPremises')->where('premiseID', '=', $roomBooking->id)->get();

            if ($roomBooking->description == null) {

                foreach ($tempArrays as $tempArray) {

                    $newTitle = $tempArray->room . ' at ' . $tempArray->building;
                }
            } else {
                $newTitle = $roomBooking->description;
            }



            $data[] = [
                'type' => 'calEvent',
                'id' => $counter,
                'title' => $newTitle,
                'booking_id' => $roomBooking->id,
                'userid' => $roomBooking->userid,
                // 'room' => $room,
                // 'building' => $building,
                'premiseID' => $roomBooking->room,
                'start' => $roomBooking->start_date,
                'end' => $roomBooking->end_date,
                'color' => "#6EBF8B",
                'typeEvent' => 'myBooking', //booking - event created from a room booking

            ];

            $counter = $counter + 1;
        }

        return $data;
    }

    public function changeNotficationStatus(Request $request,  $id)
    {
        $notification = Notifications::find($id);

        $this->getCalendarEventsForToday();

        if (!$notification) {
            return response()->json([
                'error' => 'Unable to locate the event'
            ], 404);
        }

        $notification->update([
            'openedStatus' => $request->value,
        ]);


        return response()->json("done");
    }

    public function deleteNotification(Request $request)
    {


        $thisNotification =  Notifications::where('id', '=', $request->notificationId)->first();

        if (!$thisNotification) {
            return response()->json([
                'error' => 'Unable to locate the notification'
            ], 404);
            //passing the 404 for 'not found' exception

        }

        $res = $thisNotification->delete();

        if ($res) {
            // return back()->with('success', 'User successfully deleted!');
            return response()->json("done");
        } else {
            return response()->json("error");
        };
    }


    public function getCalendarEventsForToday()
    {

        $userid = Session::get('loginId');
        $today = date_create();
        $tomorrow = date_create()->modify('+1 day');



        $calendarEvents = DB::table('calendar_events')->where([
            ['userid', '=',  $userid],
            ['start_date', '>=', $today],
            ['end_date', '<', $tomorrow],
        ])->get();

        return $calendarEvents;
    }








    public function logout()
    {
        if (Session::has('loginId')) {
            //pull is the function to forward data, in this case the loginId
            Session::pull('loginId');
            //redirect to the login page
            return redirect('login');
        }
    }
}
