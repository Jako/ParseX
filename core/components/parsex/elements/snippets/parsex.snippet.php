<?php
/**
 * ParseX
 *
 * Snippet to read and parse XML input
 *
 * Usage: [[!parsex? &source=`feed.rss` &tpl=`xmlTpl`]]
 *
 * @package parsex
 * @subpackage snippet
 *
 * @author guido.gallenkamp@bytethinker.com
 * Let me know if you add or change things, maybe I can add them to the package in a later version!
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

$corePath = $modx->getOption('parsex.core_path', null, $modx->getOption('core_path') . 'components/parsex/');
/** @var ParseX $parsex */
$parsex = $modx->getService('parsex', 'ParseX', $corePath . 'model/parsex/', array(
    'core_path' => $corePath
));

$source = $modx->getOption('source', $scriptProperties, 'https://modx.com/feeds/latest.rss', true);
$elements = $modx->getOption('elements', $scriptProperties, 'item', true);
$tpl = $modx->getOption('tpl', $scriptProperties, 'parsexTpl', true);
$wrapper = $modx->getOption('wrapper', $scriptProperties, 'parsexWrapTpl', true);
$outputSeparator = $modx->getOption('outputSeparator', $scriptProperties, "\n", true);
$limit = $modx->getOption('limit', $scriptProperties, 0, true);
$offset = $modx->getOption('offset', $scriptProperties, 0, true);
$sortby = json_decode($modx->getOption('sortby', $scriptProperties, '', true), true);
$filter = json_decode($modx->getOption('filter', $scriptProperties, '', true), true);
$totalVar = $modx->getOption('totalVar', $scriptProperties, 'total');
$debugmode = $modx->getOption('debugmode', $scriptProperties, false, true);
$cacheData = $modx->getOption('cacheData', $scriptProperties, false, true);

if (empty($source)) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, '[ParseX] Empty source adress passed, aborting.');
    return 'No source definded.';
} else {
    $output = array();
    $debug = array();

    $cacheOptions = array(
        xPDO::OPT_CACHE_KEY => $parsex->getOption('cacheKey'),
        xPDO::OPT_CACHE_HANDLER => $parsex->getOption('cacheHandler'),
        xPDO::OPT_CACHE_EXPIRES => $cacheData,
    );
    $cacheElementKey = md5($source . $elements);
    $data = $modx->cacheManager->get($cacheElementKey, $cacheOptions);
    if (empty($data)) {
        $data = $parsex->loadData($source);
    }
    if ($data && $xml = simplexml_load_string($data)) {
        if ($cacheData !== false) {
            $modx->cacheManager->set($cacheElementKey, $data, $cacheData, $cacheOptions);
        }

        $nodes = $xml->xpath("//$elements");
        if (!empty($filter)) {
            $parsex->filterNodes($nodes, $filter);
        }
        if (is_array($sortby)) {
            $sortkey = key($sortby);
            $sortdir =  reset($sortby);
            $parsex->sort($nodes, $sortkey, $sortdir);
        }
        $nodeCount = count($nodes);
        $modx->setPlaceholder($totalVar, $nodeCount);
        $idx = 0;
        if ($debugmode == true) {
            $debug[] = 'Nodes: ' . $nodeCount . '<br />';
        }
        foreach ($nodes as $node) {
            $values = $parsex->xmlObjToArr($node);
            if ($debugmode == true && $idx == 0) {
                $debug[] = 'First node:<br />' . print_r($node, true);
                $debug[] = 'First converted value:<br />' . print_r($values, true);
            }
            $values['idx'] = $idx +1;
            $idx++;
            if ($offset && $idx <= $offset) {
                continue;
            }
            $output[] = $modx->getChunk($tpl, $values);
            if ($limit && ($idx >= ($limit + $offset))) {
                break;
            }
        }
    } else {
        $modx->log(xPDO::LOG_LEVEL_ERROR, '[ParseX] can NOT read file: ' . $source);
    }

    $result = array(
        'result' => implode($outputSeparator, $output)
    );

    $debug = ($debugmode) ? '<pre>' . implode("\n", $debug) . '</pre>' : '';
    return $modx->getChunk($wrapper, $result) . $debug;
}
