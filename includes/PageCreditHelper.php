<?php
namespace Isekai\Contributors;

use Article;
use MediaWiki\MediaWikiServices;
use SpecialPage;
use Title;
use User;

class PageCreditHelper {
    private $cache;
    private $context;

    public function __construct($context){
        $this->cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
        $this->context = $context;
    }

    public function getContributors(Title $title, $limit = false){
        return $this->cache->getWithSetCallback(
            $this->cache->makeKey('isekai_page_credit', $title->getArticleID(), $title->getLatestRevID()),
            $this->cache::TTL_MINUTE * 10,
            function() use($title, $limit){
                $wikiPage = Article::newFromTitle($title, $this->context)->getPage();
                if(!$wikiPage->exists()) return [];
                
                $creator = $wikiPage->getCreator();
                $contributors = $wikiPage->getContributors();
                $lastEditor = User::newFromName($wikiPage->getUserText());

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

    public function onPageEdit(Title $title){
        $this->cache->delete($this->cache->makeKey('isekai_page_credit', $title->getArticleID(), $title->getLatestRevID()));
    }

    public function getUserInfo(User $user){
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

    /**
     * Get a link to $user's user page
     * @param User $user
     * @return string Html
     */
    protected function getUserPage( User $user ) {
        $page = $user->isAnon()
            ? SpecialPage::getTitleFor( 'Contributions', $user->getName() )
            : $user->getUserPage();

        return $page;
    }
}