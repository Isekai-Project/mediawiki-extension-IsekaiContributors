<?php

namespace Isekai\Contributors\Api;

use ApiBase;
use ApiQueryBase;
use ApiQuery;
use Config;
use MediaWiki\MediaWikiServices;
use WANObjectCache;
use Wikimedia\ParamValidator\ParamValidator;
use Exception;

use Isekai\Contributors\PageCreditHelper;

class ApiPageCredit extends ApiQueryBase {
    private const CACHE_VERSION = 2;

    private const PREFIX = 'pc';

    private $params;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var WANObjectCache
     */
    private $cache;
    /**
     * @var PageCreditHelper
     */
    private $helper;

    /**
     * @param ApiQuery $query API query module object
     * @param string $moduleName Name of this query module
     */
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, self::PREFIX);
        $this->config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'textextracts' );
        $this->cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
        $this->helper = new PageCreditHelper($this->getContext());
    }

    public function execute() {
        $titles = $this->getPageSet()->getGoodTitles();
        if (empty($titles)) {
            return;
        }

        $isXml = $this->getMain()->isInternalMode()
            || $this->getMain()->getPrinter()->getFormat() == 'XML';
        $result = $this->getResult();
        $params = $this->params = $this->extractRequestParams();
        $limit = intval($params['limit']);
        $limit = ($limit < 0) ? false : $limit;

        foreach ($titles as $id => $title) {
            try {
                $credit = $this->helper->getContributors($title, $limit);
                if ($isXml) {
                    $result->addValue(['query', 'pages', $id], 'pagecredit', ['*' => $credit]);
                } else {
                    $result->addValue(['query', 'pages', $id], 'pagecredit', $credit);
                }
            } catch(Exception $e){
                $result->addValue(['query', 'pages', $id], 'pagecredit', ['error' => $e->getTraceAsString()]);
            }
        }
    }

    /**
     * @param array $params Ignored parameters
     * @return string
     */
    public function getCacheMode($params) {
        return 'public';
    }

    public function getAllowedParams() {
        return [
            'limit' => [
                ParamValidator::PARAM_DEFAULT => -1,
                ApiBase::PARAM_TYPE => 'limit',
                ApiBase::PARAM_MIN => -1,
                ApiBase::PARAM_MAX => 100,
            ]
        ];
    }
}