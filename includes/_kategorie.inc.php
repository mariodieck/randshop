<?php
//  Copyright (c) 2004 randshop
//  http://www.randshop.com
//
//  Unter Lizenz von randshop
//	
//	Letzte Bearbeitung: 01.08.2004

//	echo "<pre>";
//	var_dump($_SESSION);
//	echo "</pre>";

include_once(DATEIPFAD . "includes/functions.language.inc.php");
include_once(DATEIPFAD . "includes/functions.shopeinstellung.inc.php");
	
function GetKategorie() {
	
	$SQLString = "SELECT kategorie_sort FROM " . TABLE_ALLGEMEIN;
	$KategorieSortObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));
	$KategorieSort = $KategorieSortObject->kategorie_sort;

	if ($KategorieSort) {
		$SQLString = "SELECT * FROM ".TABLE_KATEGORIE." WHERE parentid = '0' AND aktiv='1' ORDER BY name";
	} else {
		$SQLString = "SELECT * FROM ".TABLE_KATEGORIE." WHERE parentid = '0' AND aktiv='1' ORDER BY sort";
	}
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	while ($KategorieObject = mysql_fetch_object($MySQLQueryReference)) {
	
		echo ":: <a href=\"".URLPFAD."themes/kategorie/index.php?katid=" . $KategorieObject->id . "\"><b>" . $KategorieObject->name . "</b></a><br>";

		unset($KategoriePathArray);
		$KategoriePathArray = GetKategoriePathArray($_SESSION["katid"], $KategoriePathArray);

		$KategorieFound = false;

		foreach ($KategoriePathArray as $KategoriePathElement) {

			if ($KategoriePathElement["id"] == $KategorieObject->id) {
				$KategorieFound = true;
			}
			
		}

		if ($KategorieFound || ($KategorieObject->id == $_SESSION["katid"])) {

			GetKategorieNavigationNode($KategorieObject->id, 0);
			
		}
		
	}
	
	
}

function GetKategorieNavigationNode($ParentID, $Level) {
	
	$SQLString = "SELECT kategorie_sort FROM " . TABLE_ALLGEMEIN;
	$KategorieSortObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));
	$KategorieSort = $KategorieSortObject->kategorie_sort;

	if ($KategorieSort) {
		$SQLString = "SELECT * FROM " . TABLE_KATEGORIE . " WHERE parentid = '" . $ParentID . "' AND aktiv='1' ORDER BY name";
	} else {
		$SQLString = "SELECT * FROM " . TABLE_KATEGORIE . " WHERE parentid = '" . $ParentID . "' AND aktiv='1' ORDER BY sort";
	}
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	while ($KategorieObject = mysql_fetch_object($MySQLQueryReference)) {
	
		echo str_repeat("&nbsp;", (($Level + 1) * 2)) . ":: <a href=\"" . URLPFAD . "themes/kategorie/index.php?katid=" . $KategorieObject->id . "\">" . $KategorieObject->name . "</a><br>";

		unset($KategoriePathArray);
		$KategoriePathArray = GetKategoriePathArray($_SESSION["katid"], $KategoriePathArray);

		$KategorieFound = false;

		foreach ($KategoriePathArray as $KategoriePathElement) {
		
			if ($KategoriePathElement["id"] == $KategorieObject->id) {
				$KategorieFound = true;
			}
			
		}

		if ($KategorieFound || ($KategorieObject->id == $_SESSION["katid"])) {

			GetKategorieNavigationNode($KategorieObject->id, $Level + 1);
			
		}

		
	}

}

function GetKategoriePathID($KategorieID, $KategoriePathArray) {

	// die Kategorie einlesen
	$SQLString = "SELECT * FROM " . TABLE_KATEGORIE . " WHERE id = '" . $KategorieID . "'";
	$KategorieObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));
	
	$KategoriePathArray["parentid"][] = (int)$KategorieObject->parentid;
	$KategoriePathArray["kategorieid"][] = (int)$KategorieObject->id;
	$KategoriePathArray["name"][] = $KategorieObject->name;
	$KategoriePathArray["aktiv"][] = $KategorieObject->aktiv;

	if ($KategorieObject->parentid != 0) {
		$KategoriePathArray = GetKategoriePathID($KategorieObject->parentid, $KategoriePathArray);
	}

	return $KategoriePathArray;
	

}

function GetSelectedKategorieList($ArtikelID) {

	if ($ArtikelID) {
	
		// SQL-String zum einlesen der ersten Ebene der Kategorien
		$SQLString = "SELECT ";
		$SQLString .= TABLE_KATEGORIERELATION . ".artikelid, ";
		$SQLString .= TABLE_KATEGORIERELATION . ".kategorieid ";
		$SQLString .= " FROM " . TABLE_KATEGORIERELATION;
		$SQLString .= " WHERE " . TABLE_KATEGORIERELATION . ".artikelid = '" . $ArtikelID . "'";
		
		// Daten abfragen und in ein Array legen
		$MySQLQueryReference = errorlogged_mysql_query($SQLString);
		
		while ($SelectedKategorieRow = mysql_fetch_array($MySQLQueryReference, MYSQL_ASSOC)) {
			
			$SelectedKategorieArray[$SelectedKategorieRow["kategorieid"]] = $SelectedKategorieRow["kategorieid"];
	
		}
	
	} elseif($_SESSION["katid"]) {

		foreach ($_SESSION["katid"] as $KatID) {
			$SelectedKategorieArray[$KatID] = $KatID;			
		}
		
	}
		
//		echo "<pre>";
//		var_dump($SelectedKategorieArray);
//		echo "</pre>";

	return $SelectedKategorieArray;
	
}

