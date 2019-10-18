<?php
// Projet TraceGPS - services web
// fichier : api/services/GetLesParcoursDunUtilisateur.php
// Dernière mise à jour : 18/10/2019 par Corentin

// Rôle : ce service web permet à un utilisateur d'obtenir la liste de ses parcours ou la liste des parcours d'un utilisateur qui l'autorise.// Le service web doit recevoir 3 paramètres :

// Paramètres à fournir :
// •	pseudo : le pseudo de l'utilisateur
// •	mdp : le mot de passe de l'utilisateur hashé en sha1
// •	pseudoConsulte : le pseudo de l'utilisateur dont on veut consulter la liste des parcours
// •	lang : le langage utilisé pour le flux de données ("xml" ou "json")

// Description du traitement :
// •	Vérifier que les données transmises sont complètes
// •	Vérifier l'authentification de l'utilisateur demandeur
// •	Vérifier l'existence du pseudo de l'utilisateur consulté
// •	Vérifier si l'utilisateur demandeur consulte ses propres traces, ou si il est autorisé à consulter les traces de l'utilisateur consulté
// •	Fournir la liste des traces de l'utilisateur consulté

// Les paramètres doivent être passés par la méthode GET :
//     http://<hébergeur>/tracegps/api/GetLesParcoursDunUtilisateur?pseudo=europa
//     &mdp=13e3668bbee30b004380052b086457b014504b3e&pseudoConsulte=callisto&lang=json


// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$pseudoConsulte = ( empty($this->request['pseudoConsulte'])) ? "" : $this->request['pseudoConsulte'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];

// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// initialisation du nombre de réponses
$nbReponses = 0;
$lesTraces = array();

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET")
{	$msg = "Erreur : méthode HTTP incorrecte.";
$code_reponse = 406;
}
else {
    // Les paramètres doivent être présents
    if ( $pseudo == "" || $mdpSha1 == "" || $pseudoConsulte == "")
    {	$msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    }
    else
    {	
        if ( $dao->getNiveauConnexion($pseudo, $mdpSha1) == 0 ) {
            $msg = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        }
        else
        {	
            if ( $dao->getUnUtilisateur($pseudoConsulte) == null ) {
                $msg = "Erreur : pseudo consulté inexistant.";
                $code_reponse = 401;
            }
            else {
                if ( $dao->autoriseAConsulter($pseudoConsulte, $pseudo) && $pseudo != $pseudoConsulte) {
                    $msg = "Erreur : vous n'êtes pas autorisé par cet utilisateur.";
                    $code_reponse = 401;
                }
                else {
                    // récupération de la liste des utilisateurs à l'aide de la méthode getLesUtilisateursQueJautorise de la classe DAO
                    $lesTraces = $dao->getLesTraces($dao->getUnUtilisateur($pseudoConsulte)->getId());
                    
                    // mémorisation du nombre d'utilisateurs
                    $nbReponses = sizeof($lesTraces);
                    
                    if ($nbReponses == 0) {
                        $msg = "Aucune trace pour l'utilisateur ". $pseudoConsulte . ".";
                        $code_reponse = 200;
                    }
                    else {
                        $msg = $nbReponses . " trace(s) pour l'utilisateur ". $pseudoConsulte . ".";
                        $code_reponse = 200;
                    }
                }
            }
        }
    }
}

// ferme la connexion à MySQL :
unset($dao);

// création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";      // indique le format XML pour la réponse
    $donnees = creerFluxXML($msg, $lesTraces);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON($msg, $lesTraces);
}

// envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);

// fin du programme (pour ne pas enchainer sur les 2 fonctions qui suivent)
exit;

// ================================================================================================

