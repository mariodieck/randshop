<!DOCTYPE html>
<html>

<!-- Copyright and Shopsystem by www.randshop.com / NOT DELETE in free Lizenz -->

<head>
    <?php echo $HeadTitle ?>
    <?php echo $HeadDescription ?>
    <?php echo $HeadKeywords ?>
    <?php echo $HeadAuthor ?>
    <?php echo $HeadFacebookProperty?>
    <?php echo $Canonical ?>
    <?php echo $MetaIndex ?>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo URLPFAD ?>templates/<?php echo $rowTemp->name ?>/design/<?php echo $rowTemp->design ?>/css/ajaxsearch.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo URLPFAD ?>templates/<?php echo $rowTemp->name ?>/design/<?php echo $rowTemp->design ?>/css/layout.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo URLPFAD ?>templates/<?php echo $rowTemp->name ?>/design/<?php echo $rowTemp->design ?>/css/content.css" />
    <link rel="stylesheet" href="<?php echo URLPFAD ?>templates/<?php echo $rowTemp->name ?>/design/<?php echo $rowTemp->design ?>/css/prettyPhoto.css" type="text/css" media="screen" title="prettyPhoto main stylesheet" />
    <script type="text/javascript" src="<?php echo URLPFAD ?>js_function/jquery.js"></script>
    <script src="<?php echo URLPFAD ?>js_function/jquery.prettyPhoto.js" type="text/javascript" charset="utf-8"></script>
	<!-- In Global.js sind integriert: AnchorPosition.js, CalendarPopup.js, dataexchande.js, date.js, json2.js, popupWindow.js, ajax.js und ajaxsearch.js -->
    <script src="<?php echo URLPFAD ?>js_function/php2js.js.php" type="text/javascript"></script>
    <script type="text/javascript" src="<?php echo URLPFAD ?>js_function/global.js"></script>
    <script type="text/javascript" src="<?php echo URLPFAD ?>js_function/modernizr.js"></script>

    <?php if ($tpl_feature_ajaxsearch) { ?>
    <script type="text/javascript">

        var SearchResultObject = new SearchResultClass();
        
        SearchResultObject.ArtikelnummerAnzeigen = '<?php echo $Einstellungen->ajax_search->artikelnummer_anzeigen ?>';
        SearchResultObject.ArtikelnameAnzeigen = '<?php echo $Einstellungen->ajax_search->artikelname_anzeigen ?>';
        SearchResultObject.KurzbeschreibungAnzeigen = '<?php echo $Einstellungen->ajax_search->kurzbeschreibung_anzeigen ?>';
        SearchResultObject.ArtikelbeschreibungAnzeigen = '<?php echo $Einstellungen->ajax_search->artikelbeschreibung_anzeigen ?>';
        SearchResultObject.PreisAnzeigen = '<?php echo $Einstellungen->ajax_search->preis_anzeigen ?>';
        SearchResultObject.ArtikelbildAnzeigen = '<?php echo $Einstellungen->ajax_search->artikelbild_anzeigen ?>';
        SearchResultObject.MaximaleArtikelbildBreite = '<?php echo $Einstellungen->ajax_search->maximale_artikelbild_breite ?>';
        SearchResultObject.MaximaleArtikelbildHoehe = '<?php echo $Einstellungen->ajax_search->maximale_artikelbild_hoehe ?>';
        SearchResultObject.MaximaleSuchergebnisBreite = '<?php echo $Einstellungen->ajax_search->maximale_suchergebnis_breite ?>';
        SearchResultObject.BegrenzungSuchergebnisHoeheAnArtikelbild = '<?php echo $Einstellungen->ajax_search->begrenzung_suchergebnis_hoehe_an_artikelbild ?>';
        SearchResultObject.BeginnAbZeichen = '<?php echo $Einstellungen->ajax_search->beginn_ab_zeichen ?>';

        document.onkeydown = MoveHighlight;
        document.onclick = CloseAjaxSearch;
    </script>
    <?php } ?>
    
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function(){
            $("a[rel^='prettyPhoto']").prettyPhoto({
                opacity: 0.30 /* Value between 0 and 1 */
            });
        });
    </script>

    <script type="text/javascript">
        function AddWarenkorb(artikelid, artikelname, refertype, referid, menge, variante1, variante2, variante3, variante4, popupid) {
            var variantenString = '';
            if(variante1) {
                variantenString = '&variante1=' + variante1 + '&variante2=' + variante2 + '&variante3=' + variante3 + '&variante4=' + variante4;
            }
            $.ajax({
                    url: '<?php echo URLPFAD?>themes/warenkorb/ajax_handler.php?action=AddWarenkorb&artikelid=' + artikelid + '&refertype=' + refertype + '&referid=' + referid + '&menge=' + menge + variantenString,
                    success: function(data) {
                        $('#warenkorbAnzahl').text(data.anzahlWarenkorb);
                        $('#warenkorbWarenwert').text(data.warenwert);
                        var flashColor = $('#warenkorbFlashColor').css('background-color');
                        var red, green, blue;
                        if(flashColor.indexOf('#') != -1) {
                            red = parseInt(flashColor.substr(1, 2), 16);
                            green = parseInt(flashColor.substr(3, 2), 16);
                            blue = parseInt(flashColor.substr(5, 2), 16);
                        } else {
                            rgb= flashColor.match(/\d+(\.\d+)?%?/g);
                            for(var i=0;i<3;i++) {
                                if(rgb[i].indexOf('%')!= -1){
                                    rgb[i]= Math.round(parseFloat(rgb[i])*2.55);
                                }
                            }
                            red = rgb[0];
                            green = rgb[1];
                            blue = rgb[2];
                        }

                        var warenkorbBox = $('#warenkorbBox');
                        jQuery({percent: 0}).animate({percent: 100}, {'duration': 1000,
                            step:
                                function(curStep) {
                                    var animKey = (Math.sin(Math.min(Math.PI * 7 * curStep / 100 - Math.PI/2, Math.PI * 6 - Math.PI/2)) + 1) / 2;
                                    var animKeyDelay = (Math.sin(Math.max(-Math.PI/2, Math.PI * 7 * curStep / 100 - 3 * Math.PI/2)) + 1) / 2;
                                    var animKeyButton = curStep / 100;
                                    var shadow = Math.round(animKey * 5);
                                    var shadowDelay = Math.round(animKeyDelay * 5);
                                    if (!Modernizr.testProp('boxShadow')) {
                                        warenkorbBox.css('background-color', '#' + Math.round(0xff - animKey * (0xff - red)).toString(16) + Math.round(0xff - animKey * (0xff - green)).toString(16) + Math.round(0xff - animKey * (0xff - blue)).toString(16));
                                    } else {
                                        warenkorbBox.css('box-shadow', '0px 0px ' + shadowDelay + 'px ' + shadowDelay + 'px ' + flashColor);
                                    }
                                },
                            complete:
                                function() {
                                    if (!Modernizr.testProp('boxShadow')) {
                                        warenkorbBox.css('background-color', 'transparent');
                                    } else {
                                        warenkorbBox.css('box-shadow', '0px 0px 0px 0px ' + flashColor);
                                    }
                                },
                            easing: 'linear'
                        });
                        var menge;
                        if(document.form_artikel) {
                            menge = document.form_artikel.menge.value;
                        } else {
                            menge = '1';
                        }
                        $('#' + popupid).html(menge + ' x ' + artikelname + ' <?php echo $l_inDenWarenkorbGelegt?>').fadeIn(100).delay(1000).fadeOut(2000);
                    },
                    dataType: 'json'
                }
            );
        }
    </script>

