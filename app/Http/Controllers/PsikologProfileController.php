<?php

namespace App\Http\Controllers;

use App\Models\PsikologProfile;
use Illuminate\Http\Request;

class PsikologProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function show(PsikologProfile $psikologProfile)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PsikologProfile $psikologProfile)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PsikologProfile $psikologProfile)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PsikologProfile $psikologProfile)
    {
        //
    }

    public function me(Request $request)
{
    $user = $request->user(); // user yang login
    $profile = $user->psikologProfile; // relasi 1:1 ke PsikologProfile

    if (!$profile) {
        return response()->json(['message' => 'Profil tidak ditemukan'], 404);
    }

    return response()->json([
        'user' => $user,
        'profile' => $profile
    ]);
}

}
