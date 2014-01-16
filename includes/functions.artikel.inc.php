<?php

/**
 *
 * Copyright (C) Die Randgruppe GmbH
 *
 * http://www.randshop.com
 * http://www.dierandgruppe.com
 *
 * Unter der Lizenz von Die Randgruppe GmbH:
 * http://www.randshop.com/Lizenz
 *
 * $Author$
 * $Date$
 * $Revision$
 *
 */

include_once(DATEIPFAD . "includes/functions.kategorie.inc.php");
include_once(DATEIPFAD . "includes/functions.mwst.inc.php");
include_once(DATEIPFAD . "includes/functions.merkmale.inc.php");
include_once(DATEIPFAD . "includes/functions.waehrung.inc.php");
include_once(DATEIPFAD . "includes/functions.language.inc.php");
include_once(DATEIPFAD . "includes/functions.banner.inc.php");
include_once(DATEIPFAD . "includes/functions.datenblatt.inc.php");
include_once(DATEIPFAD . "includes/functions.lager.inc.php");

if (KUNDENGRUPPEN) {
	include_once(DATEIPFAD . "includes/functions.mod.kundengruppen.inc.php");
} else {
	include_once(DATEIPFAD . "includes/functions.kundengruppen.inc.php");
}

include_once(DATEIPFAD . "includes/functions.kunden.inc.php");

if (ARTIKELDOWNLOAD) {
	include_once(DATEIPFAD . "includes/functions.mod.download.inc.php");
}

define('ARTIKELSORT_NAME', TABLE_ARTIKEL_LANGU . '.artikel_name');
define('ARTIKELSORT_PREIS', TABLE_ARTIKEL . '.preis_brutto');
define('ARTIKELSORT_ANR', TABLE_ARTIKEL . '.artikel_nr');

define('NAVIGATION_TYPE_KATEGORIE', 1);
define('NAVIGATION_TYPE_AKTIONEN', 2);
define('NAVIGATION_TYPE_HERSTELLER', 3);
define('NAVIGATION_TYPE_SUCHE', 4);


function GetArtikelSumme($SearchField, $SearchString, $ArLagerOption = 0) {

//  echo "***** ***** ***** *****<br>";
//  echo '$SearchField: ' . $SearchField . "<br>";
//  echo '$SearchString: ' . $SearchString . "<br>";
//  echo '$ArLagerOption: ' . $ArLagerOption . "<br>";

	// Shopeinstellungen einlesen
	$FeatureObject = GetFeatureDetail();

	// Sprache ermitteln
	if (!$LanguageID) {
		$LanguageID = GetDefaultLanguageID();
	}

	$StandardLanguageID = GetDefaultLanguageID();

	// ********************************************************************************
	// ** SQL-String zum einlesen der Artikel zusammensetzen
	// ********************************************************************************

	// Felder
	$SQLString = 'SELECT DISTINCT ';
	$SQLString .= 'SUM(' . TABLE_ARTIKEL . '.lager * ' . TABLE_ARTIKEL_LIEFERANTEN . '.ek_netto) AS ek_netto_sum ';
	$SQLString .= 'FROM ' . TABLE_ARTIKEL . ' ';
	$SQLString .= 'LEFT JOIN ' . TABLE_ARTIKEL_LANGU . ' ON ((IF(' . TABLE_ARTIKEL . '.merkmalkombinationparentid, ' . TABLE_ARTIKEL . '.merkmalkombinationparentid, ' . TABLE_ARTIKEL . '.id) = ' . TABLE_ARTIKEL_LANGU . '.artikel_id) AND (' . TABLE_ARTIKEL_LANGU . '.language_id = ' . $LanguageID . ')) ';
	$SQLString .= 'LEFT JOIN ' . TABLE_ARTIKEL_LANGU . ' table_artikel_langu_standard ON ((IF(' . TABLE_ARTIKEL . '.merkmalkombinationparentid, ' . TABLE_ARTIKEL . '.merkmalkombinationparentid, ' . TABLE_ARTIKEL . '.id) = table_artikel_langu_standard.artikel_id) AND (table_artikel_langu_standard.language_id = ' . $StandardLanguageID . ')) ';
	$SQLString .= 'LEFT JOIN ' . TABLE_ARTIKEL_LIEFERANTEN . ' ON  ((' . TABLE_ARTIKEL . '.id = ' . TABLE_ARTIKEL_LIEFERANTEN . '.artikel_id) AND (' . TABLE_ARTIKEL_LIEFERANTEN . '.hauptlieferant = 1)) ';

	$SQLString .= ' WHERE ( ';

	// Suche
	if (is_array($SearchField) && is_array($SearchString)) {

		$Zeitmessung = true;

		// Volltextsuche
		if ($FeatureObject->volltextsuche) {

			if ($OptionSearchLike) {
				$SearchLike = '';
			} else {
				$SearchLike = '*';
			}

			if ($OptionSearchAll) {
				$SearchConnector = 'AND ';
			} else {
				$SearchConnector = 'OR ';
			}

			$SQLString .= '(';

			foreach ($SearchField as $SearchFieldKey => $SearchFieldValue) {

				$SearchStringArray = explode(' ', $SearchString[$SearchFieldKey]);

				$SearchStringAgainst = '';

				foreach ($SearchStringArray as $SearchStringElement) {

					$SearchStringAgainst .= $SearchLike . $SearchStringElement . $SearchLike . ' ';

				}


				$SQLString .= '(MATCH (' . $SearchFieldValue . ') AGAINST (\'' . $SearchStringAgainst . '\' IN BOOLEAN MODE)) ' . $SearchConnector;

				$SQLString = substr($SQLString, 0, strlen($SQLString) - strlen($SearchConnector));
				$SQLString .= ' OR ';

			}

			$SQLString = substr($SQLString, 0, strlen($SQLString) - strlen($SearchConnector));
			$SQLString .= ') AND ';

		// normale Suche
		} else {

			if ($OptionSearchLike == 0) {
				$SearchLike = '';
			} else {
				$SearchLike = '%';
			}

//          if ($OptionSearchLike) {
//              $SearchLike = '';
//          } else {
//              $SearchLike = '%';
//          }

			if ($OptionSearchAll == 1) {
				$SearchConnector = 'AND ';
			} else {
				$SearchConnector = 'OR ';
			}

			$SQLString .= '(';

			foreach ($SearchField as $SearchFieldKey => $SearchFieldValue) {

				if ($OptionSearchExact) {
					$SQLString .= '(' . $SearchFieldValue . ' LIKE \'' . $SearchLike . $SearchString[$SearchFieldKey] . $SearchLike . '\') ' . $SearchConnector;
				} else {

					$SearchStringArray = explode(' ', $SearchString[$SearchFieldKey]);

					foreach ($SearchStringArray as $SearchStringElement) {
						$SQLString .= '(' . $SearchFieldValue . ' LIKE \'' . $SearchLike . $SearchStringElement . $SearchLike . '\') ' . $SearchConnector;
					}

					$SQLString = substr($SQLString, 0, strlen($SQLString) - strlen($SearchConnector));
					$SQLString .= ' OR ';

				}

			}

			$SQLString = substr($SQLString, 0, strlen($SQLString) - strlen($SearchConnector));
			$SQLString .= ') AND ';

		}

	} elseif ($SearchField && $SearchString) {

		$SQLString .= '(' . $SearchField . ' LIKE \'%\' . $SearchString . \'%\') AND ';

	}

	$SQLString .= '(' . TABLE_ARTIKEL . '.merkmalkombination = 0) AND ';

	// Filter 'Unter Meldebestand'
	if ($ArLagerOption) {
		$SQLString .= '(' . TABLE_ARTIKEL . '.meldebestand >= ' . TABLE_ARTIKEL . '.lager) AND ';
	}

	$SQLString .= ' 1) ';


//	echo '$SQLString: ' . $SQLString . '<br><br>';

	$ArtikelSummeObject = mysql_fetch_object(mysql_query($SQLString));

	return $ArtikelSummeObject;

}


// ********************************************************************************
// ** GetArtikelLanguageDataArray
// ********************************************************************************
function GetArtikelLanguageDataArray($ArtikelID) {

	// Sprachen Abfragen
	$SQLString = "SELECT ";
	$SQLString .= TABLE_LANGUAGE . ".language_id, ";
	$SQLString .= "IF(ISNULL(" . TABLE_ARTIKEL_LANGU . ".artikel_name), " . TABLE_LANGUAGE . ".language_image_admintool_inactive, " . TABLE_LANGUAGE . ".language_image_admintool_active) AS language_image_admintool, ";
	$SQLString .= "IF(ISNULL(" . TABLE_ARTIKEL_LANGU . ".artikel_name), " . TABLE_LANGUAGE . ".language_image_admintool_inactive_width, " . TABLE_LANGUAGE . ".language_image_admintool_active_width) AS language_image_admintool_width, ";
	$SQLString .= "IF(ISNULL(" . TABLE_ARTIKEL_LANGU . ".artikel_name), " . TABLE_LANGUAGE . ".language_image_admintool_inactive_height, " . TABLE_LANGUAGE . ".language_image_admintool_active_height) AS language_image_admintool_height ";
	$SQLString .= "FROM ";
	$SQLString .= TABLE_LANGUAGE . " ";
	$SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_LANGU . " ON ((" . TABLE_LANGUAGE . ".language_id = " . TABLE_ARTIKEL_LANGU . ".language_id) AND (" . TABLE_ARTIKEL_LANGU . ".artikel_id = '" . $ArtikelID . "')) ";


	$MySQLQueryReference = errorlogged_mysql_query($SQLString);

	$LanguageCounter = 0;

	while ($ArtikelLanguageRow = mysql_fetch_array($MySQLQueryReference)) {
		$ArtikelLanguageDataArray[$LanguageCounter]["language_image_admintool_imagestring"] = "<img src=\"" . IMAGEPFAD . "dbimages/" . $ArtikelLanguageRow["language_image_admintool"] . "\" width=\"" . $ArtikelLanguageRow["language_image_admintool_width"] . "\" height=\"" . $ArtikelLanguageRow["language_image_admintool_height"] . "\" border=\"0\">";
		$ArtikelLanguageDataArray[$LanguageCounter]["language_id"] = $ArtikelLanguageRow["language_id"];
		$LanguageCounter++;
	}

	return $ArtikelLanguageDataArray;

}

// ********************************************************************************
// ** CopyArtikel
// ********************************************************************************
function CopyArtikel($ArtikelID, $MerkmalkombinationparentID = "") {

	global $a_ar_kopie;



	// Artikeldaten einlesen
	$ArtikelObject = GetArtikelDetail($ArtikelID);

	// Grunddaten des Artikels kopieren
	$SQLString = "INSERT INTO " . TABLE_ARTIKEL . " SET ";
	$SQLString .= TABLE_ARTIKEL . ".artikel_nr = '" . $ArtikelObject->artikel_nr . "', ";
	$SQLString .= TABLE_ARTIKEL . ".preis_alt_netto = '" . $ArtikelObject->preis_alt_netto . "', ";
	$SQLString .= TABLE_ARTIKEL . ".preis_alt_brutto = '" . $ArtikelObject->preis_alt_brutto . "', ";
	$SQLString .= TABLE_ARTIKEL . ".preis_netto = '" . $ArtikelObject->preis_netto . "', ";
	$SQLString .= TABLE_ARTIKEL . ".preis_brutto = '" . $ArtikelObject->preis_brutto . "', ";
	$SQLString .= TABLE_ARTIKEL . ".mwst = '" . $ArtikelObject->mwst . "', ";
	$SQLString .= TABLE_ARTIKEL . ".variante1 = '" . $ArtikelObject->variante1 . "', ";
	$SQLString .= TABLE_ARTIKEL . ".variante2 = '" . $ArtikelObject->variante2 . "', ";
	$SQLString .= TABLE_ARTIKEL . ".variante3 = '" . $ArtikelObject->variante3 . "', ";
	$SQLString .= TABLE_ARTIKEL . ".variante4 = '" . $ArtikelObject->variante4 . "', ";
	$SQLString .= TABLE_ARTIKEL . ".gewicht = '" . $ArtikelObject->gewicht . "', ";
	$SQLString .= TABLE_ARTIKEL . ".voe_datum = '" . $ArtikelObject->voe_datum . "', ";
	$SQLString .= TABLE_ARTIKEL . ".hersteller_id = '" . $ArtikelObject->hersteller_id . "', ";
	$SQLString .= TABLE_ARTIKEL . ".angebote = '" . $ArtikelObject->angebote . "', ";
	$SQLString .= TABLE_ARTIKEL . ".aktiv = '0', ";
	$SQLString .= TABLE_ARTIKEL . ".lieferstatus = '" . $ArtikelObject->lieferstatus . "', ";
	$SQLString .= TABLE_ARTIKEL . ".startseitenangebot = '" . $ArtikelObject->startseitenangebot . "' ";

	$MySQLQueryReference = errorlogged_mysql_query($SQLString);

	$NewArtikelID = mysql_insert_id();

	//Sprachabhängige Daten

	$SQLString = "SELECT ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".language_id, ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".artikel_name, ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".beschreibung, ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".kurz_beschreibung, ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".url_artikel_name_vorgabe, ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".url_artikel_name ";
	$SQLString .= "FROM " . TABLE_ARTIKEL_LANGU . " ";
	$SQLString .= "WHERE " . TABLE_ARTIKEL_LANGU . ".artikel_id = " . $ArtikelID;

	$QueryReference = errorlogged_mysql_query($SQLString);

	while($row = mysql_fetch_assoc($QueryReference)){

		if ($row['url_artikel_name_vorgabe']) {

			$URLArtikelNameVorgabe = $row['url_artikel_name_vorgabe'];
			$URLArtikelName = $row['url_artikel_name'];

		} else {

			$URLArtikelNameVorgabe = '';
			$URLArtikelName = DecodeArtikelSEOName($a_ar_kopie . " " . $row["artikel_name"]);

		}

		$SQLString = "INSERT INTO " . TABLE_ARTIKEL_LANGU . " SET ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".artikel_name = '" . $a_ar_kopie . " " . $row["artikel_name"] . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".beschreibung = '" . $row["beschreibung"] . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".kurz_beschreibung = '" . $row["kurz_beschreibung"] . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".url_artikel_name_vorgabe = '" . $URLArtikelNameVorgabe . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".url_artikel_name = '" . $URLArtikelName . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".language_id = '" . $row["language_id"] . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".artikel_id = '" . $NewArtikelID . "' ";


		errorlogged_mysql_query($SQLString);

	}



	// Angaben zu Merkmalkombinationen abgleichen
	if ($MerkmalkombinationparentID) {

		$SQLString = "UPDATE " . TABLE_ARTIKEL . " SET ";
		$SQLString .= TABLE_ARTIKEL . ".merkmalkombinationparentid = '" . $MerkmalkombinationparentID . "', ";
		$SQLString .= TABLE_ARTIKEL . ".merkmalkombinationsort = '" . $ArtikelObject->merkmalkombinationsort . "', ";
		$SQLString .= TABLE_ARTIKEL . ".merkmalkombinationstandard = '" . $ArtikelObject->merkmalkombinationstandard . "' ";
		$SQLString .= "WHERE " . TABLE_ARTIKEL . ".id = '" . $NewArtikelID . "'";
		$MySQLQueryReference = errorlogged_mysql_query($SQLString);

	}


	// Kategorien abgleichen
	foreach ($ArtikelObject->kategorie_array as $KategorieArray) {

		$SQLString = "INSERT INTO " . TABLE_KATEGORIERELATION . " SET ";
		$SQLString .= TABLE_KATEGORIERELATION . ".artikelid = '" . $NewArtikelID . "', ";
		$SQLString .= TABLE_KATEGORIERELATION . ".kategorieid = '" . $KategorieArray["id"] . "'";
		$MySQLQueryReference = errorlogged_mysql_query($SQLString);

	}

	// Merkmalauswahl abgleichen
	if ($ArtikelObject->merkmalauswahl) {

		$SQLString = "UPDATE " . TABLE_ARTIKEL . " SET ";
		$SQLString .= TABLE_ARTIKEL . ".merkmalauswahl = '1' ";
		$SQLString .= "WHERE " . TABLE_ARTIKEL . ".id = '" . $NewArtikelID . "'";
		$MySQLQueryReference = errorlogged_mysql_query($SQLString);

		$MerkmalauswahlDataArray = GetMerkmalauswahlDataArray($ArtikelObject->id);

		for ($VariantenCounter = 1; $VariantenCounter <= 4; $VariantenCounter++) {

			if ($ArtikelObject->{"variante" . $VariantenCounter}) {

				foreach ($MerkmalauswahlDataArray[$VariantenCounter] as $MerkmalauswahlData) {

					$SQLString = "INSERT INTO " . TABLE_MERKMALAUSWAHL . " SET ";
					$SQLString .= TABLE_MERKMALAUSWAHL . ".artikelid = '" . $NewArtikelID . "', ";
					$SQLString .= TABLE_MERKMALAUSWAHL . ".variantenid = '" . $ArtikelObject->{"variante" . $VariantenCounter} . "', ";
					$SQLString .= TABLE_MERKMALAUSWAHL . ".merkmalid = '" . $MerkmalauswahlData["id"] . "', ";
					$SQLString .= TABLE_MERKMALAUSWAHL . ".selected = '" . $MerkmalauswahlData["selected"] . "'";
					$MySQLQueryReference = errorlogged_mysql_query($SQLString);

				}

			}

		}

	}

	// Kundengruppenpreise abgleichen
	if ($ArtikelObject->kundengruppenpreis) {

		$SQLString = "UPDATE " . TABLE_ARTIKEL . " SET ";
		$SQLString .= TABLE_ARTIKEL . ".kundengruppenpreis = '1' ";
		$SQLString .= "WHERE " . TABLE_ARTIKEL . ".id = '" . $NewArtikelID . "'";
		$MySQLQueryReference = errorlogged_mysql_query($SQLString);

		$KundengruppenpreiseDataArray = GetKundengruppenpreiseDataArray($ArtikelObject->id);

		foreach ($KundengruppenpreiseDataArray as $KundengruppenpreiseData) {

			$SQLString = "INSERT INTO " . TABLE_KUDNENGRUPPENPREIS . " SET ";
			$SQLString .= TABLE_KUDNENGRUPPENPREIS . ".artikelid = '" . $NewArtikelID . "', ";
			$SQLString .= TABLE_KUDNENGRUPPENPREIS . ".kundengruppenid = '" . $KundengruppenpreiseData["kundengruppenid"] . "', ";
			$SQLString .= TABLE_KUDNENGRUPPENPREIS . ".preis_netto = '" . $KundengruppenpreiseData["preis_netto"] . "', ";
			$SQLString .= TABLE_KUDNENGRUPPENPREIS . ".preis_brutto = '" . $KundengruppenpreiseData["preis_brutto"] . "'";
			$MySQLQueryReference = errorlogged_mysql_query($SQLString);

		}

	}

	// Merkmalkombinationen abgleichen
	if ($ArtikelObject->merkmalkombination) {

		$SQLString = "UPDATE " . TABLE_ARTIKEL . " SET ";
		$SQLString .= TABLE_ARTIKEL . ".merkmalkombination = '" . $ArtikelObject->merkmalkombination . "' ";
		$SQLString .= "WHERE " . TABLE_ARTIKEL . ".id = '" . $NewArtikelID . "'";
		$MySQLQueryReference = errorlogged_mysql_query($SQLString);

		$MerkmalkombinationenSearchField = "merkmalkombinationparentid";
		$MerkmalkombinationenSearchString = $ArtikelObject->id;

		$MerkmalkombinationenDataArray = GetArtikelDataArray($MerkmalkombinationenSearchField, $MerkmalkombinationenSearchString, "", "", "", "", "", "", "", "", 0, "", 0);

		foreach ($MerkmalkombinationenDataArray as $MerkmalkombinationenData) {
			CopyArtikel($MerkmalkombinationenData["id"], $NewArtikelID);
		}

	}

	// SEO Daten hinterlegen
	$SQLString = 'SELECT ';
	$SQLString .= TABLE_LANGUAGE . '.language_id ';
	$SQLString .= 'FROM ';
	$SQLString .= TABLE_LANGUAGE . ' ';
	$SQLString .= 'WHERE ';
	$SQLString .= "(";
	$SQLString .= '(' . TABLE_LANGUAGE . '.language_active = 1) AND ';
	$SQLString .= " 1)";

	$MySQLQueryReference = errorlogged_mysql_query($SQLString);

	while ($LanguageRow = mysql_fetch_array($MySQLQueryReference, MYSQL_ASSOC)) {

		SetArtikelSEOURLs($NewArtikelID, $LanguageRow['language_id']);

	}



}

// ********************************************************************************
// ** CheckArtikelVariantenAuswahl
// ********************************************************************************
function CheckArtikelVariantenAuswahl($ArtikelID, $Variante1, $Variante1Original, $Variante2, $Variante2Original, $Variante3, $Variante3Original, $Variante4, $Variante4Original, $ErrorMessage = 1) {

	global $a_ar_msg_merkmalauswahl;

	// Hat sich was an den Varianten ge�ndert
	if ($Variante1 != $Variante1Original) {
		if (!$Variante1Original) {
			$VariantenAenderungsArray[1] = 1;
		} else {
			$VariantenAenderungsArray[1] = 2;
		}
	}

	if ($Variante2 != $Variante2Original) {
		if (!$Variante2Original) {
			$VariantenAenderungsArray[2] = 1;
		} else {
			$VariantenAenderungsArray[2] = 2;
		}
	}

	if ($Variante3 != $Variante3Original) {
		if (!$Variante3Original) {
			$VariantenAenderungsArray[3] = 1;
		} else {
			$VariantenAenderungsArray[3] = 2;
		}
	}


	if ($Variante4 != $Variante4Original) {
		if (!$Variante4Original) {
			$VariantenAenderungsArray[4] = 1;
		} else {
			$VariantenAenderungsArray[4] = 2;
		}
	}

	// �berpr�fen, es f�r den Artikel relevant ist
	if ($VariantenAenderungsArray) {

		$ArtikelObject = GetArtikelDetail($ArtikelID);

		if ($ArtikelObject->merkmalkombination) {

			if ($ErrorMessage) {
				return $a_ar_msg_merkmalauswahl;
			} else {
				return true;
			}

		} elseif ($ArtikelObject->merkmalauswahl) {

			foreach ($VariantenAenderungsArray as $VariantenAenderung) {

				if ($VariantenAenderung == 2) {
					if ($ErrorMessage) {
						return $a_ar_msg_merkmalauswahl;
					} else {
						return true;
					}
				}

			}

		}


	}

}


// ********************************************************************************
// ** SetArtikelZuordnung
// ********************************************************************************
function SetArtikelZuordnung($ArtikelID, $AbArtikelID) {

	if ($ArtikelID != $AbArtikelID) {

		// �berpr�fen, ob schon vorhanden
		$SQLString = "SELECT " . TABLE_ARTIKEL_AB . ".artikelid FROM " . TABLE_ARTIKEL_AB . " WHERE " . TABLE_ARTIKEL_AB . ".artikelid = '" . $ArtikelID . "' AND " . TABLE_ARTIKEL_AB . ".abartikelid = '" . $AbArtikelID . "'";
		$ArtikelObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));

		// Wenn noch nicht, dann zuordnen
		if (!$ArtikelObject) {

			$SQLString = "INSERT INTO " . TABLE_ARTIKEL_AB . " SET ";
			$SQLString .= TABLE_ARTIKEL_AB . ".artikelid = '" . $ArtikelID . "', ";
			$SQLString .= TABLE_ARTIKEL_AB . ".abartikelid = '" . $AbArtikelID . "'";

			errorlogged_mysql_query($SQLString);

		}

	}

}

function DeleteArtikelZuordnung($ArtikelID, $AbArtikelID) {

	// Artikelzuordnung l�schen
	$SQLString = "DELETE FROM " . TABLE_ARTIKEL_AB . " WHERE " . TABLE_ARTIKEL_AB . ".artikelid = '" . $ArtikelID . "' AND " . TABLE_ARTIKEL_AB . ".abartikelid = '" . $AbArtikelID . "'";
	errorlogged_mysql_query($SQLString);

}

