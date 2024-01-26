<?php

namespace App\Http\Controllers;

use App\Models\Notifications;
use App\Http\Controllers\Controller;
use App\Http\Controllers\json;
use App\Http\Controllers\mysql_query;
use App\Models\Booking;
use App\Models\CalendarEvents;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Date;
use App\Models\Bookings;

class CalendarController extends Controller
{




    public function index()
    {

        //return "ime o controller tou forum";

        //create empty array named $events
        $events = array();
        $data = array();

        $schoolPremises = DB::table('SchoolPremises')->get();

        $timePeriods = DB::table('TimePeriods')->get();


        $semesters = DB::table('semesters')->get();

        foreach ($semesters as $semester) {
            $lastDayOfSemester = $semester->end_date; //actually prepi na to travaw apo ton pinaka tis vasis
            $firstDayOfSemester = $semester->start_date;
            $title =  $semester->semesterTitle;
        }

        $data[] = [
            'type' => 'semester_dates',
            'lastDayOfSemester' => $lastDayOfSemester,
            'firstDayOfSemester' => $firstDayOfSemester

        ];

        //HERE I PASS MY USERNAME

        $data[] = [
            'type' => 'myID',
            'myUserId' => Session::get('loginId'),
            'myUserName' => Session::get('username'),
            'myTeacherId' => Session::get('teacherID'),
            'myFullname' => Session::get('fullname')

        ];


        $myUserType =  Session::get('userType');

        foreach ($schoolPremises as $schoolPremise) {

            $data[] = [
                'type' => 'room',
                'premiseID' => $schoolPremise->premiseID

            ];
        }

        foreach ($timePeriods as $timePeriod) {

            $data[] = [
                'type' => 'timePeriod',
                'period' => $timePeriod->period,
                'startTime' => $timePeriod->startTime,
                'stopTime' => $timePeriod->stopTime,

            ];
        }

        $userid = Session::get('loginId');


        $calendarEvents = DB::table('calendar_events')->where('userid', '=', $userid)->get();


        $counter = 1;

        foreach ($calendarEvents as $calendarEvent) {


            if ($calendarEvent->managerEvent == NULL) {

                $events[] = [
                    'id' => $counter,
                    'event_id' => $calendarEvent->id,
                    'title' => $calendarEvent->title,
                    'place' => $calendarEvent->place,
                    'start' => $calendarEvent->start_date,
                    'end' => $calendarEvent->end_date,
                    'color' => $calendarEvent->color,
                    'fromManager' => $calendarEvent->managerEvent,
                    'type' => 'plainEvent', //changeable - event that can be edited,


                ];
            } else if ($calendarEvent->managerEvent == Session::get('loginId')) {

                $events[] = [
                    'id' => $counter,
                    'event_id' => $calendarEvent->id,
                    'title' => $calendarEvent->title,
                    'place' => $calendarEvent->place,
                    'start' => $calendarEvent->start_date,
                    'end' => $calendarEvent->end_date,
                    'color' => $calendarEvent->color,
                    'fromManager' => $calendarEvent->managerEvent,
                    'type' => 'plainEvent', //changeable - event that can be edited,
                ];
            } else {

                $events[] = [
                    'id' => $counter,
                    'event_id' => $calendarEvent->id,
                    'title' => $calendarEvent->title,
                    'place' => $calendarEvent->place,
                    'start' => $calendarEvent->start_date,
                    'end' => $calendarEvent->end_date,
                    'color' => $calendarEvent->color,
                    'fromManager' => $calendarEvent->managerEvent,
                    'type' => 'managerEvent', //changeable - event that can be edited,


                ];
            }

            $counter = $counter + 1;
        }


        $thisTeacherID = Session::get('teacherID');
        //echo "Teacher id: " . $thisTeacherID . "<br>" ;


        //date TODAY
        $tempStartDate = date_create();
        //echo "start date: " . date_format($tempStartDate,"Y/m/d")  . "<br>" ;
        $tempStopDate = date_create();

        $flag = 0;

        //$semesterEnding = date_create("2025-08-04");

        if ($thisTeacherID != NULL) {

            while ($flag == 0) {

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
                    $day_of_week = "Παρασκευή";
                } else if ($day_of_week_enlish == "Saturday") {
                    $day_of_week = "Σάββατο";
                } else if ($day_of_week_enlish == "Sunday") {
                    $day_of_week = "Κυριακή";
                } else {
                }


                $timetableEvents = DB::table('Timetable')->where('teacherID', '=', $thisTeacherID)->get();
                //echo date_format($startDate,"Y/m/d");

                foreach ($timetableEvents as $timetableEvent) {
                    // red color for timatable events
                    $color  = '#D2001A';

                    if ($timetableEvent->Day == $day_of_week) {

                        //echo "<br> ITS A " .$day_of_week ."TODAY " .date_format($startDate,"Y/m/d");

                        if ($timetableEvent->courseName) {

                            $eventTitle = "{$timetableEvent->subjectName} {$timetableEvent->level} για {$timetableEvent->courseName}";
                        } else {

                            $eventTitle = "{$timetableEvent->subjectName} {$timetableEvent->level} για {$timetableEvent->joinCode}";
                        }




                        // eventplace = premiseID
                        $eventplace = $timetableEvent->premiseID;
                        //echo "<br>" .$eventTitle; 

                        $periodTables = DB::table('TimePeriods')->where('period', '=', $timetableEvent->teachingPeriod)->get();


                        foreach ($periodTables as $periodTable) {
                            $get_start_time = $periodTable->startTime;
                            $get_stop_time = $periodTable->stopTime;
                        }

                        $tempStartDate->setTime(intval(substr($get_start_time, 0, 2)), intval(substr($get_start_time, 3, 5)), intval(substr($get_start_time, 6, 8)));
                        //echo $startDate->format('Y-m-d H:i:s') . "<br>"; 
                        //echo "start date: " . date_format($tempStartDate,"Y-m-d H:i:s")  . "<br>" ;

                        $tempStopDate->setTime(intval(substr($get_stop_time, 0, 2)), intval(substr($get_stop_time, 3, 5)), intval(substr($get_stop_time, 6, 8)));
                        //echo $stopDate->format('Y-m-d H:i:s') . "<br>";

                        // while (in_array($counter, array_column($events, 'id')))
                        // {
                        //     //echo "<br> Match foundff " .$counter . "<br>";
                        //     $counter= $counter+1;

                        // }


                        $events[] = [
                            'id' => $counter,
                            'title' => $eventTitle,
                            'place' => $eventplace,
                            'start' => date_format(clone ($tempStartDate), "Y-m-d H:i:s"), //clone( $tempStartDate),
                            'end' => date_format(clone ($tempStopDate), "Y-m-d H:i:s"), //clone ($tempStopDate),
                            'color' => $color,
                            'timetable_id' => $timetableEvent->id_timetable,
                            'period' => $timetableEvent->teachingPeriod,
                            'type' => 'timetable', //fixed - cannot change,
                            'subjectName' => $timetableEvent->subjectName,
                            'courseName' => $timetableEvent->courseName,
                            'joinCode' => $timetableEvent->joinCode

                        ];

                        //echo "id:" . $counter . "title: " .$eventTitle . "start: " .date_format($tempStartDate,"Y-m-d H:i:s") . "end: " . date_format($tempStopDate,"Y-m-d H:i:s") . "<br>" ; 

                        $counter = $counter + 1;
                    }
                }

                $tempStartDate->modify('+1 day');
                $tempStopDate->modify('+1 day');


                if ($lastDayOfSemester == $tempStartDate->format("Y-m-d")) {
                    $flag = 1;
                }
            }
        }




