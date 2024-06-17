<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Route;
use App\Models\Stop;

class RouteController extends Controller
{
    public function index()
    {
        return Route::all();
    }

    public function store(Request $request)
    {
        $route = Route::create($request->all());
        return response()->json($route, 201);
    }

    public function show($id)
    {
        return Route::find($id);
    }

    public function update(Request $request, $id)
    {
        $route = Route::findOrFail($id);
        $route->update($request->all());
        return response()->json($route, 200);
    }

    public function destroy($id)
    {
        Route::destroy($id);
        return response()->json(null, 204);
    }

    public function defineAndOptimizeRoutes(){
        $stops=Stop::all();
        $depot= $stops->first();
        $route=[$depot];
        $remainingStops= $stops->except([$depot->id]);

        while($remainingStops->count()>0){
            $nextStop= $this->findNearestStop(end($route),$remainingStops);
            $route[]=$nextStop;
            $remainingStops=$remainingStops->except([$nextStop->id]);
        }
        $route[]=$depot;

        $optimizedRoute=$this->localSearchOptimization($route);
        return response()->json($optimizedRoute);

    }

    private function findNearestStop($currentStop, $remainingStops){
        $nearestStop=null;
        $shortestDistance= PHP_INT_MAX;

        foreach($remainingStops as $stop){
            $distance= $this->calculateDistance($currentStop,$stop);
            if($distance<$shortestDistance){
                $shortestDistance=$distance;
                $nearestStop=$stop;
            }
        }
        
        return $nearestStop;
    }
    private function calculateDistance($stop1, $stop2){
        $lat1=$stop1->latitude;
        $lon1= $stop1->longitude;
        $lat2= $stop2->latitude;
        $lon2= $stop2->longitude;
        $theta= $lon1 -$lon2;
        $dist=sin(deg2rad($lat1))*sin(deg2rad($lat2))+cos(deg2rad($lat1))* cos(deg2rad($lat2))*cos(deg2rad($theta));
        $dist= acos($dist);
        $dist=rad2deg($dist);
        $miles=$dist*60*1.1515;
        return  $miles;
    }

    private function localSearchOptimization($route){
        $bestRoute= $route;
        $improved=true;
        while($improved){
            $improved=false;
            for($i=1;$i<count($route)-2;$i++){
                for($j=$i+1;$j<count($route)-1;$j++){
                    $newRoute=$this->swapStops($bestRoute,$i,$j);
                    if($this->calculateTotalDistance($newRoute) < $this-> calculateTotalDistance($bestRoute)){
                        $bestRoute= $newRoute;
                        $improved=true;
                    }
                }
            }
        }
        return $bestRoute;
    }
    private function swapStops($route,$i,$j){
        $newRoute=$route;
        $temp=$newRoute[$i];
        $newRoute[$i]= $newRoute[$j];
        $newRoute[$j]= $temp;
        return $newRoute;
    }

    private function calculateTotalDistance($route){
        $totalDistance=0;
        for($i=0;$i<count($route)-1;$i++){
            $totalDistance+=$this->calculateDistance($route[$i],$route[$i+1]);
        }
        return $totalDistance;
    }
}