// ********************************************************************************
// ** SaveArtikelGrunddaten
// ********************************************************************************
function SaveArtikelGrunddaten($ArtikelID, $Artikelnummer, $Artikelname, $Beschreibung, $PreisAlt, $Preis, $PreisFormat, $MwSt, $ImageSmallArray, $ImageSmallLoeschen, $ImageBigArray, $ImageBigLoeschen, $Variante1, $Variante2, $Variante3, $Variante4, $Lager, $LagerBestellungen, $Meldebestand, $Gewicht, $VOEDatum, $Hersteller, $Aktion, $Onlinestatus, $Lieferstatus, $Startseitenangebot, $kurz_beschreibung, $Startaktuellartikel, $Ean, $ImageLanguageIndependent, $DownloadLanguageIndependent, $LanguageID = 0, $ImageArray = "", $ImageLoeschen = 0, $GiveAway = 0, $GiveAwayMinBestellwert = 0.0, $GiveAwayRabattProzent = 0, $einheit_groesse = 0.0, $einheit_masseinheit = '', $URLArtikelNameVorgabe = '', $Kundengruppe = 0, $Highlight = 0, $HighlightPreis = 0, $HighlightEndDatumTS = false) {

	// Shopeinstellungen einlesen
	$ShopeinstellungenObject = GetShopeinstellungDetail();

	// SEO Einstellungen einlesen
	$Einstellungen = GetEinstellungen('', 'seo');

	// Sprache ermitteln
	if (!$LanguageID) {
		$LanguageID = GetDefaultLanguageID();
	}

	// alten URL Namen ermitteln
	if ($ArtikelID) {

		$SQLString = 'SELECT ';
		$SQLString .= TABLE_ARTIKEL_LANGU . '.url_artikel_name ';
		$SQLString .= 'FROM ';
		$SQLString .= TABLE_ARTIKEL . ' ';
		$SQLString .= 'LEFT JOIN ' . TABLE_ARTIKEL_LANGU . ' ON ((' . TABLE_ARTIKEL . '.id = ' . TABLE_ARTIKEL_LANGU . '.artikel_id) AND (' . TABLE_ARTIKEL_LANGU . '.language_id = \'' . $LanguageID . '\')) ';
		$SQLString .= 'WHERE ';
		$SQLString .= TABLE_ARTIKEL . '.id = \'' . $ArtikelID . '\' ';

		$ArtikelObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));

		$OldSEOURLArtikelName = $ArtikelObject->url_artikel_name;

	}

	// ********************************************************************************
	// ** Preise ermitteln
	// ********************************************************************************

	$MwStObject = GetMwStDetail($MwSt);

	if ($PreisFormat == 1) {
		$PreisBrutto = round(($Preis / 100) * ($MwStObject->mwst + 100), 2);
		$PreisNetto = round($Preis, 2);
		$PreisAltBrutto = round(($PreisAlt / 100) * ($MwStObject->mwst + 100), 2);
		$PreisAltNetto = round($PreisAlt, 2);
        $PreisHighlightBrutto = round(($HighlightPreis / 100) * ($MwStObject->mwst + 100), 2);
        $PreisHighlightNetto = round($HighlightPreis, 2);
    } else {
		$PreisBrutto = round($Preis, 2);
		$PreisNetto = round(($Preis / (100 + $MwStObject->mwst)) * 100, 2);
		$PreisAltBrutto = round($PreisAlt, 2);
		$PreisAltNetto = round(($PreisAlt / (100 + $MwStObject->mwst)) * 100, 2);
        $PreisHighlightBrutto = round($HighlightPreis, 2);
        $PreisHighlightNetto = round(($HighlightPreis / (100 + $MwStObject->mwst)) * 100, 2);
    }

	// ********************************************************************************
	// ** Daten speichern
	// ********************************************************************************

	// Artikel schon vorhanden
	if ($ArtikelID) {

		$SQLString = "UPDATE " . TABLE_ARTIKEL . " SET ";
		$SQLString .= TABLE_ARTIKEL . ".artikel_nr = '" . $Artikelnummer . "', ";
		$SQLString .= TABLE_ARTIKEL . ".preis_alt_netto = '" . $PreisAltNetto . "', ";
		$SQLString .= TABLE_ARTIKEL . ".preis_alt_brutto = '" . $PreisAltBrutto . "', ";
		$SQLString .= TABLE_ARTIKEL . ".preis_netto = '" . $PreisNetto . "', ";
		$SQLString .= TABLE_ARTIKEL . ".preis_brutto = '" . $PreisBrutto . "', ";
		$SQLString .= TABLE_ARTIKEL . ".mwst = '" . $MwSt . "', ";
		$SQLString .= TABLE_ARTIKEL . ".variante1 = '" . $Variante1 . "', ";
		$SQLString .= TABLE_ARTIKEL . ".variante2 = '" . $Variante2 . "', ";
		$SQLString .= TABLE_ARTIKEL . ".variante3 = '" . $Variante3 . "', ";
		$SQLString .= TABLE_ARTIKEL . ".variante4 = '" . $Variante4 . "', ";
		$SQLString .= TABLE_ARTIKEL . ".lager = '" . $Lager . "', ";
		$SQLString .= TABLE_ARTIKEL . ".lager_bestellungen = '" . $LagerBestellungen . "', ";
		$SQLString .= TABLE_ARTIKEL . ".meldebestand = '" . $Meldebestand . "', ";
		$SQLString .= TABLE_ARTIKEL . ".gewicht = '" . $Gewicht . "', ";
		$SQLString .= TABLE_ARTIKEL . ".ean = '" . $Ean . "', ";
		$SQLString .= TABLE_ARTIKEL . ".voe_datum = '" . $VOEDatum . "', ";
		$SQLString .= TABLE_ARTIKEL . ".hersteller_id = '" . $Hersteller . "', ";
		$SQLString .= TABLE_ARTIKEL . ".angebote = '" . $Aktion . "', ";
		$SQLString .= TABLE_ARTIKEL . ".aktiv = '" . $Onlinestatus . "', ";
		$SQLString .= TABLE_ARTIKEL . ".lieferstatus = '" . $Lieferstatus . "', ";
		$SQLString .= TABLE_ARTIKEL . ".startseitenangebot = '" . $Startseitenangebot . "', ";
		$SQLString .= TABLE_ARTIKEL . ".aktuellartikel = '" . $Startaktuellartikel . "', ";
		$SQLString .= TABLE_ARTIKEL . ".image_language_independent = '" . $ImageLanguageIndependent . "', ";
		$SQLString .= TABLE_ARTIKEL . ".download_language_independent = '" . $DownloadLanguageIndependent . "', ";
		$SQLString .= TABLE_ARTIKEL . ".giveaway = " . ($GiveAway?'1':'0') . ", ";
		$SQLString .= TABLE_ARTIKEL . ".giveaway_min_bestellwert = '" . $GiveAwayMinBestellwert . "', ";
		$SQLString .= TABLE_ARTIKEL . ".giveaway_rabattprozent = '" . $GiveAwayRabattProzent . "', ";
		$SQLString .= TABLE_ARTIKEL . ".einheit_groesse = '" . $einheit_groesse . "', ";
		$SQLString .= TABLE_ARTIKEL . ".einheit_masseinheit = '" . $einheit_masseinheit . "', ";
        $SQLString .= TABLE_ARTIKEL . ".kundengruppe_id = '" . $Kundengruppe . "', ";
        $SQLString .= TABLE_ARTIKEL . ".timestamp = NOW(), ";
        $SQLString .= TABLE_ARTIKEL . ".highlight_id = '" . $Highlight . "', ";
        $SQLString .= TABLE_ARTIKEL . ".highlight_preis_brutto = '" . $PreisHighlightBrutto . "', ";
        $SQLString .= TABLE_ARTIKEL . ".highlight_preis_netto = '" . $PreisHighlightNetto . "', ";

        if($HighlightEndDatumTS) {
            $SQLString .= TABLE_ARTIKEL . ".highlight_enddatum = '" . date('Y-m-d', $HighlightEndDatumTS) . "' ";
        } else {
            $SQLString .= TABLE_ARTIKEL . ".highlight_enddatum = (NULL) ";
        }
		$SQLString .= " WHERE " . TABLE_ARTIKEL . ".id = '" . $ArtikelID . "'";

		$MySQLQueryReference = errorlogged_mysql_query($SQLString);

	// Artikel neu anlegen
	} else {

		$SQLString = "INSERT INTO " . TABLE_ARTIKEL . " SET ";
		$SQLString .= TABLE_ARTIKEL . ".artikel_nr = '" . $Artikelnummer . "', ";
		$SQLString .= TABLE_ARTIKEL . ".preis_alt_netto = '" . $PreisAltNetto . "', ";
		$SQLString .= TABLE_ARTIKEL . ".preis_alt_brutto = '" . $PreisAltBrutto . "', ";
		$SQLString .= TABLE_ARTIKEL . ".preis_netto = '" . $PreisNetto . "', ";
		$SQLString .= TABLE_ARTIKEL . ".preis_brutto = '" . $PreisBrutto . "', ";
		$SQLString .= TABLE_ARTIKEL . ".mwst = '" . $MwSt . "', ";
		$SQLString .= TABLE_ARTIKEL . ".variante1 = '" . $Variante1 . "', ";
		$SQLString .= TABLE_ARTIKEL . ".variante2 = '" . $Variante2 . "', ";
		$SQLString .= TABLE_ARTIKEL . ".variante3 = '" . $Variante3 . "', ";
		$SQLString .= TABLE_ARTIKEL . ".variante4 = '" . $Variante4 . "', ";
		$SQLString .= TABLE_ARTIKEL . ".lager = '" . $Lager . "', ";
		$SQLString .= TABLE_ARTIKEL . ".lager_bestellungen = '" . $LagerBestellungen . "', ";
		$SQLString .= TABLE_ARTIKEL . ".meldebestand = '" . $Meldebestand . "', ";
		$SQLString .= TABLE_ARTIKEL . ".gewicht = '" . $Gewicht . "', ";
		$SQLString .= TABLE_ARTIKEL . ".ean = '" . $Ean . "', ";
		$SQLString .= TABLE_ARTIKEL . ".voe_datum = '" . $VOEDatum . "', ";
		$SQLString .= TABLE_ARTIKEL . ".hersteller_id = '" . $Hersteller . "', ";
		$SQLString .= TABLE_ARTIKEL . ".angebote = '" . $Aktion . "', ";
		$SQLString .= TABLE_ARTIKEL . ".aktiv = '" . $Onlinestatus . "', ";
		$SQLString .= TABLE_ARTIKEL . ".lieferstatus = '" . $Lieferstatus . "', ";
		$SQLString .= TABLE_ARTIKEL . ".startseitenangebot = '" . $Startseitenangebot . "', ";
		$SQLString .= TABLE_ARTIKEL . ".aktuellartikel = '" . $Startaktuellartikel . "', ";
		$SQLString .= TABLE_ARTIKEL . ".image_language_independent = '" . $ImageLanguageIndependent . "', ";
		$SQLString .= TABLE_ARTIKEL . ".download_language_independent = '" . $DownloadLanguageIndependent . "', ";
		$SQLString .= TABLE_ARTIKEL . ".giveaway = " . ($GiveAway?'1':'0') . ", ";
		$SQLString .= TABLE_ARTIKEL . ".giveaway_min_bestellwert = '" . $GiveAwayMinBestellwert . "', ";
		$SQLString .= TABLE_ARTIKEL . ".giveaway_rabattprozent = '" . $GiveAwayRabattProzent . "', ";
		$SQLString .= TABLE_ARTIKEL . ".einheit_groesse = '" . $einheit_groesse . "', ";
		$SQLString .= TABLE_ARTIKEL . ".einheit_masseinheit = '" . $einheit_masseinheit . "', ";
        $SQLString .= TABLE_ARTIKEL . ".kundengruppe_id = '" . $Kundengruppe . "', ";
        $SQLString .= TABLE_ARTIKEL . ".timestamp = NOW(), ";
        $SQLString .= TABLE_ARTIKEL . ".highlight_id = '" . $Highlight . "', ";
        $SQLString .= TABLE_ARTIKEL . ".highlight_preis_brutto = '" . $PreisHighlightBrutto . "', ";
        $SQLString .= TABLE_ARTIKEL . ".highlight_preis_netto = '" . $PreisHighlightNetto . "', ";

        if($HighlightEndDatumTS) {
            $SQLString .= TABLE_ARTIKEL . ".highlight_enddatum = '" . date('Y-m-d', $HighlightEndDatumTS) . "' ";
        } else {
            $SQLString .= TABLE_ARTIKEL . ".highlight_enddatum = (NULL) ";
        }
		$MySQLQueryReference = errorlogged_mysql_query($SQLString);

		$ArtikelID = mysql_insert_id();

	}

	// Sprachabhängige Daten
	$SQLString = "SELECT ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".artikel_id ";
	$SQLString .= "FROM ";
	$SQLString .= TABLE_ARTIKEL_LANGU . " ";
	$SQLString .= "WHERE ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".artikel_id = '" . $ArtikelID . "' AND ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".language_id = '" . $LanguageID . "' ";

	$ArtikelLanguageObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));

	if ($Einstellungen->seo->sprechende_urls_aktiv || $URLArtikelNameVorgabe) {

		if ($URLArtikelNameVorgabe) {
			$URLArtikelName = DecodeArtikelSEOName($URLArtikelNameVorgabe);
		} else {
			$URLArtikelName = DecodeArtikelSEOName($Artikelname);
		}

	} else {

		$URLArtikelNameVorgabe = '';
		$URLArtikelName = '';

	}

	if ($ArtikelLanguageObject) {

		$SQLString = "UPDATE " . TABLE_ARTIKEL_LANGU . " SET ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".artikel_name = '" . htmlspecialchars($Artikelname, ENT_QUOTES) . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".beschreibung = '" . addslashes($Beschreibung) . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".kurz_beschreibung = '" . strip_tags($kurz_beschreibung) . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".url_artikel_name_vorgabe = '" . $URLArtikelNameVorgabe . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".url_artikel_name = '" . $URLArtikelName . "' ";
		$SQLString .= " WHERE ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".artikel_id = '" . $ArtikelID . "' AND ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".language_id = '" . $LanguageID . "' ";

		$MySQLQueryReference = errorlogged_mysql_query($SQLString);

	} else {

		$SQLString = "INSERT INTO  " . TABLE_ARTIKEL_LANGU . " SET ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".artikel_name = '" . htmlspecialchars($Artikelname, ENT_QUOTES) . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".beschreibung = '" . addslashes($Beschreibung) . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".kurz_beschreibung = '" . strip_tags($kurz_beschreibung) . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".url_artikel_name_vorgabe = '" . $URLArtikelNameVorgabe . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".url_artikel_name = '" . $URLArtikelName . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".artikel_id = '" . $ArtikelID . "', ";
		$SQLString .= TABLE_ARTIKEL_LANGU . ".language_id = '" . $LanguageID . "' ";

		$MySQLQueryReference = errorlogged_mysql_query($SQLString);

	}


	// ********************************************************************************
	// ** Bilder loeschen
	// ********************************************************************************

		if($ImageLoeschen || $ImageSmallLoeschen || $ImageBigLoeschen){

			if($ImageLoeschen || $ImageSmallLoeschen){
			$ImageName = "artikel_" . sprintf("%07d", $ArtikelID) . "_s_" . $LanguageID;
			unlink_wc(DATEIPFAD . "images/dbimages/", $ImageName . ".*");
			}
			if($ImageLoeschen || $ImageBigLoeschen){
			$ImageName = "artikel_" . sprintf("%07d", $ArtikelID) . "_b_" . $LanguageID;
			unlink_wc(DATEIPFAD . "images/dbimages/", $ImageName . ".*");
			}
			$SQLString = "UPDATE " . TABLE_ARTIKEL_LANGU . " SET ";
			if($ImageLoeschen || $ImageSmallLoeschen){
				$SQLString .= TABLE_ARTIKEL_LANGU . ".smallImage  = '', ";
			}
			if($ImageLoeschen || $ImageBigLoeschen){
				$SQLString .= TABLE_ARTIKEL_LANGU . ".bigImage  = '' ";
			}
			$SQLString .= " WHERE " . TABLE_ARTIKEL_LANGU . ".artikel_id = '" . $ArtikelID . "'";

			$MySQLQueryReference = errorlogged_mysql_query($SQLString);
		}

	// ********************************************************************************
	// ** Bilder auswerten
	// ********************************************************************************

	if($ShopeinstellungenObject->bildupload_aktiv == '1'){
		if($ImageArray["type"] != ""){

			$ImageName = "artikel_" . sprintf("%07d", $ArtikelID) . "_s_" . $LanguageID;
			unlink_wc(DATEIPFAD . "images/dbimages/", $ImageName . ".*");
			$ImageName = "artikel_" . sprintf("%07d", $ArtikelID) . "_m";
			unlink_wc(DATEIPFAD . "images/dbimages/", $ImageName . ".*");
			$ImageName = "artikel_" . sprintf("%07d", $ArtikelID) . "_b_" . $LanguageID;
			unlink_wc(DATEIPFAD . "images/dbimages/", $ImageName . ".*");

			$TempNameArray = explode(".", $ImageArray["name"]);

			$NewImageName = $NewImageName . "." . $TempNameArray[count($TempNameArray) - 1];

			$NewSmallImageName = "artikel_" . sprintf("%07d", $ArtikelID) . "_s_" . $LanguageID;
			$NewSmallImageName .= "." . $TempNameArray[count($TempNameArray) - 1];

			$NewBigImageName = "artikel_" . sprintf("%07d", $ArtikelID) . "_b_" . $LanguageID;
			$NewBigImageName .= "." . $TempNameArray[count($TempNameArray) - 1];



			if($ImageArray["type"] == "image/jpeg" || $ImageArray["type"] == "image/pjpeg"){
				$ImageType = 'jpg';
			}elseif($ImageArray["type"] == "image/gif"){
				$ImageType = 'gif';
			}elseif($ImageArray["type"] == "image/png"){
				$ImageType = 'png';
			}else{
				echo "<font color=\"red\">FEHLER: Bitte laden Sie nur JPG- , PNG- oder GIF-Dateien hoch.</font><br><a href=\"javascript:history.back()\">zur&uuml;ck</a>";
				die;
			}


			$ImageNameArray = ReziseImage($ImageArray["tmp_name"],$NewSmallImageName,$NewBigImageName,$ImageType);


			$SQLString = "UPDATE " . TABLE_ARTIKEL_LANGU . " SET ";
			$SQLString .= TABLE_ARTIKEL_LANGU . ".smallImage  = '" . $ImageNameArray[0] . "', ";
			$SQLString .= TABLE_ARTIKEL_LANGU . ".bigImage  = '" . $ImageNameArray[1] . "' ";
			$SQLString .= " WHERE " . TABLE_ARTIKEL_LANGU . ".artikel_id = '" . $ArtikelID . "' ";
			$SQLString .= " AND " . TABLE_ARTIKEL_LANGU . ".language_id = '" . $LanguageID . "' ";


			$MySQLQueryReference = errorlogged_mysql_query($SQLString);

		}
	}else{
		// kleines Bild
		if (($ImageSmallArray["size"] > 0) && !$ImageSmallLoeschen) {

			$NewImageName = "artikel_" . sprintf("%07d", $ArtikelID) . "_s_" . $LanguageID;

			// altes Bild löschen
			unlink_wc(DATEIPFAD . "images/dbimages/", $NewImageName . ".*");

			// temporäre Datei kopieren
			$TempNameArray = explode(".", $ImageSmallArray["name"]);
			$NewImageName = $NewImageName . "." . $TempNameArray[count($TempNameArray) - 1];
			move_uploaded_file($ImageSmallArray["tmp_name"], DATEIPFAD . "images/dbimages/" . $NewImageName);
			chmod(DATEIPFAD . "images/dbimages/" . $NewImageName, 0644);

			// Datenbank updaten
			$SQLString = "UPDATE " . TABLE_ARTIKEL_LANGU . " SET ";
			$SQLString .= TABLE_ARTIKEL_LANGU . ".smallImage  = '" . $NewImageName . "' ";
			$SQLString .= " WHERE ";
			$SQLString .= TABLE_ARTIKEL_LANGU . ".artikel_id = '" . $ArtikelID . "' AND ";
			$SQLString .= TABLE_ARTIKEL_LANGU . ".language_id = '" . $LanguageID . "' ";

			$MySQLQueryReference = errorlogged_mysql_query($SQLString);

		}

		// großes Bild
		if (($ImageBigArray["size"] > 0) && !$ImageBigLoeschen) {

			$NewImageName = "artikel_" . sprintf("%07d", $ArtikelID) . "_b_" . $LanguageID;

			// altes Bild löschen
			unlink_wc(DATEIPFAD . "images/dbimages/", $NewImageName . ".*");

			// temporäre Datei kopieren
			$TempNameArray = explode(".", $ImageBigArray["name"]);
			$NewImageName = $NewImageName . "." . $TempNameArray[count($TempNameArray) - 1];
			move_uploaded_file($ImageBigArray["tmp_name"], DATEIPFAD . "images/dbimages/" . $NewImageName);
			chmod(DATEIPFAD . "images/dbimages/" . $NewImageName, 0644);

			// Datenbank updaten
			$SQLString = "UPDATE " . TABLE_ARTIKEL_LANGU . " SET ";
			$SQLString .= TABLE_ARTIKEL_LANGU . ".bigImage  = '" . $NewImageName . "' ";
			$SQLString .= " WHERE ";
			$SQLString .= TABLE_ARTIKEL_LANGU . ".artikel_id = '" . $ArtikelID . "' AND ";
			$SQLString .= TABLE_ARTIKEL_LANGU . ".language_id = '" . $LanguageID . "' ";

			$MySQLQueryReference = errorlogged_mysql_query($SQLString);

		}
	}

	// ********************************************************************************
	// ** Merkmalkombinationen abgleichen
	// ********************************************************************************
	UpdateMerkmalkombination($ArtikelID);

	// SEO URLs neu definiern
	SetArtikelSEOURLs($ArtikelID, $LanguageID);

	return $ArtikelID;

}

// ********************************************************************************
// ** SetArtikelStartseitenangebot
// ********************************************************************************
function SetArtikelStartseitenangebot($ArtikelID) {

	// aktuelles Startseitenangebot ermitteln
	$ArtikelObject = GetArtikelDetail($ArtikelID);

	// neuens Startseitenangebot ermitteln
	if ($ArtikelObject->startseitenangebot == 1) {
		$NewStartseitenangebot = 0;
	} else {
		$NewStartseitenangebot = 1;
	}

	// neues Startseitenangebot setzen
	$SQLString = "UPDATE " . TABLE_ARTIKEL . " SET ";
	$SQLString .= TABLE_ARTIKEL . ".startseitenangebot = '" . $NewStartseitenangebot . "' ";
	$SQLString .= "WHERE " . TABLE_ARTIKEL . ".id = '" . $ArtikelID . "'";

	$MySQLQuerryReferenz = errorlogged_mysql_query($SQLString);

	// ********************************************************************************
	// ** Merkmalkombinationen abgleichen
	// ********************************************************************************
	UpdateMerkmalkombination($ArtikelID);

}


// ********************************************************************************
// ** SetArtikelStartseitenangebot
// ********************************************************************************
function SetAktuellArtikelStartseiten($ArtikelID) {

	// aktuelles Startseitenangebot ermitteln
	$ArtikelObject = GetArtikelDetail($ArtikelID);
	// neuens Startseitenangebot ermitteln
	if ($ArtikelObject->aktuellartikel == 1) {
		$NewAktuellArtikel = 0;
	} else {
		$NewAktuellArtikel = 1;
	}

	// neues Startseitenangebot setzen
	$SQLString = "UPDATE " . TABLE_ARTIKEL . " SET ";
	$SQLString .= TABLE_ARTIKEL . ".aktuellartikel = '" . $NewAktuellArtikel . "' ";
	$SQLString .= "WHERE " . TABLE_ARTIKEL . ".id = '" . $ArtikelID . "'";

	$MySQLQuerryReferenz = errorlogged_mysql_query($SQLString);

	// ********************************************************************************
	// ** Merkmalkombinationen abgleichen
	// ********************************************************************************
	UpdateMerkmalkombination($ArtikelID);

}



// ********************************************************************************
// ** SetArtikelOnlinestatus
// ********************************************************************************
function SetArtikelOnlinestatus($ArtikelID) {

	// aktuellen Onlinestatus ermitteln
	$ArtikelObject = GetArtikelDetail($ArtikelID);

	// neuen Onlinestatus ermitteln
	if ($ArtikelObject->aktiv == 1) {
		$NewOnlinestatus = 0;
	} else {
		$NewOnlinestatus = 1;
	}

	// eine Artikel-Gruppe kann nur aktiviert werden, wenn alle Teilartikel aktiv sind
	if($NewOnlinestatus == 1 && $ArtikelObject->gruppenartikel)
	{
		$SQLString = "SELECT " . TABLE_ARTIKEL . ".id, " . TABLE_ARTIKEL_LANGU . ".artikel_name ";
		$SQLString .= "FROM " . TABLE_ARTIKEL . " INNER JOIN " . TABLE_ARTIKEL_GRUPPEN . " ON " . TABLE_ARTIKEL . ".id = " . TABLE_ARTIKEL_GRUPPEN . ".teilartikel_id ";
		$SQLString .= " LEFT JOIN " . TABLE_ARTIKEL_LANGU . " ON " . TABLE_ARTIKEL . ".id = " . TABLE_ARTIKEL_LANGU . ".artikel_id ";
		$SQLString .= "WHERE " . TABLE_ARTIKEL_GRUPPEN . ".gruppenartikel_id =" . $ArtikelID;
		$SQLString .= " AND " . TABLE_ARTIKEL_LANGU . ".language_id = " . GetDefaultLanguageID();
		$SQLString .= " AND " . TABLE_ARTIKEL . ".aktiv = 0";
		$result = mysql_query($SQLString);

		$artikel = mysql_fetch_assoc($result);
		if($artikel)
		{
			$resultarray['result'] = 'group_not_activateable';
			do
			{
				$resultarray['artikel'][] = $artikel;
			}
			while($artikel = mysql_fetch_assoc($result));
			return $resultarray;
		}
	}

	// neuen Onlinestatus setzen
	$SQLString = "UPDATE " . TABLE_ARTIKEL . " SET ";
	$SQLString .= TABLE_ARTIKEL . ".aktiv = '" . $NewOnlinestatus . "' ";
	$SQLString .= "WHERE " . TABLE_ARTIKEL . ".id = '" . $ArtikelID . "'";

	$MySQLQuerryReferenz = errorlogged_mysql_query($SQLString);

	// wenn es eine Merkmalkombination ist
	if ($ArtikelObject->merkmalkombination) {

		$SQLString = "UPDATE " . TABLE_ARTIKEL . " SET ";
		$SQLString .= TABLE_ARTIKEL . ".aktiv = '" . $NewOnlinestatus . "' ";
		$SQLString .= "WHERE " . TABLE_ARTIKEL . ".id = '" . $ArtikelObject->merkmalkombination . "'";

		$MySQLQuerryReferenz = errorlogged_mysql_query($SQLString);

	}

	if($NewOnlinestatus == 0)
	{
		// Artikelgruppen raussuchen in denen dieser Artikel enthalten ist
		$SQLString = "SELECT " .TABLE_ARTIKEL_GRUPPEN . ".gruppenartikel_id, " . TABLE_ARTIKEL_LANGU . ".artikel_name FROM " . TABLE_ARTIKEL_GRUPPEN;
		$SQLString .= " LEFT JOIN " . TABLE_ARTIKEL_LANGU . " ON " . TABLE_ARTIKEL_GRUPPEN . ".gruppenartikel_id = " . TABLE_ARTIKEL_LANGU . ".artikel_id";
		$SQLString .= " WHERE " . TABLE_ARTIKEL_GRUPPEN . ".teilartikel_id = '" . $ArtikelID . "'";
		$SQLString .= " AND " . TABLE_ARTIKEL_LANGU . ".language_id = " . GetDefaultLanguageID();
		$result = mysql_query($SQLString);

		// diese deaktivieren
		while($group = mysql_fetch_assoc($result))
		{
			$SQLString = "UPDATE " . TABLE_ARTIKEL . " SET ";
			$SQLString .= TABLE_ARTIKEL . ".aktiv = " . $NewOnlinestatus;
			$SQLString .= " WHERE " . TABLE_ARTIKEL . ".id = " . $group['gruppenartikel_id'];
//			echo $SQLString;
			mysql_query($SQLString);

			$deactivated_groups[] = $group;
		}
		if($deactivated_groups)
		{
			$resultarray = array('result' => 'groups_deactivated', 'groups' => $deactivated_groups);
			return $resultarray;
		}
	}
	return array('result' => $NewOnlinestatus);

}

// ********************************************************************************
// ** SetArtikelLieferstatus
// ********************************************************************************
function SetArtikelLieferstatus($ArtikelID) {

	// aktuellen Lieferstatus ermitteln
	$ArtikelObject = GetArtikelDetail($ArtikelID);
	$LieferstatusObject = GetLieferstatusDetail($ArtikelObject->lieferstatus);

	// neuen Lieferstatus ermitteln
	$LieferstatusDataArray = GetLieferstatusDataArray("", "", TABLE_LIEFERSTATUS . ".sort", "ASC");

	foreach ($LieferstatusDataArray as $LieferstatusData) {

		if (!$NewLieferstatusID && ($LieferstatusData["sort"] > $LieferstatusObject->sort)) {
			$NewLieferstatusID = $LieferstatusData["id"];
		}

	}

	if (!$NewLieferstatusID) {
		$NewLieferstatusID = $LieferstatusDataArray[0]["id"];
	}

	// neuen Lieferstatus setzen
	$SQLString = "UPDATE " . TABLE_ARTIKEL . " SET ";
	$SQLString .= TABLE_ARTIKEL . ".lieferstatus = '" . $NewLieferstatusID . "' ";
	$SQLString .= "WHERE " . TABLE_ARTIKEL . ".id = '" . $ArtikelID . "'";

	$MySQLQuerryReferenz = errorlogged_mysql_query($SQLString);

	// wenn es eine Merkmalkombination ist
	if ($ArtikelObject->merkmalkombination) {

		$SQLString = "UPDATE " . TABLE_ARTIKEL . " SET ";
		$SQLString .= TABLE_ARTIKEL . ".lieferstatus = '" . $NewLieferstatusID . "' ";
		$SQLString .= "WHERE " . TABLE_ARTIKEL . ".id = '" . $ArtikelObject->merkmalkombination . "'";

		$MySQLQuerryReferenz = errorlogged_mysql_query($SQLString);

	}

}

