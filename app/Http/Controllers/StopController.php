<?php

namespace App\Http\Controllers;

use App\Models\Stop;
use Illuminate\Http\Request;

class StopController extends Controller
{
    public function index()
    {
        return Stop::all();
    }

    public function store(Request $request)
    {
        $stop = Stop::create($request->all());
        return response()->json($stop, 201);
    }

    public function show($id)
    {
        return Stop::find($id);
    }

    public function update(Request $request, $id)
    {
        $stop = Stop::findOrFail($id);
        $stop->update($request->all());
        return response()->json($stop, 200);
    }

    public function destroy($id)
    {
        Stop::destroy($id);
        return response()->json(null, 204);
    }
}
