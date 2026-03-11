<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Internship;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplicationController extends Controller
{
    /**
     * Store a new internship application using a transaction.
     */
    public function store(Request $request)
    {
        // 1. Transaction starts here
        return DB::transaction(function () use ($request) {

            // a) Check if user exists in the database
            $user = User::find($request->user_id);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // b) Check if internship exists and is valid
            $internship = Internship::find($request->internship_id);
            if (!$internship) {
                return response()->json(['error' => 'Internship not found'], 404);
            }

            // c) Check if the user is allowed to apply
            // Logic: Only students can apply, and they must belong to a group
            // that is actually assigned to this internship.
            $isAllowed = DB::table('group_internships')
                ->where('group_id', $user->group_id)
                ->where('internship_id', $internship->id)
                ->exists();

            if ($user->role !== 'student' || !$isAllowed) {
                return response()->json(['error' => 'User is not allowed to apply for this internship'], 403);
            }

            // If all checks pass, create the application
            $application = Application::create([
                'user_id'           => $user->id,
                'group_id'          => $user->group_id,
                'internship_id'     => $internship->id,
                'company_id'        => $request->company_id,
                'motivation_letter' => $request->motivation_letter,
                'status'            => 'pending'
            ]);

            return response()->json([
                'message' => 'Application created successfully',
                'data' => $application
            ], 201);

        }); // If any Exception is thrown, DB::transaction will automatically roll back.
    }
}