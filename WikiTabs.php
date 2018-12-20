<?php
# Extension:WikiTabs
# - Intellectual Reserve, Inc.(c) 2010
# - Author: Don B. Stringham (stringhamdb@ldschurch.org) & David R. Crowther (crowtherdr@ldschurch.org)
# - Started: 03-10-2010

# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI')) {
echo <<<EOT
To install the WikiTabs extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/WikiTabs/WikiTabs.php" );
EOT;
exit(1);
}
require_once('includes/global_cis.php');

global $other_content_actions;
$wgExtensionCredits['specialpage'][] = array (
					      'name' => 'WikiTabs',
                                             'author' => 'FamilySearch', //Don Stringham & David Crowther
                                             'version' => '0.2.0',
                                             'url' => 'http://wiki.familysearch.org',
                                             'description' => 'Uses Mediawiki:WikiTabs article for settings<br>Example:<br>@skinname<br><br>*permanent<br>#edit<br>#watch<br><br>*whichTabs<br>#first<br>#edit<br>#talk<br>#history<br>#watch<br><br>@default<br><br>*permanent<br><default permanent tabs><br><br>*whichTabs<br><default tabs and tab order (blank for all tabs)> <br><br>Control Page Tabs and their order. Show configured tabs whether or not signed in. <br>Puts any remaining tabs in  '. $other_content_actions
					     );

$wgExtensionFunctions[] = "WikiTabs_Hook";

# register hook
function WikiTabs_Hook() {
  global $wgHooks;

  $wgHooks['SkinTemplateContentActions'][] = 'WikiTabs_SkinTemplateContentActions';
}

function WikiTabs_SkinTemplateContentActions(&$content_actions){
  global $wgUser;
  $skin = $wgUser->getSkin();
  global $other_content_actions;
  $currentSkin = $skin->skinname;
	
  //	echo $currentSkin;

  $mwWikiTabsArticle = Article::newFromId(Title::newFromText("Mediawiki:WikiTabs")->getArticleId()); //Get the raw settings from MediaWiki:WikiTabs
  if(isset($mwWikiTabsArticle) && strlen(trim($mwWikiTabsArticle->getRawText())) > 0) {//If that worked and there is something in the article.
	
    //Make an array of skins including "default" - "default" will have permanent Edit & Watch & default of showing every tab.
    //get the parameters for the current skin and use them
    $mwWikiTabsSkinsArray = array_slice( explode("@", $mwWikiTabsArticle->getRawText()), 1); //Make an array of the skin tab definitions
    //			echo "1.mwWikiTabsSkinsArray size:".count($mwWikiTabsSkinsArray)."!"."<br><br>";
			
    if(count($mwWikiTabsSkinsArray) > 0){ // Did we get a valid skin definition?
		
      foreach($mwWikiTabsSkinsArray as $skinList) {
	$skinList = trim($skinList); //Remove surrounding white spaces
	//					echo "2.skinList:".$skinList."!"."<br>";
			
	$mwWikiTabsTextArray = explode("*", $skinList); //Make an array of "permanent" & "whichTabs" strings. This will include the skin name that the definition is for.
	$mwWikiTabsTextArray[0] = trim($mwWikiTabsTextArray[0]); //Remove surrounding white spaces
	//					echo "3.mwWikiTabsTextArray[0]:".$mwWikiTabsTextArray[0]."!"."<br>";
			
	$skinDef = substr($mwWikiTabsTextArray[0],0,strlen($mwWikiTabsTextArray[0])); // Get the name of the skin from the tab definition.
	$mwWikiTabsTextArray = array_slice($mwWikiTabsTextArray, 1); // Now remove the name of the skin from the array so that just the tab definitions are left.
	//					echo "4.mwWikiTabsTextArray[0]:".$mwWikiTabsTextArray[0]."!"."<br>";
	//					echo "5.skinDef=".$skinDef."!"."<br><br>";
				
	//					$mwWikiTabsTextArrayIndex = 0; //Used for debugging.
	if (($skinDef == $currentSkin) || ($skinDef == "default")){ //If there is a tab definition for the current skin, use it. Otherwise, use the default tab definition.

	  //					echo "5b.mwWikiTabsTextArray size:".count($mwWikiTabsTextArray)."!<br>";
	  if(count($mwWikiTabsTextArray)>0){
	    foreach($mwWikiTabsTextArray as $list) { // Process the "permanent" and "whichTabs" settings.
				
	      //								$mwWikiTabsTextArrayIndex++; //Used for debugging.
	      //								echo "6-".$mwWikiTabsTextArrayIndex."list=".$list."!<br>";

	      if (strpos($list, "permanent")!== false) { //Get the tabs that should be permanent 
		$permanentTabsArray = array_slice( explode("#", $list), 1); //Remove title and make an array of the rest
									    foreach($permanentTabsArray as $key => $value) {
		$permanentTabsArray[$key] = trim($value);
	      }		
	    }
	    else{ //If default permanent tabs are not defined for the current skin.
	      $permanentTabsArray = makePermanentTabsDefaults();
	    }

	    if (strpos($list, "whichTabs")!== false) { //Get the tabs that should be in the tab bar and their order 
	      $whichTabsArray = array_slice( explode("#", $list), 1); //Remove title and make an array of the rest
								      foreach($whichTabsArray as $key => $value) {
	      $whichTabsArray[$key] = trim($value);
	    }		
	  }
	  else{ //If default tabs are not defined for the current skin.
	    $whichTabsArray = makeWikiTabsDefaults();
	  }
	}
      }
      else{ //If defaults are not defined for the current skin.
	$permanentTabsArray = makePermanentTabsDefaults();
	$whichTabsArray = makeWikiTabsDefaults();
      }

      break; // We found a matching definition or used the default. Stop processing any more skin tab definitions.
    }
    else{ //If defaults are not defined for the current skin.
      $permanentTabsArray = makePermanentTabsDefaults();
      $whichTabsArray = makeWikiTabsDefaults();
    }
  }
}
 else{//If the MediaWiki:WikiTabs article does not contain a valid skin definition.
   $permanentTabsArray = makePermanentTabsDefaults();
   $whichTabsArray = makeWikiTabsDefaults();
 }
}
	else{//If the MediaWiki:WikiTabs article doesn't exist.
	  $permanentTabsArray = makePermanentTabsDefaults();
	  $whichTabsArray = makeWikiTabsDefaults();
	}

//		echo "permanentTabsArray size:".count($permanentTabsArray)."!<br>";
//		echo "whichTabsArray size:".count($whichTabsArray)."!<br>";
	
if(count($permanentTabsArray) <= 0){ //Final check for valid defaults.
  $permanentTabsArray = makePermanentTabsDefaults();
 }
if(count($whichTabsArray) <= 0){ //Final check for valid defaults.
  $whichTabsArray = makeWikiTabsDefaults();
 }

makeTabsPermanent($permanentTabsArray, $content_actions);
	
//	echo count($whichTabsArray)."<br>";
	
if(count($whichTabsArray) > 0){ //$whichTabsArray values were specified
  $content_actions_temp = $content_actions;
  $content_actions = null;

  foreach($whichTabsArray as $key => $tab){
    if($tab != "first"){
      // check to make sure that the tab exists, otherwise it throws an exception
      if (isset($content_actions_temp[$tab])) {
	$content_actions[$tab] = $content_actions_temp[$tab];
	//					$content_actions[$tab] = $currentSkin;
	unset($content_actions_temp[$tab]);
      }
    }
    else{
      $firstActionName = key($content_actions_temp);
      $content_actions[$firstActionName] = $content_actions_temp[$firstActionName];
      unset($content_actions_temp[$firstActionName]);
    }
  }
	
  $other_content_actions = $content_actions_temp;
 }

return true;
}