function Kategorieunlink_wc($dir, $pattern){
   
   if ($dh = opendir($dir)) { 
       
       //List and put into an array all files
       while (false !== ($file = readdir($dh))){
           if ($file != "." && $file != "..") {
               $files[] = $file;
           }
       }
       closedir($dh);
       
       
       //Split file name and extenssion
       if(strpos($pattern,".")) {
           $baseexp=substr($pattern,0,strpos($pattern,"."));
           $typeexp=substr($pattern,strpos($pattern,".")+1,strlen($pattern));
       }else{ 
           $baseexp=$pattern;
           $typeexp="";
       } 
       
       //Escape all regexp Characters 
       $baseexp=preg_quote($baseexp); 
       $typeexp=preg_quote($typeexp); 
       
       // Allow ? and *
       $baseexp=str_replace(array("\*","\?"), array(".*","."), $baseexp);
       $typeexp=str_replace(array("\*","\?"), array(".*","."), $typeexp);
       
       //Search for pattern match
       $i=0;
       foreach($files as $file) {
           $filename=basename($file);
           if(strpos($filename,".")) {
               $base=substr($filename,0,strpos($filename,"."));
               $type=substr($filename,strpos($filename,".")+1,strlen($filename));
           }else{
               $base=$filename;
               $type="";
           }
       
           if(preg_match("/^".$baseexp."$/i",$base) && preg_match("/^".$typeexp."$/i",$type))  {
               $matches[$i]=$file;
               $i++;
           }
       }
       
       if ($matches) {
	       while(list($idx,$val) = each($matches)){
	           if (substr($dir,-1) == "/"){
	               unlink($dir.$val);
	           }else{
	               unlink($dir."/".$val);
	           }
	       }
		}
       
   }
}

function KategorieSaveImage($FilesArray, $ImageName, $KategorieID, $LanguageID) {

	// Shopeinstellungen einlesen
	$ShopeinstellungenObject = GetShopeinstellungDetail();

	if ($FilesArray["size"] > 0) {

		// altes Bild löschen
		Kategorieunlink_wc(DATEIPFAD . "images/dbimages/", $ImageName . ".*");
	
		// temporäre Datei kopieren

		if($ShopeinstellungenObject->bildupload_aktiv == '1' && function_exists(imagecreatetruecolor)){
			
			$NewSmallImageName = "kategorie_" . sprintf("%07d", $KategorieID) . "_" . $LanguageID;
			$TempNameArray = explode(".", $FilesArray["name"]);
			$NewSmallImageName .= "." . $TempNameArray[count($TempNameArray) - 1];
			
			
			if($FilesArray["type"] == "image/jpeg" || $FilesArray["type"] == "image/pjpeg"){
				$ImageType = 'jpg';
			}elseif($FilesArray["type"] == "image/gif"){
				$ImageType = 'gif';
			}elseif($FilesArray["type"] == "image/png"){
				$ImageType = 'png';	
			}
			
			$ImageName = ReziseKatImage($FilesArray["tmp_name"],$NewSmallImageName,$ImageType);
			
		}else{
			$ImageNameArray = explode(".", $FilesArray["name"]);
			$ImageName = $ImageName . "." . $ImageNameArray[count($ImageNameArray) - 1];
			move_uploaded_file($FilesArray["tmp_name"], DATEIPFAD . "images/dbimages/" . $ImageName);
			chmod(DATEIPFAD . "images/dbimages/" . $ImageName, 0644);			
		}
		
		
		// SQL-String zum speichern der Kategorie vorbereiten
		$SQLString = "UPDATE " . TABLE_KATEGORIE_LANGU . " SET ";
		$SQLString .= TABLE_KATEGORIE_LANGU . ".smallimage  = '" . $ImageName . "' ";
		$SQLString .= "WHERE " . TABLE_KATEGORIE_LANGU . ".kategorie_id = '" . $KategorieID . "' AND ";
		$SQLString .= TABLE_KATEGORIE_LANGU . ".language_id = '" . $LanguageID . "' ";
		
		$QueryReference = errorlogged_mysql_query($SQLString);

		return $ImageName;
		
	}

}

function SaveKategorie($KategorieID, $ParentID, $Name, $Sort, $KategorieSort, $LanguageIndependent, $LanguageID = 0, $MetaDescr, $MetaTitle, $URLNameVorgabe, $Titel, $Beschreibung) {
	
    // SEO Einstellungen einlesen
    $Einstellungen = GetEinstellungen('', 'seo');
    
    // Sprache ermitteln
	if (!$LanguageID) {
		$LanguageID = GetDefaultLanguageID();
	}

	// Wenn die Kategorie schon vorhanden ist
	if ($KategorieID) {

		// SQL-String zum speichern des Artikels vorbereiten
		$SQLString = "UPDATE " . TABLE_KATEGORIE . " SET ";
		$SQLString .= TABLE_KATEGORIE . ".parentid = '" . $ParentID . "', ";
		if (!$KategorieSort) {
			$SQLString .= TABLE_KATEGORIE . ".sort = '" . $Sort . "', ";
		}
		$SQLString .= TABLE_KATEGORIE . ".language_independent = '" . $LanguageIndependent . "' ";
		$SQLString .= "WHERE " . TABLE_KATEGORIE . ".id = '" . $KategorieID . "'";
		
		$QueryReference = errorlogged_mysql_query($SQLString);
		
	// wenn die Kategorie neu angelegt werden soll
	} else {
		
		// SQL-String zum speichern des Artikels vorbereiten
		$SQLString = "INSERT INTO " . TABLE_KATEGORIE . " SET ";
		$SQLString .= TABLE_KATEGORIE . ".parentid = '" . $ParentID . "', ";
		$SQLString .= TABLE_KATEGORIE . ".sort = '" . $Sort . "', ";
		$SQLString .= TABLE_KATEGORIE . ".language_independent = '" . $LanguageIndependent . "' ";
		
		$QueryReference = errorlogged_mysql_query($SQLString);
		
		$KategorieID = mysql_insert_id();

	}
	
	// Sprachabhängige Daten
	$SQLString = "SELECT ";
	$SQLString .= TABLE_KATEGORIE_LANGU . ".url_name ";
	$SQLString .= "FROM ";
	$SQLString .= TABLE_KATEGORIE_LANGU . " ";
	$SQLString .= "WHERE ";
	$SQLString .= TABLE_KATEGORIE_LANGU . ".kategorie_id = '" . $KategorieID . "' AND ";
	$SQLString .= TABLE_KATEGORIE_LANGU . ".language_id = '" . $LanguageID . "' ";
	
	$KategorieLanguageObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));

    if ($Einstellungen->seo->sprechende_urls_aktiv || $URLNameVorgabe) {
        
        if ($URLNameVorgabe) {
            $URLName = DecodeCMSKategorieSEOName($URLNameVorgabe); 
        } else {
            $URLName = DecodeCMSKategorieSEOName($Name);
        }
    
    } else {
        
        $URLNameVorgabe = '';
        $URLName = '';
    
    }
		
	if ($KategorieLanguageObject) {
		
		$SQLString = "UPDATE " . TABLE_KATEGORIE_LANGU . " SET ";
		$SQLString .= TABLE_KATEGORIE_LANGU . ".name = '" . htmlspecialchars($Name, ENT_QUOTES) . "', ";
        $SQLString .= TABLE_KATEGORIE_LANGU . ".url_name_vorgabe = '" . $URLNameVorgabe . "', ";
        $SQLString .= TABLE_KATEGORIE_LANGU . ".url_name = '" . $URLName . "', ";
        $SQLString .= TABLE_KATEGORIE_LANGU . ".titel = '" . $Titel . "', ";
        $SQLString .= TABLE_KATEGORIE_LANGU . ".beschreibung = '" . $Beschreibung . "', ";
        $SQLString .= TABLE_KATEGORIE_LANGU . ".meta_title = '" . $MetaTitle . "', ";
		$SQLString .= TABLE_KATEGORIE_LANGU . ".meta_descr = '" . $MetaDescr . "' ";
		$SQLString .= " WHERE ";
		$SQLString .= TABLE_KATEGORIE_LANGU . ".kategorie_id = '" . $KategorieID . "' AND ";
		$SQLString .= TABLE_KATEGORIE_LANGU . ".language_id = '" . $LanguageID . "' ";

		$MySQLQueryReference = errorlogged_mysql_query($SQLString);
		
		if ($KategorieLanguageObject->url_name != $URLName) {
		    $ChangeURLName = true;
		}

	} else {
		
		$SQLString = "INSERT INTO  " . TABLE_KATEGORIE_LANGU . " SET ";
		$SQLString .= TABLE_KATEGORIE_LANGU . ".name = '" . htmlspecialchars($Name, ENT_QUOTES) . "', ";
        $SQLString .= TABLE_KATEGORIE_LANGU . ".url_name_vorgabe = '" . $URLNameVorgabe . "', ";
        $SQLString .= TABLE_KATEGORIE_LANGU . ".url_name = '" . $URLName . "', ";
        $SQLString .= TABLE_KATEGORIE_LANGU . ".titel = '" . $Titel . "', ";
        $SQLString .= TABLE_KATEGORIE_LANGU . ".beschreibung = '" . $Beschreibung . "', ";
		$SQLString .= TABLE_KATEGORIE_LANGU . ".meta_title = '" . $MetaTitle . "', ";
		$SQLString .= TABLE_KATEGORIE_LANGU . ".meta_descr = '" . $MetaDescr . "', ";
		$SQLString .= TABLE_KATEGORIE_LANGU . ".kategorie_id = '" . $KategorieID . "', ";
		$SQLString .= TABLE_KATEGORIE_LANGU . ".language_id = '" . $LanguageID . "' ";

		$MySQLQueryReference = errorlogged_mysql_query($SQLString);

        $ChangeURLName = true;
	
	}

	// SEO URLs neu definiern 
    if ($Einstellungen->seo->sprechende_urls_aktiv) {
    	SetKategorieSEOURLs($KategorieID, $LanguageID);
        SetArtikelKategorieSEOURLs($KategorieID, $LanguageID);
    }
    
	return $KategorieID;

}

