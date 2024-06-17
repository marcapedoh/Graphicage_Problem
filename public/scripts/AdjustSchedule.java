
import java.util.*;
import java.sql.*;
import com.google.gson.Gson;
import com.google.gson.GsonBuilder;
import com.google.ortools.Loader;
import com.google.ortools.linearsolver.MPSolver;
import com.google.ortools.linearsolver.MPVariable;
import com.google.ortools.linearsolver.MPObjective;
import com.google.ortools.linearsolver.MPConstraint;

public class AdjustSchedule {
    static {
        Loader.loadNativeLibraries();
    }

    public static void main(String[] args) {
        int[] departures = solvePLNE();
        // Définir les contraintes (à remplacer par tes propres contraintes opérationnelles)
         // Définir les contraintes dynamiquement en fonction de la taille de departures
         int[] contraintes = new int[departures.length - 1];
         for (int i = 0; i < contraintes.length; i++) {
             contraintes[i] = 12; // Par exemple, définir un intervalle de 10 minutes entre chaque départ
         }
        //Définir le solveur
        MPSolver solver= new MPSolver("AdjustScheculer",MPSolver.OptimizationProblemType.CBC_MIXED_INTEGER_PROGRAMMING);
    
        //Définir les variables
        MPVariable[] variables= new MPVariable[departures.length];
        for (int i = 0; i < departures.length; i++) {
            variables[i] = solver.makeIntVar(0, 1260, "departure_" + i);
            variables[i].setBounds(departures[i],1260); // Définir les valeurs initiales des variables
        }
        
        // Définir les contraintes (par exemple, temps minimum entre chaque départ)
        for (int i = 0; i < departures.length - 1; i++) {
            MPConstraint constraint = solver.makeConstraint(contraintes[i], 1260); // Temps minimum entre les départs
            constraint.setCoefficient(variables[i], 1);
            constraint.setCoefficient(variables[i + 1], -1);
        }
        
            // Définir les contraintes (par exemple, temps minimum entre chaque départ)
            for (int i = 0; i < departures.length - 1; i++) {
                MPConstraint constraint = solver.makeConstraint(contraintes[i], 1440); // Temps minimum entre les départs
                constraint.setCoefficient(variables[i], 1);
                constraint.setCoefficient(variables[i + 1], -1);
            }
    
            // Définir la fonction objective (par exemple, minimiser les écarts par rapport aux horaires initiaux)
            MPObjective objective = solver.objective();
            for (int i = 0; i < departures.length; i++) {
                objective.setCoefficient(variables[i], 1);
            }
            objective.setMinimization();
    
            // Résoudre le problème
            final MPSolver.ResultStatus resultStatus = solver.solve();
            if (resultStatus == MPSolver.ResultStatus.OPTIMAL || resultStatus == MPSolver.ResultStatus.FEASIBLE) {
                // Récupérer les horaires ajustés
                List<Integer> adjustedDepartures = new ArrayList<>();;
                for (int i = 0; i < departures.length; i++) {
                    adjustedDepartures.add(i, (int) variables[i].solutionValue());
                }
    
                // Afficher les résultats
                /*for (int i = adjustedDepartures.length-1; i >= 0; i--) {
                    System.out.println(adjustedDepartures[i]);
                }*/
                List<Map<String, Integer>> adjustedDeparturesAssociative = adjustedDepartures.stream()
                .map(time -> Map.of("departure_time", time))
                .toList();
                // Convertir les résultats en format JSON
                Gson gson = new GsonBuilder().setPrettyPrinting().create();
                String jsonResult = gson.toJson(adjustedDeparturesAssociative);

                // Afficher les résultats en format JSON
                System.out.println(jsonResult);
            } else {
                System.out.println("Le solveur n'a pas pu trouver de solution optimale.");
            }
    }