// ********************************************************************************
// ** GetArtikelDetail
// ********************************************************************************
function GetArtikelDetail($ArtikelID, $KundenEmail = "", $FilterAktiv = 0, $LanguageID = 0) {

	global $l_ab;

	// Kundengruppe ermitteln
	if (!$KundenEmail) {
		$KundengruppenID = GetDefaultKundengruppe();
		$KundengruppenObject = GetKundengruppenDetail($KundengruppenID);
	} else {
		$KundengruppenObject = GetKundengruppenDetail("", $KundenEmail);
	}

	if(!$KundengruppenObject) {
		$KundengruppenID = GetDefaultKundengruppe();
		$KundengruppenObject = GetKundengruppenDetail($KundengruppenID);
	}

	// Währung einlesen
	$WaehrungObject = GetWaehrungDetail();

	// Sprache ermitteln
	if (!$LanguageID) {
		$LanguageID = GetDefaultLanguageID();
	}

	$StandardLanguageID = GetDefaultLanguageID();

	// SQL-String für den Artikel zusammensetzen
	$SQLString = "SELECT DISTINCT ";
	$SQLString .= TABLE_ARTIKEL . ".id, ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".artikel_name AS admin_artikel_name, ";
	$SQLString .= "table_artikel_langu_standard.artikel_name AS standard_artikel_name, ";
	$SQLString .= "IFNULL(" . TABLE_ARTIKEL_LANGU . ".artikel_name, table_artikel_langu_standard.artikel_name) AS artikel_name, ";
	$SQLString .= TABLE_ARTIKEL . ".artikel_nr, ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".beschreibung AS admin_beschreibung, ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".kurz_beschreibung AS admin_kurz_beschreibung, ";
	$SQLString .= "IFNULL(" . TABLE_ARTIKEL_LANGU . ".beschreibung, table_artikel_langu_standard.beschreibung) AS beschreibung, ";
	$SQLString .= "IFNULL(" . TABLE_ARTIKEL_LANGU . ".kurz_beschreibung, table_artikel_langu_standard.kurz_beschreibung) AS kurz_beschreibung, ";
	$SQLString .= "table_artikel_langu_standard.beschreibung AS standard_beschreibung, ";
	$SQLString .= "table_artikel_langu_standard.kurz_beschreibung AS standard_kurz_beschreibung, ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".url_artikel_name_vorgabe, ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".url_artikel_name, ";
	$SQLString .= TABLE_ARTIKEL . ".mwst, ";
	$SQLString .= TABLE_MWST . ".mwst AS mwstsatz, ";
	$SQLString .= TABLE_ARTIKEL . ".preis_alt_netto, ";
	$SQLString .= TABLE_ARTIKEL . ".preis_alt_brutto, ";
	$SQLString .= TABLE_ARTIKEL . ".preis_netto, ";
	$SQLString .= TABLE_ARTIKEL . ".preis_brutto, ";
    $SQLString .= "IF(" . TABLE_ARTIKEL . ".image_language_independent, table_artikel_langu_standard.bigImage, " . TABLE_ARTIKEL_LANGU . ".bigImage) AS bigImage_parent, ";
    $SQLString .= "IF(" . TABLE_ARTIKEL . ".image_language_independent, table_artikel_langu_standard.smallImage, " . TABLE_ARTIKEL_LANGU . ".smallImage) AS smallImage_parent, ";
    $SQLString .= "IF(" . TABLE_ARTIKEL . ".image_language_independent, images_standard.bigImage, images.bigImage) AS bigImage_merkmalkombi, ";
    $SQLString .= "IF(" . TABLE_ARTIKEL . ".image_language_independent, images_standard.smallImage, images.smallImage) AS smallImage_merkmalkombi, ";
    $SQLString .= TABLE_ARTIKEL . ".variante1, ";
	$SQLString .= "table_variante_langu1.name AS variante1name, ";
	$SQLString .= TABLE_ARTIKEL . ".variante2, ";
	$SQLString .= "table_variante_langu2.name AS variante2name, ";
	$SQLString .= TABLE_ARTIKEL . ".variante3, ";
	$SQLString .= "table_variante_langu3.name AS variante3name, ";
	$SQLString .= TABLE_ARTIKEL . ".variante4, ";
	$SQLString .= "table_variante_langu4.name AS variante4name, ";
	$SQLString .= TABLE_ARTIKEL . ".aktiv, ";
	$SQLString .= TABLE_ARTIKEL . ".lieferstatus, ";
	$SQLString .= TABLE_ARTIKEL . ".hersteller_id, ";
	$SQLString .= TABLE_ARTIKEL . ".angebote, ";
	$SQLString .= TABLE_ARTIKEL . ".lager, ";
	$SQLString .= TABLE_ARTIKEL . ".meldebestand, ";
	$SQLString .= TABLE_ARTIKEL . ".lager_bestellungen, ";
	$SQLString .= TABLE_ARTIKEL . ".gewicht, ";
	$SQLString .= TABLE_ARTIKEL . ".ean, ";
	$SQLString .= TABLE_ARTIKEL . ".voe_datum, ";
	$SQLString .= TABLE_ARTIKEL . ".startseitenangebot, ";
	$SQLString .= TABLE_ARTIKEL . ".aktuellartikel, ";
	$SQLString .= TABLE_ARTIKEL . ".merkmalauswahl, ";
	$SQLString .= TABLE_ARTIKEL . ".kundengruppenpreis, ";
	$SQLString .= TABLE_ARTIKEL . ".merkmalkombination, ";
	$SQLString .= TABLE_ARTIKEL . ".merkmalkombinationsort, ";
	$SQLString .= TABLE_ARTIKEL . ".merkmalkombinationstandard, ";
	$SQLString .= TABLE_ARTIKEL . ".merkmalkombinationparentid, ";
	$SQLString .= TABLE_ARTIKEL . ".artikel_download, ";
	$SQLString .= "MIN(" . TABLE_ARTIKEL_PREISSTAFFEL . ".preis_brutto) as min_staffel_brutto, ";
	$SQLString .= "MIN(" . TABLE_ARTIKEL_PREISSTAFFEL . ".preis_netto) as min_staffel_netto, ";
	$SQLString .= TABLE_ARTIKEL . ".image_language_independent, ";
	$SQLString .= TABLE_ARTIKEL . ".download_language_independent, ";
	$SQLString .= TABLE_ARTIKEL . ".giveaway, ";
	$SQLString .= TABLE_ARTIKEL . ".giveaway_min_bestellwert, ";
	$SQLString .= TABLE_ARTIKEL . ".giveaway_rabattprozent, ";
	$SQLString .= TABLE_LIEFERSTATUS . ".verkaufstop, ";
	$SQLString .= TABLE_LIEFERSTATUS . ".id AS lieferstatus_id, ";
	$SQLString .= "IFNULL(" . TABLE_LIEFERSTATUS_LANGU . ".name, table_lieferstatus_langu_standard.name) AS lieferstatus_name, ";
	$SQLString .= TABLE_LIEFERSTATUS . ".imagesmall AS lieferstatus_imagesmall, ";
	$SQLString .= "IF(" . TABLE_LIEFERSTATUS . ".language_independent, table_lieferstatus_langu_standard.image, " . TABLE_LIEFERSTATUS_LANGU . ".image) AS lieferstatus_image, ";
	$SQLString .= "NullLieferstatus.id AS nulllieferstatusid, ";
	$SQLString .= "IFNULL(NullLieferstatusLangu.name, NullLieferstatusLanguStandard.name) AS nulllieferstatus_name, ";
	$SQLString .= "NullLieferstatus.imagesmall AS nulllieferstatus_imagesmall, ";
	$SQLString .= "IF(NullLieferstatus.language_independent, NullLieferstatusLanguStandard.image, NullLieferstatusLangu.image) AS nulllieferstatus_image, ";
	$SQLString .= TABLE_KUNDENGRUPPENPREISE . ".preis_netto AS kundengruppenpreis_netto, ";
	$SQLString .= TABLE_KUNDENGRUPPENPREISE . ".preis_brutto AS kundengruppenpreis_brutto, ";
	$SQLString .= TABLE_ARTIKEL . ".gruppenartikel, ";
	$SQLString .= TABLE_ARTIKEL . ".einheit_groesse, ";
	$SQLString .= TABLE_ARTIKEL . ".einheit_masseinheit, ";
    $SQLString .= TABLE_ARTIKEL . ".kundengruppe_id ";
    if(UPLOAD) {
		$SQLString .= ", " . TABLE_ARTIKEL . ".upload ";
	}
    $SQLString .= ", " . TABLE_ARTIKEL . ".highlight_id ";
    $SQLString .= ", " . TABLE_ARTIKEL . ".highlight_preis_brutto ";
    $SQLString .= ", " . TABLE_ARTIKEL . ".highlight_preis_netto ";
    $SQLString .= ", UNIX_TIMESTAMP(" . TABLE_ARTIKEL . ".highlight_enddatum) as highlight_enddatum_ts ";
    $SQLString .= ", DATE_FORMAT(" . TABLE_ARTIKEL . ".highlight_enddatum, '%d.%m.%Y') as highlight_enddatum_format ";

    $SQLString .= ", IF(" . TABLE_ARTIKEL . ".highlight_enddatum > NOW()," . TABLE_HIGHLIGHTS . ".css_class, '') as highlight_css_class ";
    $SQLString .= ", IF(" . TABLE_ARTIKEL . ".highlight_enddatum > NOW(), IFNULL(" . TABLE_HIGHLIGHTS_LANGU . ".name, table_highlights_langu_standard.name), '') AS highlight_name ";

    $SQLString .= "FROM " . TABLE_ARTIKEL . " ";
	$SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_LANGU . " ON ((IF(" . TABLE_ARTIKEL . ".merkmalkombinationparentid, " . TABLE_ARTIKEL . ".merkmalkombinationparentid, " . TABLE_ARTIKEL . ".id) = " . TABLE_ARTIKEL_LANGU . ".artikel_id) AND (" . TABLE_ARTIKEL_LANGU . ".language_id = " . $LanguageID . ")) ";
	$SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_LANGU . " table_artikel_langu_standard ON ((IF(" . TABLE_ARTIKEL . ".merkmalkombinationparentid, " . TABLE_ARTIKEL . ".merkmalkombinationparentid, " . TABLE_ARTIKEL . ".id) = table_artikel_langu_standard.artikel_id) AND (table_artikel_langu_standard.language_id = " . $StandardLanguageID . ")) ";
    $SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_LANGU . " images ON (IF(" . TABLE_ARTIKEL . ".merkmalkombination, " . TABLE_ARTIKEL . ".merkmalkombination, " . TABLE_ARTIKEL . ".id) = images.artikel_id) AND (images.language_id = " . $LanguageID . ") ";
    $SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_LANGU . " images_standard ON (IF(" . TABLE_ARTIKEL . ".merkmalkombination, " . TABLE_ARTIKEL . ".merkmalkombination, " . TABLE_ARTIKEL . ".id) = images_standard.artikel_id) AND (images_standard.language_id = " . $StandardLanguageID . ") ";
    $SQLString .= "LEFT JOIN " . TABLE_LIEFERSTATUS . " ON " . TABLE_ARTIKEL . ".lieferstatus = " . TABLE_LIEFERSTATUS . ".id ";
	$SQLString .= "LEFT JOIN " . TABLE_LIEFERSTATUS_LANGU . " ON ((" . TABLE_LIEFERSTATUS . ".id = " . TABLE_LIEFERSTATUS_LANGU . ".lieferstatus_id) AND (" . TABLE_LIEFERSTATUS_LANGU . ".language_id = " . $LanguageID . ")) ";
	$SQLString .= "LEFT JOIN " . TABLE_LIEFERSTATUS_LANGU . " table_lieferstatus_langu_standard ON (( " . TABLE_LIEFERSTATUS . ".id = table_lieferstatus_langu_standard.lieferstatus_id) AND (table_lieferstatus_langu_standard.language_id = " . $StandardLanguageID . ")) ";
	$SQLString .= "LEFT JOIN " . TABLE_LIEFERSTATUS . " AS NullLieferstatus ON " . TABLE_LIEFERSTATUS . ".nichtauflager = NullLieferstatus.id ";
	$SQLString .= "LEFT JOIN " . TABLE_LIEFERSTATUS_LANGU . " AS NullLieferstatusLangu ON ((NullLieferstatus.id = NullLieferstatusLangu.lieferstatus_id) AND (NullLieferstatusLangu.language_id = " . $LanguageID . ")) ";
	$SQLString .= "LEFT JOIN " . TABLE_LIEFERSTATUS_LANGU . " AS NullLieferstatusLanguStandard ON ((NullLieferstatus.id = NullLieferstatusLanguStandard.lieferstatus_id) AND (NullLieferstatusLanguStandard.language_id = " . $StandardLanguageID . ")) ";
	$SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_PREISSTAFFEL . " ON " . TABLE_ARTIKEL . ".id = " . TABLE_ARTIKEL_PREISSTAFFEL . ".artikel_id ";
	$SQLString .= "LEFT JOIN " . TABLE_VARIANTE . " table_variante1 ON  " . TABLE_ARTIKEL . ".variante1 = table_variante1.id ";
	$SQLString .= "LEFT JOIN " . TABLE_VARIANTE_LANGU . " table_variante_langu1 ON ((table_variante1.id = table_variante_langu1.variante_id) AND (table_variante_langu1.language_id = " . $LanguageID . ")) ";
	$SQLString .= "LEFT JOIN " . TABLE_VARIANTE . " table_variante2 ON  " . TABLE_ARTIKEL . ".variante2 = table_variante2.id ";
	$SQLString .= "LEFT JOIN " . TABLE_VARIANTE_LANGU . " table_variante_langu2 ON ((table_variante2.id = table_variante_langu2.variante_id) AND (table_variante_langu2.language_id = " . $LanguageID . ")) ";
	$SQLString .= "LEFT JOIN " . TABLE_VARIANTE . " table_variante3 ON  " . TABLE_ARTIKEL . ".variante3 = table_variante3.id ";
	$SQLString .= "LEFT JOIN " . TABLE_VARIANTE_LANGU . " table_variante_langu3 ON ((table_variante3.id = table_variante_langu3.variante_id) AND (table_variante_langu3.language_id = " . $LanguageID . ")) ";
	$SQLString .= "LEFT JOIN " . TABLE_VARIANTE . " table_variante4 ON  " . TABLE_ARTIKEL . ".variante4 = table_variante4.id ";
	$SQLString .= "LEFT JOIN " . TABLE_VARIANTE_LANGU . " table_variante_langu4 ON ((table_variante4.id = table_variante_langu4.variante_id) AND (table_variante_langu4.language_id = " . $LanguageID . ")) ";
	$SQLString .= "LEFT JOIN " . TABLE_MWST . " ON  " . TABLE_ARTIKEL . ".mwst = " . TABLE_MWST . ".id ";
	$SQLString .= "LEFT JOIN " . TABLE_KUNDENGRUPPENPREISE . " ON ((" . TABLE_ARTIKEL . ".id = " . TABLE_KUNDENGRUPPENPREISE . ".artikelid) AND (" . TABLE_KUNDENGRUPPENPREISE . ".kundengruppenid = " . $KundengruppenObject->id . ")) ";
    $SQLString .= "LEFT JOIN " . TABLE_HIGHLIGHTS . " ON " . TABLE_HIGHLIGHTS . ".id = " . TABLE_ARTIKEL . ".highlight_id ";
    $SQLString .= "LEFT JOIN " . TABLE_HIGHLIGHTS_LANGU . " ON " . TABLE_HIGHLIGHTS . ".id = " . TABLE_HIGHLIGHTS_LANGU . ".highlight_id AND " . TABLE_HIGHLIGHTS_LANGU . ".language_id = '" . $LanguageID . "' ";
    $SQLString .= "LEFT JOIN " . TABLE_HIGHLIGHTS_LANGU . " table_highlights_langu_standard ON " . TABLE_HIGHLIGHTS . ".id = table_highlights_langu_standard.highlight_id AND table_highlights_langu_standard.language_id = '" . $StandardLanguageID . "' ";
    $SQLString .= "WHERE " . TABLE_ARTIKEL . ".id = '" . $ArtikelID . "' ";
	$SQLString .= "GROUP BY " . TABLE_ARTIKEL . ".id";

//	echo '$SQLString: ' . $SQLString . '<br>';

	// Artikel abfragen
	$ArtikelObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));

    if($ArtikelObject->smallImage_merkmalkombi) {
        $ArtikelObject->smallImage = $ArtikelObject->smallImage_merkmalkombi;
    } else {
        $ArtikelObject->smallImage = $ArtikelObject->smallImage_parent;
    }

    if($ArtikelObject->bigImage_merkmalkombi) {
        $ArtikelObject->bigImage = $ArtikelObject->bigImage_merkmalkombi;
    } else {
        $ArtikelObject->bigImage = $ArtikelObject->bigImage_parent;
    }

    // Kategorien einlesen
	$ArtikelObject->kategorie_array = GetKatgorieDataArray($ArtikelObject->id);

	foreach ($ArtikelObject->kategorie_array as $KategorieData) {
		$ArtikelObject->kategoriename_array[] = $KategorieData["name"];
	}

	// formatiertes Ver�ffentlichungsdatum
	if ($ArtikelObject->voe_datum != 0) {
		if (strtotime($ArtikelObject->voe_datum) > time()) {
			$ArtikelObject->voe_datum_format = date("d.m.Y", strtotime($ArtikelObject->voe_datum));
		}
	}

	// formatierter Preis für den Shop
	if ($ArtikelObject->kundengruppenpreis_netto) {
		$ArtikelObject->preis_brutto = $ArtikelObject->kundengruppenpreis_brutto;
		$ArtikelObject->preis_netto = $ArtikelObject->kundengruppenpreis_netto;

		// Bruttopreis
		if ($KundengruppenObject->type == 1) {
			$ArtikelObject->preis = $ArtikelObject->kundengruppenpreis_brutto;
			$ArtikelObject->preis_format = number_format($ArtikelObject->kundengruppenpreis_brutto, 2, ",", ".") . " " . $WaehrungObject->symbol;
			$ArtikelObject->staffel = $ArtikelObject->min_staffel_brutto;
			$ArtikelObject->staffel_format = $l_ab . ' ' . number_format($ArtikelObject->min_staffel_brutto, 2, ",", ".") . " " . $WaehrungObject->symbol;
            $ArtikelObject->highlight_preis = $ArtikelObject->highlight_preis_brutto;
            // Nettopreis
		} else {
			$ArtikelObject->preis = $ArtikelObject->kundengruppenpreis_netto;
			$ArtikelObject->preis_format = number_format($ArtikelObject->kundengruppenpreis_netto, 2, ",", ".") . " " . $WaehrungObject->symbol;
			$ArtikelObject->staffel = $ArtikelObject->min_staffel_netto;
			$ArtikelObject->staffel_format = $l_ab . ' ' . number_format($ArtikelObject->min_staffel_netto, 2, ",", ".") . " " . $WaehrungObject->symbol;
            $ArtikelObject->highlight_preis = $ArtikelObject->highlight_preis_netto;
        }

	} else {
        if($ArtikelObject->highlight_id && $ArtikelObject->highlight_enddatum_ts > time() && $ArtikelObject->highlight_preis_brutto) {

            if ($KundengruppenObject->type == 1) {
                $ArtikelObject->preis = $ArtikelObject->highlight_preis_brutto;
                $ArtikelObject->preis_format = number_format($ArtikelObject->highlight_preis_brutto, 2, ",", ".") . " " . $WaehrungObject->symbol;
                $ArtikelObject->staffel = $ArtikelObject->min_staffel_brutto;
                $ArtikelObject->staffel_format = $l_ab . ' ' . number_format($ArtikelObject->min_staffel_brutto, 2, ",", ".") . " " . $WaehrungObject->symbol;
                $ArtikelObject->highlight_preis = $ArtikelObject->highlight_preis_brutto;

                // Nettopreis
            } else {
                $ArtikelObject->preis = $ArtikelObject->highlight_preis_netto;
                $ArtikelObject->preis_format = number_format($ArtikelObject->highlight_preis_netto, 2, ",", ".") . " " . $WaehrungObject->symbol;
                $ArtikelObject->staffel = $ArtikelObject->min_staffel_netto;
                $ArtikelObject->staffel_format = $l_ab . ' ' . number_format($ArtikelObject->min_staffel_netto, 2, ",", ".") . " " . $WaehrungObject->symbol;
                $ArtikelObject->highlight_preis = $ArtikelObject->highlight_preis_netto;
            }
        } else {
            // Bruttopreis
            if ($KundengruppenObject->type == 1) {
                $ArtikelObject->preis = $ArtikelObject->preis_brutto;
                $ArtikelObject->preis_format = number_format($ArtikelObject->preis_brutto, 2, ",", ".") . " " . $WaehrungObject->symbol;
                $ArtikelObject->staffel = $ArtikelObject->min_staffel_brutto;
                $ArtikelObject->staffel_format = $l_ab . ' ' . number_format($ArtikelObject->min_staffel_brutto, 2, ",", ".") . " " . $WaehrungObject->symbol;
                $ArtikelObject->highlight_preis = $ArtikelObject->highlight_preis_brutto;

                // Nettopreis
            } else {
                $ArtikelObject->preis = $ArtikelObject->preis_netto;
                $ArtikelObject->preis_format = number_format($ArtikelObject->preis_netto, 2, ",", ".") . " " . $WaehrungObject->symbol;
                $ArtikelObject->staffel = $ArtikelObject->min_staffel_netto;
                $ArtikelObject->staffel_format = $l_ab . ' ' . number_format($ArtikelObject->min_staffel_netto, 2, ",", ".") . " " . $WaehrungObject->symbol;
                $ArtikelObject->highlight_preis = $ArtikelObject->highlight_preis_netto;
            }
        }

	}

	// formatierter alter Preis fuer den Shop
	if ($ArtikelObject->preis_alt_brutto > 0) {
		if ($KundengruppenObject->type == 1) {
			$ArtikelObject->preis_alt_format = number_format($ArtikelObject->preis_alt_brutto, 2, ",", ".") . " " . $WaehrungObject->symbol;
		} else {
			$ArtikelObject->preis_alt_format = number_format($ArtikelObject->preis_alt_netto, 2, ",", ".") . " " . $WaehrungObject->symbol;
		}
	}

	// kleines Bild formatieren
	if ($ArtikelObject->smallImage && file_exists(DATEIPFAD . "/images/dbimages/" . $ArtikelObject->smallImage)) {
		$ImageSizeArray = getimagesize(DATEIPFAD . "/images/dbimages/" . $ArtikelObject->smallImage);
		$ArtikelObject->imagesmall_imagestring = "<img src=\"" . URLPFAD . "images/dbimages/" . $ArtikelObject->smallImage . "\" width=\"" . $ImageSizeArray[0] . "\" height=\"" . $ImageSizeArray[1] . "\" alt=\"" . $ArtikelObject->artikel_name . "\" />";
		$ArtikelObject->imagesmall_width = $ImageSizeArray[0];
		$ArtikelObject->imagesmall_height = $ImageSizeArray[1];

        // Bestellübersichtsausgabe
        $faktor = min(1, 60 / $ImageSizeArray[0], 60 / $ImageSizeArray[1]);
        $zielbreite = round($ImageSizeArray[0] * $faktor);
        $zielhoehe = round($ImageSizeArray[1] * $faktor);
        $ArtikelObject->image_klein_format = "<img src=\"" . URLPFAD . "images/dbimages/" . $ArtikelObject->smallImage . "\" width=\"" . $zielbreite . "\" height=\"" . $zielhoehe . "\" alt=\"" . $ArtikelObject->artikel_name . "\" />";


    }

	// großes Bild formatieren
	if ($ArtikelObject->bigImage && file_exists(DATEIPFAD . "/images/dbimages/" . $ArtikelObject->bigImage)) {
		$ImageSizeArray = getimagesize(DATEIPFAD . "/images/dbimages/" . $ArtikelObject->bigImage);
		$ArtikelObject->imagebig_imagestring = "<img src=\"" . URLPFAD . "images/dbimages/" . $ArtikelObject->bigImage . "\" width=\"" . $ImageSizeArray[0] . "\" height=\"" . $ImageSizeArray[1] . "\" alt=\"" . $ArtikelObject->artikel_name . "\" />";

		// Adminausgabe
		$faktor = min(1, 150 / $ImageSizeArray[0], 150 / $ImageSizeArray[1]);
		$zielbreite = round($ImageSizeArray[0] * $faktor);
		$zielhoehe = round($ImageSizeArray[1] * $faktor);
		$ArtikelObject->imagebig_imagestring_admin = "<img src=\"" . URLPFAD . "images/dbimages/" . $ArtikelObject->bigImage . "\" width=\"" . $zielbreite . "\" height=\"" . $zielhoehe . "\" alt=\"" . $ArtikelObject->artikel_name . "\" />";

        // Detailseite
        $faktor = min(1, 300 / $ImageSizeArray[0], 300 / $ImageSizeArray[1]);
        $zielbreite = round($ImageSizeArray[0] * $faktor);
        $zielhoehe = round($ImageSizeArray[1] * $faktor);
        $ArtikelObject->imagebig_imagestring_format = "<img src=\"" . URLPFAD . "images/dbimages/" . $ArtikelObject->bigImage . "\" width=\"" . $zielbreite . "\" height=\"" . $zielhoehe . "\" alt=\"" . $ArtikelObject->artikel_name . "\" />";

		$ArtikelObject->imagebig_width = $ImageSizeArray[0];
		$ArtikelObject->imagebig_height = $ImageSizeArray[1];
	}

	// großes Lieferstatusbild
	if ($ArtikelObject->lager_bestellungen > 0 || !$ArtikelObject->nulllieferstatusid) {

		if ($ArtikelObject->lieferstatus_image && file_exists(DATEIPFAD . "/images/dbimages/" . $ArtikelObject->lieferstatus_image)) {
			$ImageSizeArray = getimagesize(DATEIPFAD . "/images/dbimages/" . $ArtikelObject->lieferstatus_image);
			$ArtikelObject->lieferstatus_imagestring = "<img src=\"" . URLPFAD . "images/dbimages/" . $ArtikelObject->lieferstatus_image . "\" width=\"" . $ImageSizeArray[0] . "\" height=\"" . $ImageSizeArray[1] . "\" alt=\"" . $ArtikelObject->lieferstatus_name . "\" />";
		} else {
			$ArtikelObject->lieferstatus_imagestring = $ArtikelObject->lieferstatus_name;
		}

	} else {

		if ($ArtikelObject->nulllieferstatus_image && file_exists(DATEIPFAD . "/images/dbimages/" . $ArtikelObject->nulllieferstatus_image)) {
			$ImageSizeArray = getimagesize(DATEIPFAD . "/images/dbimages/" . $ArtikelObject->nulllieferstatus_image);
			$ArtikelObject->lieferstatus_imagestring = "<img src=\"" . URLPFAD . "images/dbimages/" . $ArtikelObject->nulllieferstatus_image . "\" width=\"" . $ImageSizeArray[0] . "\" height=\"" . $ImageSizeArray[1] . "\" alt=\"" . $ArtikelObject->lieferstatus_name . "\" />";
		} else {
			$ArtikelObject->lieferstatus_imagestring = $ArtikelObject->nulllieferstatus_name;
		}
        $ArtikelObject->lieferstatus_name = $ArtikelObject->nulllieferstatus_name;
	}


	// Varianten

	// Artikel mit Merkmalkombinationen
	if ($ArtikelObject->merkmalkombination || $ArtikelObject->merkmalkombinationparentid) {

		$MerkmalkombinationenSearchField[] = "merkmalkombinationparentid";

		if ($ArtikelObject->merkmalkombinationparentid) {
			$MerkmalkombinationenSearchString[] = $ArtikelObject->merkmalkombinationparentid;
		} else {
			$MerkmalkombinationenSearchString[] = $ArtikelObject->id;
		}

		$MerkmalkombinationenSortField = "merkmalkombinationsort";
		$MerkmalkombinationenSortOrder = "ASC";

		$MerkmalkombinationenDataArray = GetArtikelDataArray($MerkmalkombinationenSearchField, $MerkmalkombinationenSearchString, $MerkmalkombinationenSortField, $MerkmalkombinationenSortOrder, "", "", "", "", "", "", "", "", 0, $FilterAktiv, "", "", 2);

		$MerkmalkombinationenCounter = 0;

		foreach ($MerkmalkombinationenDataArray as $MerkmalkombinationenData) {

			$MerkmalString = "";

			foreach ($MerkmalkombinationenData["varianten_array"] as $Merkmalkombinationen) {
				$MerkmalString .= $Merkmalkombinationen["merkmalname"] . " / ";
			}

			$MerkmalString = substr($MerkmalString, 0, strlen($MerkmalString) - 3);

			$ArtikelObject->varianten_array[1][$MerkmalkombinationenCounter]["merkmalid"] = $MerkmalkombinationenData["id"];
			$ArtikelObject->varianten_array[1][$MerkmalkombinationenCounter]["merkmalname"] = $MerkmalString;
			$MerkmalkombinationenCounter++;
		}


	// Artikel mit normalen Varianten
	} else {

		$MerkmalauswahlDataArray = GetMerkmalauswahlDataArray($ArtikelObject->id, $LanguageID);

		if ($MerkmalauswahlDataArray) {

			foreach ($MerkmalauswahlDataArray as $MerkmalauswahlKey => $MerkmalArray) {

				$MerkmalCounter = 0;

				foreach ($MerkmalArray as $Merkmal) {

					if ($Merkmal["selected"]) {
						$ArtikelObject->varianten_array[$MerkmalauswahlKey][$MerkmalCounter]["merkmalid"] = $Merkmal["id"];
						$ArtikelObject->varianten_array[$MerkmalauswahlKey][$MerkmalCounter]["merkmalname"] = $Merkmal["name"];
						$MerkmalCounter++;
					}

				}

			}

		}

	}

// 	echo '<pre>';
// 	var_dump($ArtikelObject);
// 	echo '</pre>';

	return $ArtikelObject;

}

// ********************************************************************************
// ** DeleteArtikel
// ********************************************************************************
function DeleteArtikel($ArtikelID) {

	// Artikelabh�ngigkeit l�schen
	$SQLString = "DELETE FROM " . TABLE_ARTIKEL_AB . " WHERE artikelid = '" . $ArtikelID . "'";
	$MySQLQuerryReferenz = errorlogged_mysql_query($SQLString);

	// Merkmalauswahl l�schen
	$SQLString = "DELETE FROM " . TABLE_MERKMALAUSWAHL . " WHERE artikelid = '" . $ArtikelID . "'";
	$MySQLQuerryReferenz = errorlogged_mysql_query($SQLString);

	// Merkmalkombinationen
	DeleteMerkmalkombinationen($ArtikelID);

	// Artikeldaten l�schen
	$SQLString = "DELETE FROM " . TABLE_ARTIKEL . " WHERE id = '" . $ArtikelID . "'";
	$MySQLQuerryReferenz = errorlogged_mysql_query($SQLString);

	// Sprachdaten
	$SQLString = "DELETE FROM " . TABLE_ARTIKEL_LANGU . " WHERE artikel_id = '" . $ArtikelID . "'";
	$MySQLQuerryReferenz = errorlogged_mysql_query($SQLString);

	// Kategorien
	$SQLString = "DELETE FROM " . TABLE_KATEGORIERELATION . " WHERE artikelid = '" . $ArtikelID . "'";
	$MySQLQuerryReferenz = errorlogged_mysql_query($SQLString);

	//Download loeschen
	if(ARTIKELDOWNLOAD) {
		DeleteDownloadDetail($ArtikelID);
	}

	// Bilder l�schen
	$ImageName = "artikel_" . sprintf("%07d", $ArtikelID) . "_s*";
	unlink_wc(DATEIPFAD . "images/dbimages/", $ImageName . ".*");

	$ImageName = "artikel_" . sprintf("%07d", $ArtikelID) . "_b*";
	unlink_wc(DATEIPFAD . "images/dbimages/", $ImageName . ".*");

	// Banner
	DeleteBanner("", $ArtikelID);

	// Wunschzettel
	$SQLString = "DELETE FROM " . TABLE_WUNSCHZETTEL . " WHERE artikelid = '" . $ArtikelID . "'";
	$MySQLQuerryReferenz = errorlogged_mysql_query($SQLString);

	// Bewertung
	$SQLString = "DELETE FROM " . TABLE_BEWERTUNG . " WHERE artId = '" . $ArtikelID . "'";
	$MySQLQuerryReferenz = errorlogged_mysql_query($SQLString);

	// Datenblatt
	$SQLString = "SELECT ";
	$SQLString .= TABLE_DATENBLATT . ".datenblatt_id ";
	$SQLString .= "FROM ";
	$SQLString .= TABLE_DATENBLATT . " ";
	$SQLString .= "WHERE ";
	$SQLString .= TABLE_DATENBLATT . ".artikel_id = '" . $ArtikelID . "' ";

	$MySQLQuerryReferenz = errorlogged_mysql_query($SQLString);

	while ($DatenblattRow = mysql_fetch_array($MySQLQuerryReferenz)) {
		DeleteDatenblatt($DatenblattRow["datenblatt_id"]);
	}

	// Preisstaffel
	$SQLString = "DELETE FROM " . TABLE_ARTIKEL_PREISSTAFFEL . " WHERE artikel_id = " . $ArtikelID;
	mysql_query($SQLString);

	// SEO URLs
	DeleteArtikelSEOURL($ArtikelID);

}