        $roomBookings =  DB::table('bookings')->where('userid', '=', $userid)->get();



        foreach ($roomBookings as $roomBooking) {

            $tempArrays = DB::table('SchoolPremises')->where('premiseID', '=', $roomBooking->room)->get();

            if ($roomBooking->description == null) {

                foreach ($tempArrays as $tempArray) {

                    $newTitle =  $roomBooking->room;
                }
            } else {
                $newTitle = $roomBooking->description;
            }



            $events[] = [
                'id' => $counter,
                'title' => $newTitle,
                //'place' =>  "Booking at " .$roomBooking->room ,
                'booking_id' => $roomBooking->id,
                'userid' => $roomBooking->userid,
                'premiseID' => $roomBooking->room,
                'start' => $roomBooking->start_date,
                'end' => $roomBooking->end_date,
                'color' => "#6EBF8B",
                'type' => 'myBooking', //booking - event created from a room booking

            ];

            $counter = $counter + 1;
        }


        if ($myUserType == 'T') {

            //echo json_encode($events);

            return view('calendar.index', ['events' => $events], ['data' => $data]);
        } else {

            return view('calendar.indexManager', ['events' => $events], ['data' => $data]);
        }
    }






    public function store(Request $request)
    {

        $request->validate([
            'title' => 'required | string'

        ]);


        if ($request->teacherArray != null) {

            $event = new CalendarEvents();
            $event->title = $request->title;
            $event->place = $request->place;
            $event->userid = Session::get('loginId');
            $event->start_date = $request->start_date;
            $event->end_date = $request->end_date;
            $event->color = $request->color;
            $event->managerEvent = Session::get('loginId');
            $event->save();
            $sharedEventId = CalendarEvents::max('id');

            //the row of table that goes back to the view
            $newEvent = ([
                'id' => $request->counterID,
                'event_id' => $event->id,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'title' => $event->title,
                'place' => $event->place,
                'color' => $event->color,
                'fromManager' => $event->managerEvent,
                'type' => 'plainEvent'
            ]);
        } else {
            $event = new CalendarEvents();
            $event->title = $request->title;
            $event->place = $request->place;
            $event->userid = Session::get('loginId');
            $event->start_date = $request->start_date;
            $event->end_date = $request->end_date;
            $event->color = $request->color;
            //the row of table that goes back to the view
            $newEvent = ([
                'id' => $request->counterID,
                'event_id' => $event->id,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'title' => $event->title,
                'place' => $event->place,
                'color' => $event->color,
                'fromManager' => null,
                'type' => 'plainEvent'
            ]);

            $event->save();
        }

        if ($request->teacherArray != null) {



            $length = count($request->teacherArray);

            $curr = 0;

            while ($curr < $length) {

                $event = new CalendarEvents();
                $event->title = $request->title;
                $event->place = $request->place;
                $event->userid = $request->teacherArray[$curr];
                $event->start_date = $request->start_date;
                $event->end_date = $request->end_date;
                $event->color = $request->color;
                $event->managerEvent = Session::get('loginId');
                $event->sharedEventId = $sharedEventId;

                $event->save();

                $curr = $curr + 1;
            }
        }








        return response()->json(['newEvent' => $newEvent]);
    }


    // Edw pernw ola ta bookings apo ton pinaka booking pou iparxoun apo simera ke meta
    public function getBookings(Request $request)
    {

        //pos na perasw ta bookings extos tou timetable

        $allBookings = array();

        $tempStartDate = date_create();

        $roomBookings =  DB::table('bookings')->where('start_date', '>=', $tempStartDate)->get();


        return json_encode($roomBookings);
    }

    public function getTimetable(Request $request)
    {

        //pos na perasw ta bookings extos tou timetable

        $timetable = DB::table('Timetable')->get();


        return json_encode($timetable);
    }

    public function getUsers(Request $request)
    {

        //get all users except me

        $users =  DB::table('users')->select('id', 'name', 'username', 'userType', 'teacherID')->where('id', '!=', Session::get('loginId'))->get();

        return json_encode($users);

        // return json_encode($timetable);

    }


    public function storeBooking(Request $request)
    {

        $color = "#6EBF8B";


        $booking = Bookings::create([
            'userid' => Session::get('loginId'),
            'room' => $request->bookRoom,
            'start_date' => $request->fullBookStart,
            'end_date' => $request->fullBookEnd,
            'description' => $request->bookTitle
            //'type' => 'c',

        ]);

        $newEvent = ([
            'id' => $request->counterID,
            'booking_id' => $booking->id,
            'userid' => $booking->userid,
            'premiseID' => $booking->room,
            'start_date' => $booking->start_date,
            'end_date' => $booking->end_date,
            'title' => $booking->description,
            'color' => $color,
            'type' => 'myBooking'
        ]);

        return response()->json(['newEvent' => $newEvent]);
    }




    public function update(Request $request, $id)
    {
        $event = CalendarEvents::find($id);

        if (!$event) {
            return response()->json([
                'error' => 'Unable to locate the event'
            ], 404);
        }
        $event->update([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        $allSharedEvents = CalendarEvents::where('sharedEventId', '=', $id)->get();

        foreach ($allSharedEvents as $allSharedEvent) {

            $allSharedEvent->update([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);
        }

        return response()->json('Event updated');
    }


    public function updateEvent(Request $request, $id)
    {
        $event = CalendarEvents::find($id);

        if (!$event) {
            return response()->json([
                'error' => 'Unable to locate the event'
            ], 404);
        }

        $event->update([
            'title' => $request->title,
            'place' => $request->place,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'color' => $request->newColor,
        ]);

        $allSharedEvents = CalendarEvents::where('sharedEventId', '=', $id)->get();

        foreach ($allSharedEvents as $allSharedEvent) {

            $allSharedEvent->update([
                'title' => $request->title,
                'place' => $request->place,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'color' => $request->newColor,
            ]);
        }

        // return response()->json('Event updated');

        $updatedEvent = CalendarEvents::find($id);

        //the row of table that goes back to the view
        $newEvent = ([
            'event_id' => $updatedEvent->id,
            'userid' => $updatedEvent->userid,
            'start_date' => $updatedEvent->start_date,
            'end_date' => $updatedEvent->end_date,
            'title' => $updatedEvent->title,
            'place' => $updatedEvent->place,
            'color' => $updatedEvent->color,
            'fromManager' => $updatedEvent->managerEvent

        ]);

        return response()->json(['newEvent' => $newEvent]);
    }



    public function destroy($id)
    {
        $event = CalendarEvents::find($id);

        if (!$event) {
            return response()->json([
                'error' => 'Unable to locate the event'
            ], 404);
            //passing the 404 for 'not found' exception

        }

        //If the event is a shared event then a notification is sent that the event is deleted

        $notText = "The user " . Session::get('fullname') . "(" . Session::get('username') . ") deleted an event you were added to on "  . $event->start_date . ". Event: '" . $event->title . "'";

        if ($event->managerEvent != null) {

            $calEvents = CalendarEvents::where('sharedEventId', '=', $id)->get(['userid']);

            foreach ($calEvents as $calEvent) {

                $notif = new Notifications();
                $notif->fromUser = Session::get('loginId');
                $notif->text = $notText;
                $notif->toUser = $calEvent->userid;
                $notif->openedStatus = 0;
                $notif->save();
            }
        }

        //delete the main event
        $event->delete();

        //delete the events of added users if the event is shared
        $allSharedEvents = CalendarEvents::where('sharedEventId', '=', $id)->get();

        foreach ($allSharedEvents as $allSharedEvent) {
            $allSharedEvent->delete();
        }

        return $id;
    }


    public function destroyBooking($id)
    {
        $booking = Bookings::find($id);
        if (!$booking) {
            return response()->json([
                'error' => 'Unable to locate the event'
            ], 404);
            //passing the 404 for 'not found' exception

        }
        $booking->delete();
        return $id;
    }



    public function getRoster(Request $request)
    {


        $allStudents = array();

        $subjectName = $request->subjectName;

        // get subject id from table
        $subjectID_table = DB::table('Subjects')->select('subjectID')->where('subjectName', '=', $subjectName);

        $courseID_table = DB::table('Courses')->select('courseID')->where('courseName', '=',  $request->courseName);




        $allStudents = DB::table('Student_Subject')

            ->join('Students', 'Student_Subject.studentID', '=', 'Students.studentID')
            // ->join($students_table, 'Student_Subject.studentID', '=', 'Students.studentID')
            ->select('lastName', 'firstName', 'fatherFirstName', 'courseID')
            ->whereIn('courseID',  $courseID_table)
            ->whereIn('subjectID', $subjectID_table)

            ->get();

        // $course_students = 




        return json_encode($allStudents);
    }

    public function getEventMembersList(Request $request)
    {

        $eventMembersList = array();

        $eventMembersList = DB::table('calendar_events')->join('users', 'calendar_events.userid', '=', 'users.id')
            ->select('users.username', 'users.name')
            ->where('calendar_events.sharedEventId', '=', $request->mainEventId)->get();




        return json_encode($eventMembersList);
    }


    public function getUserEvents(Request $request)
    {



        $semesters = DB::table('semesters')->get();

        foreach ($semesters as $semester) {
            $lastDayOfSemester = $semester->end_date; //actually prepi na to travaw apo ton pinaka tis vasis

        }


        //create empty array named $events
        $events = array();


        $userid = $request->userid;

        $calendarEvents = DB::table('calendar_events')->where('userid', '=', $userid)->get();


        $counter = 1;

        foreach ($calendarEvents as $calendarEvent) {


            if ($calendarEvent->managerEvent == NULL) {

                $events[] = [
                    'id' => $counter,
                    'event_id' => $calendarEvent->id,
                    'title' => $calendarEvent->title,
                    'place' => $calendarEvent->place,
                    'start' => $calendarEvent->start_date,
                    'end' => $calendarEvent->end_date,
                    'color' => $calendarEvent->color,

                    'type' => 'plainEvent', //changeable - event that can be edited,


                ];
            } else {

                $events[] = [
                    'id' => $counter,
                    'event_id' => $calendarEvent->id,
                    'title' => $calendarEvent->title,
                    'place' => $calendarEvent->place,
                    'start' => $calendarEvent->start_date,
                    'end' => $calendarEvent->end_date,
                    'color' => $calendarEvent->color,
                    'fromManager' => $calendarEvent->managerEvent,
                    'type' => 'managerEvent', //changeable - event that can be edited,


                ];
            }

            $counter = $counter + 1;
        }

        $thisTeacherID = $request->teacherID;

        //date TODAY
        $tempStartDate = date_create();
        $tempStopDate = date_create();

        $flag = 0;


        if ($thisTeacherID != NULL) {

            while ($flag == 0) {

                $day_of_week = date_format($tempStartDate, "l");


                $timetableEvents = DB::table('Timetable')->where('teacherID', '=', $thisTeacherID)->get();

                foreach ($timetableEvents as $timetableEvent) {
                    // red color for timatable events
                    $color  = '#D2001A';

                    if ($timetableEvent->Day == $day_of_week) {


                        $eventTitle = "{$timetableEvent->subjectName} {$timetableEvent->level} for {$timetableEvent->courseName}";

                        $eventplace = $timetableEvent->premiseID;

                        $periodTables = DB::table('TimePeriods')->where('period', '=', $timetableEvent->teachingPeriod)->get();


                        foreach ($periodTables as $periodTable) {
                            $get_start_time = $periodTable->startTime;
                            $get_stop_time = $periodTable->stopTime;
                        }

                        $tempStartDate->setTime(intval(substr($get_start_time, 0, 2)), intval(substr($get_start_time, 3, 5)), intval(substr($get_start_time, 6, 8)));

                        $tempStopDate->setTime(intval(substr($get_stop_time, 0, 2)), intval(substr($get_stop_time, 3, 5)), intval(substr($get_stop_time, 6, 8)));
                        //echo $stopDate->format('Y-m-d H:i:s') . "<br>";

                        // while (in_array($counter, array_column($events, 'id')))
                        // {
                        //     //echo "<br> Match foundff " .$counter . "<br>";
                        //     $counter= $counter+1;

                        // }


                        $events[] = [
                            'id' => $counter,
                            'title' => $eventTitle,
                            'place' => $eventplace,
                            'start' => date_format(clone ($tempStartDate), "Y-m-d H:i:s"), //clone( $tempStartDate),
                            'end' => date_format(clone ($tempStopDate), "Y-m-d H:i:s"), //clone ($tempStopDate),
                            'color' => $color,
                            'timetable_id' => $timetableEvent->id_timetable,
                            'period' => $timetableEvent->teachingPeriod,
                            'type' => 'timetable', //fixed - cannot change,
                            'subjectName' => $timetableEvent->subjectName,
                            'courseName' => $timetableEvent->courseName

                        ];

                        //echo "id:" . $counter . "title: " .$eventTitle . "start: " .date_format($tempStartDate,"Y-m-d H:i:s") . "end: " . date_format($tempStopDate,"Y-m-d H:i:s") . "<br>" ; 

                        $counter = $counter + 1;
                    }
                }

                $tempStartDate->modify('+1 day');
                $tempStopDate->modify('+1 day');


                if ($lastDayOfSemester == $tempStartDate->format("Y-m-d")) {
                    $flag = 1;
                }
            }
        }




        $roomBookings =  DB::table('bookings')->where('userid', '=', $userid)->get();



        foreach ($roomBookings as $roomBooking) {

            $tempArrays = DB::table('SchoolPremises')->where('premiseID', '=', $roomBooking->id)->get();

            if ($roomBooking->description == null) {

                foreach ($tempArrays as $tempArray) {

                    $newTitle = $tempArray->room . ' at ' . $tempArray->building;
                }
            } else {
                $newTitle = $roomBooking->description;
            }



            $events[] = [
                'id' => $counter,
                'title' => $newTitle,
                //'place' =>  "Booking at " .$roomBooking->room ,
                'booking_id' => $roomBooking->id,
                'userid' => $roomBooking->userid,
                // 'room' => $room,
                // 'building' => $building,
                'premiseID' => $roomBooking->room,
                'start' => $roomBooking->start_date,
                'end' => $roomBooking->end_date,
                'color' => "#6EBF8B",
                'type' => 'myBooking', //booking - event created from a room booking

            ];

            $counter = $counter + 1;
        }


        return json_encode($events);
    }

    public function getSelectedUserEvents(Request $request)
    {


        if ($request->teacherList == 0) {

            return redirect('calendar');
        } else {
            $userIdSelected = $request->teacherList;

            $tempArrs =  DB::table('users')->select('username', 'teacherID', 'name')->where('id', '=', $request->teacherList)->get();

            foreach ($tempArrs as $tempArr) {
                $teacherIdSelected = $tempArr->teacherID;
                $selectedUserName = $tempArr->username;
                $selectedUserFullname = $tempArr->name;
            }
        }

        //create empty array named $events
        $events = array();
        $data = array();

        $data[] = [
            'type' => 'selectedUser',
            'selectedUserId' => $userIdSelected,
            'selectedUserName' => $selectedUserName,
            'selectedUserFullname' => $selectedUserFullname

        ];

        $schoolPremises = DB::table('SchoolPremises')->get();

        $timePeriods = DB::table('TimePeriods')->get();


        $semesters = DB::table('semesters')->get();

        foreach ($semesters as $semester) {
            $lastDayOfSemester = $semester->end_date; //actually prepi na to travaw apo ton pinaka tis vasis
            $firstDayOfSemester = $semester->start_date;
            $title =  $semester->semesterTitle;
        }

        $data[] = [
            'type' => 'semester_dates',
            'lastDayOfSemester' => $lastDayOfSemester,
            'firstDayOfSemester' => $firstDayOfSemester,
            'building' => $title,

        ];

        //HERE I PASS MY USERNAME

        $data[] = [
            'type' => 'myID',
            'myUserId' => Session::get('loginId'),
            'myUserName' => Session::get('username'),
            'myTeacherId' => Session::get('teacherID'),
            'myFullname' => Session::get('fullname'),


        ];





        foreach ($schoolPremises as $schoolPremise) {

            $data[] = [
                'type' => 'room',
                'premiseID' => $schoolPremise->premiseID

            ];
        }

        foreach ($timePeriods as $timePeriod) {

            $data[] = [
                'type' => 'timePeriod',
                'period' => $timePeriod->period,
                'startTime' => $timePeriod->startTime,
                'stopTime' => $timePeriod->stopTime,

            ];
        }

        $userid = Session::get('loginId');


        $calendarEvents = DB::table('calendar_events')->where('userid', '=', $userIdSelected)->get();


        $counter = 1;

        foreach ($calendarEvents as $calendarEvent) {


            if ($calendarEvent->managerEvent == NULL) {

                $events[] = [
                    'id' => $counter,
                    'event_id' => $calendarEvent->id,
                    'title' => $calendarEvent->title,
                    'place' => $calendarEvent->place,
                    'start' => $calendarEvent->start_date,
                    'end' => $calendarEvent->end_date,
                    'color' => $calendarEvent->color,

                    'type' => 'plainEvent', //changeable - event that can be edited,


                ];
            } else {

                $events[] = [
                    'id' => $counter,
                    'event_id' => $calendarEvent->id,
                    'title' => $calendarEvent->title,
                    'place' => $calendarEvent->place,
                    'start' => $calendarEvent->start_date,
                    'end' => $calendarEvent->end_date,
                    'color' => $calendarEvent->color,
                    'fromManager' => $calendarEvent->managerEvent,
                    'type' => 'managerEvent', //changeable - event that can be edited,


                ];
            }

            $counter = $counter + 1;
        }



        //date TODAY
        $tempStartDate = date_create();
        //echo "start date: " . date_format($tempStartDate,"Y/m/d")  . "<br>" ;
        $tempStopDate = date_create();

        $flag = 0;

        //$semesterEnding = date_create("2025-08-04");

        if ($teacherIdSelected != NULL) {

            while ($flag == 0) {

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
                    $day_of_week = "Παρασκευή";
                } else if ($day_of_week_enlish == "Saturday") {
                    $day_of_week = "Σάββατο";
                } else {
                    $day_of_week = "Κυριακή";
                }


                $timetableEvents = DB::table('Timetable')->where('teacherID', '=', $teacherIdSelected)->get();
                //echo date_format($startDate,"Y/m/d");

                foreach ($timetableEvents as $timetableEvent) {
                    // red color for timatable events
                    $color  = '#D2001A';

                    if ($timetableEvent->Day == $day_of_week) {

                        //echo "<br> ITS A " .$day_of_week ."TODAY " .date_format($startDate,"Y/m/d");


                        if ($timetableEvent->courseName) {

                            $eventTitle = "{$timetableEvent->subjectName} {$timetableEvent->level} για {$timetableEvent->courseName}";
                        } else {

                            $eventTitle = "{$timetableEvent->subjectName} {$timetableEvent->level} για {$timetableEvent->joinCode}";
                        }

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


                        $events[] = [
                            'id' => $counter,
                            'title' => $eventTitle,
                            'place' => $eventplace,
                            'start' => date_format(clone ($tempStartDate), "Y-m-d H:i:s"), //clone( $tempStartDate),
                            'end' => date_format(clone ($tempStopDate), "Y-m-d H:i:s"), //clone ($tempStopDate),
                            'color' => $color,
                            'timetable_id' => $timetableEvent->id_timetable,
                            'period' => $timetableEvent->teachingPeriod,
                            'type' => 'timetable', //fixed - cannot change,
                            'subjectName' => $timetableEvent->subjectName,
                            'courseName' => $timetableEvent->courseName,
                            'joinCode' => $timetableEvent->joinCode

                        ];

                        //echo "id:" . $counter . "title: " .$eventTitle . "start: " .date_format($tempStartDate,"Y-m-d H:i:s") . "end: " . date_format($tempStopDate,"Y-m-d H:i:s") . "<br>" ; 

                        $counter = $counter + 1;
                    }
                }

                $tempStartDate->modify('+1 day');
                $tempStopDate->modify('+1 day');


                if ($lastDayOfSemester == $tempStartDate->format("Y-m-d")) {
                    $flag = 1;
                }
            }
        }




        $roomBookings =  DB::table('bookings')->where('userid', '=', $userIdSelected)->get();



        foreach ($roomBookings as $roomBooking) {

            $tempArrays = DB::table('SchoolPremises')->where('premiseID', '=', $roomBooking->room)->get();

            if ($roomBooking->description == null) {

                foreach ($tempArrays as $tempArray) {

                    $newTitle = $tempArray->premiseID;
                }
            } else {
                $newTitle = $roomBooking->description;
            }



            $events[] = [
                'id' => $counter,
                'title' => $newTitle,
                //'place' =>  "Booking at " .$roomBooking->room ,
                'booking_id' => $roomBooking->id,
                'userid' => $roomBooking->userid,
                // 'room' => $room,
                // 'building' => $building,
                'premiseID' => $roomBooking->room,
                'start' => $roomBooking->start_date,
                'end' => $roomBooking->end_date,
                'color' => "#6EBF8B",
                'type' => 'myBooking', //booking - event created from a room booking

            ];

            $counter = $counter + 1;
        }



        return view('calendar.indexManagerOtherView', ['events' => $events], ['data' => $data]);
    }

    public function addedToSharedEventNotification(Request $request)
    {

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
            return response()->json('Notifications created successfully!');
        } else {
            return back()->with('fail', 'Something went wrong');
        };
    }

    public function updateEventNotification(Request $request)
    {

        $calEvents = CalendarEvents::where('sharedEventId', '=', $request->sharedEventId)->get(['userid']);

        foreach ($calEvents as $calEvent) {

            $notif = new Notifications();
            $notif->fromUser = Session::get('loginId');
            $notif->text = $request->notText;
            $notif->toUser = $calEvent->userid;
            $notif->openedStatus = 0;

            $res = $notif->save();
        }

        if ($res) {
            return response()->json('Notifications created successfully!');
        } else {
            return back()->with('fail', 'Something went wrong');
        };
    }

    public function getJoinRoster(Request $request)
    {

        $roster = array();

        $joinCode = $request->joinCode;

        //take joinID 

        $tempJoinIDArrays = DB::table('Joins')->where('joinCode', '=', $joinCode)->get();



        $joinID = null;

        foreach ($tempJoinIDArrays as $tempJoinIDArray) {

            $joinID = $tempJoinIDArray->joinID;
        }

        if ($joinID == NULL) {
            return json_encode($roster);
        }

        //find subject id

        $tempSubjectIDArrays = DB::table('Subjects')->where('joinID', '=', $joinID)->get();

        $subjectID = null;

        foreach ($tempSubjectIDArrays as $tempSubjectIDArray) {

            $subjectID = $tempSubjectIDArray->subjectID;
        }

        if ($subjectID == NULL) {
            return json_encode($roster);
        }


        $allStudentIds = DB::table('Student_Subject')->select('studentID')->where('subjectID', '=', $subjectID);

        $roster =  DB::table('Students')->whereIn('studentID', $allStudentIds)->join('Courses', 'Courses.courseID', '=', 'Students.courseID')
            ->select('Students.studentID', 'Students.lastName', 'Students.firstName', 'Students.fatherFirstName', 'Courses.courseName' )
            ->get();


        return json_encode($roster);
    }
}
