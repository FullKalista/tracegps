<?php
// Projet TraceGPS
// fichier : modele/Trace.class.php
// Rôle : la classe Trace représente une trace ou un parcours
// Dernière mise à jour : 9/9/2019 par Moi

include_once ('PointDeTrace.class.php');

class Trace
{
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------- Attributs privés de la classe -------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    private $id;				// identifiant de la trace
    private $dateHeureDebut;		// date et heure de début
    private $dateHeureFin;		// date et heure de fin
    private $terminee;			// true si la trace est terminée, false sinon
    private $idUtilisateur;		// identifiant de l'utilisateur ayant créé la trace
    private $lesPointsDeTrace;		// la collection (array) des objets PointDeTrace formant la trace
    
    // ------------------------------------------------------------------------------------------------------
    // ----------------------------------------- Constructeur -----------------------------------------------
    // ------------------------------------------------------------------------------------------------------
     
    public function __construct($unId, $uneDateHeureDebut, $uneDateHeureFin, $terminee, $unIdUtilisateur) {
        $this->id = $unId;
        $this->dateHeureDebut = $uneDateHeureDebut;
        $this->dateHeureFin = $uneDateHeureFin;
        $this->terminee = $terminee;
        $this->idUtilisateur = $unIdUtilisateur;
        $this->lesPointsDeTrace = array();
    }
    
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------------- Getters et Setters ------------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    public function getId() {return $this->id;}
    public function setId($unId) {$this->id = $unId;}
    
    public function getDateHeureDebut() {return $this->dateHeureDebut;}
    public function setDateHeureDebut($uneDateHeureDebut) {$this->dateHeureDebut = $uneDateHeureDebut;}
    
    public function getDateHeureFin() {return $this->dateHeureFin;}
    public function setDateHeureFin($uneDateHeureFin) {$this->dateHeureFin= $uneDateHeureFin;}
    
    public function getTerminee() {return $this->terminee;}
    public function setTerminee($terminee) {$this->terminee = $terminee;}
    
    public function getIdUtilisateur() {return $this->idUtilisateur;}
    public function setIdUtilisateur($unIdUtilisateur) {$this->idUtilisateur = $unIdUtilisateur;}
    
    public function getLesPointsDeTrace() {return $this->lesPointsDeTrace;}
    public function setLesPointsDeTrace($lesPointsDeTrace) {$this->lesPointsDeTrace = $lesPointsDeTrace;}
    
    // Fournit une chaine contenant toutes les données de l'objet
    public function toString() {
        $msg = "Id : " . $this->getId() . "<br>";
        $msg .= "Utilisateur : " . $this->getIdUtilisateur() . "<br>";
        if ($this->getDateHeureDebut() != null) {
            $msg .= "Heure de début : " . $this->getDateHeureDebut() . "<br>";
        }
        if ($this->getTerminee()) {
            $msg .= "Terminée : Oui  <br>";
        }
        else {
            $msg .= "Terminée : Non  <br>";
        }
        $msg .= "Nombre de points : " . $this->getNombrePoints() . "<br>";
        if ($this->getNombrePoints() > 0) {
            if ($this->getDateHeureFin() != null) {
                $msg .= "Heure de fin : " . $this->getDateHeureFin() . "<br>";
            }
            $msg .= "Durée en secondes : " . $this->getDureeEnSecondes() . "<br>";
            $msg .= "Durée totale : " . $this->getDureeTotale() . "<br>";
            $msg .= "Distance totale en Km : " . $this->getDistanceTotale() . "<br>";
            $msg .= "Dénivelé en m : " . $this->getDenivele() . "<br>";
            $msg .= "Dénivelé positif en m : " . $this->getDenivelePositif() . "<br>";
            $msg .= "Dénivelé négatif en m : " . $this->getDeniveleNegatif() . "<br>";
            $msg .= "Vitesse moyenne en Km/h : " . $this->getVitesseMoyenne() . "<br>";
            $msg .= "Centre du parcours : " . "<br>";
            $msg .= "   - Latitude : " . $this->getCentre()->getLatitude() . "<br>";
            $msg .= "   - Longitude : "  . $this->getCentre()->getLongitude() . "<br>";
            $msg .= "   - Altitude : " . $this->getCentre()->getAltitude() . "<br>";
        }
        return $msg;
    }
    
    // Fournit le nombre de points dans la trace
    public function getNombrePoints() {      
        return sizeof($this->lesPointsDeTrace);
    }
    
    // Fournit un point au centre du parcours
    public function getCentre() {
        if ($this->getNombrePoints() > 0) {
            $premierPoint = $this->lesPointsDeTrace[0];
            
            $latMin = $premierPoint->getLatitude();
            $latMax = $premierPoint->getLatitude();
            
            $lonMin = $premierPoint->getLongitude();
            $lonMax = $premierPoint->getLongitude();
            
            foreach ($this->lesPointsDeTrace as $point) {
                if ($point->getLatitude() < $latMin) {
                    $latMin = $point->getLatitude();
                }
                if ($point->getLatitude() > $latMax) {
                    $latMax = $point->getLatitude();
                }
                if ($point->getLongitude() < $lonMin) {
                    $lonMin = $point->getLongitude();
                }
                if ($point->getLongitude() > $lonMax) {
                    $lonMax = $point->getLongitude();
                }
            }
            
            $latMoy = ($latMin + $latMax) / 2;
            $lonMoy = ($lonMin + $lonMax) / 2;
            
            $unPoint = new Point($latMoy, $lonMoy, 0);
            
            return $unPoint;
        }
        
        return null;
    }
    