function KategorieCheckImage($FilesArray, $MaxFileSize, $PossibleFileTypeArray, $FieldName) {

	global $a_artikel_image_error_zugross;
	global $a_artikel_image_error_falschesformat;
	
	if ($FilesArray["size"] > 0) {

		// die Gr��e �berpr�fen
		if ($FilesArray["size"] > $MaxFileSize) {
			
			$ImageErrorMessage = sprintf($a_artikel_image_error_zugross, $FieldName, number_format($MaxFileSize, 0, "", "."));
			
		}
		
		// den Filetyp �berpr�fen
		$FileTypeError = true;
		foreach ($PossibleFileTypeArray as $PossibleFileType) {
			
			if ($FilesArray["type"] == $PossibleFileType) {
				$FileTypeError = false;			
			}
			
		}
		
		if ($FileTypeError && !$ImageErrorMessage) {
			$ImageErrorMessage = sprintf($a_artikel_image_error_falschesformat, $FieldName);
		}
			
		return $ImageErrorMessage;
		
	}
	
}


function DeleteKategorie($KategorieID) {
	
	$SQLString = "DELETE FROM " . TABLE_KATEGORIE;
	$SQLString .= " WHERE " . TABLE_KATEGORIE . ".id = '" . $KategorieID . "'";

	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	$SQLString = "DELETE FROM " . TABLE_KATEGORIE_LANGU;
	$SQLString .= " WHERE " . TABLE_KATEGORIE_LANGU . ".kategorie_id = '" . $KategorieID . "'";

	$MySQLQueryReference = errorlogged_mysql_query($SQLString);

	$SQLString = "DELETE FROM " . TABLE_KATEGORIERELATION;
	$SQLString .= " WHERE " . TABLE_KATEGORIERELATION . ".kategorieid = '" . $KategorieID . "'";

	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	Kategorieunlink_wc(DATEIPFAD . "images/dbimages/", "kategorie_" . sprintf("%07d", $KategorieID) . "_*.*");

	// SEOURL loeschen
	DeleteKategorieSEOURL($KategorieID);
	
	$KategorieIDArray = GetKategorieRekursivID($KategorieID, $KategorieIDArray);

	if ($KategorieIDArray) {

		$SQLString = "DELETE FROM " . TABLE_KATEGORIE;
		$SQLString .= " WHERE " . TABLE_KATEGORIE . ".id IN (" . implode(",", $KategorieIDArray) . ")";
	
		$MySQLQueryReference = errorlogged_mysql_query($SQLString);
		
		$SQLString = "DELETE FROM " . TABLE_KATEGORIE_LANGU;
		$SQLString .= " WHERE " . TABLE_KATEGORIE_LANGU . ".kategorie_id IN (" . implode(",", $KategorieIDArray) . ")";
	
		$MySQLQueryReference = errorlogged_mysql_query($SQLString);

		$SQLString = "DELETE FROM " . TABLE_KATEGORIERELATION;
		$SQLString .= " WHERE " . TABLE_KATEGORIERELATION . ".kategorieid IN (" . implode(",", $KategorieIDArray) . ")";
	
		$MySQLQueryReference = errorlogged_mysql_query($SQLString);

		foreach ($KategorieIDArray as $KategorieID) {
		
			Kategorieunlink_wc(DATEIPFAD . "images/dbimages/", "kategorie_" . sprintf("%07d", $KategorieID) . "_*.*");
			
            // SEOURL loeschen
            DeleteKategorieSEOURL($KategorieID);
		
		}

	}


}