function makeTabsPermanent($permanentTabsArray,&$content_actions){
  global $wgTitle, $wgUser, $wgRequest, $wgScriptPath, $wgUseCis, $wgServer;

  if(count($permanentTabsArray) > 0){ //$permanentTabsArray values were specified
    foreach($permanentTabsArray as $key => $tab){
		
      $tab = strtolower($tab);
		
      if(!array_key_exists($tab,$content_actions)){ //Only do this if the tab doesn't already exist.
		
	$tab_url = $wgRequest->getRequestURL()."?action=".$tab;
	//$tab_url_server = urlencode("http://".$_SERVER['SERVER_ADDR'].$tab_url);
	$tab_url_server = urlencode($wgServer.$tab_url);

	if(!$wgUser->isLoggedIn()){
	  if($wgUseCis){
	    //$tab_url_server = urlencode("http://".$_SERVER['SERVER_ADDR'].$tab_url);
	    $tab_url_server = urlencode($wgServer.$tab_url);
            //	    $url = $wgScriptPath."/extensions/FSAuthPlugin/FSOAuthPlugin.php?action=signin&returnto=".$tab_url_server;
            $url = WIKI_CIS_OAUTH_URL;
	  } else {
	    $url = $wgScriptPath."/index.php?title=Special:UserLogin&returnto=".$wgTitle;
	  }
	}
	else {
	  $url = $tab_url;
	}

	$title = $wgRequest->getVal('title');
	if($title != Title::makeName(NS_SPECIAL, "UserLogin") && !$wgUser->isLoggedIn()){//Usually the permanent tabs are those that don't show when a person is not signed in.
	  //If the person is signed in, there is no need to make the tab because it will already show.
	  //This is especially for the Watch/Unwatch tab so that "Watch" & "Unwatch" do not display at the same time
	  //when a page is being watched by the person that is signed in.
	  $content_actions[$tab] = Array('text' => wfMsg($tab),
					 'href' => $url ,
					 );
	}
      }
    } //end foreach
  }

  return true;
}

function makePermanentTabsDefaults(){
  $permanentTabsArray = array('edit','watch'); //Default permanent tabs
  //	$permanentTabsArray = array(); //Defaults for testing
  return $permanentTabsArray;
}

function makeWikiTabsDefaults(){
  $whichTabsArray = array(); //Default tabs. Show all if empty.
  //	$whichTabsArray = array('first','edit','talk','watch','delete'); //Defaults for testing
  //	$whichTabsArray = array('first','edit','talk','history','watch','delete'); //Defaults for testing
  return $whichTabsArray;
}


//Sample MediaWiki:WikiTabs article
/*
 @familysearch

 *permanent

 #edit 
#watch

*whichTabs

#first 
#edit 
#talk 
#history 
#watch
#unwatch

@default

*permanent

#edit 
#watch
*/
