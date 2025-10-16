<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Setting::first();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'allow_grades'           => 'sometimes|boolean',
            'school_in_template'     => 'sometimes|nullable|string',
            'school_out_template'    => 'sometimes|nullable|string',
            'class_in_template'      => 'sometimes|nullable|string',
            'class_out_template'     => 'sometimes|nullable|string',
            'class_absent_template'  => 'sometimes|nullable|string',
            'subject_in_template'    => 'sometimes|nullable|string',
            'subject_absent_template' => 'sometimes|nullable|string',
        ]);

        $setting = Setting::findOrFail($id);
        $setting->update($validated);

        return response()->json($setting);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
