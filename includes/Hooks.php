<?php
namespace Isekai\Contributors;

use Skin;
use Exception;
use MediaWiki\Output\OutputPage;
use MediaWiki\Config\Config;
use MediaWiki\Title\Title;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;

class Hooks {
    public static function onBeforePageDisplay(OutputPage $outputPage) {
        $outputPage->addModuleStyles('ext.isekai.contrib.styles');
        $outputPage->addModules(['ext.isekai.contrib.images', 'ext.isekai.contrib.dialog']);

        if ($outputPage->isArticle()) {
            $title = $outputPage->getTitle();
            if (!$title || !in_array($title->getNamespace(), [NS_MAIN, NS_CATEGORY, NS_FILE, NS_HELP, NS_PROJECT])){
                return;
            }

            $props = self::getPageProp($title);
            if (!isset($props['nocreditbox'])) {
                try {
                    $html = (new ContributorsBox($outputPage->getContext(), $title))->getTopPanelHtml();
                    // 阻止出现subtitle空行
                    $subtitle = $outputPage->getSubtitle();
                    $outputPage->setSubtitle($subtitle . $html);
                } catch(Exception $e){

                }
            }
        }
    }

    public static function onSidebarBeforeOutput(Skin $skin, array &$sidebar) {
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

    public static function onGetDoubleUnderscoreIDs(array &$ids) {
		$ids[] = 'nocreditbox';
	}

    public static function onResourceLoaderGetConfigVars(array &$vars, string $skin, Config $config){
        $vars['wgIsekaiContributorAvatar'] = $config->get('IsekaiContributorAvatar');
    }

    public static function getPageProp(Title $title) {
        $id = $title->getArticleID();
        $props = MediaWikiServices::getInstance()->getPageProps()->getAllProperties($title);
		return $props[$id] ?? [];
    }

    private static $userGetRights = false;

    public static function onUserGetRights($user, array &$rights) {
        if (self::$userGetRights) { // 防止递归调用
            return true;
        }

        self::$userGetRights = true;

        $config = MediaWikiServices::getInstance()->getMainConfig();
		$title = RequestContext::getMain()->getTitle();
        if ($user && $title && $title->exists()) {
            $helper = new PageCreditHelper();
            if ($helper->isCreator($title, $user)) {
                $authorRights = $config->get('IsekaiCreatorPermissions');
                if (!empty($authorRights)) {
                    $rights = array_merge($rights, $authorRights);
                    $rights = array_unique($rights);
                }
            }
        }

        return true;
    }
}