function GetKategorieDetail($KategorieID, $LanguageID = 0) {
	
	// Sprache ermitteln
	if (!$LanguageID) {
		$LanguageID = GetDefaultLanguageID();
	}

	$StandardLanguageID = GetDefaultLanguageID();

	// SQL-String zum einlsen der Kategorien aufbauen
	$SQLString = "SELECT ";
	$SQLString .= TABLE_KATEGORIE . ".id, ";
	$SQLString .= TABLE_KATEGORIE . ".parentid, ";
	$SQLString .= TABLE_KATEGORIE_LANGU . ".name, ";
    $SQLString .= TABLE_KATEGORIE_LANGU . ".url_name, ";
    $SQLString .= TABLE_KATEGORIE_LANGU . ".url_name_vorgabe, ";
    $SQLString .= "table_kategorie_langu_standard.name AS standard_name, ";
	$SQLString .= "IF(" . TABLE_KATEGORIE . ".language_independent, table_kategorie_langu_standard.smallimage, " . TABLE_KATEGORIE_LANGU . ".smallimage) AS smallimage, ";
	$SQLString .= TABLE_KATEGORIE . ".aktiv, ";
	$SQLString .= TABLE_KATEGORIE . ".language_independent, ";
    $SQLString .= TABLE_KATEGORIE . ".facettensuche_aktiv, ";
    $SQLString .= TABLE_KATEGORIE . ".facettensuche_preisfilter_aktiv, ";
    $SQLString .= TABLE_KATEGORIE . ".facettensuche_unterkategoriefilter_aktiv, ";
    $SQLString .= TABLE_KATEGORIE . ".sort, ";
	$SQLString .= TABLE_KATEGORIE_LANGU . ".titel, ";
	$SQLString .= "table_kategorie_langu_standard.titel AS standard_titel, ";
	$SQLString .= TABLE_KATEGORIE_LANGU . ".beschreibung, ";
	$SQLString .= "table_kategorie_langu_standard.beschreibung AS standard_beschreibung, ";
	$SQLString .= TABLE_KATEGORIE_LANGU . ".meta_title, ";
	$SQLString .= "table_kategorie_langu_standard.meta_title AS standard_meta_title, ";
	$SQLString .= TABLE_KATEGORIE_LANGU . ".meta_descr, ";
	$SQLString .= "table_kategorie_langu_standard.meta_descr AS standard_meta_descr ";
	$SQLString .= " FROM " . TABLE_KATEGORIE . " ";
	$SQLString .= "LEFT JOIN " . TABLE_KATEGORIE_LANGU . " ON ((" .TABLE_KATEGORIE . ".id = " . TABLE_KATEGORIE_LANGU . ".kategorie_id) AND (" . TABLE_KATEGORIE_LANGU . ".language_id = '" . $LanguageID . "'))";
	$SQLString .= "LEFT JOIN " . TABLE_KATEGORIE_LANGU . " table_kategorie_langu_standard ON ((" . TABLE_KATEGORIE . ".id = table_kategorie_langu_standard.kategorie_id) AND (table_kategorie_langu_standard.language_id = " . $StandardLanguageID . ")) ";
	$SQLString .= " WHERE " . TABLE_KATEGORIE . ".id = '" . $KategorieID . "'";
	
	//echo '$SQLString: ' . $SQLString . '<br>';
	
	// Daten abfragen 
	$KategorieObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));

	return $KategorieObject;

}

function SaveKategorieFacettensuche($KategorieID, $facettensucheAktiv, $preisfilterAktiv, $unterkategorieFilterAktiv)
{
    $SQLString = 'UPDATE ' . TABLE_KATEGORIE . ' SET '; 
    $SQLString .= TABLE_KATEGORIE . '.facettensuche_aktiv = \'' . ($facettensucheAktiv?'1':'0') . '\', ';
    $SQLString .= TABLE_KATEGORIE . '.facettensuche_preisfilter_aktiv = \'' . ($preisfilterAktiv?'1':'0') . '\', ';
    $SQLString .= TABLE_KATEGORIE . '.facettensuche_unterkategoriefilter_aktiv = \'' . ($unterkategorieFilterAktiv?'1':'0') . '\' ';    
    $SQLString .= ' WHERE ' . TABLE_KATEGORIE . '.id = \'' . $KategorieID . '\'';

    errorlogged_mysql_query($SQLString);
}

function SetKategorieAktiv($KategorieID, $Aktiv = "") {
	
	// wenn $Aktiv nicht �bergeben wurde, die aktuelle Einstellung umdrehen
	if ($Aktiv == "") {
	
		$SQLString = "SELECT aktiv FROM " . TABLE_KATEGORIE . " WHERE id = '" . $KategorieID . "'";
		$KategorieObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));
		
		if ($KategorieObject->aktiv == 1) {
		
			$SQLString = "UPDATE " . TABLE_KATEGORIE . " SET aktiv = 0 WHERE id = '" . $KategorieID . "'";
			$KategorieQueryReference = errorlogged_mysql_query($SQLString);

		
		} else {

			$SQLString = "UPDATE " . TABLE_KATEGORIE . " SET aktiv = 1 WHERE id = '" . $KategorieID . "'";
			$KategorieQueryReference = errorlogged_mysql_query($SQLString);

		}
		
		
	// wenn $Aktiv �bergeben wurde, den Artikel darauf setzen
	} else {
	
		$SQLString = "UPDATE " . TABLE_KATEGORIE . " SET aktiv = '" . $Aktiv . "'";
		$KategorieQueryReference = errorlogged_mysql_query($SQLString);
		
	}
	
}

function GetKategorieRekursivID($ParentID, $KategorieIDArray) {
	
	$SQLString = "SELECT " . TABLE_KATEGORIE . ".id FROM " . TABLE_KATEGORIE;
	$SQLString .= " WHERE " . TABLE_KATEGORIE . ".parentid = '" . $ParentID . "'";
	
	// Daten abfragen und in ein Array legen
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	while ($KategorieRow = mysql_fetch_array($MySQLQueryReference, MYSQL_ASSOC)) {
		
		$KategorieIDArray[] = $KategorieRow["id"];
		
		$KategorieIDArray = GetKategorieRekursivID($KategorieRow["id"], $KategorieIDArray);

	}
	
//	echo "<pre>";
//	var_dump($KategorieIDArray);
//	echo "</pre>";

	return $KategorieIDArray;
	
}


