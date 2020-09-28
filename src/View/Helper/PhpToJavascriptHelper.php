<?php
declare(strict_types=1);

namespace PhpToJavascript\View\Helper;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Utility\Text;
use PhpToJavascript\Plugin;

/**
 * Helper class which stores and generates javascript block from php variables.
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class PhpToJavascriptHelper extends \Cake\View\Helper
{
    /**
     * @var array Stores the set variables which will be passed to Js
     */
    private $storage = [];

    /**
     * @var string[]
     */
    public $helpers = ['Html'];

    /**
     * @var array
     */
    protected $_defaultConfig = [
        'function' => 'p',
        'storage' => '__phptojavascript',
        'encode' => [
            'options' => 0,
            'depth' => 512,
        ],
        'cache' => [
            'enabled' => false,
            'key' => '__phptojavascript',
            'config' => 'default',
        ],
    ];

    /**
     * @param \Cake\View\View $View The View this helper is being attached to
     * @param array $config Configuration settings for the helper
     */
    public function __construct(\Cake\View\View $View, array $config = [])
    {
        $config += (array)Configure::read('PhpToJavascript');
        parent::__construct($View, $config);
    }

    /**
     * Converts a variable to javascript safely
     *
     * @param mixed $value Value to convert
     * @return mixed The converted value
     */
    private function convert($value)
    {
        $type = gettype($value);
        switch ($type) {
            case 'double':
            case 'integer':
            case 'NULL':
                return $value;
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'string':
            case 'object':
            case 'array':
                return json_encode(
                    $value,
                    $this->getConfig('encode.options', 0),
                    $this->getConfig('encode.depth', 512)
                );
            default:
                throw new \InvalidArgumentException(sprintf('Invalid type to convert into JSON: %s', $type));
        }
    }

    /**
     * Returns the main javascript file's content
     *
     * @return string
     */
    private function getJsFileContent(): string
    {
        $cache_enabled = $this->getConfig('cache.enabled');
        if ($cache_enabled) {
            $content = Cache::read($this->getConfig('cache.key'), $this->getConfig('cache.config'));
            if (is_string($content)) {
                return $content;
            }
        }
        $template = (new Plugin())->getConfigPath() . 'main.js-template';
        $content = Text::insert(file_get_contents($template), [
            'function' => $this->getConfig('function'),
            'storage' => $this->getConfig('storage'),
        ]);
        if ($cache_enabled) {
            Cache::write($this->getConfig('cache.key'), $content, $this->getConfig('cache.config'));
        }

        return $content;
    }

    /**
     * Pushes a variable into the storage.
     *
     * @param string $key he key where the php variable will be available in client side
     * @param mixed $value Value to store
     * @param bool $overwrite Overwrite if exists or not
     * @return bool True on success, false on failure
     */
    private function push(string $key, $value, bool $overwrite): bool
    {
        if ($overwrite) {
            $this->storage[$key] = $this->convert($value);

            return true;
        }

        if (!array_key_exists($key, $this->storage)) {
            $this->storage[$key] = $this->convert($value);

            return true;
        }

        return false;
    }

    /**
     * Sets a PHP variable into the storage.
     * If the key exists, overrides it.
     *
     * @param string|array $key The key where the php variable will be available in client side,
     * or multiple key value pairs. In case of array, the second parameter will be ignored
     * @param mixed $value Value to store
     * @return void
     */
    public function set($key, $value = null): void
    {
        if (is_array($key)) {
            foreach ($key as $_key => $_value) {
                $this->push($_key, $_value, true);
            }
        } else {
            $this->push($key, $value, true);
        }
    }

    /**
     * Adds a PHP variable into the storage.
     * If the key exists, the operation fails.
     *
     * @param string|array $key The key where the php variable will be available in client side,
     * or multiple key value pairs. In case of array, the second parameter will be ignored
     * @param mixed $value Value to store
     * @return void
     */
    public function add($key, $value = null): void
    {
        if (is_array($key)) {
            foreach ($key as $_key => $_value) {
                $this->push($_key, $_value, false);
            }
        } else {
            $this->push($key, $value, false);
        }
    }

    /**
     * Removes a PHP variable from storage.
     *
     * @param string $key The key to remove
     * @return bool True on success, false on failure.
     */
    public function remove(string $key): bool
    {
        if (array_key_exists($key, $this->storage)) {
            unset($this->storage[$key]);

            return true;
        }

        return false;
    }

    /**
     * Returns the javascript part of the conversion.
     * Does not adds to the storage.
     *
     * @param string $key The key of the variable.
     * @param mixed $value The value of the variable
     * @param bool $with_tags Returns the result inner a script tag or not.
     * @return string
     */
    public function put(string $key, $value, bool $with_tags = true): string
    {
        $result = '';
        if (empty($key)) {
            return $result;
        }
        $storage = $this->getConfig('storage');
        $result = Text::insert('window.:storage.:key = :value;', [
            'storage' => $storage,
            'key' => $key,
            'value' => $value ?? 'null',
        ]);

        return $with_tags ? $this->Html->scriptBlock($result) : $result;
    }

    /**
     * Returns the current storage as a string
     *
     * @param bool $include_javascript_file Includes the plugin's javascript file. Default: true
     * @param bool $with_tags Append script tag or not. Default: true
     * @return string
     */
    public function get(bool $include_javascript_file = true, bool $with_tags = true): string
    {
        if (empty($this->storage)) {
            return '';
        }
        $result = '';
        if ($include_javascript_file) {
            $result = $this->getJsFileContent();
        }
        foreach ($this->storage as $key => $value) {
            $result .= $this->put($key, $value, false);
        }

        return $with_tags ? $this->Html->scriptBlock($result) : $result;
    }
}
