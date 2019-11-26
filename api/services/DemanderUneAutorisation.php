<?php
// Projet TraceGPS - services web
// fichier :  api/services/DemanderMDP.php
// Dernière mise à jour : 18/10/2019 par Yvan

// Rôle : ce service permet à un utilisateur de demander un mail pour retrouver son mdp perdu
// Le service web doit recevoir 6 paramètre :
//     pseudo : le pseudo de l'utilisateur
//     mdp : le mot de passe de l'utilisateur
//     pseudoDestinataire : le pseudo du destinataire
//     texteMessage : le texte d'un message accompagnant la demande
//     nomPrenom : le nom et le prénom du demandeur
//     lang : le langage utilisé pour le flux de données ("xml" ou "json")
// Le service retourne un flux de données XML ou JSON contenant un compte-rendu d'exécution


// http://<hébergeur>/tracegps/api/DemanderMdp
// connexion du serveur web à la base MySQL
$dao = new DAO();

//Classe outil



//Récupération du pseudo de l'utilisateur
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdp = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$pseudoDestinataire = ( empty($this->request['pseudoDestinataire'])) ? "" : $this->request['pseudoDestinataire'];
$texteMessage = ( empty($this->request['texteMessage'])) ? "" : $this->request['texteMessage'];
$nomPrenom = ( empty($this->request['nomPrenom'])) ? "" : $this->request['nomPrenom'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];

if ($lang != "json") $lang = "xml";

if ( $pseudo == ""|| $mdp == ""|| $pseudoDestinataire == ""|| $texteMessage == ""|| $nomPrenom == "") {
    $msg = "Erreur : données incomplètes.";
    $code_reponse = 400;
}
else {
    
    
    if ($dao->getNiveauConnexion($pseudo, $mdp) == 0 ) {
        $msg = "Erreur : authentification incorrecte.";
        $code_reponse = 401;
    }
    else {
        
        if ( $dao->existePseudoUtilisateur($pseudoDestinataire) == false ) {
            $msg = "Erreur : pseudo utilisateur inexistant.";
            $code_reponse = 500;
        }
        else {
            $user1 = $dao->getUnUtilisateur($pseudo);
            $adresseDemandeur = $user1->getAdrMail();
            $user2 = $dao->getUnUtilisateur($pseudoDestinataire);
            $adresseAutreMembre = $user2->getAdrMail();
            $sujet = "Demande d'autorisation de la part d'un utilisateur du système TraceGPS";
            // envoie un courriel  à l'utilisateur avec son nouveau mot de passe
            $ok = Outils::envoyerMail($adresseAutreMembre, $sujet, $texteMessage, $adresseDemandeur);
            if ( ! $ok ) {
                $msg = "Erreur : l'envoi du courriel de demande d'autorisation a rencontré un problème.";
                $code_reponse = 500;
            }
            else {
                $msg = $pseudoDestinataire."va recevoir un courriel avec votre demande.";
                $code_reponse = 200;
            }
        }
    }
    
}


// ferme la connexion à MySQL :
unset($dao);

// création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";      // indique le format XML pour la réponse
    $donnees = creerFluxXML ($msg);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON ($msg);
}

// envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);

// fin du programme (pour ne pas enchainer sur les 2 fonctions qui suivent)
exit;

// ================================================================================================

// création du flux XML en sortie
function creerFluxXML($msg)
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
    $elt_commentaire = $doc->createComment('Service web DemanderUneAutorisation - BTS SIO - Lycée De La Salle - Rennes');
    // place ce commentaire à la racine du document XML
    $doc->appendChild($elt_commentaire);
    
    // crée l'élément 'data' à la racine du document XML
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);
    
    // place l'élément 'reponse' juste après l'élément 'data'
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);
    
    // Mise en forme finale
    $doc->formatOutput = true;
    
    // renvoie le contenu XML
    return $doc->saveXML();
}

// ================================================================================================

// création du flux JSON en sortie
function creerFluxJSON($msg)
{
    /* Exemple de code JSON
     {
     "data": {
     "reponse": "Erreur : authentification incorrecte."
     }
     }
     */
    
    // construction de l'élément "data"
    $elt_data = ["reponse" => $msg];
    
    // construction de la racine
    $elt_racine = ["data" => $elt_data];
    
    // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}

// ================================================================================================
?>
