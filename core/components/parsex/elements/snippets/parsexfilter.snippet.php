<?php
/**
 * ParseXFilter
 *
 * Copyright 2016 by Thomas Jakobi <office@treehillstudio.com>
 *
 * Description:
 * Snippet to generate a SQL Query that can be used in WHERE condition in getResources/pdoResources snippet
 *
 * Usage:
 * [[!ParseX?
 * &filter=`[[!ParseXFilter?
 *          &where=`{"whatever": 0}`
 *          &params=`requestparam:operator:prefix:suffix`
 *          ]]`
 * ]]
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

$corePath = $modx->getOption('parsex.core_path', null, $modx->getOption('core_path') . 'components/parsex/');
/** @var ParseX $parsex */
$parsex = $modx->getService('parsex', 'ParseX', $corePath . 'model/parsex/', array(
    'core_path' => $corePath
));

// Snippet properties
$where = json_decode($modx->getOption('where', $scriptProperties, ''), true);
$where = ($where) ? $where : array();
$params = $modx->getOption('params', $scriptProperties, '');
$params = ($params) ? explode(',', $params) : array();

// URL parameter
$filters = array();
foreach ($params as $param) {
    $param = explode(':', $param);
    $filter = $modx->getOption($param[0], $_REQUEST, '');
    if (is_array($filter)) {
        array_walk($filter, array($parsex, 'cleanRequestParameter'));
    } else {
        $parsex->cleanRequestParameter($filter, '');
    }
    if ((is_string($filter) && $filter !== '') ||
        (is_array($filter) && !empty($filter))
    ) {
        $filters[] = array(
            'key' => $param[0],
            'value' => $filter,
            'operator' => (isset($param[1])) ? $param[1] : '',
            'prefix' => (isset($param[2])) ? $param[2] : '',
            'suffix' => (isset($param[3])) ? $param[3] : '',
        );
    }
}

foreach ($filters as $filter) {
    if (is_array($filter['value'])) {
        $where[$filter['prefix'] . $filter['key'] . $filter['suffix'] . ':IN'] = $filter['value'];
        $modx->setPlaceholder($filter . '_filter', json_encode($filter['value']));
    } else {
        $where[$filter['prefix'] . $filter['key'] . $filter['suffix'] . ($filter['operator'] ? ':' . $filter['operator'] : '')] = $filter['value'];
        $modx->setPlaceholder($filter . '_filter', $filter['value']);
    }
}

return str_replace(array('[[', ']]'), array('[ [', '] ]'), json_encode($where));