</head>

<body onload="document.getElementById('ajaxsearch_searchstring').focus();">

<div class="wrapper">



    <header>

        <div class="logoPosition">
            <a href="<?php echo URLPFAD ?>index.php"><?php echo $logoImage ?></a>
        </div>

        <?php if($_SESSION["com"] == "true") { ?>
            <ul class="meinKonto">
                <li><a href="<?php echo URLPFAD ?>themes/user/index.php?action=kundenseite"><?php echo $lang_headline_meinkonto ?></a></li>
                <li><a href="<?php echo URLPFAD ?>themes/user/index.php?action=grunddaten&amp;sourceid=9"><?php echo $s_k_nav_meinedaten ?></a></li>
                <li><a href="<?php echo URLPFAD ?>index.php?action=abmelden"><?php echo $s_k_nav_abmelden ?></a></li>
            </ul>
        <?php } else { ?>
            <ul class="meinKonto">
                <li><a href="<?php echo URLPFAD ?>themes/user/index.php"><?php echo $lang_kunden_login ?></a></li>
                <li><a id='button_neukunde' href="<?php echo URLPFAD ?>themes/user/index.php?action=registrieren&amp;login_source=click"><?php echo $lang_neukunde ?>?</a></li>
            </ul>
        <?php } ?>


        <!-- Menuepunkte -->
        <nav class="infoMenuePunkte">
            <ul>
                <?php if ($tpl_rowFeat) { ?>
                    <li><a href="<?php echo URLPFAD ?>themes/news/index.php">News</a></li>
                <?php } ?>
                <?php if (sizeof($menueNames)) { ?>
                    <?php foreach ($menueNames as $einMenue) { ?>
                        <li><a href="<?php echo $einMenue["menUrl"] ?>"><?php echo $einMenue["menHeadline"] ?></a></li>
                    <?php } ?>
                <?php } ?>
            </ul>
        </nav>

        <!-- Suche -->
        <?php if (!$tpl_feature_ajaxsearch) { ?>
        <div class="suchBox">
            <form name="searchForm" method="get" action="<?php echo URLPFAD ?>themes/suche/index.php">
            <fieldset>
                <select name="suchekategorie">
                    <option value=""><?php echo $lang_sucheNach ?></option>
                    <?php echo $selectOptions ?>
                </select>
                <input id="suche" type="text" name="sucheallgemein" value="<?php echo $tpl_sucheallgemein ?>" />
                <span><input type="submit" value="<?php echo $lang_submitGo ?>" /></span>
                <small><a href="<?php echo URLPFAD ?>themes/suche/index.php"><?php echo $lang_such_erweitert ?></a></small>
            </fieldset>
            </form>
        </div>
        <?php } else { ?>
        <!-- AJAX Suche -->
        <div class="suchBox">
            <div class="ajaxsearch_container">
                <input type="text" name="ajaxsearch_searchstring" id="ajaxsearch_searchstring" onkeyup="GetSearchResult(event);" class="ajaxsearch_input" /><a class="ajaxsearch_go_button" href="#" onclick="document.location.href = '<?php echo URLPFAD?>themes/suche/index.php?suchekategorie=&amp;sucheallgemein=' + document.getElementById('ajaxsearch_searchstring').value;"><?php echo $s_as_go ?></a>
            </div>
            <div id="ajaxsearchresult_focuscatcher" class="ajaxsearchresult_focuscatcher">
                <input type="text" id="focuscatcher" />
            </div>
            <div id="ajaxsearchresult_container" class="ajaxsearchresult_container">
                <div class="ajaxsearchresult_noresult" id="ajaxsearchresult_noresult">
                    <?php echo $s_as_noresult?>
                </div>
                <div class="ajaxsearchresult_searchheadline" id="ajaxsearchresult_searchheadline">
                    <div class="ajaxsearchresult_searchheadline_headline_container"><?php echo $s_as_searchheadline?></div>
                    <div class="ajaxsearchresult_searchheadline_img_container"><img src="<?php echo URLPFAD ?>templates/<?php echo $rowTemp->name ?>/design/<?php echo $rowTemp->design ?>/images/searchclose.png" onclick="SearchResultObject.HideSearchResultDiv();" alt="Close" /></div>
                </div>
                <div id="ajaxsearchresult_list_container" class="ajaxsearchresult_list_container">&nbsp;</div>
                <div class="ajaxsearchresult_searchmore" id="ajaxsearchresult_searchmore">
                    <a onmouseover="HighlightShowMore()" onmouseout="HighlightResetShowMore()" id="ajaxsearchresult_searchmore_link" href="#"><?php echo $s_as_moresearchresults?></a>
                </div>
                <div class="ajaxsearchresult_searchmore_end" id="ajaxsearchresult_searchmore_end">&nbsp;</div>
            </div>
        </div>
        <?php } ?>

        <!-- Sprachenauswahl -->
        <?php if (count($LanguageDataArray) > 1) { ?>
        <div class="sprachBox">
            <?php foreach($LanguageDataArray as $LanguageData) { ?>
                <a href="<?php echo $LanguageData["language_select_url"] ?>"><?php echo $LanguageData["language_image_shop_imagestring"] ?></a>
            <?php } ?>
        </div>
        <?php } ?>

        <!-- Warenkorb -->
        <?php if (true /*$tpl_warAnzahl*/) { ?>
        <div class="warenkorbBox" id="warenkorbBox">
            <div id="warenkorbFlashColor"></div>
            <h5><?php echo $lang_headline_warenkorb ?></h5>
            <div>
                <h4><span id="warenkorbAnzahl"><?php echo $gesamtmenge ?></span> <?php echo $imWarenkorbSind ?></h4>
                <?php if ($imWarenkorbSum) { ?><h4><?php echo $lang_warenwert . ": "?><span id="warenkorbWarenwert"><?php echo $imWarenkorbSum ?></span></h4><?php } ?>
                <h4><a href="<?php echo URLPFAD ?>themes/warenkorb/index.php"><?php echo $l_warenkorbEinsehen ?></a></h4>
            </div>
        </div>
        <?php } ?>

    </header>




