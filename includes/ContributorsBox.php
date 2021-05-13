<?php
namespace Isekai\Contributors;

use Html;
use IContextSource;
use MediaWiki\MediaWikiServices;
use Title;

class ContributorsBox {
    private $context;
    private $title;
    private $helper;
    private $contributors;

    public function __construct(IContextSource $context, Title $title){
        $this->context = $context;
        $this->title = $title;
        $this->helper = new PageCreditHelper($context);

        $this->contributors = $this->helper->getContributors($this->title, 5);
    }

    public function getSideboxPanelHtml(){
        global $wgIsekaiContributorAvatar;
        if(empty($this->contributors)) return '';
        $userList = array_merge([$this->contributors['last_editor']], [$this->contributors['creator']],
            $this->contributors['contributors']);
        $userHtml = [];
        $exportedUser = [];
        foreach ($userList as $user){
            if(!in_array($user['user_name'], $exportedUser)) {
                $displayName = $user['display_name'];
                if($user['user_name'] != $displayName) $displayName .= ' [@' . $user['user_name'] . ']';
                $userHtml[] = Html::element('a', [
                        'href' => $user['user_page']->getLinkURL(),
                        'target' => '_blank',
                        'title' => $displayName,
                        'style' => "background-image: url('" . sprintf($wgIsekaiContributorAvatar, urlencode($user['user_name'])) . "')",
                    ]);
                $exportedUser[] = $user['user_name'];
            }
        }
        $userHtml[] = Html::element('a', [
            'href' => 'javascript:;',
            'title' => wfMessage('isekaicontrib-viewall')->text(),
            'class' => 'isekai-img-more-contrib isekai-contrib-open-dialog',
        ]);
        return Html::openElement('div', ['class' => 'isekai-contrib-sidebox-panel']) .
            Html::openElement('div', ['class' => 'isekai-contrib-panel']) .
            implode('', $userHtml) . Html::closeElement('div') . Html::closeElement('div');
    }

    public function getTopPanelHtml(){
        global $wgIsekaiContributorAvatar;
        if(empty($this->contributors)) return '';
        $userList = array_merge([$this->contributors['last_editor']], [$this->contributors['creator']],
            $this->contributors['contributors']);
        $userHtml = [
            Html::openElement('div', ['class' => 'avatar-zone']),
            Html::element('span', ['class' => 'isekai-contrib-panel-title'], wfMessage('isekai-contrib')->text())
        ];
        $exportedUser = [];
        foreach ($userList as $user){
            if(!in_array($user['user_name'], $exportedUser)) {
                $displayName = $user['display_name'];
                if($user['user_name'] != $displayName) $displayName .= ' [@' . $user['user_name'] . ']';
                $userHtml[] = Html::element('a', [
                        'href' => $user['user_page']->getLinkURL(),
                        'target' => '_blank',
                        'title' => $displayName,
                        'style' => "background-image: url('" . sprintf($wgIsekaiContributorAvatar, urlencode($user['user_name'])) . "')",
                    ]);
                $exportedUser[] = $user['user_name'];
            }
        }
        $userHtml[] = Html::closeElement('div');
        $userHtml[] = Html::element('div', ['class' => 'spacer']);
        $userHtml[] = Html::element('a', [
            'href' => 'javascript:;',
            'title' => wfMessage('isekaicontrib-viewall')->text(),
            'class' => 'isekai-img-more-contrib isekai-contrib-open-dialog',
        ]);
        return Html::openElement('div', ['class' => 'isekai-contrib-top-panel isekai-contrib-panel']) .
            implode('', $userHtml) . Html::closeElement('div');
    }
}