function GetArtikelAnzahlPA($params)
{
	extract($params);
	if(!isset($SearchField)) $SearchField = '';
	if(!isset($SearchString)) $SearchString = '';
	if(!isset($KundenEmail)) $KundenEmail = "";
	if(!isset($FilterHerstellerID)) $FilterHerstellerID = "";
	if(!isset($FilterKategorieID)) $FilterKategorieID = "";
	if(!isset($FilterKategorieRekursive)) $FilterKategorieRekursive = "";
	if(!isset($FilterKategorieDoppelt)) $FilterKategorieDoppelt = 0;
	if(!isset($FilterMerkmalkombinationen)) $FilterMerkmalkombinationen = "";
	if(!isset($FilterAktiv)) $FilterAktiv = "";
	if(!isset($FilterWunschzettel)) $FilterWunschzettel = 0;
	if(!isset($OptionSearchAll)) $OptionSearchAll = 0;
	if(!isset($OptionSearchLike)) $OptionSearchLike = 0;
	if(!isset($LanguageID)) $LanguageID = 0;
	if(!isset($FilterArtikelGruppen)) $FilterArtikelGruppen = 0;
	if(!isset($facettensucheAuspraegungenFilter)) $facettensucheAuspraegungenFilter = false;
    if(!isset($LieferantenSearchOption)) $LieferantenSearchOption = 0;
    if(!isset($LieferantenID)) $LieferantenID = 0;
    if(!isset($LieferantenLagerOption)) $LieferantenLagerOption = false;

    // echo '***** ***** ***** *****<br>';
//  echo '$SearchField: ' . $SearchField . "<br>";
//  echo '$SearchString: ' . $SearchString . "<br>";
//  echo '$KundenEmail: ' . $KundenEmail . "<br>";
//  echo '$FilterHerstellerID: ' . $FilterHerstellerID . "<br>";
//  echo '$FilterKategorieID: ' . $FilterKategorieID . "<br>";
//  echo '$FilterKategorieRekursive: ' . $FilterKategorieRekursive . "<br>";
//  echo '$FilterKategorieDoppelt: ' . $FilterKategorieDoppelt . "<br>";
//  echo '$FilterMerkmalkombinationen: ' . $FilterMerkmalkombinationen . "<br>";
//  echo '$FilterAktiv: ' . $FilterAktiv . "<br>";
//  echo '$FilterWunschzettel: ' . $FilterWunschzettel . "<br>";
//  echo '$OptionSearchAll: ' . $OptionSearchAll . "<br>";
//  echo '$OptionSearchLike: ' . $OptionSearchLike . "<br>";

	if(!$LanguageID) {
		$LanguageID = GetDefaultLanguageID();
	}

	// Shopeinstellungen einlesen
	$FeatureObject = GetFeatureDetail();

	// Kunde ermitteln
	if ($KundenEmail) {
		$KundenObject = GetKundenDetail($KundenEmail);
	}

	if($facettensucheAuspraegungenFilter)
	{
		// Kundengruppe ermitteln
		if (!$KundenEmail) {
			$KundengruppenID = GetDefaultKundengruppe();
			$KundengruppenObject = GetKundengruppenDetail($KundengruppenID);
		} else {
			$KundengruppenObject = GetKundengruppenDetail("", $KundenEmail);
		}
	}

	// ********************************************************************************
	// ** SQL-String zum einlesen der Artikelanzahl
	// ********************************************************************************

	// Felder
	$SQLString = "SELECT ";

	// Filter alle Kategorieeintr�ge
	if ($FilterKategorieDoppelt) {
		$SQLString .= "COUNT(" . TABLE_ARTIKEL . ".id) AS ArtikelAnzahl ";
	} else {
		$SQLString .= "COUNT(DISTINCT(" . TABLE_ARTIKEL . ".id)) AS ArtikelAnzahl ";
	}

	$SQLString .= "FROM " . TABLE_ARTIKEL . " ";
	$SQLString .= "LEFT JOIN " . TABLE_KATEGORIERELATION . " ON " . TABLE_ARTIKEL . ".id = " . TABLE_KATEGORIERELATION . ".artikelid ";

	// Filter alle Kategorieeintr�ge
	if ($FilterKategorieDoppelt || $SearchField == TABLE_KATEGORIE_LANGU . '.name') {
		$SQLString .= "LEFT JOIN " . TABLE_KATEGORIE . " ON " . TABLE_KATEGORIERELATION . ".kategorieid = " . TABLE_KATEGORIE . ".id ";
        $SQLString .= "LEFT JOIN " . TABLE_KATEGORIE_LANGU . " ON " . TABLE_KATEGORIE . ".id = " . TABLE_KATEGORIE_LANGU . ".kategorie_id ";
	}

	// Wunschzettel
	if ($FilterWunschzettel && $KundenEmail) {
		$SQLString .= "LEFT JOIN " . TABLE_WUNSCHZETTEL . " ON ((" . TABLE_ARTIKEL . ".id = " . TABLE_WUNSCHZETTEL . ".artikelid) AND (" . TABLE_WUNSCHZETTEL . ".kundenid = " . $KundenObject->id . ")) ";
	}

	$SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_LANGU . " ON ((IF(" . TABLE_ARTIKEL . ".merkmalkombinationparentid, " . TABLE_ARTIKEL . ".merkmalkombinationparentid, " . TABLE_ARTIKEL . ".id) = " . TABLE_ARTIKEL_LANGU . ".artikel_id) AND (" . TABLE_ARTIKEL_LANGU . ".language_id = " . $LanguageID . ")) ";

	// Artikelgruppen herausfiltern
	if ($FilterArtikelGruppen)
	{
		$SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_GRUPPEN . " ON " . TABLE_ARTIKEL . ".id = " . TABLE_ARTIKEL_GRUPPEN . ".gruppenartikel_id ";
	}

	if($FilterArtikelGruppen > 0)
	{
		$SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_GRUPPEN . " ON " . TABLE_ARTIKEL . ".id = " . TABLE_ARTIKEL_GRUPPEN . ".teilartikel_id ";
	}

	if($facettensucheAuspraegungenFilter)
	{
		$SQLString .= "LEFT JOIN " . TABLE_KUNDENGRUPPENPREISE . " ON ((" . TABLE_ARTIKEL . ".id = " . TABLE_KUNDENGRUPPENPREISE . ".artikelid) AND (" . TABLE_KUNDENGRUPPENPREISE . ".kundengruppenid = " . $KundengruppenObject->id . ")) ";
	}

    // Lieferantenzuordnung
    if ($LieferantenSearchOption && $LieferantenID) {
        $SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_LIEFERANTEN . " ON ((IF(" . TABLE_ARTIKEL . ".merkmalkombinationparentid, " . TABLE_ARTIKEL . ".merkmalkombinationparentid, " . TABLE_ARTIKEL . ".id) = " . TABLE_ARTIKEL_LIEFERANTEN . ".artikel_id) AND (" . TABLE_ARTIKEL_LIEFERANTEN . ".lieferanten_id = '" . $LieferantenID . "')) ";
    }

    $SQLString .= " WHERE ( ";

	// Suche
	if (is_array($SearchField) && is_array($SearchString)) {

		// Volltextsuche
		if ($FeatureObject->volltextsuche) {

			if ($OptionSearchLike) {
				$SearchLike = "";
			} else {
				$SearchLike = "*";
			}

			if ($OptionSearchAll) {
				$SearchConnector = "AND ";
			} else {
				$SearchConnector = "OR ";
			}

			$SQLString .= "(";

			foreach ($SearchField as $SearchFieldKey => $SearchFieldValue) {

				$SearchStringArray = explode(" ", $SearchString[$SearchFieldKey]);

				$SearchStringAgainst = "";

				foreach ($SearchStringArray as $SearchStringElement) {

					$SearchStringAgainst .= $SearchLike . $SearchStringElement . $SearchLike . " ";

				}


				$SQLString .= "(MATCH (" . $SearchFieldValue . ") AGAINST ('" . $SearchStringAgainst . "' IN BOOLEAN MODE)) " . $SearchConnector;

				$SQLString = substr($SQLString, 0, strlen($SQLString) - strlen($SearchConnector));
				$SQLString .= " OR ";

			}

			$SQLString = substr($SQLString, 0, strlen($SQLString) - strlen($SearchConnector));
			$SQLString .= ") AND ";

		// normale Suche
		} else {

			if ($OptionSearchLike == 0) {
				$SearchLike = "";
			} else {
				$SearchLike = "%";
			}


//          if ($OptionSearchLike) {
//              $SearchLike = "";
//          } else {
//              $SearchLike = "%";
//          }

			if ($OptionSearchAll) {
				$SearchConnector = "AND ";
			} else {
				$SearchConnector = "OR ";
			}

			$SQLString .= "(";

			foreach ($SearchField as $SearchFieldKey => $SearchFieldValue) {

				if ($OptionSearchExact) {
					$SQLString .= "(" . $SearchFieldValue . " LIKE '" . $SearchLike . $SearchString[$SearchFieldKey] . $SearchLike . "') " . $SearchConnector;
				} else {

					$SearchStringArray = explode(" ", $SearchString[$SearchFieldKey]);

					foreach ($SearchStringArray as $SearchStringElement) {
						$SQLString .= "(" . $SearchFieldValue . " LIKE '" . $SearchLike . $SearchStringElement . $SearchLike . "') " . $SearchConnector;
					}

					$SQLString = substr($SQLString, 0, strlen($SQLString) - strlen($SearchConnector));
					$SQLString .= " OR ";

				}
			}

			$SQLString = substr($SQLString, 0, strlen($SQLString) - strlen($SearchConnector));
			$SQLString .= ") AND ";

		}

	} elseif ($SearchField && $SearchString) {
		$SQLString .= "(" . $SearchField . " LIKE '%" . $SearchString . "%') AND ";
	}

	// Filter Hersteller
	if ($FilterHerstellerID) {
		$SQLString .= "(" . TABLE_ARTIKEL . ".hersteller_id = '" . $FilterHerstellerID . "') AND ";
	}

	// Filter Kategorie
	if ($FilterKategorieID && !$FilterKategorieRekursive) {

		$SQLString .= "(" . TABLE_KATEGORIERELATION . ".kategorieid = '" . $FilterKategorieID . "') AND ";

	} elseif ($FilterKategorieID && $FilterKategorieRekursive) {

		if(is_array($FilterKategorieID))
		{
		$KategorieArray = $FilterKategorieID;
		foreach($FilterKategorieID as $KategorieID)
			$KategorieArray = GetKategorieIDs($KategorieID, $KategorieArray);
		}
		else
		{
			$KategorieArray[] = $FilterKategorieID;
			$KategorieArray = GetKategorieIDs($FilterKategorieID, $KategorieArray);
		}
		$SQLString .= "(" . TABLE_KATEGORIERELATION . ".kategorieid IN (" . implode($KategorieArray, ",") . ")) AND ";
	}

	// Filter Merkmalkombinationen
	if ($FilterMerkmalkombinationen) {
		$SQLString .= "(" . TABLE_ARTIKEL . ".merkmalkombinationparentid = 0) AND ";
	} else {
		$SQLString .= "(" . TABLE_ARTIKEL . ".merkmalkombination = 0) AND ";
	}

	// Filter Aktiv
	if ($FilterAktiv) {
		$SQLString .= "(" . TABLE_ARTIKEL . ".aktiv = '1') AND ";
	}

	// Filter Wunschzettel
	if ($FilterWunschzettel && $KundenEmail) {
		$SQLString .= "(" . TABLE_WUNSCHZETTEL . ".kundenid = '" . $KundenObject->id . "') AND ";
	}

	if($facettensucheAuspraegungenFilter)
	{

		$preisfilter = getFacettensucheFilter(false, false, FACETTENSUCHE_FILTER_AUTO_PREIS);
		$preis_auspraegungen = getFacettenSucheAuspraegungenDataArray($preisfilter['id']);
		foreach($preis_auspraegungen as $preis_auspraegung)
		{
			$key = array_search($preis_auspraegung['id'], $facettensucheAuspraegungenFilter['auspraegungen']);
			if($key !== false)
			{
				$preis_auspraegungen_selected[] = $preis_auspraegung['id'];
				unset($facettensucheAuspraegungenFilter['auspraegungen'][$key]);
			}
		}
		if(isset($preis_auspraegungen_selected))
			$facettensucheAuspraegungenFilter['filter_anzahl']--;

		if($facettensucheAuspraegungenFilter['filter_anzahl'])
			$SQLString .= '(' . $facettensucheAuspraegungenFilter['filter_anzahl'] . ' <= (SELECT COUNT(*) FROM ' . TABLE_FACETTENSUCHE_ARTIKEL_AUSPRAEGUNG . ' WHERE ' . TABLE_FACETTENSUCHE_ARTIKEL_AUSPRAEGUNG . '.artikel_id = ' . TABLE_ARTIKEL . '.id AND ' . TABLE_FACETTENSUCHE_ARTIKEL_AUSPRAEGUNG . '.auspraegung_id IN (' . implode($facettensucheAuspraegungenFilter['auspraegungen'], ',') . '))) AND ';
//        $SQLString .= 'GROUP BY ' . TABLE_ARTIKEL . '.id HAVING GROUP_CONCAT(' . TABLE_FACETTENSUCHE_ARTIKEL_AUSPRAEGUNG . '.auspraegung_id) = \'' . implode($facettensucheAuspraegungenFilter, ',') . '\' ';
		if(isset($preis_auspraegungen_selected))
		{
			$SQLString .= '1 <= ( SELECT COUNT(*) FROM ' . TABLE_FACETTENSUCHE_AUSPRAEGUNGEN . ' WHERE ' . TABLE_FACETTENSUCHE_AUSPRAEGUNGEN . '.id IN (' . implode($preis_auspraegungen_selected, ',') . ') AND IFNULL(' . TABLE_FACETTENSUCHE_AUSPRAEGUNGEN . '.preis_von, 0.0) <= ';
			if ($KundengruppenObject->type == 1)
				$SQLString .= 'IFNULL(' . TABLE_KUNDENGRUPPENPREISE . '.preis_brutto, ' . TABLE_ARTIKEL . '.preis_brutto) ';
			else
				$SQLString .= 'IFNULL(' . TABLE_KUNDENGRUPPENPREISE . '.preis_netto, ' . TABLE_ARTIKEL . '.preis_netto) ';
			$SQLString .= ' AND IFNULL(' . TABLE_FACETTENSUCHE_AUSPRAEGUNGEN . '.preis_bis, 999999999999.99) > ';
			if ($KundengruppenObject->type == 1)
				$SQLString .= 'IFNULL(' . TABLE_KUNDENGRUPPENPREISE . '.preis_brutto, ' . TABLE_ARTIKEL . '.preis_brutto) ';
			else
				$SQLString .= 'IFNULL(' . TABLE_KUNDENGRUPPENPREISE . '.preis_netto, ' . TABLE_ARTIKEL . '.preis_netto) ';
			$SQLString .= ') AND ';
		}
	}

    // Filter Lieferantenzuordnung
    if ($LieferantenSearchOption && $LieferantenID) {

        if ($LieferantenSearchOption == 1) {
            $SQLString .= "(" . TABLE_ARTIKEL_LIEFERANTEN . ".hauptlieferant = 1) AND ";
            $SQLString .= "(!ISNULL(" . TABLE_ARTIKEL_LIEFERANTEN . ".lieferanten_id)) AND ";
        } elseif ($LieferantenSearchOption == 2) {
            $SQLString .= "(!ISNULL(" . TABLE_ARTIKEL_LIEFERANTEN . ".lieferanten_id)) AND ";
        }

    }

    // Filter "Unter Meldebestand"
    if ($LieferantenLagerOption) {
        $SQLString .= "(" . TABLE_ARTIKEL . ".meldebestand >= " . TABLE_ARTIKEL . ".lager) AND ";
    }

    $SQLString .= " 1) ";

//    echo '$SQLString: ' . $SQLString . "<br>";

	$ArtikelAnzahlObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));

	return $ArtikelAnzahlObject->ArtikelAnzahl;

}


// ********************************************************************************
// ** GetArtikelAnzahl
// ********************************************************************************
function GetArtikelAnzahl($SearchField, $SearchString, $KundenEmail = "", $FilterHerstellerID = "", $FilterKategorieID = "", $FilterKategorieRekursive = "", $FilterKategorieDoppelt = 0, $FilterMerkmalkombinationen = "", $FilterAktiv = "", $FilterWunschzettel = 0, $OptionSearchAll = 0, $OptionSearchLike = 0, $LanguageID = 0, $FilterArtikelGruppen = 0, $facettensucheAuspraegungenFilter = false, $LieferantenSearchOption = 0, $LieferantenID = 0, $LieferantenLagerOption = false) {

//	echo '***** ***** ***** *****<br>';
//	echo '$SearchField: ' . $SearchField . "<br>";
//	echo '$SearchString: ' . $SearchString . "<br>";
//	echo '$KundenEmail: ' . $KundenEmail . "<br>";
//	echo '$FilterHerstellerID: ' . $FilterHerstellerID . "<br>";
//	echo '$FilterKategorieID: ' . $FilterKategorieID . "<br>";
//	echo '$FilterKategorieRekursive: ' . $FilterKategorieRekursive . "<br>";
//	echo '$FilterKategorieDoppelt: ' . $FilterKategorieDoppelt . "<br>";
//	echo '$FilterMerkmalkombinationen: ' . $FilterMerkmalkombinationen . "<br>";
//	echo '$FilterAktiv: ' . $FilterAktiv . "<br>";
//	echo '$FilterWunschzettel: ' . $FilterWunschzettel . "<br>";
//	echo '$OptionSearchAll: ' . $OptionSearchAll . "<br>";
//	echo '$OptionSearchLike: ' . $OptionSearchLike . "<br>";

	return GetArtikelAnzahlPA(array(
	'SearchField' => $SearchField,
	'SearchString' => $SearchString,
	'KundenEmail' => $KundenEmail,
	'FilterHerstellerID' => $FilterHerstellerID,
	'FilterKategorieID' => $FilterKategorieID,
	'FilterKategorieRekursive' => $FilterKategorieRekursive,
	'FilterKategorieDoppelt' => $FilterKategorieDoppelt,
	'FilterMerkmalkombinationen' => $FilterMerkmalkombinationen,
	'FilterAktiv' => $FilterAktiv,
	'FilterWunschzettel' => $FilterWunschzettel,
	'OptionSearchAll' => $OptionSearchAll,
	'OptionSearchLike' => $OptionSearchLike,
	'LanguageID' => $LanguageID,
	'FilterArtikelGruppen' => $FilterArtikelGruppen,
	'facettensucheAuspraegungenFilter' => $facettensucheAuspraegungenFilter,
    'LieferantenSearchOption' => $LieferantenSearchOption,
    'LieferantenID' => $LieferantenID,
    'LieferantenLagerOption' => $LieferantenLagerOption,
    'FilterEKPreis' => $FilterEKPreis));
}
/*
 * 16.09.09, c.h.
 * Beginn der Funktionen zum Fuellen der Datenbank-Tabelle
 * Diese ist nur fuer die Entwicklung der Ajax-Suche vorgesehen
 * Spaeter wird sie nicht(!!!) mehr benoetigt.
 * Deswegen ist sie mit Ende der Entwicklung separat
 * zur Wiederverwertung oder in einer Bibliothek zu speichern.
function randomWord($first = false)
{
	$len = rand(1,20);
	$word = "";
	if ($first)
		$word = chr(rand(65,90));
	else if ((rand(0,10) > 5))
		$word = chr(rand(65,90));
	else
		$word = chr(rand(97,122));

	$count = 0;
	while($count < $len){
		$word .= chr(rand(97,122));
		$count++;
	}
		return $word;
}

function randomSentence()
{
	$words = rand(5,10);
	$sentence = randomWord(true);
	$count = 0;
	while($count < $words){
		$sentence .= " " . randomWord();
		if ($count%4 == 0) $sentence .= "<br>";
		$count++;
	}
		return $sentence . ".";
}

function randomText()
{
	$sentences = rand(1,6);
	$count = 0;
	$text = "";
	while ($count < $sentences) {
		$text .= randomSentence();
		$count++;
	}
	return $text;
}

function fillInDescriptions()
{

	$count = 12;
	while($count < 531) {
	$text = randomText();
	$zahl = $count;
	$SQLString = "UPDATE " . TABLE_ARTIKEL_LANGU . " SET `beschreibung` = '" . $text . "' WHERE " . TABLE_ARTIKEL_LANGU . ".`artikel_id` = " . $zahl;
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	$count++;
	}

}
Ende der Funktionen, die zum Fuellen der Datenbank-Tabelle dienten.
16.09.09, c.h.
*/

/*
function randomText(){

	//Jeder Text enth�lt 200 Zeichen.
	$word = chr(rand(65,90));
	$word_amount = 600;
	$word_count  = 0;
	$words = array();
	$items = array();
	for ($word_count = 0; $word_count < $word_amount;$word_count++)
	{

	$i = 0;
	$word = chr(rand(65,90));
	$zahl = rand(4,12);
	while($i < $zahl){
	$word .= chr(rand(97,122));
	$i++;
	}
	$preis = rand(1,100) + rand(1,99) * 0.01;
	if (($word_count + 7) < 10) {
		$artikelnummer = "00" . ($word_count + 7);
	} else {
			if (($word_count + 7 < 100))
				$artikelnummer = "0" . ($word_count + 7);
			else
				$artikelnummer = $word_count + 7;
	}
	$words[$word_count] = array($word, $word . "_s_1.jpg", $preis, $artikelnummer);
	$word = "";

	echo "<pre>" . var_dump($words) . "</pre>";
	echo "<br>Wert= " . $words[$word_count][0];


	//Ausk. von C.H. am 08.09.09, Grund: Wegen Entwicklung der Ajax-Suche
	//$SQLString = "INSERT INTO " . TABLE_ARTIKEL .  "(`artikel_id`, `language_id`, `artikel_name`, `beschreibung`, `kurz_beschreibung`, `smallImage`, `bigImage`) VALUES ('" . ($word_count+7) . "', '0', '". $words[$word_count][0] . "', '', '', '".$words[$word_count][1]."', '')";


	$SQLString = "INSERT INTO ". TABLE_ARTIKEL . " (`id`, `artikel_nr`, `variante1`, `variante2`, `variante3`, `variante4`, `preis_alt_netto`, `preis_alt_brutto`, `preis_netto`, `preis_brutto`, `mwst`, `timestamp`, `aktiv`, `lager`, `wie_oft_bestellt`, `angebote`, `merkmalauswahl`, `kundengruppenpreis`, `merkmalkombination`, `merkmalkombinationparentid`, `merkmalkombinationsort`, `merkmalkombinationstandard`, `produktgruppe`, `gewicht`, `voe_datum`, `hersteller_id`, `startseitenangebot`, `aktuellartikel`, `lieferstatus`, `artikel_download`, `ean`, `image_language_independent`, `download_language_independent`, `gruppenartikel`) VALUES (NULL, '" . $words[$word_count][3] . "', '0', '0', '0', '0', '0.00', '0.00', '0.00', '" . $words[$word_count][2] . "', '0', CURRENT_TIMESTAMP, '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0.00', '0000-00-00', '0', '0', '0', '0', '0', '', '0', '0', '0')";


}






/* Diese Funktion ist nur zu Entwicklungszwecken deklariert worden
 * Sie braucht nicht im Betrieb benutzt zu werden
 * C.H. 08.09.09
function fillTable(){


echo "Zufallszahl= " . rand(0,20);
echo "<br>Zufallswort= " . chr(rand(65,90)) . chr(rand(97,122));
echo "<br>Zufallskommazahl= " . rand();

$word = chr(rand(65,90));
$word_amount = 600;
$word_count  = 0;
$words = array();
$items = array();
for ($word_count = 0; $word_count < $word_amount;$word_count++)
{

	$i = 0;
	$word = chr(rand(65,90));
	$zahl = rand(4,12);
	while($i < $zahl){
	$word .= chr(rand(97,122));
	$i++;
	}
	$preis = rand(1,100) + rand(1,99) * 0.01;
	if (($word_count + 7) < 10) {
		$artikelnummer = "00" . ($word_count + 7);
	} else {
			if (($word_count + 7 < 100))
				$artikelnummer = "0" . ($word_count + 7);
			else
				$artikelnummer = $word_count + 7;
	}
	$words[$word_count] = array($word, $word . "_s_1.jpg", $preis, $artikelnummer);
	$word = "";

	echo "<pre>" . var_dump($words) . "</pre>";
	echo "<br>Wert= " . $words[$word_count][0];


	//Ausk. von C.H. am 08.09.09, Grund: Wegen Entwicklung der Ajax-Suche
	//$SQLString = "INSERT INTO " . TABLE_ARTIKEL .  "(`artikel_id`, `language_id`, `artikel_name`, `beschreibung`, `kurz_beschreibung`, `smallImage`, `bigImage`) VALUES ('" . ($word_count+7) . "', '0', '". $words[$word_count][0] . "', '', '', '".$words[$word_count][1]."', '')";


	$SQLString = "INSERT INTO ". TABLE_ARTIKEL . " (`id`, `artikel_nr`, `variante1`, `variante2`, `variante3`, `variante4`, `preis_alt_netto`, `preis_alt_brutto`, `preis_netto`, `preis_brutto`, `mwst`, `timestamp`, `aktiv`, `lager`, `wie_oft_bestellt`, `angebote`, `merkmalauswahl`, `kundengruppenpreis`, `merkmalkombination`, `merkmalkombinationparentid`, `merkmalkombinationsort`, `merkmalkombinationstandard`, `produktgruppe`, `gewicht`, `voe_datum`, `hersteller_id`, `startseitenangebot`, `aktuellartikel`, `lieferstatus`, `artikel_download`, `ean`, `image_language_independent`, `download_language_independent`, `gruppenartikel`) VALUES (NULL, '" . $words[$word_count][3] . "', '0', '0', '0', '0', '0.00', '0.00', '0.00', '" . $words[$word_count][2] . "', '0', CURRENT_TIMESTAMP, '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0.00', '0000-00-00', '0', '0', '0', '0', '0', '', '0', '0', '0')";






	echo "<br><br>SQLString= " . $SQLString;
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	echo (($MySQLQueryReference == null) ? "NEIN" : "JA");
}

	echo "<table>";
	echo "<tr><td><b>Artikelname</b></td><td><b>Bilddatei</b></td><td><b>Preis</b></td><td><b>Artikelnummer</b></td>";
	foreach($words as $word){
		echo "<tr>";
	foreach($word as $entry)
		echo "<td>".$entry."</td>";
	echo "</tr>";
	}
	echo "<table>";
}
*/

function GetArtikelNamenArrayByDescription($expression) {

	$SQLString = "SELECT DISTINCT " . TABLE_ARTIKEL_LANGU . ".artikel_name, ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".artikel_id FROM " . TABLE_ARTIKEL_LANGU;

	$SQLString =  "SELECT " . TABLE_ARTIKEL_LANGU . ".artikel_id, " .  TABLE_ARTIKEL_LANGU . ".artikel_name, ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".beschreibung, " . "smallImage, " . TABLE_ARTIKEL . ".preis_brutto FROM `";
	$SQLString .= TABLE_ARTIKEL_LANGU . "` LEFT JOIN " . TABLE_ARTIKEL . " ON " . TABLE_ARTIKEL_LANGU . ".artikel_id ";
	$SQLString .= "WHERE ". TABLE_ARTIKEL_LANGU . ".artikel_id = " . TABLE_ARTIKEL . ".id AND ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".beschreibung LIKE '%" . $expression . "%'";



	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	$ArtikelCounter = 0;
	$ArtikelNamenArray = array();
	while($ArtikelRowArray = mysql_fetch_array($MySQLQueryReference, MYSQL_ASSOC)){

		$ArtikelNamenArray[$ArtikelCounter]["id"] = $ArtikelRowArray["artikel_id"];
		$ArtikelNamenArray[$ArtikelCounter]["artikelname"] = $ArtikelRowArray["artikel_name"];
		$ArtikelNamenArray[$ArtikelCounter]["beschreibung"] = $ArtikelRowArray["beschreibung"];
		$ArtikelNamenArray[$ArtikelCounter]["bild"] = $ArtikelRowArray["smallImage"];
		$ArtikelNamenArray[$ArtikelCounter]["preis"] = $ArtikelRowArray["preis_brutto"];
		$ArtikelCounter++;
	}

	return $ArtikelNamenArray;
}






