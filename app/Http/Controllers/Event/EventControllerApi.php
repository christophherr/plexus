<?php

namespace App\Http\Controllers\Event;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Hash;
use Validator;
use Session;
use Auth;
use Redirect;
use App\Event;
use App\Society;
use App\Score;
use App\Question;
use Carbon\Carbon;
use File;
use Response;

class EventControllerApi extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(
            'society', [
                'except' => ['show', 'index']
            ]
        );
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $currentEvents = Event::where(
            [
            ['startTime' , '<=', Carbon::now()],
            ['endTime', '>', Carbon::now()],
            ]
        )->get()->toJson();

        $pastEvents = Event::where(
            'endTime', '<=', Carbon::now()
        )->get()->toJson();

        $futureEvents = Event::where(
            'startTime', '>', Carbon::now()
        )->get()->toJson();

        $privilege = 0;

        $events = [
            'currentEvents' => $currentEvents,
            'futureEvents' => $futureEvents,
            'pastEvents' => $pastEvents,
            'privilege' => $privilege
        ];

        return Response::json($events);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $eventInput = Input::all();

        $validator = Validator::make(
            $eventInput, [
            'eventName' => 'required|max:255',
            'eventDes' => 'required|max:255',
            'startTime' => 'required|max:255',
            'endTime' => 'required|max:255',
            'duration' => 'required|max:255',
            'totalQues' => 'required|max:255',
            'type' => 'required|max:255',
            'forum' => 'required|max:255',
            ]
        );

        if ($validator->fails()) {
            return Response::json(
                [
                "status" => false,
                "errors" => $validator->errors()
                ]
            );
        }

        $eventInput['societyId'] = Auth::guard('society')->id();

        $event = Event::create($eventInput);

        return Response::json(
            [
            "redirect" => '/event',
            "status" => true
            ]
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Get event details
        $event = Event::find($id);

        if (Auth::guard('user')->check() || Auth::guard('society')->check()) {
            return Response::json(
                [
                "status" => true,
                "data" => $event
                ]
            );
        }
        return Response::json(
            [
            "status" => false,
            "data" => [],
            "error" => "You are not logged in"
            ]
        );

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $eventInput = Input::all();

        $validator = Validator::make(
            $eventInput, [
            'eventName' => 'required|max:255',
            'eventDes' => 'required|max:255',
            'startTime' => 'required|max:255',
            'endTime' => 'required|max:255',
            'duration' => 'required|max:255',
            'totalQues' => 'required|max:255',
            'type' => 'required|max:255',
            // 'active' => 'required|max:255',
            'forum' => 'required|max:255',
            ]
        );

        if ($validator->fails()) {
            return $validator->errors()->toJson();
        }

        $event = Event::find($id);

        $event->eventName = $eventInput['eventName'];
        $event->eventDes = $eventInput['eventDes'];
        $event->startTime = $eventInput['startTime'];
        $event->endTime = $eventInput['endTime'];
        $event->duration = $eventInput['duration'];
        $event->totalQues = $eventInput['totalQues'];
        $event->type = $eventInput['type'];
        $event->forum = $eventInput['forum'];

        if ($event->save()) {
            return response()->json(["success" => "Event is updated"]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $event = Event::find($id);

        if ($event->delete()) {
            return Response::json(
                [
                "status" => true,
                "data" => ["Event is deleted"]
                ]
            );
        }
        return Response::json(
            [
            "status" => false,
            "data" => [],
            "error" => "Error in deletion"
            ]
        );
    }

    /**
     * Approve the Event.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function approve($id)
    {
        $approve = Input::all();

        $event = Event::find($id);

        if ($approve['approve']) {
            $event->approve = 1;
            return Response::json(
                [
                "status" => true,
                "data" => ["Event is approved"]
                ]
            );
        }
        $event->approve = 0;
        return Response::json(
            [
            "status" => false,
            "data" => [],
            "error" => "Event in disapproved"
            ]
        );
    }

    /**
     * Active the Event.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function active($id)
    {
        $active = Input::all();

        $event = Event::find($id);

        if ($active['active']) {
            $event->active = 1;
            return Response::json(
                [
                "status" => true,
                "data" => ["Event is activated"]
                ]
            );
        }
        $event->active = 0;
        return Response::json(
            [
            "status" => false,
            "data" => [],
            "error" => ["Event is deactivated"]
            ]
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function dashboard($id)
    {
        // Get event details
        $event = Event::find($id)->toJson();
        $level = 0;
        $question = [];

        if (Auth::guard('user')->check()) {
            $getScore = Score::where(
                [
                ['eventId', $id],
                ['userId', Auth::guard('user')->id],
                ]
            )->get();

            if ($getScore != []) {
                $level = $getScore->level + 1;
            } else {
                $newUserScore = new Score;
                $newUserScore->userId = Auth::guard('user')->id;
                $newUserScore->eventId = $id;

                $newUserScore->save();
            }

            $question = Question::where(
                [
                ['eventId', $id],
                ['level', $level],
                ]
            )->get()->toJson();

        } elseif (Auth::guard('society')->check()) {
            $question = Question::where('eventId', $id)->get()->toJson();
        } else {
            return Redirect::to('/login');
            // $question = Question::where('eventId', $id)->get()->toJson();
        }

        $data = [
            'status' => true,
            'event' => $event,
            'question' => $question
        ];

        return Response::json($data);
    }
}
