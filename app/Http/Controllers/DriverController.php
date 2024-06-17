<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index()
    {
        return Driver::all();
    }

    public function store(Request $request)
    {
        $driver = Driver::create($request->all());
        return response()->json($driver, 201);
    }

    public function show($id)
    {
        return Driver::find($id);
    }

    public function update(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);
        $driver->update($request->all());
        return response()->json($driver, 200);
    }

    public function destroy($id)
    {
        Driver::destroy($id);
        return response()->json(null, 204);
    }
}