//07.09.09, Aus Entwicklungszwecken hinzugef�gt, C.H.
//soll sp�ter wieder entfernt bzw. mit
//GetArtikelDataArray vereint werden
function GetArtikelNamenArray($chars) {

	$SQLString = "SELECT DISTINCT " . TABLE_ARTIKEL_LANGU . ".artikel_name, ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".artikel_id FROM " . TABLE_ARTIKEL_LANGU;

	$SQLString =  "SELECT " . TABLE_ARTIKEL_LANGU . ".artikel_id, " .  TABLE_ARTIKEL_LANGU . ".artikel_name, ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".smallImage, " . TABLE_ARTIKEL . ".preis_brutto FROM `";
	$SQLString .= TABLE_ARTIKEL_LANGU . "` LEFT JOIN " . TABLE_ARTIKEL . " ON " . TABLE_ARTIKEL_LANGU . ".artikel_id ";
	$SQLString .= "WHERE ". TABLE_ARTIKEL_LANGU . ".artikel_id = " . TABLE_ARTIKEL . ".id AND ";
	$SQLString .= TABLE_ARTIKEL_LANGU . ".artikel_name LIKE '" . $chars . "%'";

	$MySQLQueryReference = errorlogged_mysql_query($SQLString);
	$ArtikelCounter = 0;
	$ArtikelNamenArray = array();
	while($ArtikelRowArray = mysql_fetch_array($MySQLQueryReference, MYSQL_ASSOC)){

		$ArtikelNamenArray[$ArtikelCounter]["id"] = $ArtikelRowArray["artikel_id"];
		$ArtikelNamenArray[$ArtikelCounter]["artikelname"] = $ArtikelRowArray["artikel_name"];
		$ArtikelNamenArray[$ArtikelCounter]["bild"] = $ArtikelRowArray["smallImage"];
		$ArtikelNamenArray[$ArtikelCounter]["preis"] = $ArtikelRowArray["preis_brutto"];
		$ArtikelCounter++;
	}




	return $ArtikelNamenArray;
}