<?php if($tpl_kategorieTree) {?>
    <div class="mainNavTree">
    <ul class="mainNavTreeULTopLvl">
        <?php foreach($tpl_kategorieTree as $kategorieObjTopLvl) {?>
        <li class="mainNavTreeLITopLvl">
            <a href="<?php echo GetKategorieLink($kategorieObjTopLvl->id, $SEOURLArray['kategorie'][$kategorieObjTopLvl->id]) ?>">
            <?php echo $kategorieObjTopLvl->name?>
            </a>
            <?php if($kategorieObjTopLvl->children) {?>
            <ul class="mainNavTreeULSecLvl">
                <?php foreach($kategorieObjTopLvl->children as $kategorieObjSecLvl) {?>
                <li class="mainNavTreeLISecLvl">
                    <a href="<?php echo GetKategorieLink($kategorieObjSecLvl->id, $SEOURLArray['kategorie'][$kategorieObjSecLvl->id]) ?>">
                    <?php echo $kategorieObjSecLvl->name?>
                    </a>
                    <?php if($kategorieObjSecLvl->children) {?>
                    <ul class="mainNavTreeULThrdLvl">
                        <?php foreach($kategorieObjSecLvl->children as $kategorieObjThrdLvl) {?>
                        <li class="mainNavTreeLIThrdLvl">
                            <a href="<?php echo GetKategorieLink($kategorieObjThrdLvl->id, $SEOURLArray['kategorie'][$kategorieObjThrdLvl->id]) ?>">
                                <?php echo $kategorieObjThrdLvl->name?>
                            </a>
                        </li>
                        <?php }?>
                    </ul>
                    <?php }?>
                </li>
                <?php }?>
                <div class="artikelCleaner"></div>
            </ul>
            <?php }?>
        </li>
        <?php }?>
    </ul>
    </div>
    <div class="artikelCleaner"></div>

<?php }?>



    <div class="mainNav">
    <?php if($tpl_facettensucheAktiv) { ?>
            <form name="form_facettensuche" action="<?php echo GetKategorieLink($KategorieID, $SEOURLArray['kategorie'][$KategorieID]) ?>" method="post">
            <div class="stammNavigation facettenSuche" id="facettenSuche" style="z-index: 5">
                <h4><?php echo $lang_facettensuche?></h4>
                <?php foreach($tpl_facettensucheArray as $filterId => $filter) {?>
                <div class="facettensuche_filter">
                <div class="facettensuche_filter_head <?php echo ($filter['auswahl']?'facettensuche_selected':'')?>">
                <?php if(isset($filter['image_format'])): echo $filter['image_format']; endif?> <?php echo $filter['filterName']?>
                <?php if(isset($auspraegung['auspraegungBeschreibung'])) {?>
                    <div class="tooltip facettensuche_fragezeichen">?
                    <div class="tooltiptext"><?php echo $filter['filterBeschreibung']?></div>
                    </div>
                <?php } ?>
                </div>
                <ul class="facettensuche_filter_auspraegungen">
                    <?php foreach($filter['auspraegungen'] as $auspraegungId => $auspraegung) { ?>
                    <li class="facettensuche_auspraegung <?php echo ($auspraegung['auswahl']?'facettensuche_selected':'')?>">
                    <?php if($filter['mehrfachselektion']) {?>
                    	<input type="checkbox" name="filter[<?php echo $filterId?>][<?php echo $auspraegungId?>]" onclick="document.form_facettensuche.submit()" <?php echo (isset($auspraegung['auswahl'])?'checked':'')?> />
                    <?php } else { ?>
                    	<input type="radio" name="filter[<?php echo $filterId?>]" value="<?php echo $auspraegungId?>" onclick="document.form_facettensuche.submit()" <?php echo $auspraegung['auswahl']?'onclick="this.checked = false;document.form_facettensuche.submit();"':''?> <?php echo ($auspraegung['auswahl']?'checked':'')?>/>
                    <?php } ?>
                    <?php if(isset($auspraegung['image_format'])): echo $auspraegung['image_format']; endif?> <?php echo $auspraegung['auspraegungName']?>
                        <?php if($auspraegung['auspraegungBeschreibung']) {?>
                        <div class="tooltip facettensuche_fragezeichen">?
                            <div class="tooltiptext"><?php echo $auspraegung['auspraegungBeschreibung']?></div>
                        </div>
                        <?php } ?>
                    </li>
                    <?php } ?>
                </ul>
                </div>
                <?php }?>
            </div>
            </form>
    <?php } ?>

        <?php if(!$tpl_kategorieTree) {?>
        <!-- Kategorien -->
        <nav class="stammNavigation kategorieNavigation">
            <h4><?php echo $lang_headline_kategorien ?></h4>
            <ul class="kategorieUlNavigation">
	            <?php foreach ($KategorieArray as $Kategorie) { ?>
	            <?php if ($Kategorie["level"] == 0) { ?>
	                <li <?php if(isset($Kategorie["highlight"])) { echo 'class="highlight"'; }?>><a href="<?php echo GetKategorieLink($Kategorie["kategorieid"], $SEOURLArray['kategorie'][$Kategorie["kategorieid"]]) ?>"><?php echo $Kategorie["kategoriename"] ?></a></li>
	            <?php } elseif ($Kategorie["level"] == 1) { ?>
	                <li class="secondNavigation <?php if(isset($Kategorie["highlightsub"])) { echo "highlightSub"; }?>"><a href="<?php echo GetKategorieLink($Kategorie["kategorieid"], $SEOURLArray['kategorie'][$Kategorie["kategorieid"]]) ?>"><?php echo $Kategorie["kategoriename"] ?></a></li>
	            <?php } elseif ($Kategorie["level"] >= 2) { ?>
	                <li class="thirdNavigation <?php if(isset($Kategorie["highlightsub"])) { echo "highlightSub"; }?>"><a href="<?php echo GetKategorieLink($Kategorie["kategorieid"], $SEOURLArray['kategorie'][$Kategorie["kategorieid"]]) ?>"><?php echo $Kategorie["kategoriename"] ?></a></li>
	            <?php } ?>
	            <?php } ?>
	        </ul>
        </nav>
        <?php }?>

        <!-- Aktionen -->
        <?php if (sizeof($aktionNames)) { ?>
        <nav class="stammNavigation angebotBox">
            <h4><?php echo $lang_headline_angebote ?></h4>
            <ul>
	            <?php foreach($aktionNames as $Ak) { ?>
	                <li><a href="<?php echo GetAktionsLink($Ak["akt_id"], $SEOURLArray['aktion'][$Ak["akt_id"]]) ?>"><?php echo $Ak["aktions_name"] ?></a></li>
	            <?php } ?>
	        </ul>
        </nav>
        <?php } ?>
        
        <!-- Hersteller -->
        <?php if ($herstellerCheck && sizeof($HerstellerDataArray)) { ?>
        <div class="stammNavigation herstellerBox">
            <h4><?php echo $lang_headline_hersteller ?></h4>
            <fieldset>
	            <form name="herstellerVerzeichnis" method="get" action="<?php echo URLPFAD ?>themes/kategorie/hersteller.php">
	                <input type="hidden" value="1" name="unsetDataOffset" />
	                <select name="herstellerid" onchange="if (document.herstellerVerzeichnis.herstellerid.value){ document.location.href = document.herstellerVerzeichnis.herstellerid.value; }">
	                    <option value=""><?php echo $lang_auswahl?></option>
	                    <?php foreach($HerstellerDataArray as $hersteller) { ?>
	                    <?php if ($HerstellerID == $hersteller["id"]) { ?>
	                        <option value="<?php echo GetHerstellerLink($hersteller["id"], $SEOURLArray['hersteller'][$hersteller["id"]]) ?>" selected="selected"><?php echo $hersteller["name"]?></option>
	                    <?php } else { ?>
	                        <option value="<?php echo GetHerstellerLink($hersteller["id"], $SEOURLArray['hersteller'][$hersteller["id"]]) ?>"><?php echo $hersteller["name"]?></option>
	                    <?php } ?>
	                    <?php } ?>
	                </select>
	            </form>
	        </fieldset>
        </div>
        <?php } ?>
        
        <?php if($tpl_boxArrayLeft) {?>
			<?php foreach ($tpl_boxArrayLeft as $boxData) {?>
			<div class="stammNavigation ExtraBoxLeft">
	            <h4><?php echo $boxData["headline"]?></h4>
	            <div><?php echo $boxData["text"]?></div>
	        </div>
		<?php } }?>

        <!-- PDF Katalog -->
        <?php if($tpl_pdfkatalog_check):?>
        <div class="stammNavigation katalogBox">
            <h4><?php echo $l_preisliste ?></h4>
            <p><a target="_blank" href="<?php echo URLPFAD ?>themes/preisliste/index.php?email=<?php echo $_SESSION["mail"] ?>"><?php echo $lang_download ?></a></p>
        </div>
        <?php endif?>

        <!-- Weiterempfehlen -->
        <?php if ($empfehlen) { ?>
        <div class="stammNavigation empfehlenBox">
            <h4><?php echo $lang_headline_weiterempfehlen ?></h4>
            <p><a href="<?php echo URLPFAD ?>themes/weiterempfehlen/index.php"><?php echo $tpl_firmname ?> <?php echo $lang_empfehlen_headlineText ?></a></p>
        </div>
        <?php } ?>
    
    </div><!-- Ende mainNav -->

    <div class="content">
        
        <article class="mainContent" id="mainContent">
            <?php include_once($contentFile) ?>
        </article>

        <div class="secondaryContent">

            <!-- MODUL: Partnerprogramm -->
            <?php if ($_SESSION["kunde_partner_key"]) { ?>
            <div class="stammNavigation partnerBox">
                <h4><?php echo $lang_partnerProgramm ?></h4>
                <p><?php echo $lang_provision_offen ?>: <?php echo $_SESSION['provision_offen'] ?> &euro;</p>
	            <p><?php echo $lang_provision_bezahlt ?>: <?php echo $_SESSION['provision_bezahlt' ]?> &euro;</p>
            </div>
            <?php } ?>

            <!-- Banner -->
            <?php if ($tpl_banner) { ?>
            <div class="bannerPosition">
                <?php echo $tpl_banner?>
            </div>
            <?php } ?>
            
            
            <?php if($tpl_boxArrayRight) { ?>
			<?php foreach ($tpl_boxArrayRight as $boxData) { ?>
			<div class="stammNavigation ExtraBoxRight">
	            <h4><?php echo $boxData["headline"]?></h4>
	            <div><?php echo $boxData["text"]?></div>
	        </div>
			<?php } }?>

            <?php if($BewertungenSeitenArray):?>
                <div class="stammNavigation bewertungdurchschnitt">
                    <h4><?php echo $str_anzahlKundenBew ?></h4>
                    <div>
                        <small><?=$BewertungenSeitenArray["global"]["anzahl"]?> <?php echo $lang_bewertungen_gesamt ?></small>
                        <strong><?=$BewertungenSeitenArray["global"]["durchschnitt"]?> Sterne</strong>
                        <span><?=$BewertungenSeitenArray["global"]["sterne"]?></span>
                    </div>
                </div>
            <?endif?>


            <!-- Bestseller -->
            <?php if (sizeof($tpl_bestsellerarray)) { ?>
            <div class="stammNavigation bestsellerBox">
                <h4><?php echo $l_bestseller ?></h4>
                <div>
	                <ol>
	                <?php foreach ($tpl_bestsellerarray as $tpl_bestsellerkey => $tpl_bestseller) { ?>
	                    <li>
	                        <a href="<?php echo GetArtikelLink($tpl_bestseller["id"], $tpl_bestseller["kategorieid"], '', '', 1, $tpl_refertype_bestseller, '', $SEOURLArray); ?>">
	                            <?php echo $tpl_bestseller["artikelname"] ?>
	                        </a>
	                        <strong><?php echo $tpl_bestseller["preis_format"] ?></strong>
	                    </li>
	                <?php } ?>
	                </ol>
				</div>
            </div>
            <?php } ?>

        </div><!-- Ende secondaryContent -->

    </div><!-- Ende content -->


