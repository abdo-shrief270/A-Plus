<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\League;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class LeaderboardController extends BaseApiController
{
    public function index(Request $request)
    {
        // Default to user's current league or the base league
        $user = auth('api')->user();
        $student = $user?->student;
        $leagueId = $request->query('league_id');

        if (!$leagueId && $student) {
            $leagueId = $student->current_league_id;
        }

        if (!$leagueId) {
            $firstLeague = League::orderBy('min_score')->first();
            $leagueId = $firstLeague ? $firstLeague->id : null;
        }

        if (!$leagueId) {
            return $this->errorResponse('No leagues found', 404);
        }

        $topStudents = Student::with('user')
            ->where('current_league_id', $leagueId)
            ->orderByDesc('current_score')
            ->take(50)
            ->get();

        // Map to simpler structure if needed, or return as is with user relation    
        $leaderboard = $topStudents->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->user->name,
                'avatar_path' => $student->user->avatar_path ?? null, // Check where avatar is
                'current_score' => $student->current_score,
            ];
        });

        return $this->successResponse([
            'league' => League::find($leagueId),
            'leaderboard' => $leaderboard
        ], 'Leaderboard retrieved successfully');
    }
}