function GetArtikelDataArrayPA($params)
{
	extract($params);
	if(!isset($SearchField)) $SearchField = '';
	if(!isset($SearchString)) $SearchString = '';
	if(!isset($SortField)) $SortField = '';
	if(!isset($SortOrder)) $SortOrder = '';
	if(!isset($DataOffset)) $DataOffset = '';
	if(!isset($DataCount)) $DataCount = '';
	if(!isset($KundenEmail)) $KundenEmail = '';
	if(!isset($FilterHerstellerID)) $FilterHerstellerID = 0;
	if(!isset($FilterKategorieID)) $FilterKategorieID = 0;
	if(!isset($FilterKategorieRekursive)) $FilterKategorieRekursive = 0;
	if(!isset($FilterKategorieDoppelt)) $FilterKategorieDoppelt = 0;
	if(!isset($FilterArtikelAbhaenigkeit)) $FilterArtikelAbhaenigkeit = 0;
	if(!isset($FilterMerkmalkombinationen)) $FilterMerkmalkombinationen = 1;
	if(!isset($FilterAktiv)) $FilterAktiv = 0;
	if(!isset($FilterWunschzettel)) $FilterWunschzettel = 0;
	if(!isset($FilterBestseller)) $FilterBestseller = '';
	if(!isset($OptionVarianten)) $OptionVarianten = 0;
	if(!isset($OptionSearchAll)) $OptionSearchAll = 0;
	if(!isset($OptionSearchLike)) $OptionSearchLike = 0;
	if(!isset($LanguageID)) $LanguageID = 0;
	if(!isset($FilterArtikelGruppen)) $FilterArtikelGruppen = 0;
	if(!isset($FilterGiveAways)) $FilterGiveAways = 0;
	if(!isset($GiveAwayMinBestellwert)) $GiveAwayMinBestellwert = -1;
	if(!isset($facettensucheAuspraegungenFilter)) $facettensucheAuspraegungenFilter = false;
    if(!isset($LieferantenSearchOption)) $LieferantenSearchOption = 0;
    if(!isset($LieferantenID)) $LieferantenID = 0;
    if(!isset($LieferantenLagerOption)) $LieferantenLagerOption = false;
    if(!isset($FilterEKPreis)) $FilterEKPreis = false;
    if(!isset($FilterBestellLagerNotNull)) $FilterBestellLagerNotNull = false;
    if(!isset($CalcSearchScore)) $CalcSearchScore = false;

	global $kurzTextAusgabe, $a_ls_standard;
	global $Suchdauer;
	global $c_landnichtuebersetzt;

	// Shopeinstellungen einlesen
	$FeatureObject = GetFeatureDetail();

	// Kundengruppe ermitteln
	if (!$KundenEmail) {
		$KundengruppenID = GetDefaultKundengruppe();
		$KundengruppenObject = GetKundengruppenDetail($KundengruppenID);
	} else {
		$KundengruppenObject = GetKundengruppenDetail("", $KundenEmail);
	}

	if(!$KundengruppenObject) {
		$KundengruppenID = GetDefaultKundengruppe();
		$KundengruppenObject = GetKundengruppenDetail($KundengruppenID);
	}

	// Kunde ermitteln
	if ($KundenEmail) {
		$KundenObject = GetKundenDetail($KundenEmail);
	}

	// Währung einlesen
	$WaehrungObject = GetWaehrungDetail();

	// Sprache ermitteln
	if (!$LanguageID) {
		$LanguageID = GetDefaultLanguageID();
	}

	$StandardLanguageID = GetDefaultLanguageID();

	// ********************************************************************************
	// ** SQL-String zum einlesen der Artikel zusammensetzen
	// ********************************************************************************

	// Felder
	$SQLString = "SELECT DISTINCT ";
	$SQLString .= TABLE_ARTIKEL . ".id, ";
	$SQLString .= TABLE_ARTIKEL . ".artikel_nr, ";
	$SQLString .= "IFNULL(" . TABLE_ARTIKEL_LANGU . ".artikel_name, table_artikel_langu_standard.artikel_name) AS artikel_name, ";
	$SQLString .= "!ISNULL(" . TABLE_ARTIKEL_LANGU . ".artikel_name) AS translate_artikel_name, ";
	$SQLString .= "IFNULL(" . TABLE_ARTIKEL_LANGU . ".beschreibung, table_artikel_langu_standard.beschreibung) AS beschreibung, ";
	$SQLString .= "IFNULL(" . TABLE_ARTIKEL_LANGU . ".kurz_beschreibung, table_artikel_langu_standard.kurz_beschreibung) AS kurz_beschreibung, ";
	$SQLString .= TABLE_ARTIKEL . ".preis_alt_netto, ";
	$SQLString .= TABLE_ARTIKEL . ".preis_alt_brutto, ";
	$SQLString .= TABLE_ARTIKEL . ".preis_netto, ";
	$SQLString .= TABLE_ARTIKEL . ".preis_brutto, ";

    if($FilterMerkmalkombinationen) {
        $SQLString .= TABLE_ARTIKEL . ".bestellanzahl_subselect.wie_oft_bestellt, ";
    } else {
        $SQLString .= TABLE_ARTIKEL . ".wie_oft_bestellt, ";
    }

	if (!$FilterEKPreis) {
		$SQLString .= TABLE_ARTIKEL . ".variante1, ";
		$SQLString .= "table_variante_langu1.name AS variante1name, ";
		$SQLString .= TABLE_ARTIKEL . ".variante2, ";
		$SQLString .= "table_variante_langu2.name AS variante2name, ";
		$SQLString .= TABLE_ARTIKEL . ".variante3, ";
		$SQLString .= "table_variante_langu3.name AS variante3name, ";
		$SQLString .= TABLE_ARTIKEL . ".variante4, ";
		$SQLString .= "table_variante_langu4.name AS variante4name, ";
	}

    $SQLString .= "IF(" . TABLE_ARTIKEL . ".image_language_independent, table_artikel_langu_standard.bigImage, " . TABLE_ARTIKEL_LANGU . ".bigImage) AS bigImage_parent, ";
    $SQLString .= "IF(" . TABLE_ARTIKEL . ".image_language_independent, table_artikel_langu_standard.smallImage, " . TABLE_ARTIKEL_LANGU . ".smallImage) AS smallImage_parent, ";
    $SQLString .= "IF(" . TABLE_ARTIKEL . ".image_language_independent, images_standard.bigImage, images.bigImage) AS bigImage_merkmalkombi, ";
    $SQLString .= "IF(" . TABLE_ARTIKEL . ".image_language_independent, images_standard.smallImage, images.smallImage) AS smallImage_merkmalkombi, ";
    $SQLString .= TABLE_ARTIKEL . ".aktiv, ";
	$SQLString .= TABLE_ARTIKEL . ".lager, ";
	$SQLString .= TABLE_ARTIKEL . ".lager_bestellungen, ";
	$SQLString .= TABLE_ARTIKEL . ".meldebestand, ";
	$SQLString .= TABLE_ARTIKEL . ".gewicht, ";
	$SQLString .= TABLE_ARTIKEL . ".ean, ";
	$SQLString .= TABLE_ARTIKEL . ".voe_datum, ";
	$SQLString .= TABLE_ARTIKEL . ".startseitenangebot, ";
	$SQLString .= TABLE_ARTIKEL . ".aktuellartikel, ";
	$SQLString .= TABLE_ARTIKEL . ".merkmalauswahl, ";
	$SQLString .= TABLE_ARTIKEL . ".merkmalkombination, ";
	$SQLString .= TABLE_ARTIKEL . ".merkmalkombinationparentid, ";
	$SQLString .= TABLE_ARTIKEL . ".merkmalkombinationsort, ";
	$SQLString .= TABLE_ARTIKEL . ".merkmalkombinationstandard, ";
	$SQLString .= TABLE_ARTIKEL . ".giveaway, ";
	$SQLString .= TABLE_ARTIKEL . ".giveaway_min_bestellwert, ";
	$SQLString .= TABLE_ARTIKEL . ".giveaway_rabattprozent, ";
	$SQLString .= TABLE_ARTIKEL . ".einheit_groesse, ";
	$SQLString .= TABLE_ARTIKEL . ".einheit_masseinheit, ";
    $SQLString .= TABLE_ARTIKEL . ".highlight_id, ";
    $SQLString .= TABLE_ARTIKEL . ".highlight_preis_brutto, ";
    $SQLString .= TABLE_ARTIKEL . ".highlight_preis_netto, ";
    $SQLString .= 'UNIX_TIMESTAMP(' . TABLE_ARTIKEL . ".highlight_enddatum) as highlight_enddatum_ts, ";
    $SQLString .= TABLE_HIGHLIGHTS . ".css_class as highlight_css_class, ";
    $SQLString .= "IFNULL(" . TABLE_HIGHLIGHTS_LANGU . ".name, table_highlights_langu_standard.name) AS highlight_name, ";

    if($CalcSearchScore) {
        if(is_array($SearchField)) {
            $SearchFieldArray = $SearchField;
            $SearchStringArray = $SearchString;
        } else {
            $SearchFieldArray = array($SearchField);
            $SearchStringArray = array($SearchString);
        }
        $SearchHitCountSQLSubstringArray = array();
        foreach($SearchFieldArray as $SearchKey => $SearchFieldEntry) {
            $SearchStringWords = explode(' ', $SearchStringArray[$SearchKey]);
            foreach($SearchStringWords as $SearchStringWord) {
                $SearchHitCountSQLSubstringArray[] = '(CHAR_LENGTH(' . $SearchFieldEntry . ') - CHAR_LENGTH(REPLACE(LOWER(' . $SearchFieldEntry . '), \'' . $SearchStringWord . '\', \'\'))) / CHAR_LENGTH(\'' . $SearchStringWord . '\')';
            }
        }
        $SQLString .= implode(' + ', $SearchHitCountSQLSubstringArray) . ' as search_score, ';
    }

	if ($FilterEKPreis) {
		$SQLString .= TABLE_ARTIKEL_LIEFERANTEN . ".ek_netto, ";
		$SQLString .= "(" . TABLE_ARTIKEL . ".lager * " . TABLE_ARTIKEL_LIEFERANTEN . ".ek_netto) AS ek_netto_gesamt, ";
	}

	if (!$FilterEKPreis) {
		$SQLString .= TABLE_LIEFERSTATUS . ".verkaufstop, ";
		$SQLString .= TABLE_LIEFERSTATUS_LANGU . ".name as lieferstatusname, ";
		$SQLString .= TABLE_LIEFERSTATUS . ".id AS lieferstatus_id, ";
		$SQLString .= "IFNULL(" . TABLE_LIEFERSTATUS_LANGU . ".name, table_lieferstatus_langu_standard.name) AS lieferstatus_name, ";
		$SQLString .= TABLE_LIEFERSTATUS . ".imagesmall AS lieferstatus_imagesmall, ";
		$SQLString .= "IF(" . TABLE_LIEFERSTATUS . ".language_independent, table_lieferstatus_langu_standard.image, " . TABLE_LIEFERSTATUS_LANGU . ".image) AS lieferstatus_image, ";
		$SQLString .= "NullLieferstatus.id AS nulllieferstatusid, ";
		$SQLString .= "IFNULL(NullLieferstatusLangu.name, NullLieferstatusLanguStandard.name) AS nulllieferstatus_name, ";
		$SQLString .= "NullLieferstatus.imagesmall AS nulllieferstatus_imagesmall, ";
		$SQLString .= "IF(NullLieferstatus.language_independent, NullLieferstatusLanguStandard.image, NullLieferstatusLangu.image) AS nulllieferstatus_image, ";
	}

	// Wunschzettel
	if ($FilterWunschzettel && $KundenEmail) {
		$SQLString .= TABLE_WUNSCHZETTEL . ".timestamp AS wunschzettel_timestamp, ";
	}

	// Filter alle Kategorieeintr�ge
	if ($FilterKategorieDoppelt) {
		$SQLString .= TABLE_KATEGORIE_LANGU . ".name AS kategoriename, ";
		$SQLString .= TABLE_KATEGORIE . ".id AS kategorieid, ";
	}

	if($FilterArtikelGruppen > 0)   {
		$SQLString .= TABLE_ARTIKEL_GRUPPEN . ".menge, ";
	}

	// Lieferantenzuordnung
	if ($LieferantenSearchOption && $LieferantenID) {
		$SQLString .= TABLE_ARTIKEL_LIEFERANTEN . ".ek_netto, ";
		$SQLString .= TABLE_ARTIKEL_LIEFERANTEN . ".bestellnummer, ";
		$SQLString .= TABLE_ARTIKEL_LIEFERANTEN . ".hauptlieferant, ";
		$SQLString .= TABLE_ARTIKEL_LIEFERANTEN . ".lieferanten_id, ";
	}

	$SQLString .= TABLE_KUNDENGRUPPENPREISE . ".preis_netto AS kundengruppenpreis_netto, ";
	$SQLString .= TABLE_KUNDENGRUPPENPREISE . ".preis_brutto AS kundengruppenpreis_brutto ";
	$SQLString .= "FROM " . TABLE_ARTIKEL . " ";
	$SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_LANGU . " ON ((IF(" . TABLE_ARTIKEL . ".merkmalkombinationparentid, " . TABLE_ARTIKEL . ".merkmalkombinationparentid, " . TABLE_ARTIKEL . ".id) = " . TABLE_ARTIKEL_LANGU . ".artikel_id) AND (" . TABLE_ARTIKEL_LANGU . ".language_id = " . $LanguageID . ")) ";
	$SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_LANGU . " table_artikel_langu_standard ON ((IF(" . TABLE_ARTIKEL . ".merkmalkombinationparentid, " . TABLE_ARTIKEL . ".merkmalkombinationparentid, " . TABLE_ARTIKEL . ".id) = table_artikel_langu_standard.artikel_id) AND (table_artikel_langu_standard.language_id = " . $StandardLanguageID . ")) ";
    $SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_LANGU . " images ON (IF(" . TABLE_ARTIKEL . ".merkmalkombination, " . TABLE_ARTIKEL . ".merkmalkombination, " . TABLE_ARTIKEL . ".id) = images.artikel_id) AND (images.language_id = " . $LanguageID . ") ";
    $SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_LANGU . " images_standard ON (IF(" . TABLE_ARTIKEL . ".merkmalkombination, " . TABLE_ARTIKEL . ".merkmalkombination, " . TABLE_ARTIKEL . ".id) = images_standard.artikel_id) AND (images_standard.language_id = " . $StandardLanguageID . ") ";

    if (!$FilterEKPreis) {
		$SQLString .= "LEFT JOIN " . TABLE_LIEFERSTATUS . " ON " . TABLE_ARTIKEL . ".lieferstatus = " . TABLE_LIEFERSTATUS . ".id ";
		$SQLString .= "LEFT JOIN " . TABLE_LIEFERSTATUS_LANGU . " ON ((" . TABLE_LIEFERSTATUS . ".id = " . TABLE_LIEFERSTATUS_LANGU . ".lieferstatus_id) AND (" . TABLE_LIEFERSTATUS_LANGU . ".language_id = " . $LanguageID . ")) ";
		$SQLString .= "LEFT JOIN " . TABLE_LIEFERSTATUS_LANGU . " table_lieferstatus_langu_standard ON (( " . TABLE_LIEFERSTATUS . ".id = table_lieferstatus_langu_standard.lieferstatus_id) AND (table_lieferstatus_langu_standard.language_id = " . $StandardLanguageID . ")) ";
		$SQLString .= "LEFT JOIN " . TABLE_LIEFERSTATUS . " AS NullLieferstatus ON " . TABLE_LIEFERSTATUS . ".nichtauflager = NullLieferstatus.id ";
		$SQLString .= "LEFT JOIN " . TABLE_LIEFERSTATUS_LANGU . " AS NullLieferstatusLangu ON ((NullLieferstatus.id = NullLieferstatusLangu.lieferstatus_id) AND (NullLieferstatusLangu.language_id = " . $LanguageID . ")) ";
		$SQLString .= "LEFT JOIN " . TABLE_LIEFERSTATUS_LANGU . " AS NullLieferstatusLanguStandard ON ((NullLieferstatus.id = NullLieferstatusLanguStandard.lieferstatus_id) AND (NullLieferstatusLanguStandard.language_id = " . $StandardLanguageID . ")) ";
	}

	$SQLString .= "LEFT JOIN " . TABLE_KUNDENGRUPPENPREISE . " ON ((" . TABLE_ARTIKEL . ".id = " . TABLE_KUNDENGRUPPENPREISE . ".artikelid) AND (" . TABLE_KUNDENGRUPPENPREISE . ".kundengruppenid = " . $KundengruppenObject->id . ")) ";
	$SQLString .= "LEFT JOIN " . TABLE_KATEGORIERELATION . " ON " . TABLE_ARTIKEL . ".id = " . TABLE_KATEGORIERELATION . ".artikelid ";

	if (!$FilterEKPreis) {
		$SQLString .= "LEFT JOIN " . TABLE_VARIANTE . " table_variante1 ON  " . TABLE_ARTIKEL . ".variante1 = table_variante1.id ";
		$SQLString .= "LEFT JOIN " . TABLE_VARIANTE_LANGU . " table_variante_langu1 ON ((table_variante1.id = table_variante_langu1.variante_id) AND (table_variante_langu1.language_id = " . $LanguageID . ")) ";
		$SQLString .= "LEFT JOIN " . TABLE_VARIANTE . " table_variante2 ON  " . TABLE_ARTIKEL . ".variante2 = table_variante2.id ";
		$SQLString .= "LEFT JOIN " . TABLE_VARIANTE_LANGU . " table_variante_langu2 ON ((table_variante2.id = table_variante_langu2.variante_id) AND (table_variante_langu2.language_id = " . $LanguageID . ")) ";
		$SQLString .= "LEFT JOIN " . TABLE_VARIANTE . " table_variante3 ON  " . TABLE_ARTIKEL . ".variante3 = table_variante3.id ";
		$SQLString .= "LEFT JOIN " . TABLE_VARIANTE_LANGU . " table_variante_langu3 ON ((table_variante3.id = table_variante_langu3.variante_id) AND (table_variante_langu3.language_id = " . $LanguageID . ")) ";
		$SQLString .= "LEFT JOIN " . TABLE_VARIANTE . " table_variante4 ON  " . TABLE_ARTIKEL . ".variante4 = table_variante4.id ";
		$SQLString .= "LEFT JOIN " . TABLE_VARIANTE_LANGU . " table_variante_langu4 ON ((table_variante4.id = table_variante_langu4.variante_id) AND (table_variante_langu4.language_id = " . $LanguageID . ")) ";
	}

	if ($FilterEKPreis) {
		$SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_LIEFERANTEN . " ON  ((" . TABLE_ARTIKEL . ".id = " . TABLE_ARTIKEL_LIEFERANTEN . ".artikel_id) AND (" . TABLE_ARTIKEL_LIEFERANTEN . ".hauptlieferant = 1)) ";
	}

	// Lieferantenzuordnung
	if ($LieferantenSearchOption && $LieferantenID) {
        $SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_LIEFERANTEN . " ON ((IF(" . TABLE_ARTIKEL . ".merkmalkombinationparentid, " . TABLE_ARTIKEL . ".merkmalkombinationparentid, " . TABLE_ARTIKEL . ".id) = " . TABLE_ARTIKEL_LIEFERANTEN . ".artikel_id) AND (" . TABLE_ARTIKEL_LIEFERANTEN . ".lieferanten_id = '" . $LieferantenID . "')) ";
	}

	// Artikelabh�ngigkeit
	if ($FilterArtikelAbhaenigkeit) {
		$SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_AB . " ON " . TABLE_ARTIKEL . ".id = " . TABLE_ARTIKEL_AB . ".abartikelid ";
	}

	// Wunschzettel
	if ($FilterWunschzettel && $KundenEmail) {
		$SQLString .= "LEFT JOIN " . TABLE_WUNSCHZETTEL . " ON ((" . TABLE_ARTIKEL . ".id = " . TABLE_WUNSCHZETTEL . ".artikelid) AND (" . TABLE_WUNSCHZETTEL . ".kundenid = " . $KundenObject->id . ")) ";
	}

	// Filter alle Kategorieeinträge
	if ($FilterKategorieDoppelt || $SearchField == TABLE_KATEGORIE_LANGU . '.name') {
		$SQLString .= "LEFT JOIN " . TABLE_KATEGORIE . " ON " . TABLE_KATEGORIERELATION . ".kategorieid = " . TABLE_KATEGORIE . ".id ";
		$SQLString .= "LEFT JOIN " . TABLE_KATEGORIE_LANGU . " ON " . TABLE_KATEGORIE . ".id = " . TABLE_KATEGORIE_LANGU . ".kategorie_id ";
	}

	if($FilterArtikelGruppen > 0) {
		$SQLString .= "LEFT JOIN " . TABLE_ARTIKEL_GRUPPEN . " ON " . TABLE_ARTIKEL . ".id = " . TABLE_ARTIKEL_GRUPPEN . ".teilartikel_id ";
	}

    if($FilterMerkmalkombinationen) {
        $SQLString .= 'LEFT JOIN (SELECT merkmalkombinationparentid, SUM(wie_oft_bestellt) as wie_oft_bestellt FROM ' . TABLE_ARTIKEL . ' GROUP BY merkmalkombinationparentid) bestellanzahl_subselect ON bestellanzahl_subselect.merkmalkombinationparentid = ' . TABLE_ARTIKEL . '.id ';
    }
    $SQLString .= "LEFT JOIN " . TABLE_HIGHLIGHTS . " ON " . TABLE_HIGHLIGHTS . ".id = " . TABLE_ARTIKEL . ".highlight_id ";
    $SQLString .= "LEFT JOIN " . TABLE_HIGHLIGHTS_LANGU . " ON " . TABLE_HIGHLIGHTS . ".id = " . TABLE_HIGHLIGHTS_LANGU . ".highlight_id AND " . TABLE_HIGHLIGHTS_LANGU . ".language_id = '" . $LanguageID . "' ";
    $SQLString .= "LEFT JOIN " . TABLE_HIGHLIGHTS_LANGU . " table_highlights_langu_standard ON " . TABLE_HIGHLIGHTS . ".id = table_highlights_langu_standard.highlight_id AND table_highlights_langu_standard.language_id = '" . $StandardLanguageID . "' ";

    $SQLString .= " WHERE ( ";

	// Suche
	if (is_array($SearchField) && is_array($SearchString)) {

		$Zeitmessung = true;

		// Volltextsuche
		if ($FeatureObject->volltextsuche) {

			if ($OptionSearchLike) {
				$SearchLike = "";
			} else {
				$SearchLike = "*";
			}

			if ($OptionSearchAll) {
				$SearchConnector = "AND ";
			} else {
				$SearchConnector = "OR ";
			}

			$SQLString .= "(";

			foreach ($SearchField as $SearchFieldKey => $SearchFieldValue) {

				$SearchStringArray = explode(" ", $SearchString[$SearchFieldKey]);

				$SearchStringAgainst = "";

				foreach ($SearchStringArray as $SearchStringElement) {

					$SearchStringAgainst .= $SearchLike . $SearchStringElement . $SearchLike . " ";

				}


				$SQLString .= "(MATCH (" . $SearchFieldValue . ") AGAINST ('" . $SearchStringAgainst . "' IN BOOLEAN MODE)) " . $SearchConnector;

				$SQLString = substr($SQLString, 0, strlen($SQLString) - strlen($SearchConnector));
				$SQLString .= " OR ";

			}

			$SQLString = substr($SQLString, 0, strlen($SQLString) - strlen($SearchConnector));
			$SQLString .= ") AND ";

		// normale Suche
		} else {

			if ($OptionSearchLike == 0) {
				$SearchLike = "";
			} else {
				$SearchLike = "%";
			}

//          if ($OptionSearchLike) {
//              $SearchLike = "";
//          } else {
//              $SearchLike = "%";
//          }

			if ($OptionSearchAll == 1) {
				$SearchConnector = "AND ";
			} else {
				$SearchConnector = "OR ";
			}

			$SQLString .= "(";

			foreach ($SearchField as $SearchFieldKey => $SearchFieldValue) {

				if (isset($OptionSearchExact)) {
					$SQLString .= "(" . $SearchFieldValue . " LIKE '" . $SearchLike . $SearchString[$SearchFieldKey] . $SearchLike . "') " . $SearchConnector;
				} else {

					$SearchStringArray = explode(" ", $SearchString[$SearchFieldKey]);

					foreach ($SearchStringArray as $SearchStringElement) {
						$SQLString .= "(" . $SearchFieldValue . " LIKE '" . $SearchLike . $SearchStringElement . $SearchLike . "') " . $SearchConnector;
					}

					$SQLString = substr($SQLString, 0, strlen($SQLString) - strlen($SearchConnector));
					$SQLString .= " OR ";

				}

			}

			$SQLString = substr($SQLString, 0, strlen($SQLString) - strlen($SearchConnector));
			$SQLString .= ") AND ";

		}

	} elseif ($SearchField && $SearchString) {

		$SQLString .= "(" . $SearchField . " LIKE '%" . $SearchString . "%') AND ";

	}

	// Filter Hersteller
	if ($FilterHerstellerID) {
		$SQLString .= "(" . TABLE_ARTIKEL . ".hersteller_id = '" . $FilterHerstellerID . "') AND ";
	}

	// Filter Kategorie
	if ($FilterKategorieID && !$FilterKategorieRekursive) {

		$SQLString .= "(" . TABLE_KATEGORIERELATION . ".kategorieid = '" . $FilterKategorieID . "') AND ";

	} elseif ($FilterKategorieID && $FilterKategorieRekursive) {

		if(is_array($FilterKategorieID))
		{
		$KategorieArray = $FilterKategorieID;
		foreach($FilterKategorieID as $KategorieID)
			$KategorieArray = GetKategorieIDs($KategorieID, $KategorieArray);
		}
		else
		{
		$KategorieArray[] = $FilterKategorieID;
		$KategorieArray = GetKategorieIDs($FilterKategorieID, $KategorieArray);
		}
		$SQLString .= "(" . TABLE_KATEGORIERELATION . ".kategorieid IN (" . implode($KategorieArray, ",") . ")) AND ";

	}

	// Filter Artikelabhängigkeit
	if ($FilterArtikelAbhaenigkeit) {
		$SQLString .= "(" . TABLE_ARTIKEL_AB . ".artikelid = '" . $FilterArtikelAbhaenigkeit . "') AND ";
	}

	// Filter Merkmalkombinationen
	if ($FilterMerkmalkombinationen) {
		$SQLString .= "(" . TABLE_ARTIKEL . ".merkmalkombinationparentid = 0) AND ";
	} else {
		$SQLString .= "(" . TABLE_ARTIKEL . ".merkmalkombination = 0) AND ";
	}

	// Filter Aktiv
	if ($FilterAktiv) {
		$SQLString .= "(" . TABLE_ARTIKEL . ".aktiv = '1') AND ";
	}

	// Filter Wunschzettel
	if ($FilterWunschzettel && $KundenEmail) {
		$SQLString .= "(" . TABLE_WUNSCHZETTEL . ".kundenid = '" . $KundenObject->id . "') AND ";
	}

	// Filter Bestseller
	if (is_numeric($FilterBestseller)) {
		$SQLString .= "(" . TABLE_ARTIKEL . ".wie_oft_bestellt >= " . $FilterBestseller . ") AND ";
	}

	if($FilterArtikelGruppen == -1)
	{
		$SQLString .= "(" . TABLE_ARTIKEL . ".gruppenartikel = 0) AND ";
	}
	elseif($FilterArtikelGruppen > 0)
	{
		$SQLString .= "(" . TABLE_ARTIKEL_GRUPPEN . ".gruppenartikel_id = " . $FilterArtikelGruppen . ") AND ";
	}

	if($OptionVarianten == -1)
	{
		$SQLString .= "(" . TABLE_ARTIKEL . ".variante1 = 0 AND " . TABLE_ARTIKEL . ".variante2 = 0 AND " . TABLE_ARTIKEL . ".variante3 = 0 AND " . TABLE_ARTIKEL . ".variante4 = 0) AND ";
	}

	if($OptionVarianten == 2)
	{
		$SQLString .= "((" . TABLE_ARTIKEL . ".variante1 = 0 AND " . TABLE_ARTIKEL . ".variante2 = 0 AND " . TABLE_ARTIKEL . ".variante3 = 0 AND " . TABLE_ARTIKEL . ".variante4 = 0) OR ((" . TABLE_ARTIKEL . ".merkmalkombinationparentid <> 0) OR (" . TABLE_ARTIKEL . ".merkmalkombination = 0))) AND ";
	}

	if($FilterGiveAways)
	{
		$SQLString .= "(" . TABLE_ARTIKEL . ".giveaway = 1) AND ";
		if($GiveAwayMinBestellwert != -1)
		{
			$SQLString .= "(" . TABLE_ARTIKEL . ".giveaway_min_bestellwert <= " . $GiveAwayMinBestellwert . ") AND ";
		}
	}

	if($facettensucheAuspraegungenFilter)
	{
		$preisfilter = getFacettensucheFilter(false, false, FACETTENSUCHE_FILTER_AUTO_PREIS);
		$preis_auspraegungen = getFacettenSucheAuspraegungenDataArray($preisfilter['id']);
		foreach($preis_auspraegungen as $preis_auspraegung)
		{
			$key = array_search($preis_auspraegung['id'], $facettensucheAuspraegungenFilter['auspraegungen']);
			if($key !== false)
			{
				$preis_auspraegungen_selected[] = $preis_auspraegung['id'];
				unset($facettensucheAuspraegungenFilter['auspraegungen'][$key]);
			}
		}
		if(isset($preis_auspraegungen_selected))
			$facettensucheAuspraegungenFilter['filter_anzahl']--;

		if($facettensucheAuspraegungenFilter['filter_anzahl'])
			$SQLString .= '(' . $facettensucheAuspraegungenFilter['filter_anzahl'] . ' <= (SELECT COUNT(*) FROM ' . TABLE_FACETTENSUCHE_ARTIKEL_AUSPRAEGUNG . ' WHERE ' . TABLE_FACETTENSUCHE_ARTIKEL_AUSPRAEGUNG . '.artikel_id = ' . TABLE_ARTIKEL . '.id AND ' . TABLE_FACETTENSUCHE_ARTIKEL_AUSPRAEGUNG . '.auspraegung_id IN (' . implode($facettensucheAuspraegungenFilter['auspraegungen'], ',') . '))) AND ';
		if(isset($preis_auspraegungen_selected))
		{
			$SQLString .= '1 <= ( SELECT COUNT(*) FROM ' . TABLE_FACETTENSUCHE_AUSPRAEGUNGEN . ' WHERE ' . TABLE_FACETTENSUCHE_AUSPRAEGUNGEN . '.id IN (' . implode($preis_auspraegungen_selected, ',') . ') AND IFNULL(' . TABLE_FACETTENSUCHE_AUSPRAEGUNGEN . '.preis_von, 0.0) <= ';
			if ($KundengruppenObject->type == 1)
				$SQLString .= 'IFNULL(' . TABLE_KUNDENGRUPPENPREISE . '.preis_brutto, ' . TABLE_ARTIKEL . '.preis_brutto) ';
			else
				$SQLString .= 'IFNULL(' . TABLE_KUNDENGRUPPENPREISE . '.preis_netto, ' . TABLE_ARTIKEL . '.preis_netto) ';
			$SQLString .= ' AND IFNULL(' . TABLE_FACETTENSUCHE_AUSPRAEGUNGEN . '.preis_bis, 999999999999.99) > ';
			if ($KundengruppenObject->type == 1)
				$SQLString .= 'IFNULL(' . TABLE_KUNDENGRUPPENPREISE . '.preis_brutto, ' . TABLE_ARTIKEL . '.preis_brutto) ';
			else
				$SQLString .= 'IFNULL(' . TABLE_KUNDENGRUPPENPREISE . '.preis_netto, ' . TABLE_ARTIKEL . '.preis_netto) ';
			$SQLString .= ') AND ';
		}
	}

	// Filter Lieferantenzuordnung
	if ($LieferantenSearchOption && $LieferantenID) {

		if ($LieferantenSearchOption == 1) {
			$SQLString .= "(" . TABLE_ARTIKEL_LIEFERANTEN . ".hauptlieferant = 1) AND ";
			$SQLString .= "(!ISNULL(" . TABLE_ARTIKEL_LIEFERANTEN . ".lieferanten_id)) AND ";
		} elseif ($LieferantenSearchOption == 2) {
			$SQLString .= "(!ISNULL(" . TABLE_ARTIKEL_LIEFERANTEN . ".lieferanten_id)) AND ";
		}

	}

	// Filter "Unter Meldebestand"
	if ($LieferantenLagerOption) {
		$SQLString .= "(" . TABLE_ARTIKEL . ".meldebestand >= " . TABLE_ARTIKEL . ".lager) AND ";
	}

    // Wenn die Funktion nicht aus dem Admin aufgerufen wurde, wird auch nach Kundengruppe gefiltert
    if(stripos($_SERVER['SCRIPT_NAME'], '/admin/') === false) {
        $SQLString .= "(" . TABLE_ARTIKEL . '.kundengruppe_id = 0 OR ' . TABLE_ARTIKEL . '.kundengruppe_id = \'' . $KundengruppenObject->id . '\') AND ';
    }

    if($FilterBestellLagerNotNull) {
        $SQLString .= '(' . TABLE_ARTIKEL . '.lager_bestellungen > 0) AND';
    }

    $SQLString .= " 1) ";

	// Sortierung
	if ($SortField && $SortOrder) {
		$SQLString .= "ORDER BY " . $SortField . " " . $SortOrder . " ";
	}

	// Limit
	if ((string)$DataOffset != "" && (string)$DataCount != "") {
		$SQLString .= "LIMIT " . $DataOffset . ", " . $DataCount . " ";
	}

//echo '$SQLString: ' . $SQLString . "<br><br>";

	// Zeitmessung
	if (isset($Zeitmessung)) {
		$MicrotimeArray = explode(" ", microtime());
		$MicrotimeStart = (float)$MicrotimeArray[0] + (float)$MicrotimeArray[1];
	}

	$MySQLQueryReferenz = errorlogged_mysql_query($SQLString);

	// Zeitmessung
	if (isset($Zeitmessung)) {
		$MicrotimeArray = explode(" ", microtime());
		$MicrotimeAfterMySQLQuery = (float)$MicrotimeArray[0] + (float)$MicrotimeArray[1];  ;
	}

	// ********************************************************************************
	// ** die Artikeldaten in ein Array ablegen
	// ********************************************************************************

	$ArtikelCounter = 0;
	$ArtikelDataArray = array();


	while ($ArtikelRowArray = mysql_fetch_array($MySQLQueryReferenz, MYSQL_ASSOC)) {

		// Daten f�r die Ausgabe formatieren
		$ArtikelDataArray[$ArtikelCounter]["id"] = $ArtikelRowArray["id"];
		$ArtikelDataArray[$ArtikelCounter]["verkaufstop"] = $ArtikelRowArray["verkaufstop"];
		$ArtikelDataArray[$ArtikelCounter]["artikelnummer"] = $ArtikelRowArray["artikel_nr"];
        $ArtikelDataArray[$ArtikelCounter]["wie_oft_bestellt"] = $ArtikelRowArray["wie_oft_bestellt"];
		$ArtikelDataArray[$ArtikelCounter]["artikelname"] = $ArtikelRowArray["artikel_name"];
		$ArtikelDataArray[$ArtikelCounter]["translate_artikel_name"] = $ArtikelRowArray["translate_artikel_name"];
//        $ArtikelDataArray[$ArtikelCounter]["kategoriename"] = $ArtikelRowArray["kategoriename"];
//        $ArtikelDataArray[$ArtikelCounter]["kategorieid"] = $ArtikelRowArray["kategorieid"];
		$ArtikelDataArray[$ArtikelCounter]["beschreibung"] = $ArtikelRowArray["beschreibung"];
		$ArtikelDataArray[$ArtikelCounter]["kurz_beschreibung"] = $ArtikelRowArray["kurz_beschreibung"];
        $ArtikelDataArray[$ArtikelCounter]["beschreibung_kurz"] = word_substr(strip_tags($ArtikelRowArray["beschreibung"]), $kurzTextAusgabe, 5);
		$ArtikelDataArray[$ArtikelCounter]["preis_brutto"] = $ArtikelRowArray["preis_brutto"];
		$ArtikelDataArray[$ArtikelCounter]["preis_netto"] = $ArtikelRowArray["preis_netto"];
		$ArtikelDataArray[$ArtikelCounter]["aktiv"] = $ArtikelRowArray["aktiv"];
		$ArtikelDataArray[$ArtikelCounter]["meldebestand"] = $ArtikelRowArray["meldebestand"];
//        if($ArtikelRowArray['merkmalkombination']) {
//            $SQLString = 'SELECT lager, lager_bestellungen FROM ' . TABLE_ARTIKEL . ' WHERE merkmalkombinationparentid = \'' . $ArtikelDataArray[$ArtikelCounter]['id'] . '\' ORDER BY lager_bestellungen DESC LIMIT 0,1';
//            $merkmalkombiresult = errorlogged_mysql_query($SQLString);
//            $merkmalkombi = mysql_fetch_object($merkmalkombiresult);
//            $ArtikelDataArray[$ArtikelCounter]["lager"] = $merkmalkombi->lager;
//            $ArtikelDataArray[$ArtikelCounter]["lager_bestellungen"] = $merkmalkombi->lager_bestellungen;
//        } else {
            $ArtikelDataArray[$ArtikelCounter]["lager"] = $ArtikelRowArray["lager"];
            $ArtikelDataArray[$ArtikelCounter]["lager_bestellungen"] = $ArtikelRowArray["lager_bestellungen"];
//        }
		$ArtikelDataArray[$ArtikelCounter]["gewicht"] = $ArtikelRowArray["gewicht"];
		$ArtikelDataArray[$ArtikelCounter]["variante1"] = $ArtikelRowArray["variante1"];
		$ArtikelDataArray[$ArtikelCounter]["variante1name"] = $ArtikelRowArray["variante1name"];
		$ArtikelDataArray[$ArtikelCounter]["variante2"] = $ArtikelRowArray["variante2"];
		$ArtikelDataArray[$ArtikelCounter]["variante2name"] = $ArtikelRowArray["variante2name"];
		$ArtikelDataArray[$ArtikelCounter]["variante3"] = $ArtikelRowArray["variante3"];
		$ArtikelDataArray[$ArtikelCounter]["variante3name"] = $ArtikelRowArray["variante3name"];
		$ArtikelDataArray[$ArtikelCounter]["variante4"] = $ArtikelRowArray["variante4"];
		$ArtikelDataArray[$ArtikelCounter]["variante4name"] = $ArtikelRowArray["variante4name"];
		$ArtikelDataArray[$ArtikelCounter]["lieferstatus"] = $ArtikelRowArray["lieferstatus_id"];
		$ArtikelDataArray[$ArtikelCounter]["lieferstatusname"] = $ArtikelRowArray["lieferstatusname"];
		$ArtikelDataArray[$ArtikelCounter]["onlinestatus"] = $ArtikelRowArray["aktiv"];
//        $ArtikelDataArray[$ArtikelCounter]["wunschzettel_datum"] = $ArtikelRowArray["wunschzettel_datum"];
		$ArtikelDataArray[$ArtikelCounter]["ean"] = $ArtikelRowArray["ean"];
		$ArtikelDataArray[$ArtikelCounter]["languagearray"] = GetArtikelLanguageDataArray($ArtikelRowArray["id"]);
		$ArtikelDataArray[$ArtikelCounter]["einheit_groesse"] = $ArtikelRowArray["einheit_groesse"];
		$ArtikelDataArray[$ArtikelCounter]["einheit_masseinheit"] = $ArtikelRowArray["einheit_masseinheit"];
        if($ArtikelRowArray["highlight_id"] && $ArtikelRowArray["highlight_enddatum_ts"] > time()) {
            $ArtikelDataArray[$ArtikelCounter]["highlight_id"] = $ArtikelRowArray["highlight_id"];
            $ArtikelDataArray[$ArtikelCounter]["highlight_preis_brutto"] = $ArtikelRowArray["highlight_preis_brutto"];
            $ArtikelDataArray[$ArtikelCounter]["highlight_preis_netto"] = $ArtikelRowArray["highlight_preis_netto"];
            $ArtikelDataArray[$ArtikelCounter]["highlight_enddatum_ts"] = $ArtikelRowArray["highlight_enddatum_ts"];
            $ArtikelDataArray[$ArtikelCounter]["highlight_css_class"] = $ArtikelRowArray["highlight_css_class"];
            $ArtikelDataArray[$ArtikelCounter]["highlight_name"] = $ArtikelRowArray["highlight_name"];
        }
        $ArtikelDataArray[$ArtikelCounter]["ek_netto"] = $ArtikelRowArray["ek_netto"];
		$ArtikelDataArray[$ArtikelCounter]["ek_netto_gesamt"] = $ArtikelRowArray["ek_netto_gesamt"];

        if($ArtikelRowArray['smallImage_merkmalkombi']) {
            $ArtikelRowArray['smallImage'] = $ArtikelRowArray['smallImage_merkmalkombi'];
            $ArtikelDataArray[$ArtikelCounter]['imagesmall_from_parent'] = false;
        } else {
            $ArtikelRowArray['smallImage'] = $ArtikelRowArray['smallImage_parent'];
            $ArtikelDataArray[$ArtikelCounter]['imagesmall_from_parent'] = true;
        }

        if($ArtikelRowArray['bigImage_merkmalkombi']) {
            $ArtikelRowArray['bigImage'] = $ArtikelRowArray['bigImage_merkmalkombi'];
            $ArtikelDataArray[$ArtikelCounter]['imagebig_from_parent'] = false;
        } else {
            $ArtikelRowArray['bigImage'] = $ArtikelRowArray['bigImage_parent'];
            $ArtikelDataArray[$ArtikelCounter]['imagebig_from_parent'] = true;
        }

        if ($FilterWunschzettel) {
			$ArtikelDataArray[$ArtikelCounter]["wunschzettel_datum"] = date("d.m.Y", $ArtikelRowArray["wunschzettel_timestamp"]);
		}

		// formatiertes Ver�ffentlichungsdatum
		if ($ArtikelRowArray["voe_datum"] != 0) {
			$ArtikelDataArray[$ArtikelCounter]["voe_datum"] = $ArtikelRowArray["voe_datum"];
			if (strtotime($ArtikelRowArray["voe_datum"]) > time()) {
				$ArtikelDataArray[$ArtikelCounter]["voe_datum_format"] = date("d.m.Y", strtotime($ArtikelRowArray["voe_datum"]));
			}
		}

		// formatierter Preis f�r den Shop
		if ($ArtikelRowArray["kundengruppenpreis_netto"]) {

			$ArtikelDataArray[$ArtikelCounter]["preis_netto"] = $ArtikelRowArray["kundengruppenpreis_netto"];
			$ArtikelDataArray[$ArtikelCounter]["preis_brutto"] = $ArtikelRowArray["kundengruppenpreis_brutto"];

			// Bruttopreis
			if ($KundengruppenObject->type == 1) {
				$ArtikelDataArray[$ArtikelCounter]["preis"] = $ArtikelRowArray["kundengruppenpreis_brutto"];
				$ArtikelDataArray[$ArtikelCounter]["preis_format"] = number_format($ArtikelRowArray["kundengruppenpreis_brutto"], 2, ",", ".") . " " . $WaehrungObject->symbol;
			// Nettopreis
			} else {
				$ArtikelDataArray[$ArtikelCounter]["preis"] = $ArtikelRowArray["kundengruppenpreis_netto"];
				$ArtikelDataArray[$ArtikelCounter]["preis_format"] = number_format($ArtikelRowArray["kundengruppenpreis_netto"], 2, ",", ".") . " " . $WaehrungObject->symbol;
			}

		} else {
            if(!$ArtikelRowArray['merkmalkombination'] && $ArtikelRowArray["highlight_id"] && $ArtikelRowArray['highlight_preis_brutto'] > 0 && $ArtikelRowArray['highlight_enddatum_ts'] > time()) {
                $ArtikelDataArray[$ArtikelCounter]["preis_netto"] = $ArtikelRowArray["highlight_preis_netto"];
                $ArtikelDataArray[$ArtikelCounter]["preis_brutto"] = $ArtikelRowArray["highlight_preis_brutto"];

                if ($KundengruppenObject->type == 1) {
                    $ArtikelDataArray[$ArtikelCounter]["preis"] = $ArtikelRowArray["highlight_preis_brutto"];
                    $ArtikelDataArray[$ArtikelCounter]["preis_format"] = number_format($ArtikelRowArray["highlight_preis_brutto"], 2, ",", ".") . " " . $WaehrungObject->symbol;
                    // Nettopreis
                } else {
                    $ArtikelDataArray[$ArtikelCounter]["preis"] = $ArtikelRowArray["highlight_preis_netto"];
                    $ArtikelDataArray[$ArtikelCounter]["preis_format"] = number_format($ArtikelRowArray["highlight_preis_netto"], 2, ",", ".") . " " . $WaehrungObject->symbol;
                }

            } else {

                // Bruttopreis
                if ($KundengruppenObject->type == 1) {
                    $ArtikelDataArray[$ArtikelCounter]["preis"] = $ArtikelRowArray["preis_brutto"];
                    $ArtikelDataArray[$ArtikelCounter]["preis_format"] = number_format($ArtikelRowArray["preis_brutto"], 2, ",", ".") . " " . $WaehrungObject->symbol;
                // Nettopreis
                } else {
                    $ArtikelDataArray[$ArtikelCounter]["preis"] = $ArtikelRowArray["preis_netto"];
                    $ArtikelDataArray[$ArtikelCounter]["preis_format"] = number_format($ArtikelRowArray["preis_netto"], 2, ",", ".") . " " . $WaehrungObject->symbol;
                }
            }
		}

		if($FilterGiveAways)
		{
			$ArtikelDataArray[$ArtikelCounter]["giveaway_preis"] = $ArtikelDataArray[$ArtikelCounter]["preis"] * (100 - $ArtikelRowArray["giveaway_rabattprozent"]) / 100;
			$ArtikelDataArray[$ArtikelCounter]["giveaway_preis_format"] = number_format($ArtikelDataArray[$ArtikelCounter]["giveaway_preis"], 2, ",", ".") . " " . $WaehrungObject->symbol;
		}

		// Mehrfachwaehrung
		$tpl_waehrungarray = GetWaehrungDataArray();
		if($tpl_waehrungarray) {
			foreach ($tpl_waehrungarray as $tpl_waehrung_key => $tpl_waehrung) {
				$waehrungsumrechnung = $ArtikelDataArray[$ArtikelCounter]["preis"] * $tpl_waehrung["umrechnung"];
				$waehrungsformatierung = number_format($waehrungsumrechnung,2,',','');
				$ArtikelDataArray[$ArtikelCounter]["waehrungsformatierung"][$tpl_waehrung_key] .= $waehrungsformatierung . " " .$tpl_waehrung["symbol"];
			}
		}


		// formatierter alter Preis für den Shop
		if ($ArtikelRowArray["preis_alt_brutto"] > 0) {
			if ($KundengruppenObject->type == 1) {
				$ArtikelDataArray[$ArtikelCounter]["preis_alt_format"] = number_format($ArtikelRowArray["preis_alt_brutto"], 2, ",", ".") . " " . $WaehrungObject->symbol;
			} else {
				$ArtikelDataArray[$ArtikelCounter]["preis_alt_format"] = number_format($ArtikelRowArray["preis_alt_netto"], 2, ",", ".") . " " . $WaehrungObject->symbol;
			}
		}

		// kleines Bild formatieren
		if ($ArtikelRowArray["smallImage"] && file_exists(DATEIPFAD . "/images/dbimages/" . $ArtikelRowArray["smallImage"])) {
			$ImageSizeArray = getimagesize(DATEIPFAD . "/images/dbimages/" . $ArtikelRowArray["smallImage"]);
			$ArtikelDataArray[$ArtikelCounter]["imagesmall_imagestring"] = "<img src=\"" . IMAGEPFAD . "dbimages/" . $ArtikelRowArray["smallImage"] . "\" width=\"" . $ImageSizeArray[0] . "\" height=\"" . $ImageSizeArray[1] . "\" alt=\"" . $ArtikelRowArray["artikel_name"] . "\" />";
            $ArtikelDataArray[$ArtikelCounter]['imagesmall'] = $ArtikelRowArray['smallImage'];
        }

		// kleines Bild fuer Crosselling formatieren
		if ($ArtikelRowArray["smallImage"] && file_exists(DATEIPFAD . "/images/dbimages/" . $ArtikelRowArray["smallImage"])) {
			$ImageSizeArray = getimagesize(DATEIPFAD . "/images/dbimages/" . $ArtikelRowArray["smallImage"]);
			$ArtikelDataArray[$ArtikelCounter]["imagesmall_imagestring"] = "<img src=\"" . IMAGEPFAD . "dbimages/" . $ArtikelRowArray["smallImage"] . "\" width=\"" . $ImageSizeArray[0] . "\" height=\"" . $ImageSizeArray[1] . "\" alt=\"" . $ArtikelRowArray["artikel_name"] . "\" />";
			$faktor = min(1, 60 / $ImageSizeArray[0], 60 / $ImageSizeArray[1]);
			$zielbreite = round($ImageSizeArray[0] * $faktor);
			$zielhoehe = round($ImageSizeArray[1] * $faktor);
			$ArtikelDataArray[$ArtikelCounter]["imagesmall_imagestring2"] = "<img src=\"" . IMAGEPFAD . "dbimages/" . $ArtikelRowArray["smallImage"] . "\" width=\"" . $zielbreite . "\" height=\"" . $zielhoehe . "\" alt=\"" . $ArtikelRowArray["artikel_name"] . "\" />";
		}


		// großes Bild formatieren
		if ($ArtikelRowArray["bigImage"] && file_exists(DATEIPFAD . "/images/dbimages/" . $ArtikelRowArray["bigImage"])) {
			$ImageSizeArray = getimagesize(DATEIPFAD . "/images/dbimages/" . $ArtikelRowArray["bigImage"]);
			$ArtikelDataArray[$ArtikelCounter]["imagebig_imagestring"] = "<img src=\"" . IMAGEPFAD . "dbimages/" . $ArtikelRowArray["bigImage"] . "\" width=\"" . $ImageSizeArray[0] . "\" height=\"" . $ImageSizeArray[1] . "\" alt=\"" . $ArtikelRowArray["artikel_name"] . "\" />";
            $ArtikelDataArray[$ArtikelCounter]['imagebig'] = $ArtikelRowArray['bigImage'];

            $faktor = min(1, 300 / $ImageSizeArray[0], 300 / $ImageSizeArray[1]);
            $zielbreite = round($ImageSizeArray[0] * $faktor);
            $zielhoehe = round($ImageSizeArray[1] * $faktor);
            $ArtikelDataArray[$ArtikelCounter]["imagebig_imagestring_format"] = "<img src=\"" . IMAGEPFAD . "dbimages/" . $ArtikelRowArray["bigImage"] . "\" width=\"" . $zielbreite . "\" height=\"" . $zielhoehe . "\" alt=\"" . $ArtikelRowArray["artikel_name"] . "\" />";
        }

		// Lieferantendaten
		$ArtikelDataArray[$ArtikelCounter]["ek_netto_format"] = number_format($ArtikelRowArray["ek_netto"], 2, ",", ".");
		$ArtikelDataArray[$ArtikelCounter]["hauptlieferant"] = $ArtikelRowArray["hauptlieferant"];
		$ArtikelDataArray[$ArtikelCounter]["bestellnummer"] = $ArtikelRowArray["bestellnummer"];

		if ($ArtikelRowArray["hauptlieferant"]) {
			$ArtikelDataArray[$ArtikelCounter]["hauptlieferant_image_string"] = "<img width=\"13\" height=\"13\" border=\"0\" src=\"" . URLPFAD. "admin/images/haeckchen_klein.gif\">";
		} elseif ($ArtikelRowArray["lieferanten_id"]) {
			$ArtikelDataArray[$ArtikelCounter]["hauptlieferant_image_string"] = "<img width=\"13\" height=\"13\" border=\"0\" src=\"" . URLPFAD. "admin/images/startseite_nein.gif\">";
		}

		// Aktivimage
		if ($ArtikelRowArray["aktiv"]) {
			$ArtikelDataArray[$ArtikelCounter]["aktiv_imagestring"] = "<img src=\"../images/on.gif\" width=\"20\" height=\"12\" border=\"0\" alt=\"on\" />";
		} else {
			$ArtikelDataArray[$ArtikelCounter]["aktiv_imagestring"] = "<img src=\"../images/off.gif\" width=\"21\" height=\"12\" border=\"0\" alt=\"off\" />";
		}

		// Startseitenangebot
		if ($ArtikelRowArray["startseitenangebot"]) {
			$ArtikelDataArray[$ArtikelCounter]["startseitenangebot_imagestring"] = "<img src=\"../images/haeckchen_klein.gif\" width=\"13\" height=\"13\" border=\"0\" alt=\"on\" />";
		} else {
			$ArtikelDataArray[$ArtikelCounter]["startseitenangebot_imagestring"] = "<img src=\"../images/startseite_nein.gif\" width=\"13\" height=\"13\" border=\"0\" alt=\"off\" />";
		}

		// Aktuelle Artikel
		if ($ArtikelRowArray["aktuellartikel"]) {
			$ArtikelDataArray[$ArtikelCounter]["aktuellartikel_imagestring"] = "<img src=\"../images/haeckchen_klein.gif\" width=\"13\" height=\"13\" border=\"0\" alt=\"on\" />";
		} else {
			$ArtikelDataArray[$ArtikelCounter]["aktuellartikel_imagestring"] = "<img src=\"../images/startseite_nein.gif\" width=\"13\" height=\"13\" border=\"0\" alt=\"off\" />";
		}

		// kleines Lieferstatusbild
		if ($ArtikelRowArray["lieferstatus_imagesmall"] && file_exists(DATEIPFAD . "/images/dbimages/" . $ArtikelRowArray["lieferstatus_imagesmall"])) {
			$ImageSizeArray = getimagesize(DATEIPFAD . "/images/dbimages/" . $ArtikelRowArray["lieferstatus_imagesmall"]);
			$ArtikelDataArray[$ArtikelCounter]["lieferstatus_smallimagestring"] = "<img src=\"" . IMAGEPFAD . "dbimages/" . $ArtikelRowArray["lieferstatus_imagesmall"] . "\" width=\"" . $ImageSizeArray[0] . "\" height=\"" . $ImageSizeArray[1] . "\" alt=\"" . $ArtikelRowArray["lieferstatusname"] . "\" />";
		}

		// gro�es Lieferstatusbild
		if ($ArtikelRowArray["lager_bestellungen"] > 0 || !$ArtikelRowArray["nulllieferstatusid"]) {

			if ($ArtikelRowArray["lieferstatus_image"] && file_exists(DATEIPFAD . "/images/dbimages/" . $ArtikelRowArray["lieferstatus_image"])) {
				$ImageSizeArray = getimagesize(DATEIPFAD . "/images/dbimages/" . $ArtikelRowArray["lieferstatus_image"]);
				$ArtikelDataArray[$ArtikelCounter]["lieferstatus_imagestring"] = "<img src=\"" . IMAGEPFAD . "dbimages/" . $ArtikelRowArray["lieferstatus_image"] . "\" width=\"" . $ImageSizeArray[0] . "\" height=\"" . $ImageSizeArray[1] . "\" alt=\"" . $ArtikelRowArray["lieferstatusname"] . "\" />";
			} else {
				$ArtikelDataArray[$ArtikelCounter]["lieferstatus_imagestring"] = $ArtikelRowArray["lieferstatus_name"];
			}

		} else {

			if ($ArtikelRowArray["nulllieferstatus_image"] && file_exists(DATEIPFAD . "/images/dbimages/" . $ArtikelRowArray["nulllieferstatus_image"])) {
				$ImageSizeArray = getimagesize(DATEIPFAD . "/images/dbimages/" . $ArtikelRowArray["nulllieferstatus_image"]);
				$ArtikelDataArray[$ArtikelCounter]["lieferstatus_imagestring"] = "<img src=\"" . IMAGEPFAD . "dbimages/" . $ArtikelRowArray["nulllieferstatus_image"] . "\" width=\"" . $ImageSizeArray[0] . "\" height=\"" . $ImageSizeArray[1] . "\" alt=\"" . $ArtikelRowArray["lieferstatusname"] . "\" />";
			} else {
				$ArtikelDataArray[$ArtikelCounter]["lieferstatus_imagestring"] = $ArtikelRowArray["nulllieferstatus_name"];
			}

		}


		// Kategorien einlesen
		$ArtikelDataArray[$ArtikelCounter]["kategorie_array"] = GetKatgorieDataArray($ArtikelRowArray["id"], $LanguageID);

		foreach ($ArtikelDataArray[$ArtikelCounter]["kategorie_array"] as $KategorieData) {
			$ArtikelDataArray[$ArtikelCounter]["kategoriename_array"][] = $KategorieData["name"];
		}

		if ($ArtikelDataArray[$ArtikelCounter]["kategorie_array"][0]["id"] && !$FilterKategorieDoppelt) {
			$ArtikelDataArray[$ArtikelCounter]["kategorieid"] = $ArtikelDataArray[$ArtikelCounter]["kategorie_array"][0]["id"];
		}

//        var_dump($ArtikelDataArray[$ArtikelCounter]['id']);
//        var_dump($ArtikelDataArray[$ArtikelCounter]['lager_bestellungen']);

		// Varianten
		if ($OptionVarianten) {

			// Alle Varianten einlsen
			if ($OptionVarianten == 1) {

				// Artikel mit Merkmalkombinationen
				if ($ArtikelRowArray["merkmalkombination"] || $ArtikelRowArray["merkmalkombinationparentid"]) {

					unset($MerkmalkombinationenSearchField);
					unset($MerkmalkombinationenSearchString);

					$MerkmalkombinationenSearchField[] = "merkmalkombinationparentid";

					if ($ArtikelRowArray["merkmalkombinationparentid"]) {
						$MerkmalkombinationenSearchString[] = $ArtikelRowArray["merkmalkombinationparentid"];
					} else {
						$MerkmalkombinationenSearchString[] = $ArtikelRowArray["id"];
					}

					$MerkmalkombinationenSortField = "merkmalkombinationsort";
					$MerkmalkombinationenSortOrder = "ASC";

//                    echo 'rekurse';
					$MerkmalkombinationenDataArray = GetArtikelDataArray($MerkmalkombinationenSearchField, $MerkmalkombinationenSearchString, $MerkmalkombinationenSortField, $MerkmalkombinationenSortOrder, "", "", "", "", "", "", "", "", 0, $FilterAktiv, "", "", 2);
//                    echo 'rekursend';
					foreach ($MerkmalkombinationenDataArray as $MerkmalkombinationenData) {
                        if(!$MerkmalkombinationenData['verkaufstop'] || $MerkmalkombinationenData['lager_bestellungen'] > 0) {
                            foreach ($MerkmalkombinationenData["varianten_array"] as $VariantenKey => $Merkmalkombinationen) {
                                $ArtikelDataArray[$ArtikelCounter]["varianten_array"][$VariantenKey][$Merkmalkombinationen['merkmalid']] = array('merkmalid' => $Merkmalkombinationen['merkmalid'], 'merkmalname' => $Merkmalkombinationen['merkmalname']);
                            }
                        }
                    }


				// Artikel mit normalen Varianten
				} else {

					$MerkmalauswahlDataArray = GetMerkmalauswahlDataArray($ArtikelRowArray["id"], $LanguageID);

					if ($MerkmalauswahlDataArray) {

						foreach ($MerkmalauswahlDataArray as $MerkmalauswahlKey => $MerkmalArray) {

							$MerkmalCounter = 0;

							foreach ($MerkmalArray as $Merkmal) {

								if ($Merkmal["selected"]) {
									$ArtikelDataArray[$ArtikelCounter]["varianten_array"][$MerkmalauswahlKey][$MerkmalCounter]["merkmalid"] = $Merkmal["id"];
									$ArtikelDataArray[$ArtikelCounter]["varianten_array"][$MerkmalauswahlKey][$MerkmalCounter]["merkmalname"] = $Merkmal["name"];
									$MerkmalCounter++;
								}

							}

						}

					}

				}

			// Nur die Merkmalkombinationen einlesen
			} elseif (($OptionVarianten == 2) && ($ArtikelRowArray["merkmalkombinationparentid"])) {

				for ($VariantenCounter = 1; $VariantenCounter <= 4; $VariantenCounter++) {

					if ($ArtikelRowArray["variante" . $VariantenCounter]) {

						$MerkmalObject = GetMerkmalDetail($ArtikelRowArray["variante" . $VariantenCounter], $_SESSION["languageid"]);

						$ArtikelDataArray[$ArtikelCounter]["varianten_array"][$VariantenCounter]["varianteid"] = $MerkmalObject->varianteid;
						$ArtikelDataArray[$ArtikelCounter]["varianten_array"][$VariantenCounter]["variantename"] = $MerkmalObject->variantename;
						$ArtikelDataArray[$ArtikelCounter]["varianten_array"][$VariantenCounter]["merkmalid"] = $MerkmalObject->merkmalid;
						$ArtikelDataArray[$ArtikelCounter]["varianten_array"][$VariantenCounter]["merkmalname"] = $MerkmalObject->merkmalname;

					}

				}

			}

		}

		// Merkmalkombinationen
		$ArtikelDataArray[$ArtikelCounter]["merkmalkombination"] = $ArtikelRowArray["merkmalkombination"];
		$ArtikelDataArray[$ArtikelCounter]["merkmalkombinationsort"] = $ArtikelRowArray["merkmalkombinationsort"];
		$ArtikelDataArray[$ArtikelCounter]["merkmalkombinationparentid"] = $ArtikelRowArray["merkmalkombinationparentid"];

		if ($ArtikelRowArray["merkmalkombinationstandard"]) {
			$ArtikelDataArray[$ArtikelCounter]["merkmalkombinationstandard"] = 1;
			$ArtikelDataArray[$ArtikelCounter]["merkmalkombinationstandard_imagestring"] = "<img src=\"" . URLPFAD . "admin/images/haeckchen_klein.gif\" width=\"13\" height=\"13\" alt=\"" . $a_ls_standard . "\">";
		}
		if($FilterArtikelGruppen > 0)
		{
			$ArtikelDataArray[$ArtikelCounter]["menge"] = $ArtikelRowArray["menge"];
		}

		$ArtikelCounter++;
	}

	// Zeitmessung
	if (isset($Zeitmessung)) {
		$MicrotimeArray = explode(" ", microtime());
		$MicrotimeAfterDataPrepare = (float)$MicrotimeArray[0] + (float)$MicrotimeArray[1]; ;

		$Suchdauer = ($MicrotimeAfterMySQLQuery - $MicrotimeStart);
	}

	return $ArtikelDataArray;
}

