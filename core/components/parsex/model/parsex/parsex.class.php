<?php
/**
 * ParseX
 *
 * Copyright 2016 by Guido Gallenkamp <guido.gallenkamp@bytethinker.com>
 *
 * @package parsex
 * @subpackage classfile
 */

/**
 * Class ParseX
 */
class ParseX
{
    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    public $modx;

    /**
     * The namespace
     * @var string $namespace
     */
    public $namespace = 'parsex';

    /**
     * The version
     * @var string $version
     */
    public $version = '1.1.0';

    /**
     * The class options
     * @var array $options
     */
    public $options = array();

    /**
     * ParseX constructor
     *
     * @param modX $modx A reference to the modX instance.
     * @param array $options An array of options. Optional.
     */
    public function __construct(modX &$modx, $options = array())
    {
        $this->modx =& $modx;

        $corePath = $this->getOption('core_path', $options, $this->modx->getOption('core_path') . "components/{$this->namespace}/");
        $assetsPath = $this->getOption('assets_path', $options, $this->modx->getOption('assets_path') . "components/{$this->namespace}/");
        $assetsUrl = $this->getOption('assets_url', $options, $this->modx->getOption('assets_url') . "components/{$this->namespace}/");

        // Load some default paths for easier management
        $this->options = array_merge(array(
            'namespace' => $this->namespace,
            'version' => $this->version,
            'assetsPath' => $assetsPath,
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $assetsUrl . 'connector.php',
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'vendorPath' => $corePath . 'vendor/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'pagesPath' => $corePath . 'elements/pages/',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'pluginsPath' => $corePath . 'elements/plugins/',
            'controllersPath' => $corePath . 'controllers/',
            'processorsPath' => $corePath . 'processors/',
            'templatesPath' => $corePath . 'templates/',
        ), $options);

        // set default options
        $this->options = array_merge($this->options, array(
            'debug' => $this->getOption('debug', null, false),
            'cacheKey' => $this->namespace,
            'cacheHandler' => $this->modx->getOption('cache_resource_handler', null, $this->modx->getOption(xPDO::OPT_CACHE_HANDLER, null, 'xPDOFileCache'))
        ));

        $this->modx->lexicon->load("{$this->namespace}:default");
    }

    /**
     * Get a local configuration option or a namespaced system setting by key.
     *
     * @param string $key The option key to search for.
     * @param array $options An array of options that override local options.
     * @param mixed $default The default value returned if the option is not found locally or as a
     * namespaced system setting; by default this value is null.
     * @return mixed The option value or the default value specified.
     */
    public function getOption($key, $options = array(), $default = null)
    {
        $option = $default;
        if (!empty($key) && is_string($key)) {
            if ($options != null && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (array_key_exists($key, $this->options)) {
                $option = $this->options[$key];
            } elseif (array_key_exists("$this->namespace.$key", $this->modx->config)) {
                $option = $this->modx->getOption("$this->namespace.$key");
            }
        }
        return $option;
    }

    /**
     * Recursive convert a SimpleXMLElement to an array
     *
     * @param SimpleXMLElement $xml
     * @return array
     */
    public function xmlObjToArr($xml)
    {
        $arr = array();
        foreach ($xml->children() as $r) {
            /** @var SimpleXMLElement|Countable $r */
            if (count($r->children()) == 0) {
                $arr[$r->getName()] = strval($r);
            } else {
                $arr[$r->getName()][] = $this->xmlObjToArr($r);
            }
        }
        return $arr;
    }

    /**
     * Load data from URL with curl
     *
     * @param $url
     * @return mixed
     */
    public function loadData($url)
    {
        $curloptHeader = false;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, $curloptHeader);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $safeMode = @ini_get('safe_mode');
        $openBasedir = @ini_get('open_basedir');
        if (empty($safeMode) && empty($openBasedir)) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $data = curl_exec($ch);
        } else {
            $redirects = 0;
            $data = $this->curl_redirect_exec($ch, $redirects, $curloptHeader);
        }
        curl_close($ch);