<footer>

    <!-- Counter -->
    <?php if($zeigeCounter) { ?>
    <div class="counterBox">
        <h4><?php echo $lang_headline_besucher?></h4>
        <h5><?php echo $l_besucher?>: <?php echo $tpl_countAusgabe?></h5>
        <h5><?php echo $l_heute?>: <?php echo $tpl_countDay?></h5>
        <h5><?php echo $l_userOnline?>: <?php echo $tpl_countOn?></h5>
    </div>
    <?php } ?>

    <!-- Newsletter -->
    <?php if ($newsletter) { ?>
    <div class="newsletterBox">
        <h4><?php echo $lang_headline_newsletter ?></h4>
        <form name="newsletter" method="post" action="<?php echo URLPFAD ?>index.php">
            <fieldset>
                <input type="text" name="email" value="<?php echo $lang_email ?>" size="5" onclick="if (this.value == '<?php echo $lang_email ?>') { this.value = '' };" />
                <span>
                    <input type="submit" name="submitNewsletter" value="<?php echo $lang_button_anmelden ?>" />
                </span>
                <?php if ($tpl_newsAusgabe) { ?>
                <p><?php echo $tpl_newsAusgabe ?></p>
                <?php } ?>
            </fieldset>
        </form>
    </div>
    <?php } ?>


    <nav class="navigationFooterBox">
        <h4><?php echo $_lang_informationen ?></h4>
        <ul>
            <?php if ($tpl_rowFeat) { ?>
                <li><a href="<?php echo URLPFAD ?>themes/news/index.php">News</a></li>
            <?php } ?>
            <?php if (sizeof($menueNames)) { ?>
            <?php foreach ($menueNames as $einMenue) { ?>
                <li><a href="<?php echo $einMenue["menUrl"] ?>"><?php echo $einMenue["menHeadline"] ?></a></li>
            <?php } ?>
            <?php } ?>
        </ul>
    </nav>

    <nav class="navigationFooterBox">
        <h4><a href="<?php echo URLPFAD ?>themes/user/index.php?action=kundenseite"><?php echo $lang_headline_meinkonto ?></a></h4>
        <ul>
            <li><a href="<?php echo URLPFAD ?>themes/user/index.php?action=grunddaten&amp;sourceid=9"><?php echo $s_k_nav_meinedaten ?></a></li>
            <li><a href="<?php echo URLPFAD_NOSSL ?>themes/wunschzettel/index.php"><?php echo $s_k_nav_meinwunschzettel ?></a></li>
            <?php if (RECHNUNGSWESEN) { ?>
                <li><a href="<?php echo URLPFAD_NOSSL ?>themes/user/index.php?action=bestellungen"><?php echo $s_k_nav_meinebestellungen ?></a></li>
            <?php } ?>
            <?php if (ARTIKELDOWNLOAD) { ?>
                <li><a href="<?php echo URLPFAD_NOSSL ?>themes/user/index.php?action=artikeldownload"><?php echo $s_k_navi_artikeldownload ?></a></li>
            <?php } ?>
            <li><a href="<?php echo URLPFAD_NOSSL ?>themes/user/index.php?action=passwortaendern"><?php echo $s_k_nav_passwortaendern ?></a></li>
            <li><a href="<?php echo URLPFAD_NOSSL ?>index.php?action=abmelden"><?php echo $s_k_nav_abmelden ?></a></li>
        </ul>
    </nav>
	
	<nav class="linkBox">
        <h4><?php echo $_lang_links ?></h4>
        <ul>
            <li><a href="http://www.skoda-tuning.com/tuningshop" target="_blank">www.skoda-tuning.com</a></li>
        </ul>
    </nav>




    <!--
        Das Copyright darf in der kostenlosen Download Version weder veraendert noch geloescht bzw. unsichtbar gemacht werden!!!
        Bitte beachtet diese einzige Einschränkung, da wir auf Urheberechtsverletzungen keine Rücksicht nehmen können!
    -->
    <span class="copy">
        <a href="http://www.randshop.com" target="_blank">&copy; 2004-<?php echo date("Y")?> shopsystem by <strong>randshop</strong></a> -
        <a href="http://www.dierandgruppe.com">Ein Produkt der Randgruppe GmbH</a>
    </span>

</footer>




</div><!-- Ende wrapper -->

<!-- Version 2.2 -->

</body>
</html>