function GetKategorieList($ParentID = 0, $Aktiv = 0, $KategorieSort, $LanguageID = 0) {

	global $a_online, $a_deaktiv, $c_landnichtuebersetzt;
	
	// Sprache ermitteln
	if (!$LanguageID) {
		$LanguageID = GetDefaultLanguageID();
	}

	$StandardLanguageID = GetDefaultLanguageID();

	// SQL-String zum einlesen der ersten Ebene der Kategorien
	$SQLString = "SELECT ";
	$SQLString .= TABLE_KATEGORIE . ".id, ";
	$SQLString .= TABLE_KATEGORIE . ".parentid, ";
	$SQLString .= TABLE_KATEGORIE . ".sort, ";
	$SQLString .= TABLE_KATEGORIE . ".aktiv, ";
	$SQLString .= "IFNULL(" . TABLE_KATEGORIE_LANGU . ".name, table_kategorie_langu_standard.name) AS name, ";
	$SQLString .= "!ISNULL(" . TABLE_KATEGORIE_LANGU . ".name) AS translate_name, ";
	$SQLString .= TABLE_KATEGORIE_LANGU . ".smallImage ";
	$SQLString .= " FROM " . TABLE_KATEGORIE . " ";
	$SQLString .= "LEFT JOIN " . TABLE_KATEGORIE_LANGU . " ON ((" .TABLE_KATEGORIE . ".id = " . TABLE_KATEGORIE_LANGU . ".kategorie_id) AND (" . TABLE_KATEGORIE_LANGU . ".language_id = '" . $LanguageID . "'))";
	$SQLString .= "LEFT JOIN " . TABLE_KATEGORIE_LANGU . " table_kategorie_langu_standard ON ((" . TABLE_KATEGORIE . ".id = table_kategorie_langu_standard.kategorie_id) AND (table_kategorie_langu_standard.language_id = " . $StandardLanguageID . ")) ";
	$SQLString .= " WHERE " . TABLE_KATEGORIE . ".parentid = '" . $ParentID . "'";
	
	if ($Aktiv) {
		$SQLString .= " AND " . TABLE_KATEGORIE . ".aktiv = 1 ";
	}
	
	if ($KategorieSort) {
		$SQLString .= " ORDER BY " . TABLE_KATEGORIE_LANGU . ".name";
	} else {
		$SQLString .= " ORDER BY " . TABLE_KATEGORIE . ".sort";
	}
	
	// Daten abfragen und in ein Array legen
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	while ($KategorieRow = mysql_fetch_array($MySQLQueryReference, MYSQL_ASSOC)) {
		
		$KategorieCounter = count($KategorieArray);
		$KategorieArray[$KategorieCounter] = $KategorieRow;
		$KategorieArray[$KategorieCounter]["level"] = 1;
		$KategorieArray[$KategorieCounter]["languagearray"] = GetKategorieLanguageDataArray($KategorieRow["id"]);
		
		$KategorieArray = GetKategorieNode($KategorieRow["id"], 1, $KategorieArray, $Aktiv, $KategorieSort, $LanguageID);
		
		// wenn es Untermenues gibt, dies sichern
		if (($KategorieCounter + 1) < count($KategorieArray)) {
			$KategorieArray[$KategorieCounter]["submenue"] = 1;
		} else {
			$KategorieArray[$KategorieCounter]["submenue"] = 0;
		}

		// Aktiv
		if ($KategorieRow["aktiv"] == 1) {
			$KategorieArray[$KategorieCounter]["tpl_aktivtext"] = $a_online;
		} else {
			$KategorieArray[$KategorieCounter]["tpl_aktivtext"] = $a_deaktiv;
		}
	
	}
	
//	echo "<pre>";
//	var_dump($KategorieArray);
//	echo "</pre>";
	
	return $KategorieArray;
	
}

function GetKategorieLanguageDataArray($KategorieID) {
	
	// Sprachen Abfragen
	$SQLString = "SELECT ";
	$SQLString .= TABLE_LANGUAGE . ".language_id, ";
	$SQLString .= "IF(ISNULL(" . TABLE_KATEGORIE_LANGU . ".name), " . TABLE_LANGUAGE . ".language_image_admintool_inactive, " . TABLE_LANGUAGE . ".language_image_admintool_active) AS language_image_admintool, ";
	$SQLString .= "IF(ISNULL(" . TABLE_KATEGORIE_LANGU . ".name), " . TABLE_LANGUAGE . ".language_image_admintool_inactive_width, " . TABLE_LANGUAGE . ".language_image_admintool_active_width) AS language_image_admintool_width, ";
	$SQLString .= "IF(ISNULL(" . TABLE_KATEGORIE_LANGU . ".name), " . TABLE_LANGUAGE . ".language_image_admintool_inactive_height, " . TABLE_LANGUAGE . ".language_image_admintool_active_height) AS language_image_admintool_height ";
	$SQLString .= "FROM ";
	$SQLString .= TABLE_LANGUAGE . " ";
	$SQLString .= "LEFT JOIN " . TABLE_KATEGORIE_LANGU . " ON ((" . TABLE_LANGUAGE . ".language_id = " . TABLE_KATEGORIE_LANGU . ".language_id) AND (" . TABLE_KATEGORIE_LANGU . ".kategorie_id = '" . $KategorieID . "')) ";
	
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	$LanguageCounter = 0;
	
	while ($KategorieLanguageRow = mysql_fetch_array($MySQLQueryReference)) {
		$KategorieLanguageDataArray[$LanguageCounter]["language_image_admintool_imagestring"] = "<img src=\"" . IMAGEPFAD . "dbimages/" . $KategorieLanguageRow["language_image_admintool"] . "\" border=\"0\">";
		$KategorieLanguageDataArray[$LanguageCounter]["language_id"] = $KategorieLanguageRow["language_id"];
		$LanguageCounter++;
	}
	
	return $KategorieLanguageDataArray;

}