// ********************************************************************************
// ** GetArtikelDataArray
// ** $SearchField
// ** $SearchString
// ** $SortField
// ** $SortOrder
// ** $DataOffset = ""
// ** $DataCount = ""
// ** $KundenEmail = ""
// ** $FilterKategorieID = 0
// ** $FilterKategorieRekursive = 0
// ** $FilterArtikelAbhaenigkeit = 0
// ** $FilterMerkmalkombinationen = 1
// ** $FilterAktiv = 0
// ** $FilterWunschzettel = 0
// ** $OptionVarianten = 0
// ********************************************************************************
function GetArtikelDataArray($SearchField, $SearchString, $SortField, $SortOrder, $DataOffset = "", $DataCount = "", $KundenEmail = "", $FilterHerstellerID = 0, $FilterKategorieID = 0, $FilterKategorieRekursive = 0, $FilterKategorieDoppelt = 0, $FilterArtikelAbhaenigkeit = 0, $FilterMerkmalkombinationen = 1, $FilterAktiv = 0, $FilterWunschzettel = 0, $FilterBestseller = "", $OptionVarianten = 0, $OptionSearchAll = 0, $OptionSearchLike = 0, $LanguageID = 0, $FilterArtikelGruppen = 0, $FilterGiveAways = 0, $GiveAwayMinBestellwert = -1, $facettensucheAuspraegungenFilter = false, $LieferantenSearchOption = 0, $LieferantenID = 0, $LieferantenLagerOption = false, $FilterEKPreis = false) {
//
//	echo "***** ***** ***** *****<br>";
//	echo '$SearchField: ' . $SearchField . "<br>";
//	echo '$SearchString: ' . $SearchString . "<br>";
//	echo '$SortField: ' . $SortField . "<br>";
//	echo '$SortOrder: ' . $SortOrder . "<br>";
//	echo '$DataOffset: ' . $DataOffset . "<br>";
//	echo '$DataCount: ' . $DataCount . "<br>";
//	echo '$KundenEmail: ' . $KundenEmail . "<br>";
//	echo '$FilterHerstellerID: ' . $FilterHerstellerID . "<br>";
//	echo '$FilterKategorieID: ' . $FilterKategorieID . "<br>";
//	echo '$FilterKategorieRekursive: ' . $FilterKategorieRekursive . "<br>";
//	echo '$FilterKategorieDoppelt: ' . $FilterKategorieDoppelt . "<br>";
//	echo '$FilterArtikelAbhaenigkeit: ' . $FilterArtikelAbhaenigkeit . "<br>";
//	echo '$FilterMerkmalkombinationen: ' . $FilterMerkmalkombinationen . "<br>";
//	echo '$FilterAktiv: ' . $FilterAktiv . "<br>";
//	echo '$FilterWunschzettel: ' . $FilterWunschzettel . "<br>";
//	echo '$FilterBestseller: ' . $FilterBestseller . "<br>";
//	echo '$OptionVarianten: ' . $OptionVarianten . "<br>";
//	echo '$OptionSearchAll: ' . $OptionSearchAll . "<br>";
//	echo '$OptionSearchLike: ' . $OptionSearchLike . "<br>";
//	echo '$LanguageID: ' . $LanguageID . "<br>";
//	echo '$FilterArtikelGruppen: ' . $FilterArtikelGruppen . "<br>";
//	echo '$FilterGiveAways: ' . $FilterGiveAways . "<br>";
//	echo '$GiveAwayMinBestellwert: ' . $GiveAwayMinBestellwert . "<br>";
//	echo '$facettensucheAuspraegungenFilter: ' . $facettensucheAuspraegungenFilter . "<br>";
//	echo '$LieferantenSearchOption: ' . $LieferantenSearchOption . "<br>";
//	echo '$LieferantenID: ' . $LieferantenID . "<br>";
//	echo '$LieferantenLagerOption: ' . $LieferantenLagerOption . "<br>";
//	echo '$FilterEKPreis: ' . $FilterEKPreis . "<br>";

	//
//	if (is_array($SearchField)) {
//		echo '<pre>';
//		var_dump($SearchField);
//        echo '</pre>';
//	}
//
//    if (is_array($SearchString)) {
//        echo '<pre>';
//        var_dump($SearchString);
//        echo '</pre>';
//    }

	// $OptionVarianten
	// -1 = Artikel mit Varianten nicht einlesen
	// 0 = keine Varianten einlesen
	// 1 = alle M�glichen Varianten als Array einlesen (abh�ngige von den ausgew�hlten Varianten des Artikels, der Merkmalauswahl und den Merkmalkombinationen)
	// 2 = nur bei Merkmalkombinationen die entsprechenden Merkmale auslesen

	return GetArtikelDataArrayPA(array(
	'SearchField' => $SearchField,
	'SearchString' => $SearchString,
	'SortField' => $SortField,
	'SortOrder' => $SortOrder,
	'DataOffset' => $DataOffset,
	'DataCount' => $DataCount,
	'KundenEmail' => $KundenEmail,
	'FilterHerstellerID' => $FilterHerstellerID,
	'FilterKategorieID' => $FilterKategorieID,
	'FilterKategorieRekursive' => $FilterKategorieRekursive,
	'FilterKategorieDoppelt' => $FilterKategorieDoppelt,
	'FilterArtikelAbhaenigkeit' => $FilterArtikelAbhaenigkeit,
	'FilterMerkmalkombinationen' => $FilterMerkmalkombinationen,
	'FilterAktiv' => $FilterAktiv,
	'FilterWunschzettel' => $FilterWunschzettel,
	'FilterBestseller' => $FilterBestseller,
	'OptionVarianten' => $OptionVarianten,
	'OptionSearchAll' => $OptionSearchAll,
	'OptionSearchLike' => $OptionSearchLike,
	'LanguageID' => $LanguageID,
	'FilterArtikelGruppen' => $FilterArtikelGruppen,
	'FilterGiveAways' => $FilterGiveAways,
	'GiveAwayMinBestellwert' => $GiveAwayMinBestellwert,
	'facettensucheAuspraegungenFilter' => $facettensucheAuspraegungenFilter,
	'LieferantenSearchOption' => $LieferantenSearchOption,
	'LieferantenID' => $LieferantenID,
	'LieferantenLagerOption' => $LieferantenLagerOption,
	'FilterEKPreis' => $FilterEKPreis
	));
}

function SaveArtikelKategorie($ArtikelID, $SelectedKategorieArray) {

	// SEO Einstellungen einlesen
	$Einstellungen = GetEinstellungen('', 'seo');

	if (!$SelectedKategorieArray) {
		$SelectedKategorieArray = array();
	}

	$DeleteKategorieArray = array();
	$OldKategorieArray = array();

	// alle aktiven Sprachen für die SEO URLs einlesen
	$SQLString = 'SELECT ';
	$SQLString .= TABLE_LANGUAGE . '.language_id ';
	$SQLString .= 'FROM ';
	$SQLString .= TABLE_LANGUAGE . ' ';
	$SQLString .= 'WHERE ';
	$SQLString .= "(";
	$SQLString .= '(' . TABLE_LANGUAGE . '.language_active = 1) AND ';
	$SQLString .= " 1)";

	$MySQLQueryReference = errorlogged_mysql_query($SQLString);

	while ($LanguageRow = mysql_fetch_array($MySQLQueryReference, MYSQL_ASSOC)) {
		$LanguageIDArray[] = $LanguageRow['language_id'];
	}

	// alte Kategoriezuordnungen einlesen
	$SQLString = 'SELECT ';
	$SQLString .= TABLE_KATEGORIERELATION . '.kategorieid ';
	$SQLString .= 'FROM ';
	$SQLString .= TABLE_KATEGORIERELATION . ' ';
	$SQLString .= 'WHERE ';
	$SQLString .= TABLE_KATEGORIERELATION . '.artikelid = \'' . $ArtikelID . '\' ';

	$MySQLQueryReference = errorlogged_mysql_query($SQLString);

	// Prüfen, welche Kategoriezuordnungen gelöscht wurden
	while ($KategorieRelationRow = mysql_fetch_array($MySQLQueryReference, MYSQL_ASSOC)) {

		if (!in_array($KategorieRelationRow['kategorieid'], $SelectedKategorieArray)) {
			$DeleteKategorieArray[] = $KategorieRelationRow['kategorieid'];
		}

		$OldKategorieArray[] = $KategorieRelationRow['kategorieid'];

	}

	// alte Einträge löschen und die SEO URLs auf die SEO URL des Artikels ohne Kategoriezuordnung
	// umleiten
	foreach ($DeleteKategorieArray as $DeleteKategorieID) {

		// Kategoriezuorndung löschen
		$SQLString = 'DELETE FROM ';
		$SQLString .= TABLE_KATEGORIERELATION . ' ';
		$SQLString .= 'WHERE ';
		$SQLString .= TABLE_KATEGORIERELATION . '.artikelid = \'' . $ArtikelID . '\' AND ';
		$SQLString .= TABLE_KATEGORIERELATION . '.kategorieid = \'' . $DeleteKategorieID . '\' ';

		$MySQLQueryReference = errorlogged_mysql_query($SQLString);

		foreach ($LanguageIDArray as $LanguageID) {

			// SEO URL des Einzelartikels einlesen
			$SQLString = 'SELECT ';
			$SQLString .= TABLE_SEOURLS . '.seourl_id ';
			$SQLString .= 'FROM ';
			$SQLString .= TABLE_SEOURLS . ' ';
			$SQLString .= 'WHERE ';
			$SQLString .= TABLE_SEOURLS . '.artikel_id = \'' . $ArtikelID . '\' AND ';
			$SQLString .= TABLE_SEOURLS . '.kategorie_id = \'0\' AND ';
			$SQLString .= TABLE_SEOURLS . '.language_id = \'' . $LanguageID . '\' AND ';
			$SQLString .= TABLE_SEOURLS . '.type = \'' . SEOURL_TYPE_ARTIKEL . '\' AND ';
			$SQLString .= TABLE_SEOURLS . '.http_status = \'0\' ';

			$SEOURLObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));

			// Artikel mit gelöschten Kategoriezuweisung löschen
			DeleteArtikelSEOURLElement($ArtikelID, $DeleteKategorieID, 0, 0, $LanguageID, $SEOURLObject->seourl_id, false);

		}

	}

	// neue Einträge eintragen und sie als SEO URL hinterlegen
	foreach ($SelectedKategorieArray as $SelectedKategorie) {

		if (!in_array($SelectedKategorie, $OldKategorieArray)) {

			$SQLString = 'INSERT INTO ' . TABLE_KATEGORIERELATION . ' SET ';
			$SQLString .= 'artikelid = \'' . $ArtikelID . '\', ';
			$SQLString .= 'kategorieid = \'' . $SelectedKategorie . '\' ';

			$MySQLQueryReference = errorlogged_mysql_query($SQLString);

			if ($Einstellungen->seo->sprechende_urls_artikel_mit_kategorie) {

				foreach ($LanguageIDArray as $LanguageID) {

					// SEO URL Name des Artikels ermitteln
					$ArtikelSEONameArray = GetArtikelSEONameArray($ArtikelID, $LanguageID);

					unset($KategorieSEOURL);

					$KategorieSEOPathArray = GetKategorieSEOPathArray($SelectedKategorie, $LanguageID, array());

					// SEO Kategorie Pfad zusammensetzen
					foreach ($KategorieSEOPathArray as $KategorieSEOPathElement) {
						$KategorieSEOURL .= $KategorieSEOPathElement['url_name'] . '/';
					}

					$ArtikelSEOURL = $SEOURLLanguageName . substr($KategorieSEOURL, 0, strlen($KategorieSEOURL) - 1) . '/' . $ArtikelSEONameArray['url_artikel_name'];

					// Pruefen, ob für den Artikel ein Eintrag mit dem gleichen SEO Pfad schon vorhanden ist.
					if (ExistsArtikelSEOURL($ArtikelID, $SelectedKategorie, 0, 0, $LanguageID, $ArtikelSEOURL)) {
						continue;
					}

					// SEO URL auf eindeutigkeit pruefen und ggf. eine Nummer anhaengen
					$ArtikelSEOURL = GetUniqueURL($ArtikelSEOURL);

					// SEO URL sichern
					$SEOURLID = SaveArtikelSEOURL($ArtikelID, $SelectedKategorie, 0, 0, $LanguageID, $ArtikelSEOURL, $KategorieSEOPathArray);

				}

			}

		}

	}

	// ********************************************************************************
	// ** Merkmalkombinationen abgleichen
	// ********************************************************************************
	UpdateMerkmalkombination($ArtikelID);

}

function GetArtikelKategorieDataArray($KategorieID, $Rekursiv, $Anzahl) {

	// die IDs der Kategorien ermitteln
	if ($Rekursiv) {

		$KategorieIDInString = implode(GetKategorieIDs($KategorieID, ""), ",");

	} else {

		$KategorieIDInString = $KategorieID;

	}

	// Die Artikel Abfragen
	//$SQLString = "SELECT DISTINCT " . TABLE_ARTIKEL . ".*, " . TABLE_KATEGORIE . ".name AS kategoriename, " . TABLE_KATEGORIE . ".id AS kategorieid FROM " . TABLE_ARTIKEL . " LEFT JOIN " . TABLE_KATEGORIERELATION . " ON " . TABLE_ARTIKEL . ".id = " . TABLE_KATEGORIERELATION . ".artikelid LEFT JOIN " . TABLE_KATEGORIE . " ON " . TABLE_KATEGORIERELATION . ".kategorieid = " . TABLE_KATEGORIE . ".id WHERE " . TABLE_KATEGORIERELATION . ".kategorieid IN (" . $KategorieIDInString . ") AND " . TABLE_ARTIKEL . ".aktiv = '1' ORDER BY " . TABLE_ARTIKEL . ".datum DESC LIMIT 0," . $Anzahl;
	$SQLString = "SELECT DISTINCT " . TABLE_ARTIKEL . ".* FROM " . TABLE_ARTIKEL . " LEFT JOIN " . TABLE_KATEGORIERELATION . " ON " . TABLE_ARTIKEL . ".id = " . TABLE_KATEGORIERELATION . ".artikelid LEFT JOIN " . TABLE_KATEGORIE . " ON " . TABLE_KATEGORIERELATION . ".kategorieid = " . TABLE_KATEGORIE . ".id WHERE " . TABLE_KATEGORIERELATION . ".kategorieid IN (" . $KategorieIDInString . ") AND " . TABLE_ARTIKEL . ".aktiv = '1' ORDER BY " . TABLE_ARTIKEL . ".datum DESC LIMIT 0," . $Anzahl;
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);

	while ($RowArray = mysql_fetch_array($MySQLQueryReference)) {

		// Kategorie einlesen
		$SQLString = "SELECT " . TABLE_KATEGORIE . ".id as kategorieid, " . TABLE_KATEGORIE . ".name as kategoriename FROM " . TABLE_KATEGORIE . " LEFT JOIN " . TABLE_KATEGORIERELATION . " ON " . TABLE_KATEGORIERELATION . ".kategorieid = " . TABLE_KATEGORIE . ".id WHERE " . TABLE_KATEGORIERELATION . ".artikelid = '" . $RowArray["id"] . "' ORDER BY " . TABLE_KATEGORIE . ".name LIMIT 0,1";
		$KategorieObject = mysql_fetch_object(errorlogged_mysql_query($SQLString));

		$RowArray["kategorieid"] = $KategorieObject->kategorieid;
		$RowArray["kategoriename"] = $KategorieObject->kategoriename;

		$ResultDataArray[] = $RowArray;

	}


	return $ResultDataArray;

}



function GetAllKategorien() {

	// Alle Kategorien einlesen
	$SQLString = "SELECT * FROM " . TABLE_KATEGORIE . " WHERE " . TABLE_KATEGORIE . ".root = 1 ORDER BY NAME";
	$KategorieQuerry = errorlogged_mysql_query($SQLString);

	while($KategorieDataRow = mysql_fetch_object($KategorieQuerry)) {
		$NewCount = count($KategorieDataArray);
		$KategorieDataArray[$NewCount]["id"] = $KategorieDataRow->id;
		$KategorieDataArray[$NewCount]["name"] = $KategorieDataRow->name;
		$KategorieDataArray[$NewCount]["neu"] = 0;
	}

	return $KategorieDataArray;

}





function XAddMwSt($MwStDataArray, $MaxMwStID, $MwStValue) {

	$NewCount = count($MwStDataArray);
	$MwStDataArray[$NewCount]["id"] = $MaxMwStID;
	$MwStDataArray[$NewCount]["mwst"] = $MwStValue;
	$MwStDataArray[$NewCount]["neu"] = 1;

	return $MwStDataArray;

}

function XAddVarianten($VariantenDataArray, $MaxVariantenID, $VariantenName) {

	$NewCount = count($VariantenDataArray);
	$VariantenDataArray[$NewCount]["id"] = $MaxVariantenID;
	$VariantenDataArray[$NewCount]["name"] = $VariantenName;
	$VariantenDataArray[$NewCount]["neu"] = 1;

	return $VariantenDataArray;

}




function getArtikelList($artikelKatId)
{
	$SQLString = "SELECT ";
	$SQLString .= TABLE_KATEGORIERELATION.".kategorieid,";
	$SQLString .= TABLE_ARTIKEL.".id, ";
	$SQLString .= TABLE_ARTIKEL.".artikel_name, ";
	$SQLString .= TABLE_ARTIKEL.".artikel_nr ";
	$SQLString .= "FROM " . TABLE_KATEGORIERELATION . " ";
	$SQLString .= "LEFT JOIN " . TABLE_ARTIKEL . " ON ";
	$SQLString .= TABLE_ARTIKEL.".id = " . TABLE_KATEGORIERELATION.".artikelid ";
	$SQLString .= "WHERE ".TABLE_KATEGORIERELATION.".kategorieid = '" . $artikelKatId ."' and ".TABLE_ARTIKEL.".id != ''  ORDER by ".TABLE_ARTIKEL.".artikel_name";

	//echo $SQLString;

	$ArtikelQueryReference = errorlogged_mysql_query($SQLString);
	while($artikelListRow = mysql_fetch_array($ArtikelQueryReference, MYSQL_ASSOC))
	{
		$artikelListCounter++;
		$artikelListArray[$artikelListCounter] = $artikelListRow;
		$artikelListArray[$artikelListCounter]["artikel_name"];
	}
	return $artikelListArray;
}




// ********************************************************************************
// ** Speichern von weiteren Bildern
// ********************************************************************************
function SaveArtikelBilder($ArtikelID, $ImageSmallArray, $ImageBigArray, $ImageArray) {


	//Shopeinstellungen
	$ShopeinstellungenObject = GetShopeinstellungDetail();


	// ********************************************************************************
	// ** Daten speichern
	// ********************************************************************************
	$SQLString = "INSERT INTO " . TABLE_ARTIKEL_BILDER . " SET ";
	$SQLString .= TABLE_ARTIKEL_BILDER . ".artikel_id = '" . $ArtikelID . "' ";
	$MySQLQueryReference = errorlogged_mysql_query($SQLString);

	$ArtikelBildID = mysql_insert_id();

	if($ShopeinstellungenObject->bildupload_aktiv == '1'){

		// ********************************************************************************
		// ** Bilder mit GD auswerten
		// ********************************************************************************
		if($ImageArray["type"] != ""){

				$TempNameArray = explode(".", $ImageArray["name"]);

				$NewImageName = $NewImageName . "." . $TempNameArray[count($TempNameArray) - 1];

				$NewSmallImageName = "artikel_" . sprintf("%07d", $ArtikelID). "_".$ArtikelBildID . "_s";
				$NewSmallImageName .= "." . $TempNameArray[count($TempNameArray) - 1];

				$NewMediumImageName = "artikel_" . sprintf("%07d", $ArtikelID). "_".$ArtikelBildID . "_m";
				$NewMediumImageName .= "." . $TempNameArray[count($TempNameArray) - 1];

				$NewBigImageName = "artikel_" . sprintf("%07d", $ArtikelID). "_".$ArtikelBildID . "_b";
				$NewBigImageName .= "." . $TempNameArray[count($TempNameArray) - 1];



				if($ImageArray["type"] == "image/jpeg" || $ImageArray["type"] == "image/pjpeg"){
					$ImageType = 'jpg';
				}elseif($ImageArray["type"] == "image/gif"){
					$ImageType = 'gif';
				}elseif($ImageArray["type"] == "image/png"){
					$ImageType = 'png';	
				}else{
					echo "<font color=\"red\">FEHLER: Bitte laden Sie nur JPG- , PNG- oder GIF-Dateien hoch.</font><br><a href=\"javascript:history.back()\">zur&uuml;ck</a>";
					die;
				}


				$ImageNameArray = ReziseImage($ImageArray["tmp_name"],$NewSmallImageName,$NewBigImageName,$ImageType);


				$SQLString = "UPDATE " . TABLE_ARTIKEL_BILDER . " SET ";
				$SQLString .= TABLE_ARTIKEL_BILDER . ".smallImage  = '" . $ImageNameArray[0] . "', ";
				$SQLString .= TABLE_ARTIKEL_BILDER . ".bigImage  = '" . $ImageNameArray[1] . "' ";
				$SQLString .= " WHERE " . TABLE_ARTIKEL_BILDER . ".artikel_id = '" . $ArtikelID . "' ";
				$SQLString .= " AND " . TABLE_ARTIKEL_BILDER . ".imageid = '" . $ArtikelBildID . "'";

	//			echo $SQLString;

				$MySQLQueryReference = errorlogged_mysql_query($SQLString);

			}
	}else{

		// ********************************************************************************
		// ** Bilder ohne GD auswerten
		// ********************************************************************************

		// kleines Bild
		if ($ImageSmallArray["size"] > 0) {
			$NewImageName = "artikel_" . sprintf("%07d", $ArtikelID) . "_".$ArtikelBildID."_s";

			// altes Bild l�schen
			unlink_wc(DATEIPFAD . "images/dbimages/", $NewImageName . ".*");

			// tempor�re Datei kopieren
			$TempNameArray = explode(".", $ImageSmallArray["name"]);
			$NewImageName = $NewImageName . "." . $TempNameArray[count($TempNameArray) - 1];
			move_uploaded_file($ImageSmallArray["tmp_name"], DATEIPFAD . "images/dbimages/" . $NewImageName);
			chmod(DATEIPFAD . "images/dbimages/" . $NewImageName, 0644);

			// Datenbank updaten
			$SQLString = "UPDATE " . TABLE_ARTIKEL_BILDER . " SET ";
			$SQLString .= TABLE_ARTIKEL_BILDER . ".smallImage  = '" . $NewImageName . "' ";
			$SQLString .= " WHERE " . TABLE_ARTIKEL_BILDER . ".imageid = '" . $ArtikelBildID . "'";

			$MySQLQueryReference = errorlogged_mysql_query($SQLString);

		}
		// gro�es Bild
		if ($ImageBigArray["size"] > 0) {

			$NewImageName = "artikel_" . sprintf("%07d", $ArtikelID) . "_".$ArtikelBildID."_b";

			// altes Bild l�schen
			unlink_wc(DATEIPFAD . "images/dbimages/", $NewImageName . ".*");

			// tempor�re Datei kopieren
			$TempNameArray = explode(".", $ImageBigArray["name"]);
			$NewImageName = $NewImageName . "." . $TempNameArray[count($TempNameArray) - 1];
			move_uploaded_file($ImageBigArray["tmp_name"], DATEIPFAD . "images/dbimages/" . $NewImageName);
			chmod(DATEIPFAD . "images/dbimages/" . $NewImageName, 0644);

			// Datenbank updaten
			$SQLString = "UPDATE " . TABLE_ARTIKEL_BILDER . " SET ";
			$SQLString .= TABLE_ARTIKEL_BILDER . ".bigImage  = '" . $NewImageName . "' ";
			$SQLString .= " WHERE " . TABLE_ARTIKEL_BILDER . ".imageid = '" . $ArtikelBildID . "'";

			$MySQLQueryReference = errorlogged_mysql_query($SQLString);

		}
	}

	return $ArtikelID;

}



