<?php
$dao = new DAO();

//Classe outil



//Récupération du pseudo de l'utilisateur
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpsha1 = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];

$laTrace = null;
if ( $pseudo == "" || $mdpsha1 == "") {
    $msg = "Erreur : données incomplètes.";
    $code_reponse = 400;
}
else
{	$idUser = $dao->getUnUtilisateur($pseudo)->getId();
    if ( $dao->getNiveauConnexion($pseudo, $mdpsha1) == 0 ){
         $msg = "Erreur : authentification incorrecte.";
         $code_reponse = 401;
    }
    else{
         $msg = "Trace créée";
         $code_reponse = 200;
         $trace = new Trace(1,date('Y-m-d H:i:s', time()),null,0,$idUser);
        
         $dao->creerUneTrace($trace);
        
         $lesTrace = $dao->getToutesLesTraces();
         $laTrace = $dao->getUneTrace(sizeof($lesTrace)-1);
        
    }
}



// ferme la connexion à MySQL :
unset($dao);

// création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";      // indique le format XML pour la réponse
    $donnees = creerFluxXML ($msg,$laTrace);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON ($msg,$laTrace);
}

// envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);

function creerFluxXML($msg,$laTrace)
{
    /* Exemple de code XML
     <?xml version="1.0" encoding="UTF-8"?>
     <!--Service web ChangerDeMdp - BTS SIO - Lycée De La Salle - Rennes-->
     <data>
     <reponse>Erreur : authentification incorrecte.</reponse>
     </data>
     */
    
    // crée une instance de DOMdocument (DOM : Document Object Model)
  
    
    
    
    $doc = new DOMDocument();
    
    // specifie la version et le type d'encodage
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';
    
    // crée un commentaire et l'encode en UTF-8
    $elt_commentaire = $doc->createComment('Service web DemanderMdp - BTS SIO - Lycée De La Salle - Rennes');
    // place ce commentaire à la racine du document XML
    $doc->appendChild($elt_commentaire);
    
    // crée l'élément 'data' à la racine du document XML
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);
    
    // place l'élément 'reponse' juste après l'élément 'data'
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);
    if($laTrace != null){
        $elt_donnee = $doc->createElement('donnees');
        $elt_data->appendChild($elt_donnee);
    
        $elt_trace = $doc->createElement('trace');
        $elt_data->appendChild($elt_trace);
    
        $elt_id = $doc->createElement('id', $laTrace->getId());
        $elt_trace->appendChild($elt_id);
    
        $elt_dateDebut = $doc->createElement('dateDebut',$laTrace->getDateHeureDebut());
        $elt_trace->appendChild($elt_dateDebut);
    
        $elt_terminee = $doc->createElement('terminee',0);
        $elt_trace->appendChild($elt_terminee);
    
        $elt_idUtilisateur = $doc->createElement('idUtilisateur',$laTrace->getIdUtilisateur());
        $elt_trace->appendChild($elt_idUtilisateur);
    
    }
    
    // Mise en forme finale
    $doc->formatOutput = true;
    
    // renvoie le contenu XML
    return $doc->saveXML();
}

// ================================================================================================

// création du flux JSON en sortie
function creerFluxJSON($msg, $laTrace)
{
    /* Exemple de code JSON
     {
     "data": {
     "reponse": "Erreur : authentification incorrecte."
     }
     }
     */
    if($laTrace != null){
    // construction de l'élément "data"
    $elt_data = ["reponse" => $msg];
    
    // construction de la racine
    $elt_racine = ["data" => $elt_data];
    
    
    $laTraceAffichee = array();
    $unObjetTrace = array();
    $unObjetTrace["id"] = $laTrace->getId();
    $unObjetTrace["dateHeureDebut"] = $laTrace->getDateHeureDebut() ;
    $unObjetTrace["terminee"] = 0 ;
    $unObjetTrace["idUtilisateur"] = $laTrace->getIdutilisateur();
    $laTraceAffichee[] = $unObjetTrace;
    
    $elt_laTrace = ["trace" => $laTraceAffichee];
    // construction de l'élément "data"
    $elt_data = ["reponse" => $msg, "donnees" => $elt_laTrace];
    }
    $elt_racine = ["data" => $elt_data];
    // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}

// ================================================================================================
?>