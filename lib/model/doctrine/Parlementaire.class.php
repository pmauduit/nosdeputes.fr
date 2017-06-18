<?php

/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class Parlementaire extends BaseParlementaire
{

  private $photo;

  public function getLink() {
    sfProjectConfiguration::getActive()->loadHelpers(array('Url'));
    return url_for('@parlementaire?slug='.$this->slug);
  }
  public function getTitre() {
    return $this->getNom().', '.$this->getLongStatut();
  }
  public function getPersonne() {
    return '';
  }
  public function getLinkSource() {
    return $this->url_an;
  }
  public function __tostring() {
    if(isset($this->nom) && $nom = $this->getNom())
      return $nom;
    return "";
  }

  public function setCirconscription($str) {
    if (preg_match('/(.*)\((\d+)/', $str, $match)) {
      $this->nom_circo = trim($match[1]);
      $this->num_circo = $match[2];
    }
  }

  public function setDepartementParNumero($n) {
    $n = strtolower(trim($n));
    if ( isset(self::$dptmt_nom["$n"]) )
      $this->_set('nom_circo', self::$dptmt_nom["$n"]);
  }

  public function getNumCircoString($list = 0) {
    if ($this->num_circo == 1) $string = $this->num_circo.'ère circonscription';
    else $string = $this->num_circo.'ème circonscription';
    if ($list == 1 && $this->num_circo < 10) {
      $string = "&nbsp;".$string."&nbsp;";
      if ($this->num_circo == 1) $string .= "&nbsp;";
    }
    return $string;
  }

  public function getNomPrenom() {
    $PrNoPaNP = $this->getPrenomNomParticule();
    return str_replace($PrNoPaNP[0].' ', '', $this->nom).', '.$PrNoPaNP[0];
  }

  public function getNomFamilleCorrect() {
    $prenom = $this->getPrenom();
    return str_replace($prenom.' ', '', $this->nom);
  }

  public function getPrenom() {
    $weird = array('é' => 'e', 'è' => 'e', 'ë' => 'e', 'Le ' => 'Le', 'La ' => 'La', '\'' => '^ ');
    $beg_name = " ".substr($this->nom_de_famille, 0, 3);
    $ct = strpos($this->nom, $beg_name);
    if (!$ct) foreach ($weird as $good => $bad)
        if ($ct = strpos($this->nom, preg_replace("/".$bad."/", $good, $beg_name)))
           break;
    $nom = substr($this->nom, $ct+1);
    $prenom = substr($this->nom, 0, strpos($this->nom, $nom));
    return preg_replace('/\s$/', '', $prenom);
  }

  public function getPrenomNomParticule() {
    $prenom = $this->getPrenom();
    $nom = str_replace($prenom.' ', '', $this->nom);
    $part = "";
    $nompart = $nom;
    if (preg_match("/^(.*) (d('|u|e(s| l['a])?))$/i", $prenom, $match)) {
      $prenom = $match[1];
      $part = $match[2];
      $nompart = $nom." (".$part.")";
    }
    return array($prenom, $nom, $part, $nompart);
  }

  public function getStatut($link = 0) {
    if ($this->type == 'depute') {
        if ($this->sexe == 'F') $type = 'députée';
        else $type = 'député';
    } else {
        if ($this->sexe == 'F') $type = 'sénatrice';
        else $type = 'sénateur';
    }
    $statut = "";
    if (!$this->isEnMandat()) {
      if ($this->sexe == 'F') $statut = 'ancienne ';
      else $statut = 'ancien ';
    }
    $groupe = "";
    if ($this->groupe_acronyme != "") {
      if ($link && function_exists('_parse_attributes') && function_exists('link_to'))
        $groupe = " ".link_to($this->groupe_acronyme, '@list_parlementaires_groupe?acro='.$this->groupe_acronyme);
      else $groupe = " ".$this->groupe_acronyme;
    }
    return $statut.$type.$groupe;
  }

  public function getMoyenStatut() {
    return $this->getStatut().' '.$this->getPrefixeCirconscription().$this->nom_circo;
  }

  public function getLongStatut($link = 0) {
    $circo = $this->nom_circo;
    if ($link && function_exists('_parse_attributes') && function_exists('link_to')) {
      $circo = link_to($this->nom_circo, '@list_parlementaires_departement?departement='.$circo);
    }
    return $this->getStatut($link).' de la '.$this->getNumCircoString().' '.$this->getPrefixeCirconscription().$circo;
  }

  public function setDebutMandat($str) {
    if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $str, $m)) {
      $this->_set('debut_mandat', $m[3].'-'.$m[2].'-'.$m[1]);
    }
  }
  public function setFinMandat($str) {
    if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $str, $m)) {
      $this->_set('fin_mandat', $m[3].'-'.$m[2].'-'.$m[1]);
    }
    if (!$str) {
      $this->_set('fin_mandat', NULL);
    }
    if ($this->fin_mandat < $this->debut_mandat)
      $this->_set('fin_mandat', NULL);
  }
  public function setFonctions($array) {
    return $this->setPOrganisme('parlementaire', $array);
  }
  public function setExtras($array) {
    return $this->setPOrganisme('extra', $array);
  }
  public function setGroupe($array) {
    return $this->setPOrganisme('groupe', $array);
  }
  public function setGroupes($array) {
    return $this->setPOrganisme('groupes', $array);
  }

  public function setPOrganisme($type, $array) {
    $today = date("Y-m-d");

    # Get existing type organismes
    $porgas = $this->getOrganismes();
    foreach($porgas as $po) {
      if ($po->type != $type)
        unset($porgas[array_search($po, $porgas)]);
    }

    # Check all input organismes
    foreach ($array as $args) {
      $fonction = $args[1];
      $importance = ParlementaireOrganisme::defImportance($fonction);

      # Create Organisme if new in db
      $orga = Doctrine::getTable('Organisme')->findOneByNomOrCreateIt($args[0], $type);

      # Search organisme in already listed one
      $found = false;
      foreach ($porgas as $po) {
        # If it already exists and the function has not changed we leave it
        if ($po->organisme_id == $orga->id && $po->fonction == $fonction) {
          $found = true;
          unset($porgas[array_search($po, $porgas)]);
          break;
        }
      }

      # If it doesn't exist, create it
      if (!$found) {
        echo "INFO: ".$this->nom." joined ".$orga->nom." as ".$fonction."\n";

        # Special case of groupe impacting specific field
        if ($type == 'groupe') {
          $acro = $orga->getSmallNomGroupe();
          if ($acro && $acro != $this->groupe_acronyme) {
            $this->groupe_acronyme = $orga->getSmallNomGroupe();
            # Update group_acronyme of current other afiliations except for left groupe
            foreach($this->getOrganismes() as $po)
              if ($po->type != "groupe") {
                $po->_set('parlementaire_groupe_acronyme', $acro);
                $po->save();
              }
          }
        }

        $po = new ParlementaireOrganisme();
        $po->setParlementaire($this);
        $po->_set('parlementaire_groupe_acronyme', $this->groupe_acronyme);
        $po->setOrganisme($orga);
        $po->setFonction($fonction);
        $po->setImportance($importance);
        $po->setDebutFonction($today);
        $po->save();
      }
    }

    # Declare as finished those not listed anymore
    foreach($porgas as $po) {
      echo "INFO: ".$this->nom." left ".$po->nom." as ".$po->fonction." (".$po->debut_fonction." -> ".$today.")\n";
      $po->setFinFonction($today);
      $po->save();
    }
  }

  public function getOrganismes($old=false) {
    $res = array();
    foreach($this->getParlementaireOrganismes() as $po) {
      if (($old && $po->fin_fonction) || (!$old && !$po->fin_fonction))
        array_push($res, $po);
    }
    return $res;
  }

  public static function historiqueSort($a, $b) {
    return strcmp($b->fin_fonction.$b->debut_fonction, $a->fin_fonction.$a->debut_fonction);
  }

  public function getHistorique() {
    $histo = $this->getOrganismes(true);
    usort($histo, 'Parlementaire::historiqueSort');
    return $histo;
  }

  public function setAutresMandats($array) {
    $this->_set('autres_mandats', serialize($array));
  }
  public function setAnciensMandats($array) {
    $this->_set('anciens_mandats', serialize($array));
  }
  public function setAnciensAutresMandats($array) {
    $this->_set('anciens_autres_mandats', serialize($array));
  }
  public function setSuppleantDe($nom) {
    if ($p = doctrine::getTable('Parlementaire')->findOneByNomSexeGroupeCirco($nom))
      $this->_set('SuppleantDe', $p);
  }
  public function setCollaborateurs($array) {
    $this->_set('collaborateurs', serialize($array));
  }
  public function setMails($array) {
    $this->_set('mails', serialize($array));
  }
  public function setSitesWeb($array) {
    $this->_set('sites_web', serialize($array));
  }
  public function setAdresses($array) {
    $this->_set('adresses', serialize($array));
  }
  public function getGroupe() {
    foreach($this->getOrganismes() as $po) {
      if ($po->type === 'groupe')
        return $po;
    }
  }
  public function getGroupeWhen($date, $enddate=null) {
    $date = strtotime($date);
    if ($enddate) $enddate = strtotime($enddate);
    $groupes = array();
    foreach($this->getParlementaireOrganismes() as $po) {
      if ($po->type != 'groupe')
        continue;
      $start = $po->debut_fonction;
      if (!$start) $start = $this->debut_mandat;
      $end = $po->fin_fonction;
      if (!$end) $end = date('Y-m-d');

      $start = strtotime($start);
      $end = strtotime($end);
      if (!$enddate) {
        if ($date >= $start && $date <= $end)
          return $po->groupe_acronyme;
      } else {
        if (($date < $start && $enddate >= $start) || ($date >= $start && $date <= $end)) {
          if (!isset($groupes[$po->groupe_acronyme]))
            $groupes[$po->groupe_acronyme] = 0;
          $groupes[$po->groupe_acronyme] += min($enddate, $end) - max($date, $start);
        }
      }
    }
    $grps = array_keys($groupes);
    $ngrps = count($grps);
    if ($ngrps == 1)
      return array_keys($groupes)[0];
    else if ($ngrps > 1)
      return array_search(max($groupes), $groupes);
    return "";
  }
  public function getExtras($old=false) {
    $res = array();
    foreach($this->getOrganismes($old) as $po) {
      if ($po->type == 'extra')
        array_push($res, $po);
    }
    return $res;
  }
  public function getGroupes($old=false) {
    $res = array();
    foreach($this->getOrganismes($old) as $po) {
      if ($po->type == 'groupes')
        array_push($res, $po);
    }
    return $res;
  }
  public function getResponsabilites($old=false) {
    $res = array();
    foreach($this->getOrganismes($old) as $po) {
      if ($po->type == 'parlementaire')
        $res[sprintf('%04d',abs(100-$po->importance)).$po->nom]=$po;
    }
    ksort($res);
    return array_values($res);
  }

  public function getPageLink() {
    return '@parlementaire?slug='.$this->slug;
  }

  public function getNomNumCirco() {
    $shortcirco = trim(strtolower($this->_get('nom_circo')));
    $shortcirco = preg_replace('/\s+/','-', $shortcirco);
    return $this->_get('nom_circo')." (".$this->getNumeroDepartement($shortcirco).")";
  }

  public function getNumDepartement() {
    $shortcirco = trim(strtolower($this->_get('nom_circo')));
    $shortcirco = preg_replace('/\s+/','-', $shortcirco);
    return $this->getNumeroDepartement($shortcirco);
  }

  public static $dptmt_pref = array(
     "Ain" => "de l'",
     "Aisne" => "de l'",
     "Allier" => "de l'",
     "Alpes-de-Haute-Provence" => "des",
     "Alpes-Maritimes" => "des",
     "Ardèche" => "de l'",
     "Ardennes" => "des",
     "Ariège" => "d'",
     "Aube" => "de l'",
     "Aude" => "de l'",
     "Aveyron" => "de l'",
     "Bas-Rhin" => "du",
     "Bouches-du-Rhône" => "des",
     "Calvados" => "du",
     "Cantal" => "du",
     "Charente" => "de",
     "Charente-Maritime" => "de",
     "Cher" => "du",
     "Corrèze" => "de",
     "Corse-du-Sud" => "de",
     "Côte-d'Or" => "de",
     "Côtes-d'Armor" => "des",
     "Creuse" => "de la",
     "Deux-Sèvres" => "des",
     "Dordogne" => "de la",
     "Doubs" => "du",
     "Drôme" => "de la",
     "Essonne" => "de l'",
     "Eure" => "de l'",
     "Eure-et-Loir" => "d'",
     "Finistère" => "du",
     "Français établis hors de France" => "des",
     "Gard" => "du",
     "Gers" => "du",
     "Gironde" => "de la",
     "Guadeloupe" => "de",
     "Guyane" => "de",
     "Haut-Rhin" => "du",
     "Haute-Corse" => "de",
     "Haute-Garonne" => "de la",
     "Haute-Loire" => "de la",
     "Haute-Marne" => "de la",
     "Haute-Saône" => "de la",
     "Haute-Savoie" => "de",
     "Haute-Vienne" => "de la",
     "Hautes-Alpes" => "des",
     "Hautes-Pyrénées" => "des",
     "Hauts-de-Seine" => "des",
     "Hérault" => "de l'",
     "Ille-et-Vilaine" => "d'",
     "Indre" => "de l'",
     "Indre-et-Loire" => "de l'",
     "Isère" => "de l'",
     "Jura" => "du",
     "Landes" => "des",
     "Loir-et-Cher" => "du",
     "Loire" => "de la",
     "Loire-Atlantique" => "de",
     "Loiret" => "du",
     "Lot" => "du",
     "Lot-et-Garonne" => "du",
     "Lozère" => "de la",
     "Maine-et-Loire" => "du",
     "Manche" => "de la",
     "Marne" => "de la",
     "Martinique" => "de",
     "Mayenne" => "de la",
     "Mayotte" => "de",
     "Meurthe-et-Moselle" => "de",
     "Meuse" => "de la",
     "Morbihan" => "du",
     "Moselle" => "de la",
     "Nièvre" => "de la",
     "Nord" => "du",
     "Nouvelle-Calédonie" => "de la",
     "Oise" => "de l'",
     "Orne" => "de l'",
     "Paris" => "de",
     "Pas-de-Calais" => "du",
     "Polynésie Française" => "de la",
     "Puy-de-Dôme" => "du",
     "Pyrénées-Atlantiques" => "des",
     "Pyrénées-Orientales" => "des",
     "Réunion" => "de la",
     "Rhône" => "du",
     "Saint-Pierre-et-Miquelon" => "de",
     "Saint-Barthélemy et Saint-Martin" => "de",
     "Saône-et-Loire" => "de",
     "Sarthe" => "de la",
     "Savoie" => "de",
     "Seine-et-Marne" => "de",
     "Seine-Maritime" => "de",
     "Seine-Saint-Denis" => "de",
     "Somme" => "de la",
     "Tarn" => "du",
     "Tarn-et-Garonne" => "du",
     "Territoire-de-Belfort" => "du",
     "Territoire de Belfort" => "du",
     "Val-d'Oise" => "du",
     "Val-de-Marne" => "du",
     "Var" => "du",
     "Vaucluse" => "du",
     "Vendée" => "de",
     "Vienne" => "de la",
     "Vosges" => "des",
     "Wallis-et-Futuna" => "de",
     "Yonne" => "de l'",
     "Yvelines" => "des"
    );
  public function getPrefixeCirconscription() {
    $prefixe = self::$dptmt_pref[trim($this->nom_circo)];
    if (! preg_match("/'/", $prefixe)) $prefixe = $prefixe.' ';
    return $prefixe;
  }

  public static $dptmt_nom = array(
      "1" => "Ain",
      "2" => "Aisne",
      "3" => "Allier",
      "4" => "Alpes-de-Haute-Provence",
      "5" => "Hautes-Alpes",
      "6" => "Alpes-Maritimes",
      "7" => "Ardèche",
      "8" => "Ardennes",
      "9" => "Ariège",
      "10" => "Aube",
      "11" => "Aude",
      "12" => "Aveyron",
      "13" => "Bouches-du-Rhône",
      "14" => "Calvados",
      "15" => "Cantal",
      "16" => "Charente",
      "17" => "Charente-Maritime",
      "18" => "Cher",
      "19" => "Corrèze",
      "2a" => "Corse-du-Sud",
      "2b" => "Haute-Corse",
      "21" => "Côte-d'Or",
      "22" => "Côtes-d'Armor",
      "23" => "Creuse",
      "24" => "Dordogne",
      "25" => "Doubs",
      "26" => "Drôme",
      "27" => "Eure",
      "28" => "Eure-et-Loir",
      "29" => "Finistère",
      "30" => "Gard",
      "31" => "Haute-Garonne",
      "32" => "Gers",
      "33" => "Gironde",
      "34" => "Hérault",
      "35" => "Ille-et-Vilaine",
      "36" => "Indre",
      "37" => "Indre-et-Loire",
      "38" => "Isère",
      "39" => "Jura",
      "40" => "Landes",
      "41" => "Loir-et-Cher",
      "42" => "Loire",
      "43" => "Haute-Loire",
      "44" => "Loire-Atlantique",
      "45" => "Loiret",
      "46" => "Lot",
      "47" => "Lot-et-Garonne",
      "48" => "Lozère",
      "49" => "Maine-et-Loire",
      "50" => "Manche",
      "51" => "Marne",
      "52" => "Haute-Marne",
      "53" => "Mayenne",
      "54" => "Meurthe-et-Moselle",
      "55" => "Meuse",
      "56" => "Morbihan",
      "57" => "Moselle",
      "58" => "Nièvre",
      "59" => "Nord",
      "60" => "Oise",
      "61" => "Orne",
      "62" => "Pas-de-Calais",
      "63" => "Puy-de-Dôme",
      "64" => "Pyrénées-Atlantiques",
      "65" => "Hautes-Pyrénées",
      "66" => "Pyrénées-Orientales",
      "67" => "Bas-Rhin",
      "68" => "Haut-Rhin",
      "69" => "Rhône",
      "70" => "Haute-Saône",
      "71" => "Saône-et-Loire",
      "72" => "Sarthe",
      "73" => "Savoie",
      "74" => "Haute-Savoie",
      "75" => "Paris",
      "76" => "Seine-Maritime",
      "77" => "Seine-et-Marne",
      "78" => "Yvelines",
      "79" => "Deux-Sèvres",
      "80" => "Somme",
      "81" => "Tarn",
      "82" => "Tarn-et-Garonne",
      "83" => "Var",
      "84" => "Vaucluse",
      "85" => "Vendée",
      "86" => "Vienne",
      "87" => "Haute-Vienne",
      "88" => "Vosges",
      "89" => "Yonne",
      "90" => "Territoire-de-Belfort",
      "91" => "Essonne",
      "92" => "Hauts-de-Seine",
      "93" => "Seine-Saint-Denis",
      "94" => "Val-de-Marne",
      "95" => "Val-d'Oise",
      "971" => "Guadeloupe",
      "972" => "Martinique",
      "973" => "Guyane",
      "974" => "Réunion",
      "975" => "Saint-Pierre-et-Miquelon",
      "976" => "Mayotte",
      "977" => "Saint-Barthélemy et Saint-Martin",
      "986" => "Wallis-et-Futuna",
      "987" => "Polynésie Française",
      "988" => "Nouvelle-Calédonie",
     # "99"  => "Français établis hors de France",
      "999"  => "Français établis hors de France");
  public static function getNomDepartement($numero) {
    $numero = strtolower($numero);
    if ( isset(self::$dptmt_nom["$numero"]) ) return $nom = self::$dptmt_nom["$numero"];
    else return false;
  }

    static $nom_dptmt = array(
      "ain" => "01",
      "aisne" => "02",
      "allier" => "03",
      "alpes-de-haute-provence" => "04",
      "hautes-alpes" => "05",
      "alpes-maritimes" => "06",
      "ardèche" => "07",
      "ardennes" => "08",
      "ariège" => "09",
      "aube" => "10",
      "aude" => "11",
      "aveyron" => "12",
      "bouches-du-rhône" => "13",
      "calvados" => "14",
      "cantal" => "15",
      "charente" => "16",
      "charente-maritime" => "17",
      "cher" => "18",
      "corrèze" => "19",
      "corse-du-sud" => "2A",
      "haute-corse" => "2B",
      "côte-d'or" => "21",
      "côtes-d'armor" => "22",
      "creuse" => "23",
      "dordogne" => "24",
      "doubs" => "25",
      "drôme" => "26",
      "eure" => "27",
      "eure-et-loir" => "28",
      "finistère" => "29",
      "français établis hors de france" => "999",
      "français-établis-hors-de-france" => "999",
      "gard" => "30",
      "haute-garonne" => "31",
      "gers" => "32",
      "gironde" => "33",
      "hérault" => "34",
      "ille-et-vilaine" => "35",
      "indre" => "36",
      "indre-et-loire" => "37",
      "isère" => "38",
      "jura" => "39",
      "landes" => "40",
      "loir-et-cher" => "41",
      "loire" => "42",
      "haute-loire" => "43",
      "loire-atlantique" => "44",
      "loiret" => "45",
      "lot" => "46",
      "lot-et-garonne" => "47",
      "lozère" => "48",
      "maine-et-loire" => "49",
      "manche" => "50",
      "marne" => "51",
      "haute-marne" => "52",
      "mayenne" => "53",
      "meurthe-et-moselle" => "54",
      "meuse" => "55",
      "morbihan" => "56",
      "moselle" => "57",
      "nièvre" => "58",
      "nord" => "59",
      "oise" => "60",
      "orne" => "61",
      "pas-de-calais" => "62",
      "puy-de-dôme" => "63",
      "pyrénées-atlantiques" => "64",
      "hautes-pyrénées" => "65",
      "pyrénées-orientales" => "66",
      "bas-rhin" => "67",
      "haut-rhin" => "68",
      "rhône" => "69",
      "haute-saône" => "70",
      "saône-et-loire" => "71",
      "sarthe" => "72",
      "savoie" => "73",
      "haute-savoie" => "74",
      "paris" => "75",
      "seine-maritime" => "76",
      "seine-et-marne" => "77",
      "yvelines" => "78",
      "deux-sèvres" => "79",
      "somme" => "80",
      "tarn" => "81",
      "tarn-et-garonne" => "82",
      "var" => "83",
      "vaucluse" => "84",
      "vendée" => "85",
      "vienne" => "86",
      "haute-vienne" => "87",
      "vosges" => "88",
      "yonne" => "89",
      "territoire-de-belfort" => "90",
      "essonne" => "91",
      "hauts-de-seine" => "92",
      "seine-saint-denis" => "93",
      "val-de-marne" => "94",
      "val-d'oise" => "95",
      "guadeloupe" => "971",
      "martinique" => "972",
      "guyane" => "973",
      "réunion" => "974",
      "saint-pierre-et-miquelon" => "975",
      "mayotte" => "976",
      "saint-barthélemy et saint-martin" => "977",
      "saint-barthélemy-et-saint-martin" => "977",
      "wallis-et-futuna" => "986",
      "polynésie française" => "987",
      "polynésie-française" => "987",
      "nouvelle-calédonie" => "988");
  public static function getNumeroDepartement($nom) {
    $nom = strtolower($nom);
    if (isset(self::$nom_dptmt[$nom])) return $numero = self::$nom_dptmt[$nom];
    else return 0;
  }

  public function getTop() {
    return unserialize($this->_get('top'));
  }

  public function isEnMandat() {
    return (!$this->fin_mandat || $this->fin_mandat < $this->debut_mandat);
  }

  public function getCauseFinMandat() {
    if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $this->fin_mandat, $m))
      $fin =  $m[3].'/'.$m[2].'/'.$m[1];
    else return null;
    foreach (unserialize($this->getAnciensMandats()) as $m)
      if (preg_match("/^(.*) \/ (.*) \/ (.*)$/", $m, $match))
        if ($match[2] === $fin) return $match[3];
    return null;
  }

  public function getNbMois() {
    $vacs = Doctrine::getTable('VariableGlobale')->findOneByChamp('vacances');
    $vacances = array();
    if ($vacs) {
      $vacances = unserialize($vacs->value);
      unset($vacs);
    }
    $debut = strtotime(myTools::getDebutLegislature());
    $fin = $debut + (5*365-31)*24*3600;  # fin législature définie à 4 ans et 11 mois)
    $semaines = 0;
    foreach (unserialize($this->getAnciensMandats()) as $m) {
      if (preg_match("/^(.*) \/ (.*) \/ (.*)$/", $m, $match)) {
        $match[1] = preg_replace("#^(\d+)/(\d+)/(\d+)$#", "\\3-\\2-\\1", $match[1]);
        $sta = strtotime($match[1]);
        if ($match[2] != "") {
          $match[2] = preg_replace("#^(\d+)/(\d+)/(\d+)$#", "\\3-\\2-\\1", $match[2]);
          $end = strtotime($match[2]);
        } else $end = $fin;
        if ($sta < $debut || $sta > $fin)
          continue;
        if ($end > $fin)
          $end = $fin;
        $semaines += ($end - $sta)/(3600*24*7);
        foreach ($vacances as $vacance) {
          $week = strtotime($vacance["annee"]."0104 +".($vacance["semaine"] - 1)." weeks");
          if ($week >= $sta && $week <= $end)
            $semaines--;
        }
      }
    }
    return round($semaines*12/53);
  }

  public function getMandatsLegislature() {
    $mandats = array();
    $debut = strtotime(myTools::getDebutLegislature());
    foreach (unserialize($this->getAnciensMandats()) as $m) {
      if (preg_match("/^(.*) \/ (.*) \/ (.*)$/", $m, $match)) {
        $match[1] = preg_replace("#^(\d+)/(\d+)/(\d+)$#", "\\3-\\2-\\1", $match[1]);
        if ($match[2] != "")
          $match[2] = preg_replace("#^(\d+)/(\d+)/(\d+)$#", "\\3-\\2-\\1", $match[2]);
        if (strtotime($match[1]) >= $debut)
          $mandats[] = $match[1].";".$match[2];
      }
    }
    sort($mandats);
    return $mandats;
  }

  private function setInternalPhoto($photo) {
    $this->photo = $photo;
    return true;
  }
  private function getInternalPhoto() {
    if (!isset($this->photo) || !$this->photo) {
      $this->photo = null;
      $pphoto = doctrine::getTable('ParlementairePhoto')->find($this->id);
      if ($pphoto)
        $this->photo = $pphoto->photo;
    }
    if (!$this->photo)
      return null;
    return $this->photo;
  }

  public function hasPhoto()
  {
    $photo = $this->getInternalPhoto('photo');
    return (strlen($photo) > 0) ;
  }
  public function getPhoto() {
    return $this->getInternalPhoto();
  }
  public function setPhoto($s) {
    if (preg_match('/http/', $s)) {
      $len = strlen($this->getInternalPhoto());
      if ($len < 5200 || date('d') % 3 == 0) {
        $s = @file_get_contents($s);
      }else
        return true;
      if (!$s)
        return false;
    }
    $this->setInternalPhoto($s);
  }
  public function save(Doctrine_Connection $c = null) {
    parent::save($c);
    if (isset($this->photo) && $this->photo) {
      $pphoto = doctrine::getTable('ParlementairePhoto')->findOrCreate($this->id, $this->slug);
      $pphoto->setPhoto($this->photo);
      $pphoto->save($c);
    }
    return true;
  }
}
