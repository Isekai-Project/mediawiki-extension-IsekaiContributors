<?php
namespace Isekai\Contributors;

use OutputPage;
use Skin;
use Config;
use Title;
use PageProps;
use Exception;
use MediaWiki\MediaWikiServices;

class Hooks {
    public static function onBeforePageDisplay(OutputPage $outputPage){
        $outputPage->addModuleStyles('ext.isekai.contrib.styles');
        $outputPage->addModules(['ext.isekai.contrib.images', 'ext.isekai.contrib.dialog']);
    }

    public static function onSidebarBeforeOutput(Skin $skin, array &$sidebar){
        if(!$skin->getOutput()->isArticle()) return;

        $title = $skin->getTitle();
        if($title && !in_array($title->getNamespace(), [NS_MAIN, NS_CATEGORY, NS_FILE, NS_HELP, NS_PROJECT])){
            return;
        }

        try {
            $sidebar['isekai-contrib'] = (new ContributorsBox($skin->getContext(), $title))->getSideboxPanelHtml();
        } catch(Exception $e){

        }
    }

    public static function onSkinTemplateOutputPageBeforeExec(\SkinTemplate &$skin, \QuickTemplate &$template){
        if(!$skin->getOutput()->isArticle()) return;

        $title = $skin->getTitle();
        if($title && !in_array($title->getNamespace(), [NS_MAIN, NS_CATEGORY, NS_FILE, NS_HELP, NS_PROJECT])){
            return;
        }
        
        $props = self::getPageProp($title);
        if(!isset($props['nocreditbox'])){
            try {
                $template->extend('subtitle', (new ContributorsBox($skin->getContext(), $title))->getTopPanelHtml());;
            } catch(Exception $e){

            }
        }
    }

    public static function onGetDoubleUnderscoreIDs(array &$ids){
		$ids[] = 'nocreditbox';
	}

    public static function onResourceLoaderGetConfigVars(array &$vars, string $skin, Config $config){
        $vars['wgIsekaiContributorAvatar'] = $config->get('IsekaiContributorAvatar');
    }

    public static function getPageProp(Title $title){
        $id = $title->getArticleID();
        $props = PageProps::getInstance()->getAllProperties( $title );
		return $props[$id] ?? [];
    }
}