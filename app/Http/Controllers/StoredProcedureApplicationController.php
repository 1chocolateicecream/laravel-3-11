<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class StoredProcedureApplicationController extends Controller
{
    /**
     * Izveido jaunu prakses pieteikumu, izmantojot hronīto procedūru
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'internship_id' => 'required|integer',
            'company_id' => 'nullable|integer',
            'motivation_letter' => 'required|string|max:1000',
        ]);

        // Izsauc CreateApplication procedūru
        DB::statement('SET @p_status = 0, @p_message = "", @p_application_id = 0');

        DB::statement(
            'CALL CreateApplication(?, ?, ?, ?, @p_status, @p_message, @p_application_id)',
            [
                $validated['user_id'],
                $validated['internship_id'],
                $validated['company_id'] ?? null,
                $validated['motivation_letter'],
            ]
        );

        // Saņem rezultātu
        $result = DB::select('SELECT @p_status as status, @p_message as message, @p_application_id as application_id');

        $status = (int) $result[0]->status;
        $message = $result[0]->message;
        $applicationId = $result[0]->application_id;

        // Atgriež atbilstošu HTTP statusu
        if ($status === 201) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'application_id' => $applicationId,
                ]
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}