function GetKategorieNode($KategorieID, $Level, $KategorieArray, $Aktiv = 0, $KategorieSort, $LanguageID = 0) {

	global $a_online, $a_deaktiv, $c_landnichtuebersetzt;

	// Sprache ermitteln
	if (!$LanguageID) {
		$LanguageID = GetDefaultLanguageID();
	}

	$StandardLanguageID = GetDefaultLanguageID();

	// SQL-String zum einlesen der ersten Ebene der Kategorien
	$SQLString = "SELECT ";
	$SQLString .= TABLE_KATEGORIE . ".id, ";
	$SQLString .= TABLE_KATEGORIE . ".parentid, ";
	$SQLString .= TABLE_KATEGORIE . ".sort, ";
	$SQLString .= TABLE_KATEGORIE . ".aktiv, ";
	$SQLString .= "IFNULL(" . TABLE_KATEGORIE_LANGU . ".name, table_kategorie_langu_standard.name) AS name, ";
	$SQLString .= "!ISNULL(" . TABLE_KATEGORIE_LANGU . ".name) AS translate_name, ";
	$SQLString .= TABLE_KATEGORIE_LANGU . ".smallImage ";
	$SQLString .= " FROM " . TABLE_KATEGORIE . " ";
	$SQLString .= "LEFT JOIN " . TABLE_KATEGORIE_LANGU . " ON ((" .TABLE_KATEGORIE . ".id = " . TABLE_KATEGORIE_LANGU . ".kategorie_id) AND (" . TABLE_KATEGORIE_LANGU . ".language_id = '" . $LanguageID . "'))";
	$SQLString .= "LEFT JOIN " . TABLE_KATEGORIE_LANGU . " table_kategorie_langu_standard ON ((" . TABLE_KATEGORIE . ".id = table_kategorie_langu_standard.kategorie_id) AND (table_kategorie_langu_standard.language_id = " . $StandardLanguageID . ")) ";
	$SQLString .= " WHERE " . TABLE_KATEGORIE . ".parentid = '" . $KategorieID . "'";

	if ($Aktiv) {
		$SQLString .= " AND " . TABLE_KATEGORIE . ".aktiv = 1 ";
	}

	if ($KategorieSort) {
		$SQLString .= " ORDER BY " . TABLE_KATEGORIE_LANGU . ".name";
	} else {
		$SQLString .= " ORDER BY " . TABLE_KATEGORIE . ".sort";
	}
	
	// Daten abfragen und in ein Array legen
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	while ($KategorieRow = mysql_fetch_array($MySQLQueryReference, MYSQL_ASSOC)) {
		
		$KategorieCounter = count($KategorieArray);
		$KategorieArray[$KategorieCounter] = $KategorieRow;
		$KategorieArray[$KategorieCounter]["level"] = $Level + 1;
		$KategorieArray[$KategorieCounter]["languagearray"] = GetKategorieLanguageDataArray($KategorieRow["id"]);

		$KategorieArray = GetKategorieNode($KategorieRow["id"], $Level + 1, $KategorieArray, $Aktiv, $KategorieSort, $LanguageID);

		// wenn es Untermenues gibt, dies sichern
		if (($KategorieCounter + 1) < count($KategorieArray)) {
			$KategorieArray[$KategorieCounter]["submenue"] = 1;
		} else {
			$KategorieArray[$KategorieCounter]["submenue"] = 0;
		}

		// Aktiv
		if ($KategorieRow["aktiv"] == 1) {
			$KategorieArray[$KategorieCounter]["tpl_aktivtext"] = $a_online;
		} else {
			$KategorieArray[$KategorieCounter]["tpl_aktivtext"] = $a_deaktiv;
		}

	}
	
	return $KategorieArray;
	
}














function GetKategoriePathArray($KategorieID, $KategoriePathArray) {

	// die Kategorie einlesen
	$SQLString = "SELECT * FROM " . TABLE_KATEGORIE . " WHERE id = '" . $KategorieID . "'";
	$KategorieObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));
	
	$KategoriePathArrayCounter = count($KategoriePathArray);	
	
	$KategoriePathArray[$KategoriePathArrayCounter]["id"] = $KategorieObject->id;
	$KategoriePathArray[$KategoriePathArrayCounter]["name"] = $KategorieObject->name;

	if ($KategorieObject->parentid != 0) {
		$KategoriePathArray = GetKategoriePathArray($KategorieObject->parentid, $KategoriePathArray);
	}
	
	return $KategoriePathArray;
	
}

function GetKategoriePathString($KategorieID) {

	$KategoriePathArray = array_reverse(GetKategoriePathArray($KategorieID, NULL));

	foreach ($KategoriePathArray as $KategoriePathElement) {
		
		$KategoriePathString .= "<a href=\"" . URLPFAD . "themes/kategorie/index.php?katid=" . $KategoriePathElement["id"] . "\">" . $KategoriePathElement["name"] . "</a> / ";
		
	}
	
	$KategoriePathString = substr($KategoriePathString, 0, (strlen($KategoriePathString) - 3));
	
	return $KategoriePathString;
	
}

function GetKategorieIDs($KategorieID, $KategorieIDArray) {

	// Alle Kategorien einlesen, die unter der angegebenen sind.
	$SQLString = "SELECT * FROM " . TABLE_KATEGORIE . " WHERE parentid = '" . $KategorieID . "'";
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	while ($KategorieObject = mysql_fetch_object($MySQLQueryReference)) {
	
		$KategorieIDArray[] = $KategorieObject->id;
		$KategorieIDArray = GetKategorieIDs($KategorieObject->id, $KategorieIDArray);
		
	}
	
	if (!$KategorieIDArray) {
		$KategorieIDArray[] = 0;
	}
	
	return $KategorieIDArray;
	
}


function GetKategorieArray($ArtikelID = "", $KategorieIDArray = "") {

	// Wenn nur die Kategorien eingelesen werden sollen, die einem Artikel zugeordnet sind
	if ($ArtikelID) {
	
		// Datenbank Abfragen
		$SQLString = "SELECT " . TABLE_KATEGORIE . ".name, " . TABLE_KATEGORIE . ".aktiv FROM " . TABLE_KATEGORIE . " LEFT JOIN " . TABLE_KATEGORIERELATION . " ON " . TABLE_KATEGORIE . ".id = " . TABLE_KATEGORIERELATION . ".kategorieid WHERE " . TABLE_KATEGORIERELATION . ".artikelid = '" . $ArtikelID . "' ORDER BY " . TABLE_KATEGORIE . ".sort"; 
		$MySQLQueryReference = errorlogged_mysql_query($SQLString);
		
		while ($KategorieElement = mysql_fetch_array($MySQLQueryReference)) {
			$KategorieArray[] = $KategorieElement;
		}
		
	}
	
	// Wenn die Kategorien basierend auf KategorieIDs eingelesen werden sollen
	if ($KategorieIDArray) {

		// Datenbank Abfragen
		$SQLString = "SELECT " . TABLE_KATEGORIE . ".name FROM " . TABLE_KATEGORIE . " WHERE " . TABLE_KATEGORIE . ".id IN (" . implode($KategorieIDArray, ",") . ") ORDER BY " . TABLE_KATEGORIE . ".sort"; 
		$MySQLQueryReference = errorlogged_mysql_query($SQLString);
		
		while ($KategorieElement = mysql_fetch_array($MySQLQueryReference)) {
			$KategorieArray[] = $KategorieElement;
		}

	}

	return $KategorieArray;
	
}

