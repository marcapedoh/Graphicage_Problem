<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;
use App\Utilities\ORToolsHelper;
use Illuminate\Support\Facades\Log;
class ScheduleController extends Controller
{
    public function index()
    {
        return response()->json(Schedule::all(),200);
    }

    public function store(Request $request)
    {
        $schedule = Schedule::create($request->all());
        return response()->json($schedule, 201);
    }

    public function show($id)
    {
        return Schedule::find($id);
    }

    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->update($request->all());
        return response()->json($schedule, 200);
    }

    public function destroy($id)
    {
        Schedule::destroy($id);
        return response()->json(null, 204);
    }

    public function createAndOptimizeSchedule(){
        // Étape 1: Générer les horaires initiaux avec PLNE
        if(strtoupper(substr(PHP_OS,0,3))=='WIN'){
            $classPath='scripts/lib/*;scripts';
        }else{
            $classPath='scripts/lib/*:scripts';
        }
        $currentDirectory=getcwd();
        $mainClass='AdjustSchedule';
        $classPath=$currentDirectory.'/'.$classPath;
        $command="java -cp \"$classPath\" $mainClass";
        $result = shell_exec($command.' 2>&1');
        // Étape 2: Optimiser les horaires avec le Recuit Simulé
        $jsonFormat=json_decode($result, true);
        $optimizedSchedules = $this->optimizeScheduleWithSimulatedAnnealing($jsonFormat);
        $optimizedSchedule = $this->geneticAlgorithm();

        return response()->json($optimizedSchedule,200);
    }
    private function optimizeScheduleWithSimulatedAnnealing($initialSchedules){
        $temperature=10000;
        $coolingRate=0.001;
        $currentSolution=$initialSchedules;
        $bestSolution=$initialSchedules;

        while($temperature>1){
            $newSolution=$this->generateNeighbor($currentSolution);
            $currentCost=$this->calculateCost($currentSolution);
            $newCost=$this->calculateCost($newSolution);
            if($this->acceptanceProbability($currentCost,$newCost,$temperature)> mt_rand()/mt_getrandmax()){
                $currentSolution=$newSolution;
            }

            if($newCost<$this->calculateCost($bestSolution)){
                $bestSolution=$newSolution;
            }
            $temperature*=1-$coolingRate;
        }
        return $bestSolution;
    }
    private function generateNeighbor($solution) {
        $newSolution = $solution;
        $index1 = array_rand($newSolution);
        $index2 = array_rand($newSolution);
        // Ensure index1 and index2 are different
        while ($index1 == $index2) {
            $index2 = array_rand($newSolution);
        }
        // Swap departure times
        $temp = $newSolution[$index1]['departure_time'];
        $newSolution[$index1]['departure_time'] = $newSolution[$index2]['departure_time'];
        $newSolution[$index2]['departure_time'] = $temp;
    
        // Optionally, perform additional swaps
        if (mt_rand() / mt_getrandmax() > 0.5) {
            $index3 = array_rand($newSolution);
            $index4 = array_rand($newSolution);
            while ($index3 == $index4) {
                $index4 = array_rand($newSolution);
            }
            $temp = $newSolution[$index3]['departure_time'];
            $newSolution[$index3]['departure_time'] = $newSolution[$index4]['departure_time'];
            $newSolution[$index4]['departure_time'] = $temp;
        }
    
        return $newSolution;
    }
    
    private function calculateCost($solution){
        $cost=0;
        foreach($solution as $index=>$schedule){
            if($index>0){
                $cost+=abs($solution[$index]['departure_time']- $solution[$index-1]['departure_time']);
            }
        }
        return $cost;
    }
    private function acceptanceProbability($currentCost,$newCost,$temperature){
        if($newCost<$currentCost){
            return 1.0;
        }
        return exp(($currentCost-$newCost)/$temperature);
    }

    private function countScheduleElement(){
        // Étape 1: Générer les horaires initiaux avec PLNE
        if(strtoupper(substr(PHP_OS,0,3))=='WIN'){
            $classPath='scripts/lib/*;scripts';
        }else{
            $classPath='scripts/lib/*:scripts';
        }
        $currentDirectory=getcwd();
        $mainClass='AdjustSchedule';
        $classPath=$currentDirectory.'/'.$classPath;
        $command="java -cp \"$classPath\" $mainClass";
        $result = shell_exec($command.' 2>&1');
        // Étape 2: Optimiser les horaires avec le Recuit Simulé
        $jsonFormat=json_decode($result, true);
        $counter=0;
        foreach($jsonFormat as $element){
            if($element!=null){
                $counter+=1;
            }
        }
        return $counter;
    }
    private function renderScheduleElement(){
        // Étape 1: Générer les horaires initiaux avec PLNE
        if(strtoupper(substr(PHP_OS,0,3))=='WIN'){
            $classPath='scripts/lib/*;scripts';
        }else{
            $classPath='scripts/lib/*:scripts';
        }
        $currentDirectory=getcwd();
        $mainClass='AdjustSchedule';
        $classPath=$currentDirectory.'/'.$classPath;
        $command="java -cp \"$classPath\" $mainClass";
        $result = shell_exec($command.' 2>&1');
        // Étape 2: Optimiser les horaires avec le Recuit Simulé
        $jsonFormat=json_decode($result, true);
        return $jsonFormat;
    }

    public function geneticAlgorithm(){
        $POPULATION_SIZE=100;
        $MAX_GENERATIONS=1000;
        $random=mt_rand();
        $population=[$POPULATION_SIZE];
        for($i=0;$i<$POPULATION_SIZE;$i++){
            $population[$i]=[$this->countScheduleElement()];
            for($j=0;$j<$this->countScheduleElement();$j++){
                $population[$i][$j]=$this->renderScheduleElement()[$j];
            }
        }

        for($generation=0;$generation<$MAX_GENERATIONS;$generation++){
            $fitnessScores= [$POPULATION_SIZE];
            for($i=0;$i<$POPULATION_SIZE;$i++){
                $fitnessScores[$i]=$this->fitness($population[$i]);
            }
            $bestIndex1=0;
            $bestIndex2=1;
            for($i=2;$i<$POPULATION_SIZE;$i++){
                if($fitnessScores[$i]<$fitnessScores[$bestIndex1]){
                    $bestIndex2=$bestIndex1;
                    $bestIndex1=$i;
                }elseif($fitnessScores[$i]<$fitnessScores[$bestIndex2]){
                    $bestIndex2=$i;
                }
            }
            $newPopulation=[$POPULATION_SIZE];
            $newPopulation[0]=$population[$bestIndex1];
            $newPopulation[1]= $population[$bestIndex2];
            for($i=2;$i<$POPULATION_SIZE;$i++){
                $newPopulation[$i]=$this->crossover($population[$bestIndex1],$population[$bestIndex2]);
                $this->mutate($newPopulation[$i]);
            }
            $population=$newPopulation;
        }
        return $population[0];
    }
    private function fitness($schedule){
        return array_sum($schedule);
    }

    private function crossover($schedule1,$schedule2){
        $randomValue=mt_rand();
        $crossoverPoint=mt_rand($schedule1->length);
        $newSchedule= [$schedule1->length];
        for($i=0;$i<$schedule1->length;$i++){
            if($i<$crossoverPoint){
                $newSchedule[$i]=$schedule1[$i];
            }else{
                $newSchedule[$i]= $schedule2[$i];
            }
        }
        return $newSchedule;
    }

    private function mutate($schedule){
        $MUTATION_RATE=0.01;
        $random=mt_rand();
        for($i=0;$i<$schedule->length;$i++){
            if(mt_rand()<$MUTATION_RATE){
                $schedule[$i]+=mt_rand(-5,5);
            }
        }
    }

}
