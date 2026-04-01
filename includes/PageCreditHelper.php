<?php
namespace Isekai\Contributors;

use Article;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

class PageCreditHelper {
    /**
     * @var \BagOStuff
     */
    private $cache;

    /**
     * @var \WikiPageFactory
     */
    private $wikiPageFactory;

    public function __construct() {
        $this->cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
        $this->wikiPageFactory = MediaWikiServices::getInstance()->getWikiPageFactory();
    }

    public function getContributors(Title $title, $limit = false) {
        return $this->cache->getWithSetCallback(
            $this->cache->makeKey('isekai_page_credit', $title->getArticleID()),
            $this->cache::TTL_MINUTE * 10,
            function() use($title, $limit){
                $wikiPage = $this->wikiPageFactory->newFromTitle($title);
                if (!$wikiPage->exists()) return [];

                // 页面创建者
                $creator = $wikiPage->getCreator();
                if (!$creator) $creator = User::newSystemUser('Anonymous');
                $creatorId = $creator->getId();
                $creator = MediaWikiServices::getInstance()->getUserFactory()->newFromId($creatorId);

                // 页面贡献者
                $contributors = $wikiPage->getContributors();

                // 最后编辑者
                $lastEditor = MediaWikiServices::getInstance()->getUserFactory()->newFromName($wikiPage->getUserText());
                if (!$lastEditor) $lastEditor = User::newSystemUser('Anonymous');

                $contributorInfo = [];
                $count = 0;
                foreach ($contributors as $user) {
                    $contributorInfo[] = $this->getUserInfo($user);
                    $count++;
                    if ($limit !== false && $count > $limit) {
                        break;
                    }
                }

                return [
                    'creator' => $this->getUserInfo($creator),
                    'last_editor' => $this->getUserInfo($lastEditor),
                    'contributors' => $contributorInfo,
                    'count' => $contributors->count(),
                ];
            }
        );
    }

    public function onPageEdit(Title $title) {
        $this->cache->delete($this->cache->makeKey('isekai_page_credit', $title->getArticleID()));
    }

    /**
     * @throws \MWException
     */
    public function getUserInfo(User $user) {
        $userInfo = [];
        if ($user->getRealName()) {
            $userInfo['display_name'] = $user->getRealName();
        } else {
            $userInfo['display_name'] = $user->getName();
        }
        $userInfo['user_name'] = $user->getName();
        $userInfo['user_page'] = $this->getUserPage($user);
        return $userInfo;
    }

    public function isCreator(Title $title, User $user) {
        $creator = $this->cache->getWithSetCallback(
            $this->cache->makeKey('isekai_page_creator', $title->getArticleID()),
            $this->cache::TTL_MINUTE * 10,
            function() use($title){
                $wikiPage = $this->wikiPageFactory->newFromTitle($title);
                if (!$wikiPage->exists()) return -1;
                $creator = $wikiPage->getCreator();
                if (!$creator) return -1;
                return $creator->getId();
            });
        return $creator === $user->getId();
    }

    /**
     * Get a link to $user's user page
     * @param User $user
     * @return string Html
     * @throws \MWException
     */
    protected function getUserPage( $user ) {
        $page = '';
        if ($user) {
            $page = $user->isAnon()
                ? SpecialPage::getTitleFor( 'Contributions', $user->getName() )
                : $user->getUserPage();
        }

        return $page;
    }
}