function GetKategorieNameString($ArtikelID = "", $KategorieIDArray = "", $Separator) {

	global $lang_KeineKategorie;
	
	// Wenn der String auf Basis einer Artikelnummer zusammengesetzt werden soll
	if ($ArtikelID) {

		// Kategorien einlesen
		$KategorieArray = GetKategorieArray($ArtikelID);
		
	// Wenn der String einfach nur durch IDs von Kategorien zusammengestezt werden soll
	} elseif ($KategorieIDArray) {
		
		// Kategorien einlesen
		$KategorieArray = GetKategorieArray("", $KategorieIDArray);

	}

	// String zusammen setzen
	if ($KategorieArray) {

		foreach ($KategorieArray as $KategorieElement) {
			
			if ($KategorieElement["aktiv"]) {
				$KatgorieString .= $KategorieElement["name"] . $Separator;
			} else {
				$KatgorieString .= "<i class=\"inaktiv\">" . $KategorieElement["name"] . "</i>" . $Separator;
			}
			
		}

	} else {
	
		$KatgorieString = "<i>" . $lang_KeineKategorie . "</i>";
		
	}
	
	$KatgorieString = substr($KatgorieString, 0, (strlen($KatgorieString) - strlen($Separator)));

	return $KatgorieString;
	
}


// Frontend
function XGetKategorie($KategorieLevel) {
	
	$SQLString = "SELECT * FROM ".TABLE_KATEGORIE." WHERE level = '" . $KategorieLevel . "' ORDER BY sort";
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	while ($KategorieObject = mysql_fetch_object($MySQLQueryReference)) {
	
		echo str_repeat("&nbsp;", ($KategorieObject->level * 1)) . "<a href=\"".URLPFAD."themes/kategorie/index.php?katId=" . $KategorieObject->id . "\"><b>" . $KategorieObject->name . "</b></a><br>";

		unset($KategoriePathArray);
		$KategoriePathArray = GetKategoriePathArray($_SESSION["kategorieid"], $KategoriePathArray);
		
		$KategorieFound = false;

		foreach ($KategoriePathArray as $KategoriePathElement) {
		
			if ($KategoriePathElement["id"] == $KategorieObject->id) {
				$KategorieFound = true;
			}
			
		}

		if ($KategorieFound || ($KategorieObject->id == $_SESSION["kategorieid"])) {

			GetKategorieNode($KategorieObject->id,"");
			
		}
		
	}
	
	
}

function XGetKategorieNode($ParentID) {
	
	$SQLString = "SELECT * FROM ".TABLE_KATEGORIE." WHERE parentid = '" . $ParentID . "' ORDER BY sort";
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	while ($KategorieObject = mysql_fetch_object($MySQLQueryReference)) {
	
		echo str_repeat("&nbsp;", ($KategorieObject->level * 2)) . "-<a href=\"".URLPFAD."themes/kategorie/index.php?katId=" . $KategorieObject->id . "\">" . $KategorieObject->name . "</a><br>";

		unset($KategoriePathArray);
		$KategoriePathArray = GetKategoriePathArray($_SESSION["kategorieid"], $KategoriePathArray);

		$KategorieFound = false;

		foreach ($KategoriePathArray as $KategoriePathElement) {
		
			if ($KategoriePathElement["id"] == $KategorieObject->id) {
				$KategorieFound = true;
			}
			
		}

		if ($KategorieFound || ($KategorieObject->id == $_SESSION["kategorieid"])) {

			GetKategorieNode($KategorieObject->id,"");
			
		}

		
	}

}


/************************************************************************
*																																				*
*								Kategoriefunktion fuer den Adminbereich									*
*																																				*
************************************************************************/

// Admin
// Frontend

function GetAdminKategorie($KategorieLevel, $id) {

	global $lang_artAnlegen;
	
	// Kategorien abfragen
	//$SQLString = "SELECT * FROM " . TABLE_KATEGORIE . " WHERE level = '" . $KategorieLevel . "' ORDER BY sort";
	$SQLString = "SELECT * FROM " . TABLE_KATEGORIE . " WHERE parentid = 0 ORDER BY sort";
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	// Kategorien ausgeben
	while ($KategorieObject = mysql_fetch_object($MySQLQueryReference)) {

		// Namen der Kategorie
		if ($KategorieObject->id == $id) {
			$formatKatText = "<b>" . $KategorieObject->name . "</b>";
		} else {
			$formatKatText = $KategorieObject->name;
		}
		
		echo "<tr>";
		echo "<td class=\"tdBackground2\"><input type=\"checkbox\" name=\"katId[]\" value=\"" . $KategorieObject->id . "\"></td>";
		echo "<td class=\"tdBackground2\">" . str_repeat("&nbsp;", ($KategorieObject->level * 1)) . "<b>" . $formatKatText . "</b></td>";
		echo "<td class=\"tdBackground2\">&nbsp;</td>";
		echo "</tr>";

		GetAdminKategorieNode($KategorieObject->id, $id);

	}
	
}

function GetAdminKategorieNode($ParentID,$id) {

	global $lang_artAnlegen;
	
	// Kategorien abfragen
	$SQLString = "SELECT * FROM " . TABLE_KATEGORIE . " WHERE parentid = '" . $ParentID . "' ORDER BY sort";
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	// Kategorien ausgeben
	while ($KategorieObject = mysql_fetch_object($MySQLQueryReference)) {
	
		// Namen der Kategorie
		if($KategorieObject->id == $id) {
			$formatKatText = "<b>" . $KategorieObject->name . "</b>";
		} else {
			$formatKatText = $KategorieObject->name;
		}
		
		echo "<tr>";
		echo "<td class=\"tdBackground2\"><input type=\"checkbox\" name=\"katId[]\" value=\"" . $KategorieObject->id . "\"></td>";
		echo "<td class=\"tdBackground2\">" . str_repeat("&nbsp;", ($KategorieObject->level * 3)) . "-" . $formatKatText . "</td>";

		if ($KategorieObject->smallImage != "") {
			echo "<td class=\"tdBackground2\"><img src=\"../../images/dbimages/".$KategorieObject->smallImage."\" width=\"30\" height=\"20\" border=\"0\"></td>";
		} else {
			echo "<td class=\"tdBackground2\">&nbsp;</td>";
		}

		echo "</tr>";

		GetAdminKategorieNode($KategorieObject->id,$id);
		
	}

}