    public static int[] solvePLNE() {
        Loader.loadNativeLibraries();
        
        try {
            Class.forName("com.mysql.cj.jdbc.Driver");
        } catch (ClassNotFoundException e) {
            System.out.println("Driver MySQL JDBC non trouvé : " + e.getMessage());
            return null;
        }
        // Connexion à la base de données
        String url = "jdbc:mysql://localhost:3306/laravel";
        String username = "root";
        String password = "";
        int[] departures=null;
        try (Connection conn = DriverManager.getConnection(url, username, password)) {
            // Récupérer les arrêts depuis la base de données
            List<Map<String, Object>> stops = getStopsFromDatabase(conn);
            
            departures=new int[stops.size()];
             // Définir le solveur
             MPSolver solver = MPSolver.createSolver("CBC");


            // Contraintes temporelles
            int startHour = 5; // Heure de départ à 5h
            int startMinute = 30; // Minutes de départ à 30 minutes
            int endHour = 14; // Heure de fin à 14h
            int endMinute = 30; // Minutes de fin à 30 minutes
            int breakDuration = 12 * 60; // Durée de la pause en minutes (12 heures)

            // Convertir les heures de départ en minutes
            int departureTime = startHour * 60 + startMinute;
            int endTime = endHour * 60 + endMinute;

            // définir les variables de décision
            int numStops= stops.size();
            MPVariable[][] x=new MPVariable[numStops][numStops];
            for(int i=0;i<numStops;i++){
                for(int j=0;j<numStops;j++){
                    x[i][j]=solver.makeIntVar(0,1,"x[" + i + "][" + j + "]");
                }
            }

            //Variables pour les temps de départ des arrêts
            MPVariable[] departureTimes= new MPVariable[numStops];
            for(int i=0;i<numStops;i++){
                departureTimes[i]=solver.makeIntVar(0,endTime,"departureTime[" + i + "]");
            }

            // Définir l'objectif pour miniser le temps total de parcours
            MPObjective objective=solver.objective();
            for(int i=0;i<numStops;i++){
                for(int j=0;j<numStops;j++){
                    objective.setCoefficient(x[i][j],travelTimeBetween(stops.get(i),stops.get(j)));
                }
            }
            objective.setMinimization();
            // Contraintes de départ pour le premier arrêt
            solver.makeConstraint(departureTime, departureTime).setCoefficient(departureTimes[0], 1);

            // Contraintes de départ pour les autres arrêts
            for (int i = 1; i < numStops; i++) {
                solver.makeConstraint(0, Double.POSITIVE_INFINITY).setCoefficient(departureTimes[i], 1);
            }

            //contraintes pour chaque paire d'arrêts
            for (int i = 0; i < numStops; i++) {
                for (int j = 0; j < numStops; j++) {
                    if (i != j) {
                        int travelTime = travelTimeBetween(stops.get(i), stops.get(j));
                        MPConstraint constraint = solver.makeConstraint(Double.NEGATIVE_INFINITY, 0);
                        constraint.setCoefficient(departureTimes[i], 1);
                        constraint.setCoefficient(departureTimes[j], -1);
                        constraint.setCoefficient(x[i][j], travelTime);
                    }
                }
            }
            

            //contraintes de pause obligatoire
            for (int i = 0; i < numStops; i++) {
                MPConstraint breakConstraint = solver.makeConstraint(Double.NEGATIVE_INFINITY, (startHour + 12) * 60 + breakDuration);
                breakConstraint.setCoefficient(departureTimes[i], 1);
            }
            
            //contraintes de temps de fin
            for (int i = 0; i < numStops; i++) {
                MPConstraint endConstraint = solver.makeConstraint(Double.NEGATIVE_INFINITY, endHour * 60 + endMinute);
                endConstraint.setCoefficient(departureTimes[i], 1);
            }
            

            //Résoudre le problème
            final MPSolver.ResultStatus resultStatut=solver.solve();

            //vérifier si la solution est trouvée
            if(resultStatut==MPSolver.ResultStatus.OPTIMAL || resultStatut==MPSolver.ResultStatus.FEASIBLE){
                //Afficher les horaire générés
                for(int i=0;i<numStops;i++){
                    departures[i] = (int) departureTimes[i].solutionValue();
                }
            }else{
                System.out.println("Impossible de trouver une solution optimale");
            }
           
        } catch (SQLException e) {
            System.out.println("Erreur de connexion à la base de données: " + e.getMessage());
        }
        return departures;
    }

    private static List<Map<String, Object>> getStopsFromDatabase(Connection conn) throws SQLException {
        List<Map<String, Object>> stops = new ArrayList<>();
        String query = "SELECT * FROM stops";
        try (Statement stmt = conn.createStatement();
             ResultSet rs = stmt.executeQuery(query)) {
            while (rs.next()) {
                Map<String, Object> stop = new HashMap<>();
                stop.put("id", rs.getInt("id"));
                stop.put("latitude", rs.getDouble("latitude"));
                stop.put("longitude", rs.getDouble("longitude"));
                stop.put("name", rs.getString("name"));
                // Ajouter d'autres attributs de l'arrêt si nécessaire
                stops.add(stop);
            }
        }
        return stops;
    }

    private static int travelTimeBetween(Map<String, Object> stop1, Map<String, Object> stop2) {
        // Calculer le temps de parcours entre deux arrêts
        // Pour l'exemple, nous allons simplement retourner une valeur aléatoire entre 5 et 20 minutes
        return (int) (Math.random() * (20 - 5 + 1) + 5);
    }
}