// création du flux XML en sortie
function creerFluxXML($msg, $lesTraces)
{
    /* Exemple de code XML
    <?xml version="1.0" encoding="UTF-8"?>
    <!--Service web GetLesParcoursDunUtilisateur - BTS SIO - Lycée De La Salle - Rennes-->
    <data>
      <reponse>2 trace(s) pour l'utilisateur callisto</reponse>
      <donnees>
        <lesTraces>
          <trace>
            <id>2</id>
            <dateHeureDebut>2018-01-19 13:08:48</dateHeureDebut>
            <terminee>1</terminee>
            <dateHeureFin>2018-01-19 13:11:48</dateHeureFin>
            <distance>1.2</distance>
            <idUtilisateur>2</idUtilisateur>
          </trace>
          <trace>
            <id>1</id>
            <dateHeureDebut>2018-01-19 13:08:48</dateHeureDebut>
            <terminee>0</terminee>
            <distance>0.5</distance>
            <idUtilisateur>2</idUtilisateur>
          </trace>
        </lesTraces>
      </donnees>
    </data>
    */
    
    // crée une instance de DOMdocument (DOM : Document Object Model)
    $doc = new DOMDocument();
    
    // specifie la version et le type d'encodage
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';
    
    // crée un commentaire et l'encode en UTF-8
    $elt_commentaire = $doc->createComment('Service web GetLesParcoursDunUtilisateur - BTS SIO - Lycée De La Salle - Rennes');
    // place ce commentaire à la racine du document XML
    $doc->appendChild($elt_commentaire);
    
    // crée l'élément 'data' à la racine du document XML
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);
    
    // place l'élément 'reponse' dans l'élément 'data'
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);
    
    // traitement des utilisateurs
    if (sizeof($lesTraces) > 0) {
        // place l'élément 'donnees' dans l'élément 'data'
        $elt_donnees = $doc->createElement('donnees');
        $elt_data->appendChild($elt_donnees);
        
        // place l'élément 'lesTraces' dans l'élément 'donnees'
        $elt_lesTraces = $doc->createElement('lesTraces');
        $elt_donnees->appendChild($elt_lesTraces);
        
        foreach ($lesTraces as $uneTrace)
        {
            // crée un élément vide 'trace'
            $elt_trace = $doc->createElement('trace');
            // place l'élément 'trace' dans l'élément 'lesTraces'
            $elt_lesTraces->appendChild($elt_trace);
            
            // crée les éléments enfants de l'élément 'trace'
            $elt_id         = $doc->createElement('id', $uneTrace->getId());
            $elt_trace->appendChild($elt_id);
            
            $elt_dateHeureDebut    = $doc->createElement('dateHeureDebut', $uneTrace->getDateHeureDebut());
            $elt_trace->appendChild($elt_dateHeureDebut);
            
            $elt_terminee    = $doc->createElement('terminee', $uneTrace->getTerminee());
            $elt_trace->appendChild($elt_terminee);
            
            if ($uneTrace->getTerminee() == 1) {
                $elt_dateHeureFin     = $doc->createElement('dateHeureFin', $uneTrace->getDateHeureFin());
                $elt_trace->appendChild($elt_dateHeureFin);
            }
            
            $elt_distance     = $doc->createElement('distance', round($uneTrace->getDistanceTotale(), 3));
            $elt_trace->appendChild($elt_distance);
            
            $elt_idUtilisateur = $doc->createElement('idUtilisateur', $uneTrace->getIdUtilisateur());
            $elt_trace->appendChild($elt_idUtilisateur);
        }
    }
    // Mise en forme finale
    $doc->formatOutput = true;
    
    // renvoie le contenu XML
    return $doc->saveXML();
}

// ================================================================================================

// création du flux JSON en sortie
function creerFluxJSON($msg, $lesTraces)
{
    /* Exemple de code JSON
    {
        "data": {
            "reponse": "2 trace(s) pour l'utilisateur callisto",
            "donnees": {
                "lesTraces": [
                    {
                        "id": "2",
                        "dateHeureDebut": "2018-01-19 13:08:48",
                        "terminee": "1",
                        "dateHeureFin": "2018-01-19 13:11:48",
                        "distance": "1.2",
                        "idUtilisateur": "2"
                    },
                    {
                        "id": "1",
                        "dateHeureDebut": "2018-01-19 13:08:48",
                        "terminee": "0",
                        "distance": "0.5",
                        "idUtilisateur": "2"
                    }
                ]
            }
        }
    }
    */
    
    
    if (sizeof($lesTraces) == 0) {
        // construction de l'élément "data"
        $elt_data = ["reponse" => $msg];
    }
    else {
        // construction d'un tableau contenant les traces
        $lesObjetsDuTableau = array();
        foreach ($lesTraces as $uneTrace)
        {	// crée une ligne dans le tableau
            $unObjetTrace = array();
            $unObjetTrace["id"] = $uneTrace->getId();
            $unObjetTrace["dateHeureDebut"] = $uneTrace->getDateHeureDebut();
            $unObjetTrace["terminee"] = $uneTrace->getTerminee();
            if ($uneTrace->getTerminee() == 1) {
                $unObjetTrace["dateHeureFin"] = $uneTrace->getDateHeureFin();
            }
            $unObjetTrace["distance"] = round($uneTrace->getDistanceTotale(), 3);
            $unObjetTrace["idUtilisateur"] = $uneTrace->getIdUtilisateur();
            $lesObjetsDuTableau[] = $unObjetTrace;
        }
        // construction de l'élément "lesUtilisateurs"
        $elt_traces = ["lesTraces" => $lesObjetsDuTableau];
        
        // construction de l'élément "data"
        $elt_data = ["reponse" => $msg, "donnees" => $elt_traces];
    }
    
    // construction de la racine
    $elt_racine = ["data" => $elt_data];
    
    // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}

// ================================================================================================
?>