function AddKategorieData($KategorieIDArray, $KategorieName, $Language, $smallImage, $maxgroesse) {

	// Wenn keine Kategorie ausgew�hlt wurde, die Kategorie in der ersten Ebene anlegen
	if (!$KategorieIDArray) {

		$SQLString = "INSERT INTO " . TABLE_KATEGORIE . " (level, name, langu) VALUES ('1', '" . $KategorieName . "', '" . $Language . "')";
		$MySQLQueryReference = errorlogged_mysql_query($SQLString);
		
	// die Kategorie unter allen ausgew�hlten Kategorien anlegen
	} else {
		
		foreach($KategorieIDArray as $KategorieID) {

			$SQLString = "SELECT * FROM " . TABLE_KATEGORIE . " WHERE id = '" . $KategorieID . "'";
			$KategorieObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));

			include_once("../includes/image_upload.inc.php");

			$SQLString = "INSERT INTO " . TABLE_KATEGORIE . " (level, name, langu, parentid, smallImage) VALUES ('" . ($KategorieObject->level + 1) . "', '" . $KategorieName . "', '" . $Language ."', '" . $KategorieObject->id . "', '" . $smallImage . "')";
			$MySQLQueryReference = errorlogged_mysql_query($SQLString);
			
		}		
		
	}

}


function XDeleteKategorieRekursiv($KategorieID) {

	// Kategorie einlesen
	$SQLString = "SELECT * FROM " . TABLE_KATEGORIE . " WHERE id = '" . $KategorieID . "'";
	$KategorieObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));

	// Kategorie l�schen
	$SQLString = "DELETE FROM " . TABLE_KATEGORIE . " WHERE id = '" . $KategorieID . "'";
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	// Kategorierelation ztu den Artikel l�schen
	$SQLString = "DELETE FROM " . TABLE_KATEGORIERELATION . " WHERE kategorieid = '" . $KategorieID . "'";
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	// Bilder der Kategorie l�schen
	if (($KategorieObject->smallImage) && (file_exists("../../images/dbimages/" . $KategorieObject->smallImage))) {
		unlink("../../images/dbimages/" . $KategorieObject->smallImage);	
	}
	
	// alle Kategorien, die darunter liegen ebenfalls l�schen
	$SQLString = "SELECT * FROM " . TABLE_KATEGORIE . " WHERE parentid = '" . $KategorieID . "'";
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	
	while ($KategorieObject = mysql_fetch_object($MySQLQueryReference)) {
		DeleteKategorieRekursiv($KategorieObject->id);
	}
	
}


function XDeleteKategorie($KategorieIDArray) {
	
	if ($KategorieIDArray) {

		// Kategorien Rekursiv l�schen
		foreach ($KategorieIDArray as $KategorieID) {
		
			DeleteKategorieRekursiv($KategorieID);
			
		}
	
//		$loeschen = "DELETE FROM " . TABLE_ARTIKEL . " WHERE kategorie = '".$katId."'";
//  	$loesch = errorlogged_mysql_query($loeschen);

	}

}

function RenameKategorie($KategorieID, $KategorieName, $smallImage, $maxgroesse) {
	
	$SQLString = "SELECT * FROM ".TABLE_KATEGORIE." WHERE id = '" . $KategorieID . "'";
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	$rows = mysql_fetch_object($MySQLQueryReference);

	if ($_FILES['smallImage']['tmp_name'] != "") {
		include_once("../includes/image_update.inc.php");
	}

	if ($dbUpdate != "false") {
		
		if($_FILES['smallImage']['tmp_name'] == "") {

			$SQLString = "UPDATE " . TABLE_KATEGORIE . " Set name = '" . $KategorieName . "', smallImage='' WHERE id = '" . $KategorieID . "'";

			// Bilder der Kategorie l�schen
			if (($rows->smallImage) && (file_exists("../../images/dbimages/" . $rows->smallImage))) {
				unlink("../../images/dbimages/" . $rows->smallImage);	
			}

  		} else {

			$SQLString = "UPDATE " . TABLE_KATEGORIE . " Set name = '" . $KategorieName . "', smallImage='" . $smallImage . "' WHERE id = '" . $KategorieID . "'";

		}
		$MySQLQueryReference = errorlogged_mysql_query($SQLString);

	}

}



function ReziseKatImage($Image,$NewImageName,$ImageType){
	
	global $ImageSettingsArray;
	
	$Width = $ImageSettingsArray[2]["image_width"];
	$Height = $ImageSettingsArray[2]["image_height"];
					
//	echo $Image . "<br />";
//	echo $ImageType . "<br />";
//	echo $NewImageName . "<br />";
	
	$ImageSizeArray = getimagesize($Image);
	
//		$MaxBigSize = 40000000;
	
	if ($ImageSizeArray[0] > $ImageSizeArray[1]) {
	
		$NewImageWidth = $Width;
		$NewImageHeight = $ImageSizeArray[1] * ($Width / $ImageSizeArray[0]); 
	
	} else {
	
		$NewImageWidth = $ImageSizeArray[0] * ($Height / $ImageSizeArray[1]);
		$NewImageHeight = $Height;
	}

	
	$NewImageRef = imagecreatetruecolor($NewImageWidth, $NewImageHeight);
	if($ImageType == 'jpg'){
		$NewImage = imagecreatefromjpeg($Image);
	}elseif($ImageType == 'gif'){
		$NewImage = imagecreatefromgif($Image);
	}elseif($ImageType == 'png'){
		$NewImage = imagecreatefromgif($Image);	
	}
	imagecopyresampled($NewImageRef, $NewImage, 0, 0, 0, 0, $NewImageWidth, $NewImageHeight, $ImageSizeArray[0], $ImageSizeArray[1]);


	if($ImageType == 'jpg'){
		imagejpeg($NewImageRef, DATEIPFAD . "images/dbimages/" . $NewImageName, 85);
	}elseif($ImageType == 'gif'){
		imagegif($NewImageRef, DATEIPFAD . "images/dbimages/" . $NewImageName);
	}elseif($ImageType == 'png'){
		imagegif($NewImageRef, DATEIPFAD . "images/dbimages/" . $NewImageName);	
	}

	imagedestroy($NewImage);
	imagedestroy($NewImageRef);
		
	chmod(DATEIPFAD . "images/dbimages/" . $NewImageName, 0644);
	
//	echo $NewImageName;
//	exit;
	
	return $NewImageName;
}
?>