    // Fournit le dénivelé total du parcours
    public function getDenivele() {
        if ($this->getNombrePoints() > 0) {
            $premierPoint = $this->lesPointsDeTrace[0];
            
            $altMin = $premierPoint->getAltitude();
            $altMax = $premierPoint->getAltitude();
            
            foreach ($this->lesPointsDeTrace as $point) {
                if ($point->getAltitude() < $altMin) {
                    $altMin = $point->getAltitude();
                }
                if ($point->getAltitude() > $altMax) {
                    $altMax = $point->getAltitude();
                }
            }
            
            return $altMax - $altMin;
        }
        
        return 0;
    }
    
    // Fournit la durée totale en secondes
    public function getDureeEnSecondes() {
        if ($this->getNombrePoints() > 0) {
            $premierPoint = $this->lesPointsDeTrace[0];
            $dernierPoint = $this->lesPointsDeTrace[sizeof($this->lesPointsDeTrace) - 1];

            return $dernierPoint->getTempsCumule() - $premierPoint->getTempsCumule();
        }
        
        return 0;
    }
    
    // Fournit la durée totale sous forme d'une chaîne
    public function getDureeTotale() {
        if ($this->getNombrePoints() > 0) {
            
            $tpsSec = $this->getDureeEnSecondes();
            
            $heures = floor($tpsSec / 3600);
            $tpsSec -= $heures * 3600;
            
            $minutes = floor($tpsSec / 60);
            $tpsSec -= $minutes * 60;
            
            $secondes = $tpsSec;

            return str_pad($heures, 2, 0) . ":" . str_pad($minutes, 2, 0) . ":" . str_pad($secondes, 2, 0);
        }
        
        return "00:00:00";
    }
    
    // Fournit la distance totale
    public function getDistanceTotale() {
        if ($this->getNombrePoints() > 0) {
            $dernierPoint = $this->lesPointsDeTrace[sizeof($this->lesPointsDeTrace) - 1];
            return $dernierPoint->getDistanceCumulee();
        }
        
        return 0;
    }  
    
    // Fournit le total du dénivelé positif
    public function getDenivelePositif() {
        if ($this->getNombrePoints() > 0) {
            
            $deniveleTotal = 0;
            
            for ($i = 1; $i < sizeof($this->lesPointsDeTrace); $i++){
                $point1 = $this->lesPointsDeTrace[$i - 1];
                $point2 = $this->lesPointsDeTrace[$i];
                
                $denivele = $point2->getAltitude() - $point1->getAltitude();
                
                if ($denivele > 0) {
                    $deniveleTotal += $denivele;
                }
            }
            
            return $deniveleTotal;
        }
        
        return 0;
    } 
    
    // Fournit le total du dénivelé négatif
    public function getDeniveleNegatif() {
        if ($this->getNombrePoints() > 0) {
            
            $deniveleTotal = 0;
            
            for ($i = 1; $i < sizeof($this->lesPointsDeTrace); $i++){
                $point1 = $this->lesPointsDeTrace[$i - 1];
                $point2 = $this->lesPointsDeTrace[$i];
                
                $denivele = $point1->getAltitude() - $point2->getAltitude();
                
                if ($denivele > 0) {
                    $deniveleTotal += $denivele;
                }
            }
            
            return $deniveleTotal;
        }
        
        return 0;
    } 
    
    // Fournit la vitesse moyenne du parcours
    public function getVitesseMoyenne() {
        if ($this->getNombrePoints() > 0) {
            
            $tpsTotal = $this->getDureeEnSecondes();
            $distTotal = $this->getDistanceTotale();

            return ($distTotal / $tpsTotal) * 3600;
        }
        
        return 0;
    }
    
    // Permet d'ajouter un point à la collection
    public function ajouterPoint($unPointDeTrace) {
        if ($this->getNombrePoints() > 0) {
            $dernierPoint = $this->lesPointsDeTrace[sizeof($this->lesPointsDeTrace) - 1];
            $distance = $unPointDeTrace->getDistance($unPointDeTrace, $dernierPoint);
            $duree = strtotime($unPointDeTrace->getDateHeure()) - strtotime($dernierPoint->getDateHeure());
            
            $unPointDeTrace->setDistanceCumulee($dernierPoint->getDistanceCumulee() + $distance);
            $unPointDeTrace->setTempsCumule($dernierPoint->getTempsCumule() + $duree);
            
            $tps = $unPointDeTrace->getTempsCumule() - $dernierPoint->getTempsCumule();
            
            $unPointDeTrace->setVitesse(($distance / $tps) * 3600);
        }
        else {
            $unPointDeTrace->setDistanceCumulee(0);
            $unPointDeTrace->setTempsCumule(0);
            $unPointDeTrace->setVitesse(0);
        }
        
        $this->lesPointsDeTrace[] = $unPointDeTrace;
    }
    
    // Permet de vider la collection
    public function viderListePoints() {
        $this->lesPointsDeTrace = array();
    }
} // fin de la classe Trace

// ATTENTION : on ne met pas de balise de fin de script pour ne pas prendre le risque
// d'enregistrer d'espaces après la balise de fin de script !!!!!!!!!!!!