        return $data;
    }

    /**
     * Recursive cURL with redirect and open_basedir
     * http://stackoverflow.com/questions/3890631/php-curl-with-curlopt-followlocation-error
     *
     * @param $ch
     * @param $redirects
     * @param bool $curlopt_header
     * @return mixed
     */
    private function curl_redirect_exec($ch, &$redirects, $curlopt_header = false)
    {
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $data = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 301 || $http_code == 302) {
            list($header) = explode("\r\n\r\n", $data, 2);

            $matches = array();
            preg_match("/(Location:|URI:)[^(\n)]*/", $header, $matches);
            $url = trim(str_replace($matches[1], "", $matches[0]));

            $url_parsed = parse_url($url);
            if (isset($url_parsed)) {
                curl_setopt($ch, CURLOPT_URL, $url);
                $redirects++;
                return $this->curl_redirect_exec($ch, $redirects, $curlopt_header);
            }
        }

        if ($curlopt_header) {
            return $data;
        } else {
            list(, $body) = explode("\r\n\r\n", $data, 2);
            return $body;
        }
    }

    /**
     * Filter an array of SimpleXMLElement nodes
     *
     * @param SimpleXMLElement[] $nodes
     * @param array $filters
     */
    public function filterNodes(&$nodes, $filters)
    {
        foreach ($filters as $filter => $value) {
            $filter = explode(':', $filter);
            $operator = (isset($filter[1])) ? $filter[1] : '=';
            $filter = $filter[0];
            foreach ($nodes as $i => $v) {
                $v = $this->xmlObjToArr($v);
                $v = (is_array($v)) ? $this->flattenArray($v) : array();
                switch ($operator) {
                    case '!=':
                    case '<>':
                        if (isset($v[$filter]) && $v[$filter] == $value) {
                            unset($nodes[$i]);
                            continue;
                        }
                        break;
                    case '>':
                        if (!isset($v[$filter]) || (isset($v[$filter]) && $v[$filter] <= $value)) {
                            unset($nodes[$i]);
                            continue;
                        }
                        break;
                    case '>=':
                        if (!isset($v[$filter]) || (isset($v[$filter]) && $v[$filter] < $value)) {
                            unset($nodes[$i]);
                            continue;
                        }
                        break;
                    case '<':
                        if (!isset($v[$filter]) || (isset($v[$filter]) && $v[$filter] >= $value)) {
                            unset($nodes[$i]);
                            continue;
                        }
                        break;
                    case '<=':
                        if (!isset($v[$filter]) || (isset($v[$filter]) && $v[$filter] > $value)) {
                            unset($nodes[$i]);
                            continue;
                        }
                        break;
                    case 'LIKE':
                        if (!isset($v[$filter])) {
                            continue;
                        }
                        $pattern = '/^' . str_replace('%', '(.*?)', preg_quote($value, '/')) . '$/';
                        if (isset($v[$filter]) && !preg_match($pattern, $v[$filter])) {
                            unset($nodes[$i]);
                            continue;
                        }
                        break;
                    default:
                        if (isset($v[$filter]) && $v[$filter] != $value) {
                            unset($nodes[$i]);
                            continue;
                        }
                        break;
                }
            }
        }
    }

    /**
     * Sort a multidimensional array
     *
     * @param array $array
     * @param string $sortkey
     * @param string $sortdir
     */
    public function sort(&$array, $sortkey, $sortdir = 'asc')
    {
        if (strpos($sortkey, '.') === false) {
            $this->options['sortkey'] = $sortkey;
        } else {
            $this->options['sortkey'] = explode('.', $sortkey);
        }
        $this->options['sortdir'] = ($sortdir === 'desc') ? 'desc' : 'asc';
        usort($array, array($this, 'compareSort'));
    }

    //

    /**
     * Compare sort values
     *
     * @param SimpleXMLElement $a
     * @param SimpleXMLElement $b
     * @return int
     */
    private function compareSort($a, $b)
    {
        $a = $this->xmlObjToArr($a);
        $b = $this->xmlObjToArr($b);
        if (!is_array($this->getOption('sortkey'))) {
            $val_a = isset($a[$this->getOption('sortkey')]) ? $a[$this->getOption('sortkey')] : false;
            $val_b = isset($b[$this->getOption('sortkey')]) ? $b[$this->getOption('sortkey')] : false;
        } else {
            $val_a = false;
            $val_b = false;
            foreach ($this->getOption('sortkey') as $key) {
                if (isset($a[$key])) {
                    $val_a = $a = $a[$key];
                } else {
                    $val_a = false;
                    break;
                }
                if (isset($b[$key])) {
                    $val_b = $b = $b[$key];
                } else {
                    $val_b = false;
                    break;
                }
            }
        }
        if ($val_a === false || $val_b === false || $val_a === $val_b) {
            return 0;
        } else {
            if ($val_a < $val_b) {
                return ($this->getOption('sortdir') === 'asc') ? -1 : 1;
            } else {
                return ($this->getOption('sortdir') === 'asc') ? 1 : -1;
            }
        }
    }

    /**
     * Flatten array of placeholders with nested arrays
     *
     * @param $array
     * @param string $prefix
     *
     * @return array
     */
    public function flattenArray($array, $prefix = '')
    {
        $result = array();
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $result = array_merge($result, $this->flattenArray($v, $prefix . $k . '.'));
            } else {
                $result[$prefix . $k] = $v;
            }
        }
        return $result;
    }

    /**
     * Clean request parameters
     *
     * @param $item
     * @param $key
     */
    public function cleanRequestParameter(&$item, $key)
    {
        $item = preg_replace('/[^0-9a-z-_]+/iu', '', $item);
    }

}