function getArtikelBilderDataArray($ArtikelID) {

	$SQLString = "SELECT ";
	$SQLString .= TABLE_ARTIKEL_BILDER.".imageid, ";
	$SQLString .= TABLE_ARTIKEL_BILDER.".artikel_id, ";
	$SQLString .= TABLE_ARTIKEL_BILDER.".smallImage, ";
	$SQLString .= TABLE_ARTIKEL_BILDER.".bigImage ";
	$SQLString .= "FROM " . TABLE_ARTIKEL_BILDER . " ";
	$SQLString .= "WHERE ".TABLE_ARTIKEL_BILDER.".artikel_id = '" . $ArtikelID ."' ORDER by ".TABLE_ARTIKEL_BILDER.".imageid";

	//echo '$SQLString: ' . $SQLString;

	$ArtikelQueryReference = errorlogged_mysql_query($SQLString);

	$ArtikelBildCounter = 0;
	$ArtikelBildDataArray = array();

	while($artikelBildRow = mysql_fetch_array($ArtikelQueryReference, MYSQL_ASSOC))
	{
		$ArtikelBildDataArray[$ArtikelBildCounter]["imageid"] = $artikelBildRow["imageid"];
		$ArtikelBildDataArray[$ArtikelBildCounter]["artikel_id"] = $artikelBildRow["artikel_id"];
		$ArtikelBildDataArray[$ArtikelBildCounter]["smallImage"] = $artikelBildRow["smallImage"];
		$ArtikelBildDataArray[$ArtikelBildCounter]["bigImage"] = $artikelBildRow["bigImage"];

		// kleines Bild formatieren
		if ($artikelBildRow["smallImage"] && file_exists(DATEIPFAD . "/images/dbimages/" . $artikelBildRow["smallImage"])) {
			$ImageSizeArray = getimagesize(DATEIPFAD . "/images/dbimages/" . $artikelBildRow["smallImage"]);
			$faktor = min(1, 40 / $ImageSizeArray[0], 40 / $ImageSizeArray[1]);
			$zielbreite = round($ImageSizeArray[0] * $faktor);
			$zielhoehe = round($ImageSizeArray[1] * $faktor);
			$ArtikelBildDataArray[$ArtikelBildCounter]["imagesmall_imagestring"] = "<img src=\"" . URLPFAD . "images/dbimages/" . $artikelBildRow["smallImage"] . "\" width=\"" . $zielbreite . "\" height=\"" . $zielhoehe . "\" alt=\" \" />";
		}

		// grosses Bild formatieren
		if ($artikelBildRow["bigImage"] && file_exists(DATEIPFAD . "/images/dbimages/" . $artikelBildRow["bigImage"])) {
			$ImageSizeArray = getimagesize(DATEIPFAD . "/images/dbimages/" . $artikelBildRow["bigImage"]);
			$ArtikelBildDataArray[$ArtikelBildCounter]["imagebig_imagestring"] = "<img src=\"" . URLPFAD . "images/dbimages/" . $artikelBildRow["bigImage"] . "\" width=\"" . $ImageSizeArray[0] . "\" height=\"" . $ImageSizeArray[1] . "\" alt=\" \" />";
			$ArtikelBildDataArray[$ArtikelBildCounter]["imagebig_width"] = $ImageSizeArray[0];
			$ArtikelBildDataArray[$ArtikelBildCounter]["imagebig_height"] = $ImageSizeArray[1];
		}

		$ArtikelBildCounter++;
	}

	return $ArtikelBildDataArray;
}



function deleteMoreImages($ArtikelBildID, $ArtikelID) {

	$ImageName = "artikel_" . sprintf("%07d", $ArtikelID) . "_".$ArtikelBildID."_s";
	unlink_wc(DATEIPFAD . "images/dbimages/", $ImageName . ".*");

	$ImageName = "artikel_" . sprintf("%07d", $ArtikelID) . "_".$ArtikelBildID."_b";
	unlink_wc(DATEIPFAD . "images/dbimages/", $ImageName . ".*");

	// Banner
	$SQLString = "DELETE FROM " . TABLE_ARTIKEL_BILDER . " WHERE imageid = '" . $ArtikelBildID . "'";
	$MySQLQuerryReferenz = errorlogged_mysql_query($SQLString);
}


function ReziseImage($Image,$NewSmallImageName, $NewBigImageName,$ImageType){

	global $ImageSettingsArray;

	for($i=0;$i<=1;$i++){

		$Width = $ImageSettingsArray[$i]["image_width"];
		$Height = $ImageSettingsArray[$i]["image_height"];

		switch($i){

			case 0:

				$NewImageName = $NewSmallImageName;

				break;


			case 1:

				$NewImageName = $NewBigImageName;

				break;

		}



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
			$NewImage = imagecreatefromjpeg($Image);
		}elseif($ImageType == 'png'){
			$NewImage = imagecreatefromgif($Image);
		}
		imagecopyresampled($NewImageRef, $NewImage, 0, 0, 0, 0, $NewImageWidth, $NewImageHeight, $ImageSizeArray[0], $ImageSizeArray[1]);


		if($ImageType == 'jpg'){
			imagejpeg($NewImageRef, DATEIPFAD . "images/dbimages/" . $NewImageName, 85);
		}elseif($ImageType == 'gif'){
			imagegif($NewImageRef, DATEIPFAD . "images/dbimages/" . $NewImageName, 85);
		}
		elseif($ImageType == 'png'){
			imagegif($NewImageRef, DATEIPFAD . "images/dbimages/" . $NewImageName, 85);
		}

		imagedestroy($NewImage);
		imagedestroy($NewImageRef);

		chmod(DATEIPFAD . "images/dbimages/" . $NewImageName, 0644);

		$ImageNameArray[] = $NewImageName;

	}

	return $ImageNameArray;
}

function GetArtikelPreisstaffel($ArtikelID,$KundenEmail = false){

	$WaehrungObject = GetWaehrungDetail();

		// Kundengruppe ermitteln
	if (!$KundenEmail) {
		$KundengruppenID = GetDefaultKundengruppe();
		$KundengruppenObject = GetKundengruppenDetail($KundengruppenID);
	} else {
		$KundengruppenObject = GetKundengruppenDetail("", $KundenEmail);
	}

	$SQLString = 'SELECT id, menge, format(preis_brutto, 2) as preis_brutto, format(preis_netto, 2) as preis_netto ';
	$SQLString .= 'FROM ' . TABLE_ARTIKEL_PREISSTAFFEL . ' ';
	$SQLString .= 'WHERE artikel_id = ' . $ArtikelID . ' ';
	$SQLString .= 'ORDER BY menge ASC';

	$QueryReference = mysql_query($SQLString);

    $Preisstaffel = array();

    $i = 0;
	while($row = mysql_fetch_assoc($QueryReference)){

		$Preisstaffel[$i] = $row;
		$Preisstaffel[$i]['preis_brutto'] = $Preisstaffel[$i]['preis_brutto'] . ' ' . $WaehrungObject->symbol;
		$Preisstaffel[$i]['preis_netto'] = $Preisstaffel[$i]['preis_netto'] . ' ' . $WaehrungObject->symbol;

		//brutto
		if ($KundengruppenObject->type == 1){
			$Preisstaffel[$i]['preis'] = $Preisstaffel[$i]['preis_brutto'];

		}
		//netto
		else{
			$Preisstaffel[$i]['preis'] = $Preisstaffel[$i]['preis_netto'];
		}
		$i++;
	}

	return $Preisstaffel;
}

function UpdatePreisstaffelMwst($ArtikelID, $Preisformat)
{
	$SQLString = 'SELECT '.TABLE_MWST.'.mwst ';
	$SQLString .= 'FROM ' . TABLE_ARTIKEL . ' ';
	$SQLString .= 'INNER JOIN '.TABLE_MWST.' ON ' . TABLE_ARTIKEL . '.mwst = '.TABLE_MWST.'.id ';
	$SQLString .= 'WHERE ' . TABLE_ARTIKEL . '.id = ' . $ArtikelID;
	$MwSt = mysql_result(mysql_query($SQLString),0,0);

	$SQLString = 'UPDATE ' . TABLE_ARTIKEL_PREISSTAFFEL . ' SET ';
	if($Preisformat == 1)
		$SQLString .= 'preis_brutto = ROUND(preis_netto * (100+' . $MwSt . ') / 100, 2) ';
	else
		$SQLString .= 'preis_netto = ROUND(preis_brutto * 100 / (100 + '. $MwSt .'), 2) ';
	$SQLString .= ' WHERE ' . TABLE_ARTIKEL_PREISSTAFFEL . '.artikel_id = ' . $ArtikelID;
	$SQLString .= ' OR ' . TABLE_ARTIKEL_PREISSTAFFEL . '.artikel_id IN ';
		$SQLString .= '(SELECT ' . TABLE_ARTIKEL . '.id FROM ' . TABLE_ARTIKEL;
		$SQLString .= ' WHERE ' . TABLE_ARTIKEL . '.merkmalkombinationparentid = ' . $ArtikelID . ')';


	mysql_query($SQLString);
}

function SaveArtikelPreisstaffel($ArtikelID, $Menge, $Preis, $Preisformat){


	$SQLString = 'SELECT '.TABLE_MWST.'.mwst ';
	$SQLString .= 'FROM ' . TABLE_ARTIKEL . ' ';
	$SQLString .= 'INNER JOIN '.TABLE_MWST.' ON ' . TABLE_ARTIKEL . '.mwst = '.TABLE_MWST.'.id ';
	$SQLString .= 'WHERE ' . TABLE_ARTIKEL . '.id = \'' . $ArtikelID . '\'';

	$MwSt = mysql_result(mysql_query($SQLString),0,0);

	$SQLString = 'INSERT INTO ' . TABLE_ARTIKEL_PREISSTAFFEL . ' SET ';
	$SQLString .= 'artikel_id = ' . $ArtikelID . ', ';
	$SQLString .= 'menge = ' . $Menge . ', ';
	if($Preisformat == 1)
	{
		$SQLString .= 'preis_netto = ' . $Preis . ', ';
		$SQLString .= 'preis_brutto = ' . round(($Preis + ($Preis / (100))*$MwSt),2) . ' ';
	}
	else
	{
		$SQLString .= 'preis_netto = ' . round(($Preis * 100) / (100+$MwSt),2) . ', ';
		$SQLString .= 'preis_brutto = ' . $Preis . ' ';
	}

//	echo $SQLString;
	mysql_query($SQLString);

}

function DeleteArtikelPreisstaffel($PreisstaffelID){

	$SQLString = 'DELETE FROM ' . TABLE_ARTIKEL_PREISSTAFFEL . ' WHERE id = ' . $PreisstaffelID . ' ';
	mysql_query($SQLString);
}

function MoveArtikelPreisstaffel($ArtikelID, $ArtikelIDNew)
{
	$SQLString = "UPDATE " . TABLE_ARTIKEL_PREISSTAFFEL . " SET artikel_id = " . $ArtikelIDNew;
	$SQLString .= " WHERE artikel_id = " . $ArtikelID;

	mysql_query($SQLString);
}



// ********************************************************************************
// ** Seitennavigation
// ********************************************************************************

function SeitenNavigation($NavigationType, $SEOURLArray, $ArtikelAnzahl, $DataOffset, $DataCount, $ElementID = '', $AdditionalParameter = array()) {

	global $Einstellungen;

	if (!$Einstellungen) {
		$Einstellungen = GetEinstellungen();
	}

	$ParameterArray = array();

	if ($NavigationType == NAVIGATION_TYPE_KATEGORIE) {

		if ($Einstellungen->seo->sprechende_urls_aktiv) {
			$NavigationURL = GetKategorieLink($ElementID, $SEOURLArray['kategorie'][$ElementID]);
		} else {
			$NavigationURL = URLPFAD . "themes/kategorie/index.php";
			$ParameterArray['kategorieid'] = $ElementID;
		}

	}

	if ($NavigationType == NAVIGATION_TYPE_AKTIONEN) {

		if ($Einstellungen->seo->sprechende_urls_aktiv) {
			$NavigationURL = GetAktionsLink($ElementID, $SEOURLArray['aktion'][$ElementID]);
		} else {
			$NavigationURL = URLPFAD . "themes/kategorie/aktion.php";
			$ParameterArray['aktionsid'] = $ElementID;
		}

	}

	if ($NavigationType == NAVIGATION_TYPE_HERSTELLER) {

		if ($Einstellungen->seo->sprechende_urls_aktiv) {
			$NavigationURL = GetHerstellerLink($ElementID, $SEOURLArray['hersteller'][$ElementID]);
		} else {
			$NavigationURL = URLPFAD . "themes/kategorie/hersteller.php";
			$ParameterArray['herstellerid'] = $ElementID;
		}

	}

	if ($NavigationType == NAVIGATION_TYPE_SUCHE) {

		$NavigationURL = URLPFAD . "themes/suche/index.php";

	}

	if ($AdditionalParameter) {
		$ParameterArray = array_merge($ParameterArray, $AdditionalParameter);
	}

	foreach ($ParameterArray as $Parameter => $Value) {
		$NavigationURL = AddURLParameter($NavigationURL, $Parameter, $Value);
	}

		$SeitenNaviArray["seitenarray"] = Array();

		// Seiten
		$SeitenMaximum = SEITENMAXIMUM;
		$Seitenanzahl = ceil($ArtikelAnzahl / $DataCount);
		$Seiteaktuell = ((($DataOffset) / $DataCount) + 1);

		if ($Seitenanzahl > $SeitenMaximum) {

			if ($Seiteaktuell < $SeitenMaximum) {

				$SeitenStart = 1;
				$SeitenEnd = $SeitenMaximum;

				$SeitenStartPlatzhalter = false;
				$SeitenEndPlatzhalter = true;

			} elseif ($Seiteaktuell > ($Seitenanzahl - $SeitenMaximum + 1)) {

				$SeitenStart = $Seitenanzahl - ($SeitenMaximum - 1);
				$SeitenEnd = $Seitenanzahl;

				$SeitenStartPlatzhalter = true;
				$SeitenEndPlatzhalter = false;

			} else {

				$SeitenStart = $Seiteaktuell - floor(($SeitenMaximum / 2));
				$SeitenEnd = $Seiteaktuell + floor(($SeitenMaximum / 2));

				$SeitenStartPlatzhalter = true;
				$SeitenEndPlatzhalter = true;

			}

		} else {

			$SeitenStart = 1;
			$SeitenEnd = $Seitenanzahl;

		}


		for ($SeitenCounter = $SeitenStart; $SeitenCounter <= $SeitenEnd; $SeitenCounter++) {

			if(!isset($SeitenNaviArray["seitenarray"][$SeitenCounter])) { $SeitenNaviArray["seitenarray"][$SeitenCounter] = null; }

			if ($SeitenCounter > $SeitenStart) {
				//$SeitenNaviArray["seitenarray"][$SeitenCounter - 1] .= "&nbsp;&nbsp;|&nbsp;&nbsp;";
			}

			if ($Seiteaktuell == $SeitenCounter) {

				$SeitenNaviArray["seitenarray"][$SeitenCounter] .= "<span class=\"au_page_active\">";
				$SeitenNaviArray["seitenarray"][$SeitenCounter] .= $SeitenCounter;
				$SeitenNaviArray["seitenarray"][$SeitenCounter] .= "</span>";

			} else {

//				$SeitenNaviArray["seitenarray"][$SeitenCounter] = "<a class=\"au_page_link\" href=\"".$NavDatei."?searchstring=" . $SerachString . "&amp;sucheallgemein=".$SerachString."&amp;searchfield=" . $SerachString . "&amp;sortfield=" . $SortField . "&amp;suchkategorie=".$Suchkategorie."&amp;sortorder=" . $SortOrder . "&amp;dataoffset=" . (($SeitenCounter - 1) * $DataCount) . "&amp;datacount=" . $DataCount . "\">";
				$SeitenNaviArray["seitenarray"][$SeitenCounter] = "<a class=\"au_page_link\" href=\"" . AddURLParameter($NavigationURL, 'dataoffset', (($SeitenCounter - 1) * $DataCount)) . "\">";
				$SeitenNaviArray["seitenarray"][$SeitenCounter] .= $SeitenCounter;
				$SeitenNaviArray["seitenarray"][$SeitenCounter] .= "</a>";

			}

		}

		if (isset($SeitenStartPlatzhalter)) {

			$SeitenNaviArray["seitenarray"][$SeitenStart - 1] .= "<span>";
//			$SeitenNaviArray["seitenarray"][$SeitenStart - 1] .= "<a class=\"au_page_link\" href=\"".$NavDatei."?searchstring=" . $SerachString . "&amp;sucheallgemein=".$SerachString."&amp;searchfield=" . $SerachString . "&samp;ortfield=" . $SortField . "&amp;suchkategorie=".$Suchkategorie."&amp;sortorder=" . $SortOrder . "&amp;dataoffset=" . ($DataOffset - $DataCount) . "&amp;datacount=" . $DataCount . "\">...</a>";
			$SeitenNaviArray["seitenarray"][$SeitenStart - 1] .= "<a class=\"au_page_link\" href=\"" . AddURLParameter($NavigationURL, 'dataoffset', max(0, $DataOffset - $DataCount)) . "\">...</a>";
			$SeitenNaviArray["seitenarray"][$SeitenStart - 1] .= "</span>";
			//$SeitenNaviArray["seitenarray"][$SeitenStart - 1] .= "&nbsp;&nbsp;|&nbsp;&nbsp;";

		}

		if (isset($SeitenEndPlatzhalter)) {

			//$SeitenNaviArray["seitenarray"][$SeitenEnd] .= "&nbsp;&nbsp;|&nbsp;&nbsp;";
			$SeitenNaviArray["seitenarray"][$SeitenEnd + 1] .= "<span>";
//            $SeitenNaviArray["seitenarray"][$SeitenEnd + 1] .= "<a class=\"au_page_link\" href=\"".$NavDatei."?searchstring=" . $SerachString . "&amp;sucheallgemein=".$SerachString."&amp;searchfield=" . $SerachString . "&amp;sortfield=" . $SortField . "&amp;suchkategorie=".$Suchkategorie."&amp;sortorder=" . $SortOrder . "&amp;dataoffset=" . ($DataOffset + $DataCount) . "&amp;datacount=" . $DataCount . "\">...</a>";
			$SeitenNaviArray["seitenarray"][$SeitenEnd + 1] .= "<a class=\"au_page_link\" href=\"" . AddURLParameter($NavigationURL, 'dataoffset', min($SeitenEnd * $DataCount, $DataOffset + $DataCount)) . "\">...</a>";
			$SeitenNaviArray["seitenarray"][$SeitenEnd + 1] .= "</span>";

		}

		ksort($SeitenNaviArray["seitenarray"]);

		if ($Seitenanzahl > 1) {

			// Navigationspfeile
			if (($DataOffset - 1 / $DataCount) > 1) {
//				$SeitenNaviArray["seite_anfang"] = "<a class=\"au_page_link\" href=\"".$NavDatei."?searchstring=" . $SerachString . "&amp;sucheallgemein=".$SerachString."&amp;searchfield=" . $SerachString . "&amp;sortfield=" . $SortField . "&amp;suchkategorie=".$Suchkategorie."&amp;sortorder=" . $SortOrder . "&amp;dataoffset=0&amp;datacount=" . $DataCount . "\">";
				$SeitenNaviArray["seite_anfang"] = "<a class=\"au_page_link\" href=\"" . AddURLParameter($NavigationURL, 'dataoffset', '0') . "\">";
				$SeitenNaviArray["seite_anfang"] .= "<<";
				$SeitenNaviArray["seite_anfang"] .= "</a>";
			} else {
				$SeitenNaviArray["seite_anfang"] = "<<";
			}

			if (($Seitenanzahl > 1) && (((($DataOffset) / $DataCount) + 1) != $Seitenanzahl)) {
//				$SeitenNaviArray["seite_ende"] = "<a class=\"au_page_link\" href=\"".$NavDatei."?searchstring=" . $SerachString . "&amp;sucheallgemein=".$SerachString."&amp;searchfield=" . $SerachString . "&samp;ortfield=" . $SortField . "&amp;suchkategorie=".$Suchkategorie."&amp;sortorder=" . $SortOrder . "&amp;dataoffset=" . ((($Seitenanzahl - 1) * $DataCount)) . "&amp;datacount=" . $DataCount . "\">";
				$SeitenNaviArray["seite_ende"] = "<a class=\"au_page_link\" href=\"" . AddURLParameter($NavigationURL, 'dataoffset', ((($Seitenanzahl - 1) * $DataCount))). "\">";
				$SeitenNaviArray["seite_ende"] .= ">>";
				$SeitenNaviArray["seite_ende"] .= "</a>";
			} else {
				$SeitenNaviArray["seite_ende"] = ">>";
			}

			// Navigationspfeile
			if (($DataOffset - 1 / $DataCount) > 1) {
				//$SeitenNaviArray["seite_zurueck"] = "<a href=\"".$NavDatei."?searchstring=" . $SerachString . "&amp;sucheallgemein=".$SerachString."&amp;searchfield=" . $SerachString . "&amp;sortfield=" . $SortField . "&amp;suchkategorie=".$Suchkategorie."&amp;sortorder=" . $SortOrder . "&amp;dataoffset=" . ($DataOffset - $DataCount) . "&amp;datacount=" . $DataCount . "\">";
				//$SeitenNaviArray["seite_zurueck"] .= "<";
				//$SeitenNaviArray["seite_zurueck"] .= "</a>";
			} else {
				//$SeitenNaviArray["seite_zurueck"] = "<";
			}

			if (($Seitenanzahl > 1) && (((($DataOffset) / $DataCount) + 1) != $Seitenanzahl)) {
				//$SeitenNaviArray["seite_vor"] = "<a href=\"".$NavDatei."?searchstring=" . $SerachString . "&amp;sucheallgemein=".$SerachString."&amp;searchfield=" . $SerachString . "&amp;sortfield=" . $SortField . "&amp;suchkategorie=".$Suchkategorie."&amp;sortorder=" . $SortOrder . "&amp;dataoffset=" . ($DataOffset + $DataCount) . "&amp;datacount=" . $DataCount . "\">";
				//$SeitenNaviArray["seite_vor"] .= ">";
				//$SeitenNaviArray["seite_vor"] .= "</a>";
			} else {
				//$SeitenNaviArray["seite_vor"] = ">";
			}

		}

//		echo "<pre>";
//		var_dump($AdditionalParameter);
//		echo "</pre>";

		return $SeitenNaviArray;

}




function SetArtikelGruppenZuordnung($GruppenArtikelID, $TeilArtikelID, $Menge = 1) {

	if($Menge == 0)
	{
		DeleteArtikelGruppenZuordnung($GruppenArtikelID, $TeilArtikelID);
		return;
	}

	if ($GruppenArtikelID != $TeilArtikelID) {

		// �berpr�fen, ob schon vorhanden
		$SQLString = "SELECT " . TABLE_ARTIKEL_GRUPPEN . ".gruppenartikel_id FROM " . TABLE_ARTIKEL_GRUPPEN;
		$SQLString .= " WHERE " . TABLE_ARTIKEL_GRUPPEN . ".gruppenartikel_id = '" . $GruppenArtikelID . "' AND ";
		$SQLString .= TABLE_ARTIKEL_GRUPPEN . ".teilartikel_id = '" . $TeilArtikelID . "'";
		$ArtikelObject = mysql_fetch_object(mysql_query($SQLString));

		// Wenn noch nicht, dann zuordnen
		if (!$ArtikelObject) {

			$SQLString = "INSERT INTO " . TABLE_ARTIKEL_GRUPPEN . " SET ";
			$SQLString .= TABLE_ARTIKEL_GRUPPEN . ".gruppenartikel_id = '" . $GruppenArtikelID . "', ";
			$SQLString .= TABLE_ARTIKEL_GRUPPEN . ".teilartikel_id = '" . $TeilArtikelID . "', ";
			$SQLString .= TABLE_ARTIKEL_GRUPPEN . ".menge = " . $Menge;
			mysql_query($SQLString);
		}
		else // sonst Menge updaten
		{
			$SQLString = "UPDATE " . TABLE_ARTIKEL_GRUPPEN . " SET ";
			$SQLString .= TABLE_ARTIKEL_GRUPPEN . ".menge = " . $Menge;
			$SQLString .= " WHERE ";
			$SQLString .= TABLE_ARTIKEL_GRUPPEN . ".gruppenartikel_id = '" . $GruppenArtikelID . "' AND ";
			$SQLString .= TABLE_ARTIKEL_GRUPPEN . ".teilartikel_id = '" . $TeilArtikelID . "'";
//			echo $SQLString;
			mysql_query($SQLString);
		}

		UpdateArtikelGruppenGewicht($GruppenArtikelID);
		UpdateArtikelGruppeLagerbestand($GruppenArtikelID);
	}
}

function DeleteArtikelGruppenZuordnung($GruppenArtikelID, $TeilArtikelID) {

	// Artikelzuordnung l�schen
	$SQLString = "DELETE FROM " . TABLE_ARTIKEL_GRUPPEN . " WHERE " . TABLE_ARTIKEL_GRUPPEN . ".gruppenartikel_id = '" . $GruppenArtikelID . "'";
	$SQLString .= " AND " . TABLE_ARTIKEL_GRUPPEN . ".teilartikel_id = '" . $TeilArtikelID . "'";
//	echo $SQLString;
	mysql_query($SQLString);

	UpdateArtikelGruppenGewicht($GruppenArtikelID);
	UpdateArtikelGruppeLagerbestand($GruppenArtikelID);
}

function UpdateArtikelGruppenGewicht($GruppenArtikelID)
{
	$SQLString .= "SELECT sum(" . TABLE_ARTIKEL . ".gewicht * ". TABLE_ARTIKEL_GRUPPEN . ".menge)";
	$SQLString .= " FROM " . TABLE_ARTIKEL . " LEFT JOIN " . TABLE_ARTIKEL_GRUPPEN . " ON " . TABLE_ARTIKEL . ".id = " . TABLE_ARTIKEL_GRUPPEN . ".teilartikel_id";
	$SQLString .= " WHERE " . TABLE_ARTIKEL_GRUPPEN . ".gruppenartikel_id = " . $GruppenArtikelID . "";
//	echo $SQLString;
	$result = mysql_query($SQLString);
	$gewichtrow = mysql_fetch_row($result);
	if($gewichtrow)
		$gewicht = $gewichtrow[0];
	else
		$gewicht = 0;

	$SQLString = "UPDATE " . TABLE_ARTIKEL . " SET " . TABLE_ARTIKEL . ".gewicht = " . $gewicht;
	$SQLString .= " WHERE " . TABLE_ARTIKEL . ".id = " . $GruppenArtikelID;
	mysql_query($SQLString);
}

function SetGruppenArtikel($ArtikelID)
{
	$SQLString = "UPDATE " . TABLE_ARTIKEL . " SET ";
	$SQLString .= TABLE_ARTIKEL . ".gruppenartikel = 1, ";
	$SQLString .= TABLE_ARTIKEL . ".gewicht = 0, ";
	$SQLString .= TABLE_ARTIKEL . ".variante1 = 0, ";
	$SQLString .= TABLE_ARTIKEL . ".variante2 = 0, ";
	$SQLString .= TABLE_ARTIKEL . ".variante3 = 0, ";
	$SQLString .= TABLE_ARTIKEL . ".variante4 = 0 ";
	$SQLString .= "WHERE " . TABLE_ARTIKEL . ".id = " . $ArtikelID;
	mysql_query($SQLString);
}

function DeleteArtikelGruppe($ArtikelID)
{
	$SQLString = "UPDATE " . TABLE_ARTIKEL . " SET gruppenartikel = 0 WHERE " . TABLE_ARTIKEL . ".id = " . $ArtikelID;
	mysql_query($SQLString);

	$SQLString = "DELETE FROM " . TABLE_ARTIKEL_GRUPPEN . " WHERE " . TABLE_ARTIKEL_GRUPPEN . ".gruppenartikel_id = " . $ArtikelID;
	mysql_query($SQLString);
}

function IsGruppenArtikel($ArtikelID)
{
	$SQLString = "SELECT " . TABLE_ARTIKEL . ".gruppenartikel FROM " . TABLE_ARTIKEL;
	$SQLString .= " WHERE " . TABLE_ARTIKEL . ".id = " . $ArtikelID;
	$gruppenartikel = mysql_fetch_row(mysql_query($SQLString));
	return $gruppenartikel[0];
}

function GetArtikelGruppenMitArtikel($ArtikelID)
{
	$SQLString = "SELECT " . TABLE_ARTIKEL_GRUPPEN . ".gruppenartikel_id, " . TABLE_ARTIKEL_LANGU . ".artikel_name ";
	$SQLString .= "FROM " . TABLE_ARTIKEL_GRUPPEN;
	$SQLString .= " LEFT JOIN " . TABLE_ARTIKEL_LANGU . " ON " . TABLE_ARTIKEL_GRUPPEN . ".gruppenartikel_id = " . TABLE_ARTIKEL_LANGU . ".artikel_id ";
	$SQLString .= " WHERE " . TABLE_ARTIKEL_GRUPPEN . ".teilartikel_id = " . $ArtikelID;
	$SQLString .= " AND " . TABLE_ARTIKEL_LANGU . ".language_id = " . GetDefaultLanguageID();
	$result = mysql_query($SQLString);
	while($gruppe = mysql_fetch_assoc($result))
	{
		$gruppen[] = $gruppe;
	}
	return $gruppen;
}


