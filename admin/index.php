<?php
/**
 * phpMoAdmin - built on a stripped-down version of the Vork framework
 *
 * www.phpMoAdmin.com
 * www.Vork.us
 * www.MongoDB.org
 *
 * @version 1.0.4
 * @author Eric David Benari, Chief Architect, phpMoAdmin
 */

/**
 * To enable password protection, uncomment below and then change the username => password
 * You can add as many users as needed, eg.: array('scott' => 'tiger', 'samantha' => 'goldfish', 'gene' => 'alpaca')
 */
//$accessControl = array('scott' => 'tiger');

/**
 * To connect to a remote or authenticated Mongo instance, define the connection string in the MONGO_CONNECTION constant
 * mongodb://[username:password@]host1[:port1][,host2[:port2:],...]
 * If you do not know what this means then it is not relevant to your application and you can safely leave it as-is
 */
define('MONGO_CONNECTION', '');

/**
 * Sets the design theme - themes options are: swanky-purse, trontastic and classic
 */
define('THEME', 'swanky-purse');

/**
 * Vork core-functionality tools
 */
class get {
    /**
     * Opens up public access to config constants and variables and the cache object
     * @var object
     */
    public static $config;

    /**
     * Index of objects loaded, used to maintain uniqueness of singletons
     * @var array
     */
    public static $loadedObjects = array();

    /**
     * Overloads the php function htmlentities and changes the default charset to UTF-8 and the default value for the
     * fourth parameter $doubleEncode to false. Also adds ability to pass a null value to get the default $quoteStyle
     * and $charset (removes need to repeatedly define ENT_COMPAT, 'UTF-8', just to access the $doubleEncode argument)
     *
     * If you are using a PHP version prior to 5.2.3 the $doubleEncode parameter is not available so you will need
     * to comment out the last parameter in the return clause (including the preceding comma)
     *
     * @param string $string
     * @param int $quoteStyle Uses ENT_COMPAT if null or omitted
     * @param string $charset Uses UTF-8 if null or omitted
     * @param boolean $doubleEncode
     * @return string
     */
    public static function htmlentities($string, $quoteStyle = ENT_COMPAT, $charset = 'UTF-8', $doubleEncode = false) {
        return htmlentities($string, (!is_null($quoteStyle) ? $quoteStyle : ENT_COMPAT),
                            (!is_null($charset) ? $charset : 'UTF-8'), $doubleEncode);
    }

    /**
     * Initialize the character maps needed for the xhtmlentities() method and verifies the argument values
     * passed to it are valid.
     *
     * @param int $quoteStyle
     * @param string $charset Only valid options are UTF-8 and ISO-8859-1 (Latin-1)
     * @param boolean $doubleEncode
     */
    protected static function initXhtmlentities($quoteStyle, $charset, $doubleEncode) {
        $chars = get_html_translation_table(HTML_ENTITIES, $quoteStyle);
        if (isset($chars)) {
            unset($chars['<'], $chars['>']);
            $charMaps[$quoteStyle]['ISO-8859-1'][true] = $chars;
            $charMaps[$quoteStyle]['ISO-8859-1'][false] = array_combine(array_values($chars), $chars);
            $charMaps[$quoteStyle]['UTF-8'][true] = array_combine(array_map('utf8_encode', array_keys($chars)), $chars);
            $charMaps[$quoteStyle]['UTF-8'][false] = array_merge($charMaps[$quoteStyle]['ISO-8859-1'][false],
                                                                 $charMaps[$quoteStyle]['UTF-8'][true]);
            self::$loadedObjects['xhtmlEntities'] = $charMaps;
        }
        if (!isset($charMaps[$quoteStyle][$charset][$doubleEncode])) {
            if (!isset($chars)) {
                $invalidArgument = 'quoteStyle = ' . $quoteStyle;
            } else if (!isset($charMaps[$quoteStyle][$charset])) {
                $invalidArgument = 'charset = ' . $charset;
            } else {
                $invalidArgument = 'doubleEncode = ' . (string) $doubleEncode;
            }
            trigger_error('Undefined argument sent to xhtmlentities() method: ' . $invalidArgument, E_USER_NOTICE);
        }
    }

    /**
     * Converts special characters in a string to XHTML-valid ASCII encoding the same as htmlentities except
     * this method allows the use of HTML tags within your string. Signature is the same as htmlentities except
     * that the only character sets available (third argument) are UTF-8 (default) and ISO-8859-1 (Latin-1).
     *
     * @param string $string
     * @param int $quoteStyle Constants available are ENT_NOQUOTES (default), ENT_QUOTES, ENT_COMPAT
     * @param string $charset Only valid options are UTF-8 (default) and ISO-8859-1 (Latin-1)
     * @param boolean $doubleEncode Default is false
     * @return string
     */
    public static function xhtmlentities($string, $quoteStyle = ENT_NOQUOTES, $charset = 'UTF-8',
                                         $doubleEncode = false) {
        $quoteStyles = array(ENT_NOQUOTES, ENT_QUOTES, ENT_COMPAT);
        $quoteStyle = (!in_array($quoteStyle, $quoteStyles) ? current($quoteStyles) : $quoteStyle);
        $charset = ($charset != 'ISO-8859-1' ? 'UTF-8' : $charset);
        $doubleEncode = (Boolean) $doubleEncode;
        if (!isset(self::$loadedObjects['xhtmlEntities'][$quoteStyle][$charset][$doubleEncode])) {
            self::initXhtmlentities($quoteStyle, $charset, $doubleEncode);
        }
        return strtr($string, self::$loadedObjects['xhtmlEntities'][$quoteStyle][$charset][$doubleEncode]);
    }

    /**
     * Loads an object as a singleton
     *
     * @param string $objectType
     * @param string $objectName
     * @return object
     */
    protected static function _loadObject($objectType, $objectName) {
        if (isset(self::$loadedObjects[$objectType][$objectName])) {
            return self::$loadedObjects[$objectType][$objectName];
        }
        $objectClassName = $objectName . ucfirst($objectType);
        if (class_exists($objectClassName)) {
            $objectObject = new $objectClassName;
            self::$loadedObjects[$objectType][$objectName] = $objectObject;
            return $objectObject;
        } else {
            $errorMsg = 'Class for ' . $objectType . ' ' . $objectName . ' could not be found';
        }
        trigger_error($errorMsg, E_USER_WARNING);
    }

    /**
     * Returns a helper object
     *
     * @param string $model
     * @return object
     */
    public static function helper($helper) {
        if (is_array($helper)) {
            array_walk($helper, array('self', __METHOD__));
            return;
        }
        if (!isset(self::$config['helpers']) || !in_array($helper, self::$config['helpers'])) {
            self::$config['helpers'][] = $helper;
        }
        return self::_loadObject('helper', $helper);
    }
}

/**
 * Thrown when the mongod server is not accessible
 */
class cannotConnectToMongoServer extends Exception {
    public function __toString() {
        return '<h1>Cannot connect to the MongoDB database.</h1> ' . PHP_EOL . 'If Mongo is installed then be sure that'
             . ' an instance of the "mongod" server, not "mongo" shell, is running. <br />' . PHP_EOL
             . 'Instructions and database download: <a href="http://vork.us/go/fhk4">http://vork.us/go/fhk4</a>';
    }
}

/**
 * Thrown when the mongo extension for PHP is not installed
 */
class mongoExtensionNotInstalled extends Exception {
    public function __toString() {
        return '<h1>PHP cannot access MongoDB, you need to install the Mongo extension for PHP.</h1> '
              . PHP_EOL . 'Instructions and driver download: '
              . '<a href="http://vork.us/go/tv27">http://vork.us/go/tv27</a>';
    }
}

/**
 * phpMoAdmin data model
 */
class moadminModel {
    /**
     * mongo connection - if a MongoDB object already exists (from a previous script) then only DB operations use this
     * @var Mongo
     */
    protected $_db;

    /**
     * MongoDB
     * @var MongoDB
     */
    public $mongo;

    /**
     * Returns a new Mongo connection
     * @return Mongo
     */
    protected function _mongo() {
        return (!MONGO_CONNECTION ? new Mongo() : new Mongo(MONGO_CONNECTION));
    }

    /**
     * Connects to a Mongo database if the name of one is supplied as an argument
     * @param string $db
     */
    public function __construct($db = null) {
        if ($db) {
            if (!extension_loaded('mongo')) {
                throw new mongoExtensionNotInstalled();
            }
            try {
                $this->_db = $this->_mongo();
                $this->mongo = $this->_db->selectDB($db);
            } catch (MongoConnectionException $e) {
                throw new cannotConnectToMongoServer();
            }
        }
    }

    /**
     * Executes a native JS MongoDB command
     * This method is not currently used for anything
     * @param string $cmd
     * @return mixed
     */
    protected function _exec($cmd) {
        $exec = $this->mongo->execute($cmd);
        return $exec['retval'];
    }

    /**
     * Change the DB connection
     * @param string $db
     */
    public function setDb($db) {
        if (!isset($this->_db)) {
            $this->_db = $this->_mongo();
        }
        $this->mongo = $this->_db->selectDB($db);
    }

    /**
     * Total size of all the databases
     * @var int
     */
    public $totalDbSize = 0;

    /**
     * Gets list of databases
     * @return array
     */
    public function listDbs() {
        $dbs = $this->_db->selectDB('admin')->command(array('listDatabases' => 1));
        $this->totalDbSize = $dbs['totalSize'];
        foreach ($dbs['databases'] as $db) {
            $return[$db['name']] = $db['name'] . ' (' . (!$db['empty'] ? round($db['sizeOnDisk'] / 1000000) . 'mb' : 'empty') . ')';
        }
        ksort($return);
        $dbCount = 0;
        foreach ($return as $key => $val) {
            $return[$key] = ++$dbCount . '. ' . $val;
        }
        return $return;
    }

    public function getStats() {
        $admin = $this->_db->selectDB('admin');
        $return = array_merge($admin->command(array('buildinfo' => 1)),
                              $admin->command(array('serverStatus' => 1)));
        $profile = $admin->command(array('profile' => -1));
        $return['profilingLevel'] = $profile['was'];
        $return['mongoDbTotalSize'] = round($this->totalDbSize / 1000000) . 'mb';
        $prevError = $admin->command(array('getpreverror' => 1));
        if (!$prevError['n']) {
            $return['previousDbErrors'] = 'None';
        } else {
            $return['previousDbErrors']['error'] = $prevError['err'];
            $return['previousDbErrors']['numberOfOperationsAgo'] = $prevError['nPrev'];
        }
        $return['globalLock']['totalTime'] .= ' &#0181;Sec';
        $return['uptime'] = round($return['uptime'] / 60) . ':' . str_pad($return['uptime'] % 60, 2, '0', STR_PAD_LEFT)
                          . ' minutes';
        $unshift['mongo'] = $return['version'];
        $unshift['mongoPhpDriver'] = Mongo::VERSION;
        $unshift['phpMoAdmin'] = '1.0.4';
        $unshift['gitVersion'] = $return['gitVersion'];
        unset($return['ok'], $return['version'], $return['gitVersion']);
        $return = array_merge(array('version' => $unshift), $return);
        $iniIndex = array(-1 => 'Unlimited', 'Off', 'On');
        $phpIni = array('allow_persistent', 'auto_reconnect', 'chunk_size', 'cmd', 'default_host', 'default_port',
                        'max_connections', 'max_persistent');
        foreach ($phpIni as $ini) {
            $key = 'php_' . $ini;
            $return[$key] = ini_get('mongo.' . $ini);
            if (isset($iniIndex[$return[$key]])) {
                $return[$key] = $iniIndex[$return[$key]];
            }
        }
        return $return;
    }

    /**
     * Repairs a database
     * @return array Success status
     */
    public function repairDb() {
        return $this->mongo->repair();
    }

    /**
     * Drops a database
     */
    public function dropDb() {
        $this->mongo->drop();
        return;
        if (!isset($this->_db)) {
            $this->_db = $this->_mongo();
        }
        $this->_db->dropDB($this->mongo);
    }

    /**
     * Gets a list of database collections
     * @return array
     */
    public function listCollections() {
        $collections = array();
        $MongoCollectionObjects = $this->mongo->listCollections();
        foreach ($MongoCollectionObjects as $collection) {
            $collections[] = (string) $collection;
        }
        sort($collections);
        return $collections;
    }

    /**
     * Drops a collection
     * @param string $collection
     */
    public function dropCollection($collection) {
        $this->mongo->selectCollection($collection)->drop();
    }

    /**
     * Creates a collection
     * @param string $collection
     */
    public function createCollection($collection) {
        if ($collection) {
            $this->mongo->createCollection($collection);
        }
    }

    /**
     * Gets a list of the indexes on a collection
     *
     * @param string $collection
     * @return array
     */
    public function listIndexes($collection) {
        return $this->mongo->selectCollection($collection)->getIndexInfo();
    }

    /**
     * Ensures an index
     *
     * @param string $collection
     * @param array $indexes
     * @param array $unique
     */
    public function ensureIndex($collection, array $indexes, array $unique) {
        $unique = ($unique ? true : false); //signature requires a bool in both Mongo v. 1.0.1 and 1.2.0
        $this->mongo->selectCollection($collection)->ensureIndex($indexes, $unique);
    }

    /**
     * Removes an index
     *
     * @param string $collection
     * @param array $index Must match the array signature of the index
     */
    public function deleteIndex($collection, array $index) {
        $this->mongo->selectCollection($collection)->deleteIndex($index);
    }

    /**
     * Sort array - currently only used for collections
     * @var array
     */
    public $sort = array('_id' => 1);

    /**
     * Get the records in a collection
     *
     * @param string $collection
     * @return array
     */
    public function listRows($collection) {
        foreach ($this->sort as $key => $val) { //cast vals to int
            $sort[$key] = (int) $val;
        }
        return $this->mongo->selectCollection($collection)->find()->sort($sort);
    }

    /**
     * Returns a serialized element back to its native PHP form
     *
     * @param string $_id
     * @param string $idtype
     * @return mixed
     */
    protected function _unserialize($_id, $idtype) {
        if ($idtype == 'object') {
            $errLevel = error_reporting();
            error_reporting(0); //unserializing an object that is not serialized throws a warning
            $_idObj = unserialize($_id);
            error_reporting($errLevel);
            if ($_idObj !== false) {
                $_id = $_idObj;
            }
        } else if (gettype($_id) != $idtype) {
            settype($_id, $idtype);
        }
        return $_id;
    }

    /**
     * Removes an object from a collection
     *
     * @param string $collection
     * @param string $_id
     * @param string $idtype
     */
    public function removeObject($collection, $_id, $idtype) {
        $this->mongo->selectCollection($collection)->remove(array('_id' => $this->_unserialize($_id, $idtype)));
    }

    /**
     * Retieves an object for editing
     *
     * @param string $collection
     * @param string $_id
     * @param string $idtype
     * @return array
     */
    public function editObject($collection, $_id, $idtype) {
        return $this->mongo->selectCollection($collection)->findOne(array('_id' => $this->_unserialize($_id, $idtype)));
    }

    /**
     * Saves an object
     *
     * @param string $collection
     * @param string $obj
     * @return array
     */
    public function saveObject($collection, $obj) {
        eval('$obj='.$obj.';'); //cast from string to array
        return $this->mongo->selectCollection($collection)->save($obj);
    }
}

/**
 * phpMoAdmin application control
 */
class moadminComponent {
    /**
     * $this->mongo is used to pass properties from component to view without relying on a controller to return them
     * @var array
     */
    public $mongo = array();

    /**
     * Model object
     * @var moadminModel
     */
    public static $model;

    /**
     * Routes requests and sets return data
     */
    public function __construct() {
        if (class_exists('mvc')) {
            mvc::$view = '#moadmin';
        }
        $this->mongo['dbs'] = self::$model->listDbs();
        if (isset($_GET['db'])) {
            if (strpos($_GET['db'], '.') !== false) {
                $_GET['db'] = $_GET['newdb'];
            }
            self::$model->setDb($_GET['db']);
        }

        $action = (isset($_GET['action']) ? $_GET['action'] : 'listCollections');
        if (isset($_POST['object'])) {
            $obj = self::$model->saveObject($_GET['collection'], $_POST['object']);
            $_GET['_id'] = $obj['_id'];
            $action = 'listRows';
        } else if ($action == 'createCollection') {
            self::$model->$action($_GET['collection']);
        }

        if (isset($_GET['sort']) && is_array($_GET['sort'])) {
            self::$model->sort = $_GET['sort'];
        }

        $this->mongo['listCollections'] = self::$model->listCollections();
        if ($action == 'editObject') {
            $this->mongo[$action] = (isset($_GET['_id']) ? self::$model->$action($_GET['collection'], $_GET['_id'], $_GET['idtype']) : '');
            return;
        } else if ($action == 'removeObject') {
            self::$model->$action($_GET['collection'], $_GET['_id'], $_GET['idtype']);
            $action = 'listRows';
        } else if ($action == 'ensureIndex') {
            foreach ($_GET['index'] as $key => $field) {
                $indexes[$field] = (isset($_GET['isdescending'][$key]) && $_GET['isdescending'][$key] ? -1 : 1);
            }
            self::$model->$action($_GET['collection'], $indexes, ($_GET['unique'] == 'Unique' ? array('unique' => true) : array()));
            $action = 'listCollections';
        } else if ($action == 'deleteIndex') {
            self::$model->$action($_GET['collection'], unserialize($_GET['index']));
            $action = 'listRows';
        } else if ($action == 'getStats') {
            $this->mongo[$action] = self::$model->$action();
            unset($this->mongo['listCollections']);
        } else if ($action == 'repairDb' || $action == 'getStats') {
            $this->mongo[$action] = self::$model->$action();
            $action = 'listCollections';
        } else if ($action == 'dropDb') {
            self::$model->$action();
            header('Location: ' . $_SERVER['SCRIPT_NAME']);
            return;
        }

        if (isset($_GET['collection']) && $action != 'listCollections' && method_exists(self::$model, $action)) {
            $this->mongo[$action] = self::$model->$action($_GET['collection']);
        }
        if ($action == 'listRows') {
            $this->mongo['listIndexes'] = self::$model->listIndexes($_GET['collection']);
        } else if ($action == 'dropCollection') {
            $this->mongo['listCollections'] = self::$model->listCollections();
        }
    }
}

/**
 * HTML helper tools
 */
class htmlHelper {
    /**
     * Internal storage of the link-prefix and hypertext protocol values
     * @var string
     */
    protected $_linkPrefix, $_protocol;

    /**
     * Internal list of included CSS & JS files used by $this->_tagBuilder() to assure that files are not included twice
     * @var array
     */
    protected $_includedFiles = array();

    /**
     * Flag array to avoid defining singleton JavaScript & CSS snippets more than once
     * @var array
     */
    protected $_jsSingleton = array(), $_cssSingleton = array();

    /**
     * Sets the protocol (http/https)
     */
    public function __construct() {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $this->_linkPrefix = 'http://' . $_SERVER['HTTP_HOST'];
            $this->_protocol = 'https://';
        } else {
            $this->_protocol = 'http://';
        }
    }

    /**
     * Creates simple HTML wrappers, accessed via $this->__call()
     *
     * JS and CSS files are never included more than once even if requested twice. If DEBUG mode is enabled than the
     * second request will be added to the debug log as a duplicate. The jsSingleton and cssSingleton methods operate
     * the same as the js & css methods except that they will silently skip duplicate requests instead of logging them.
     *
     * jsInlineSingleton and cssInlineSingleton makes sure a JavaScript or CSS snippet will only be output once, even
     * if echoed out multiple times and this method will attempt to place the JS code into the head section, if <head>
     * has already been echoed out then it will return the JS code inline the same as jsInline. Eg.:
     * $helloJs = "function helloWorld() {alert('Hello World');}";
     * echo $html->jsInlineSingleton($helloJs);
     *
     * Adding an optional extra argument to jsInlineSingleton/cssInlineSingleton will return the inline code bare (plus
     * a trailing linebreak) if it cannot place it into the head section, this is used for joint JS/CSS statements:
     * echo $html->jsInline($html->jsInlineSingleton($helloJs, true) . 'helloWorld();');
     *
     * @param string $tagType
     * @param array $args
     * @return string
     */
    protected function _tagBuilder($tagType, $args = array()) {
        $arg = current($args);
        if (empty($arg) || $arg === '') {
            $errorMsg = 'Missing argument for ' . __CLASS__ . '::' . $tagType . '()';
            trigger_error($errorMsg, E_USER_WARNING);
        }

        if (is_array($arg)) {
            foreach ($arg as $thisArg) {
                $return[] = $this->_tagBuilder($tagType, array($thisArg));
            }
            $return = implode(PHP_EOL, $return);
        } else {
            switch ($tagType) {
                case 'js':
                case 'jsSingleton':
                case 'css': //Optional extra argument to define CSS media type
                case 'cssSingleton':
                case 'jqueryTheme':
                    if ($tagType == 'jqueryTheme') {
                        $arg = 'http://ajax.googleapis.com/ajax/libs/jqueryui/1/themes/'
                             . str_replace(' ', '-', strtolower($arg)) . '/jquery-ui.css';
                        $tagType = 'css';
                    }
                    if (!isset($this->_includedFiles[$tagType][$arg])) {
                        if ($tagType == 'css' || $tagType == 'cssSingleton') {
                            $return = '<link rel="stylesheet" type="text/css" href="' . $arg . '"'
                                    . ' media="' . (isset($args[1]) ? $args[1] : 'all') . '" />';
                        } else {
                            $return = '<script type="text/javascript" src="' . $arg . '"></script>';
                        }
                        $this->_includedFiles[$tagType][$arg] = true;
                    } else {
                        $return = null;
                        if (DEBUG_MODE && ($tagType == 'js' || $tagType == 'css')) {
                            debug::log($arg . $tagType . ' file has already been included', 'warn');
                        }
                    }
                    break;
                case 'cssInline': //Optional extra argument to define CSS media type
                    $return = '<style type="text/css" media="' . (isset($args[1]) ? $args[1] : 'all') . '">'
                            . PHP_EOL . '/*<![CDATA[*/'
                            . PHP_EOL . '<!--'
                            . PHP_EOL . $arg
                            . PHP_EOL . '//-->'
                            . PHP_EOL . '/*]]>*/'
                            . PHP_EOL . '</style>';
                    break;
                case 'jsInline':
                    $return = '<script type="text/javascript">'
                            . PHP_EOL . '//<![CDATA['
                            . PHP_EOL . '<!--'
                            . PHP_EOL . $arg
                            . PHP_EOL . '//-->'
                            . PHP_EOL . '//]]>'
                            . PHP_EOL . '</script>';
                    break;
                case 'jsInlineSingleton': //Optional extra argument to supress adding of inline JS/CSS wrapper
                case 'cssInlineSingleton':
                    $tagTypeBase = substr($tagType, 0, -15);
                    $return = null;
                    $md5 = md5($arg);
                    if (!isset($this->{'_' . $tagTypeBase . 'Singleton'}[$md5])) {
                        $this->{'_' . $tagTypeBase . 'Singleton'}[$md5] = true;
                        if (!$this->_bodyOpen) {
                            $this->vorkHead[$tagTypeBase . 'Inline'][] = $arg;
                        } else {
                            $return = (!isset($args[1]) || !$args[1] ? $this->{$tagTypeBase . 'Inline'}($arg)
                                                                     : $arg . PHP_EOL);
                        }
                    }
                    break;
                case 'div':
                case 'li':
                case 'p':
                case 'h1':
                case 'h2':
                case 'h3':
                case 'h4':
                    $return = '<' . $tagType . '>' . $arg . '</' . $tagType . '>';
                    break;
                default:
                    $errorMsg = 'TagType ' . $tagType . ' not valid in ' . __CLASS__ . '::' . __METHOD__;
                    throw new Exception($errorMsg);
                    break;
            }
        }
        return $return;
    }

    /**
     * Creates virtual wrapper methods via $this->_tagBuilder() for the simple wrapper functions including:
     * $html->css, js, cssInline, jsInline, div, li, p and h1-h4
     *
     * @param string $method
     * @param array $arg
     * @return string
     */
    public function __call($method, $args) {
        $validTags = array('css', 'js', 'cssSingleton', 'jsSingleton', 'jqueryTheme',
                           'cssInline', 'jsInline', 'jsInlineSingleton', 'cssInlineSingleton',
                           'div', 'li', 'p', 'h1', 'h2', 'h3', 'h4');
        if (in_array($method, $validTags)) {
            return $this->_tagBuilder($method, $args);
        } else {
            $errorMsg = 'Call to undefined method ' . __CLASS__ . '::' . $method . '()';
            trigger_error($errorMsg, E_USER_ERROR);
        }
    }

    /**
     * Flag to make sure that header() can only be opened one-at-a-time and footer() can only be used after header()
     * @var boolean
     */
    private $_bodyOpen = false;

    /**
     * Sets the default doctype to XHTML 1.1
     * @var string
     */
    protected $_docType = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';

    /**
     * Allows modification of the docType
     *
     * Can either set to an actual doctype definition or to one of the presets (case-insensitive):
     * XHTML Mobile 1.2
     * XHTML Mobile 1.1
     * XHTML Mobile 1.0
     * Mobile 1.2 (alias for XHTML Mobile 1.2)
     * Mobile 1.1 (alias for XHTML Mobile 1.1)
     * Mobile 1.0 (alias for XHTML Mobile 1.0)
     * Mobile (alias for the most-strict Mobile DTD, currently 1.2)
     * XHTML 1.1 (this is the default DTD, there is no need to apply this method for an XHTML 1.1 doctype)
     * XHTML (Alias for XHTML 1.1)
     * XHTML 1.0 Strict
     * XHTML 1.0 Transitional
     * XHTML 1.0 Frameset
     * XHTML 1.0 (Alias for XHTML 1.0 Strict)
     * HTML 5
     * HTML 4.01
     * HTML (Alias for HTML 4.01)
     *
     * @param string $docType
     */
    public function setDocType($docType) {
        $docType = str_replace(' ', '', strtolower($docType));
        if ($docType == 'xhtml1.1' || $docType == 'xhtml') {
            return; //XHTML 1.1 is the default
        } else if ($docType == 'xhtml1.0') {
            $docType = 'strict';
        }
        $docType = str_replace(array('xhtml mobile', 'xhtml1.0'), array('mobile', ''), $docType);
        $docTypes = array(
            'mobile1.2'    => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" '
                            . '"http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">',
            'mobile1.1'    => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.1//EN '
                            . '"http://www.openmobilealliance.org/tech/DTD/xhtml-mobile11.dtd">',
            'mobile1.0'    => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" '
                            . '"http://www.wapforum.org/DTD/xhtml-mobile10.dtd">',
            'strict'       => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" '
                           .  '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
            'transitional' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" '
                           .  '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
            'frameset'     => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" '
                           .  '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
            'html4.01'     => '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" '
                           .  '"http://www.w3.org/TR/html4/strict.dtd">',
            'html5'        => '<!DOCTYPE html>'
        );
        $docTypes['mobile'] = $docTypes['mobile1.2'];
        $docTypes['html'] = $docTypes['html4.01'];
        $this->_docType = (isset($docTypes[$docType]) ? $docTypes[$docType] : $docType);
    }

    /**
     * Array used internally by Vork to cache JavaScript and CSS snippets and place them in the head section
     * Changing the contents of this property may cause Vork components to be rendered incorrectly.
     * @var array
     */
    public $vorkHead = array();

    /**
     * Returns an HTML header and opens the body container
     * This method will trigger an error if executed more than once without first calling
     * the footer() method on the prior usage
     * This is meant to be utilized within layouts, not views (but will work in either)
     *
     * @param array $args
     * @return string
     */
    public function header(array $args) {
        if (!$this->_bodyOpen) {
            $this->_bodyOpen = true;
            extract($args);
            $return = $this->_docType
                    . PHP_EOL . '<html xmlns="http://www.w3.org/1999/xhtml">'
                    . PHP_EOL . '<head>'
                    . PHP_EOL . '<title>' . $title . '</title>';

            if (!isset($metaheader['Content-Type'])) {
                $metaheader['Content-Type'] = 'text/html; charset=utf-8';
            }
            foreach ($metaheader as $name => $content) {
                $return .= PHP_EOL . '<meta http-equiv="' . $name . '" content="' . $content . '" />';
            }

            $meta['generator'] = 'Vork 2.00';
            foreach ($meta as $name => $content) {
                $return .= PHP_EOL . '<meta name="' . $name . '" content="' . $content . '" />';
            }

            if (isset($favicon)) {
                $return .= PHP_EOL . '<link rel="shortcut icon" href="' . $favicon . '" type="image/x-icon" />';
            }
            if (isset($animatedFavicon)) {
                $return .= PHP_EOL . '<link rel="icon" href="' . $animatedFavicon . '" type="image/gif" />';
            }

            $containers = array('css', 'cssInline', 'js', 'jsInline', 'jqueryTheme');
            foreach ($containers as $container) {
                if (isset($$container)) {
                    $return .= PHP_EOL . $this->$container($$container);
                }
            }

            if ($this->vorkHead) { //used internally by Vork tools
                foreach ($this->vorkHead as $container => $objArray) { //works only for inline code, not external files
                    $return .= PHP_EOL . $this->$container(implode(PHP_EOL, $objArray));
                }
            }

            if (isset($head)) {
                $return .= PHP_EOL . (is_array($head) ? implode(PHP_EOL, $head) : $head);
            }

            $return .= PHP_EOL . '</head>' . PHP_EOL . '<body>';
            return $return;
        } else {
            $errorMsg = 'Invalid usage of ' . __METHOD__ . '() - the header has already been returned';
            trigger_error($errorMsg, E_USER_NOTICE);
        }
    }

    /**
     * Returns an HTML footer and optional Google Analytics
     * This method will trigger an error if executed without first calling the header() method
     * This is meant to be utilized within layouts, not views (but will work in either)
     *
     * @param array $args
     * @return string
     */
    public function footer(array $args = array()) {
        if ($this->_bodyOpen) {
            $this->_bodyOpen = false;
            return '</body></html>';
        } else {
            $errorMsg = 'Invalid usage of ' . __METHOD__ . '() - header() has not been called';
            trigger_error($errorMsg, E_USER_NOTICE);
        }
    }

    /**
     * Establishes a basic set of JavaScript tools, just echo $html->jsTools() before any JavaScript code that
     * will use the tools.
     *
     * This method will only operate from the first occurrence in your code, subsequent calls will not output anything
     * but you should add it anyway as it will make sure that your code continues to work if you later remove a
     * previous call to jsTools.
     *
     * Tools provided:
     *
     * dom() method is a direct replacement for document.getElementById() that works in all JS-capable
     * browsers Y2k and newer.
     *
     * vork object - defines a global vork storage space; use by appending your own properties, eg.: vork.widgetCount
     *
     * @param Boolean $noJsWrapper set to True if calling from within a $html->jsInline() wrapper
     * @return string
     */
    public function jsTools($noJsWrapper = false) {
        return $this->jsInlineSingleton("var vork = function() {}
var dom = function(id) {
    if (typeof document.getElementById != 'undefined') {
        dom = function(id) {return document.getElementById(id);}
    } else if (typeof document.all != 'undefined') {
        dom = function(id) {return document.all[id];}
    } else {
        return false;
    }
    return dom(id);
}", $noJsWrapper);
    }

    /**
     * Load a JavaScript library via Google's AJAX API
     * http://code.google.com/apis/ajaxlibs/documentation/
     *
     * Version is optional and can be exact (1.8.2) or just version-major (1 or 1.8)
     *
     * Usage:
     * echo $html->jsLoad('jquery');
     * echo $html->jsLoad(array('yui', 'mootools'));
     * echo $html->jsLoad(array('yui' => 2.7, 'jquery', 'dojo' => '1.3.1', 'scriptaculous'));
     *
     * //You can also use the Google API format JSON-decoded in which case version is required & name must be lowercase
     * $jsLibs = array(array('name' => 'mootools', 'version' => 1.2, 'base_domain' => 'ditu.google.cn'), array(...));
     * echo $html->jsLoad($jsLibs);
     *
     * @param mixed $library Can be a string, array(str1, str2...) or , array(name1 => version1, name2 => version2...)
     *                       or JSON-decoded Google API syntax array(array('name' => 'yui', 'version' => 2), array(...))
     * @param mixed $version Optional, int or str, this is only used if $library is a string
     * @param array $options Optional, passed to Google "optionalSettings" argument, only used if $library == str
     * @return str
     */
    public function jsLoad($library, $version = null, array $options = array()) {
        $versionDefaults = array('swfobject' => 2, 'yui' => 2, 'ext-core' => 3, 'mootools' => 1.2);
        if (!is_array($library)) { //jsLoad('yui')
            $library = strtolower($library);
            if (!$version) {
                $version = (!isset($versionDefaults[$library]) ? 1 : $versionDefaults[$library]);
            }
            $library = array('name' => $library, 'version' => $version);
            $library = array(!$options ? $library : array_merge($library, $options));
        } else {
            foreach ($library as $key => $val) {
                if (!is_array($val)) {
                    if (is_int($key)) { //jsLoad(array('yui', 'prototype'))
                        $val = strtolower($val);
                        $version = (!isset($versionDefaults[$val]) ? 1 : $versionDefaults[$val]);
                        $library[$key] = array('name' => $val, 'version' => $version);
                    } else if (!is_array($val)) { // //jsLoad(array('yui' => '2.8.0r4', 'prototype' => 1.6))
                        $library[$key] = array('name' => strtolower($key), 'version' => $val);
                    }
                }
            }
        }
        $url = $this->_protocol . 'www.google.com/jsapi';
        if (!isset($this->_includedFiles['js'][$url])) { //autoload library
            $this->_includedFiles['js'][$url] = true;
            $url .= '?autoload=' . urlencode(json_encode(array('modules' => array_values($library))));
            $return = $this->js($url);
        } else { //load inline
            foreach ($library as $lib) {
                $js = 'google.load("' . $lib['name'] . '", "' . $lib['version'] . '"';
                if (count($lib) > 2) {
                    unset($lib['name'], $lib['version']);
                    $js .= ', ' . json_encode($lib);
                }
                $jsLoads[] = $js . ');';
            }
            $return = $this->jsInline(implode(PHP_EOL, $jsLoads));
        }
        return $return;
    }

    /**
     * Takes an array of key-value pairs and formats them in the syntax of HTML-container properties
     *
     * @param array $properties
     * @return string
     */
    public static function formatProperties(array $properties) {
        $return = array();
        foreach ($properties as $name => $value) {
            $return[] = $name . '="' . get::htmlentities($value) . '"';
        }
        return implode(' ', $return);
    }

    /**
     * Creates an anchor or link container
     *
     * @param array $args
     * @return string
     */
    public function anchor(array $args) {
        if (!isset($args['text']) && isset($args['href'])) {
            $args['text'] = $args['href'];
        }
        if (!isset($args['title']) && isset($args['text'])) {
            $args['title'] = str_replace(array("\n", "\r"), ' ', strip_tags($args['text']));
        }
        $return = '';
        if (isset($args['ajaxload'])) {
            $return = $this->jsSingleton('/js/ajax.js');
            $onclick = "return ajax.load('" . $args['ajaxload'] . "', this.href);";
            $args['onclick'] = (!isset($args['onclick']) ? $onclick : $args['onclick'] . '; ' . $onclick);
            unset($args['ajaxload']);
        }
        $text = (isset($args['text']) ? $args['text'] : null);
        unset($args['text']);
        return $return . '<a ' . self::formatProperties($args) . '>' . $text . '</a>';
    }

    /**
     * Shortcut to access the anchor method
     *
     * @param str $href
     * @param str $text
     * @param array $args
     * @return str
     */
    public function link($href, $text = null, array $args = array()) {
        if (strpos($href, 'http') !== 0) {
            $href = $this->_linkPrefix . $href;
        }
        $args['href'] = $href;
        if ($text !== null) {
            $args['text'] = $text;
        }
        return $this->anchor($args);
    }

    /**
     * Wrapper display computer-code samples
     *
     * @param str $str
     * @return str
     */
    public function code($str) {
        return '<code>' . str_replace('  ', '&nbsp;&nbsp;', nl2br(get::htmlentities($str))) . '</code>';
    }

    /**
     * Will return true if the number passed in is even, false if odd.
     *
     * @param int $number
     * @return boolean
     */
    public function isEven($number) {
        return (Boolean) ($number % 2 == 0);
    }

    /**
     * Internal incrementing integar for the alternator() method
     * @var int
     */
    private $alternator = 1;

    /**
     * Returns an alternating Boolean, useful to generate alternating background colors
     * Eg.:
     * $colors = array(true => 'gray', false => 'white');
     * echo '<div style="background: ' . $colors[$html->alternator()] . ';">...</div>'; //gray background
     * echo '<div style="background: ' . $colors[$html->alternator()] . ';">...</div>'; //white background
     * echo '<div style="background: ' . $colors[$html->alternator()] . ';">...</div>'; //gray background
     *
     * @return Boolean
     */
    public function alternator() {
        return $this->isEven(++$this->alternator);
    }

    /**
     * Returns a list of notifications if there are any - similar to the Flash feature of Ruby on Rails
     *
     * @param mixed $messages String or an array of strings
     * @param string $class
     * @return string Returns null if there are no notifications to return
     */
    public function getNotifications($messages, $class = 'errormessage') {
        if (isset($messages) && $messages) {
            return '<div class="' . $class . '">'
                 . (is_array($messages) ? implode('<br />', $messages) : $messages) . '</div>';
        }
    }
}

/**
 * Vork form-helper
 */
class formHelper {
    /**
     * Internal flag to keep track if a form tag has been opened and not yet closed
     * @var boolean
     */
    private $_formopen = false;

    /**
     * Internal form element counter
     * @var int
     */
    private $_inputCounter = array();

    /**
     * Converts dynamically-assigned array indecies to use an explicitely defined index
     *
     * @param string $name
     * @return string
     */
    protected function _indexDynamicArray($name) {
        $dynamicArrayStart = strpos($name, '[]');
        if ($dynamicArrayStart) {
            $prefix = substr($name, 0, $dynamicArrayStart);
            if (!isset($this->_inputCounter[$prefix])) {
                $this->_inputCounter[$prefix] = -1;
            }
            $name = $prefix . '[' . ++$this->_inputCounter[$prefix] . substr($name, ($dynamicArrayStart + 1));
        }
        return $name;
    }

    /**
     * Form types that do not change value with user input
     * @var array
     */
    protected $_staticTypes = array('hidden', 'submit', 'button', 'image');

    /**
     * Sets the standard properties available to all input elements in addition to user-defined properties
     * Standard properties are: name, value, class, style, id
     *
     * @param array $args
     * @param array $propertyNames Optional, an array of user-defined properties
     * @return array
     */
    protected function _getProperties(array $args, array $propertyNames = array()) {
        $method = (isset($this->_formopen['method']) && $this->_formopen['method'] == 'get' ? $_GET : $_POST);
        if (isset($args['name']) && (!isset($args['type']) || !in_array($args['type'], $this->_staticTypes))) {
            $arrayStart = strpos($args['name'], '[');
            if (!$arrayStart) {
                if (isset($method[$args['name']])) {
                    $args['value'] = $method[$args['name']];
                }
            } else {
                $name = $this->_indexDynamicArray($args['name']);
                if (preg_match_all('/\[(.*)\]/', $name, $arrayIndex)) {
                    array_shift($arrayIndex); //dump the 0 index element containing full match string
                }
                $name = substr($name, 0, $arrayStart);
                if (isset($method[$name])) {
                    $args['value'] = $method[$name];
                    if (!isset($args['type']) || $args['type'] != 'checkbox') {
                        foreach ($arrayIndex as $idx) {
                            if (isset($args['value'][current($idx)])) {
                                $args['value'] = $args['value'][current($idx)];
                            } else {
                                unset($args['value']);
                                break;
                            }
                        }
                    }
                }
            }
        }
        $return = array();
        $validProperties = array_merge($propertyNames, array('name', 'value', 'class', 'style', 'id'));
        foreach ($validProperties as $propertyName) {
            if (isset($args[$propertyName])) {
                $return[$propertyName] = $args[$propertyName];
            }
        }
        return $return;
    }

    /**
     * Begins a form
     * Includes a safety mechanism to prevent re-opening an already-open form
     *
     * @param array $args
     * @return string
     */
    public function getFormOpen(array $args = array()) {
        if (!$this->_formopen) {
            if(!isset($args['method'])) {
                $args['method'] = 'post';
            }

            $this->_formopen = array('id' => (isset($args['id']) ? $args['id'] : true),
                                     'method' => $args['method']);

            if(!isset($args['action'])) {
                $args['action'] = $_SERVER['REQUEST_URI'];
            }

            if (isset($args['legend'])) {
                $legend = $args['legend'];
                unset($args['legend']);
                if (!isset($args['title'])) {
                    $args['title'] = $legend;
                }
            } else if (isset($args['title'])) {
                $legend = $args['title'];
            }
            if (isset($args['alert'])) {
                if ($args['alert']) {
                    $alert = (is_array($args['alert']) ? implode('<br />', $args['alert']) : $args['alert']);
                }
                unset($args['alert']);
            }
            $return = '<form ' . htmlHelper::formatProperties($args) . '><fieldset>';
            if (isset($legend)) {
                $return .= '<legend>' . $legend . '</legend>';
            }
            if (isset($alert)) {
                $return .= $this->getErrorMessageContainer((isset($args['id']) ? $args['id'] : 'form'), $alert);
            }
            return $return;
        } else if (DEBUG_MODE) {
            $errorMsg = 'Invalid usage of ' . __METHOD__ . '() - a form is already open';
            trigger_error($errorMsg, E_USER_NOTICE);
        }
    }

    /**
     * Closes a form if one is open
     *
     * @return string
     */
    public function getFormClose() {
        if ($this->_formopen) {
            $this->_formopen = false;
            return '</fieldset></form>';
        } else if (DEBUG_MODE) {
            $errorMsg = 'Invalid usage of ' . __METHOD__ . '() - there is no open form to close';
            trigger_error($errorMsg, E_USER_NOTICE);
        }
    }

    /**
     * Adds label tags to a form element
     *
     * @param array $args
     * @param str $formElement
     * @return str
     */
    protected function _getLabel(array $args, $formElement) {
        if (!isset($args['label']) && isset($args['name'])
            && (!isset($args['type']) || !in_array($args['type'], $this->_staticTypes))) {
            $args['label'] = ucfirst($args['name']);
        }

        if (isset($args['label'])) {
            $label = get::xhtmlentities($args['label']);
            if (isset($_POST['errors']) && isset($args['name']) && isset($_POST['errors'][$args['name']])) {
                $label .= ' ' . $this->getErrorMessageContainer($args['name'], $_POST['errors'][$args['name']]);
            }
            $labelFirst = (!isset($args['labelFirst']) || $args['labelFirst']);
            if (isset($args['id'])) {
                $label = '<label for="' . $args['id'] . '" id="' . $args['id'] . 'label">'
                       . $label . '</label>';
            }
            if (isset($args['addBreak']) && $args['addBreak']) {
                $label = ($labelFirst ? $label . '<br />' : '<br />' . $label);
            }
            $formElement = ($labelFirst ? $label . $formElement : $formElement . $label);
            if (!isset($args['id'])) {
                $formElement = '<label>' . $formElement . '</label>';
            }
        }
        return $formElement;
    }

    /**
     * Returns a standardized container to wrap an error message
     *
     * @param string $id
     * @param string $errorMessage Optional, you may want to leave this blank and populate dynamically via JavaScript
     * @return string
     */
    public function getErrorMessageContainer($id, $errorMessage = '') {
        return '<span class="errormessage" id="' . $id . 'errorwrapper">'
             . get::htmlentities($errorMessage) . '</span>';
    }

    /**
     * Used for text, textarea, hidden, password, file, button, image and submit
     *
     * Valid args are any properties valid within an HTML input as well as label
     *
     * @param array $args
     * @return string
     */
    public function getInput(array $args) {
        $args['type'] = (isset($args['type']) ? $args['type'] : 'text');

        switch ($args['type']) {
            case 'select':
                return $this->getSelect($args);
                break;
            case 'checkbox':
                return $this->getCheckboxes($args);
                break;
            case 'radio':
                return $this->getRadios($args);
                break;
        }

        if ($args['type'] == 'textarea' && isset($args['maxlength'])) {
            if (!isset($args['id']) && isset($args['name'])) {
                $args['id'] = $args['name'];
            }
            if (isset($args['id'])) {
                $maxlength = $args['maxlength'];
            }
            unset($args['maxlength']);
        }

        if ($args['type'] == 'submit' && !isset($args['class'])) {
            $args['class'] = $args['type'];
        }

        $takeFocus = (isset($args['focus']) && $args['focus'] && $args['type'] != 'hidden');
        if ($takeFocus && !isset($args['id'])) {
            if (isset($args['name'])) {
                $args['id'] = $args['name'];
            } else {
                $takeFocus = false;
                if (DEBUG_MODE) {
                    $errorMsg = 'Either name or id is required to use the focus option on a form input';
                    trigger_error($errorMsg, E_USER_NOTICE);
                }
            }
        }

        $properties = $this->_getProperties($args, array('type', 'maxlength'));

        if ($args['type'] == 'image') {
            $properties['src'] = $args['src'];
            $properties['alt'] = (isset($args['alt']) ? $args['alt'] : '');
            $optionalProperties = array('height', 'width');
            foreach ($optionalProperties as $optionalProperty) {
                if (isset($args[$optionalProperty])) {
                    $properties[$optionalProperty] = $args[$optionalProperty];
                }
            }
        }

        if ($args['type'] != 'textarea') {
            $return[] = '<input ' . htmlHelper::formatProperties($properties) . ' />';
        } else {
            unset($properties['type']);
            if (isset($properties['value'])) {
                $value = $properties['value'];
                unset($properties['value']);
            }
            if (isset($args['preview']) && $args['preview'] && !isset($properties['id'])) {
                $properties['id'] = 'textarea_' . rand(100, 999);
            }
            $properties['rows'] = (isset($args['rows']) ? $args['rows'] : 13);
            $properties['cols'] = (isset($args['cols']) ? $args['cols'] : 55);
            $return[] = '<textarea ' . htmlHelper::formatProperties($properties);
            if (isset($maxlength)) {
                $return[] = ' onkeyup="document.getElementById(\''
                          . $properties['id'] . 'errorwrapper\').innerHTML = (this.value.length > '
                          . $maxlength . ' ? \'Form content exceeds maximum length of '
                          . $maxlength . ' characters\' : \'Length: \' + this.value.length + \' (maximum: '
                          . $maxlength . ' characters)\')"';
            }
            $return[] = '>';
            if (isset($value)) {
                $return[] = get::htmlentities($value, null, null, true); //double-encode allowed
            }
            $return[] = '</textarea>';
            if (isset($maxlength) && (!isset($args['validatedInput']) || !$args['validatedInput'])) {
                $return[] = $this->getErrorMessageContainer($properties['id']);
            }
        }
        if (!isset($args['addBreak'])) {
            $args['addBreak'] = true;
        }
        if ($takeFocus) {
            $html = get::helper('html');
            $return[] = $html->jsInline($html->jsTools(true) . 'dom("' . $args['id'] . '").focus();');
        }
        if (isset($args['preview']) && $args['preview']) {
            $js = 'document.writeln(\'<div class="htmlpreviewlabel">'
                . '<label for="livepreview_' . $properties['id'] . '">Preview:</label></div>'
                . '<div id="livepreview_' . $properties['id'] . '" class="htmlpreview"></div>\');' . PHP_EOL
                . 'if (dom("livepreview_' . $properties['id'] . '")) {' . PHP_EOL
                . '    var updateLivePreview_' . $properties['id'] . ' = '
                    . 'function() {dom("livepreview_' . $properties['id'] . '").innerHTML = '
                        . 'dom("' . $properties['id'] . '").value};' . PHP_EOL
                . '    dom("' . $properties['id'] . '").onkeyup = updateLivePreview_' . $properties['id'] . ';'
                . ' updateLivePreview_' . $properties['id'] . '();' . PHP_EOL
                . '}';
            if (!isset($html)) {
                $html = get::helper('html');
            }
            $return[] = $html->jsInline($html->jsTools(true) . $js);
        }
        return $this->_getLabel($args, implode($return));
    }

    /**
     * Returns a form select element
     *
     * $args = array(
     * 'name' => '',
     * 'multiple' => true,
     * 'leadingOptions' => array(),
     * 'optgroups' => array('group 1' => array('label' => 'g1o1', 'value' => 'grp 1 opt 1'),
     *                      'group 2' => array(),),
     * 'options' => array('value1' => 'text1', 'value2' => 'text2', 'value3' => 'text3'),
     * 'value' => array('value2', 'value3') //if (multiple==false) 'value' => (str) 'value3'
     * );
     *
     * @param array $args
     * @return str
     */
    public function getSelect(array $args) {
        if (!isset($args['id'])) {
            $args['id'] = $args['name'];
        }
        if (isset($args['multiple']) && $args['multiple']) {
            $args['multiple'] = 'multiple';
            if (substr($args['name'], -2) != '[]') {
                $args['name'] .= '[]';
            }
        }
        $properties = $this->_getProperties($args, array('multiple'));
        $values = (isset($properties['value']) ? $properties['value'] : null);
        unset($properties['value']);
        if (!is_array($values)) {
            $values = ($values != '' ? array($values) : array());
        }
        $return = '<select ' . htmlHelper::formatProperties($properties) . '>';
        if (isset($args['prependBlank']) && $args['prependBlank']) {
            $return .= '<option value=""></option>';
        }

        if (isset($args['leadingOptions'])) {
            $useValues = (key($args['leadingOptions']) !== 0
                          || (isset($args['useValue']) && $args['useValue']));
            foreach ($args['leadingOptions'] as $value => $text) {
                if (!$useValues) {
                    $value = $text;
                }
                $return .= '<option value="' . get::htmlentities($value) . '">'
                         . get::htmlentities($text) . '</option>';
            }
        }

        if (isset($args['optgroups'])) {
            foreach ($args['optgroups'] as $groupLabel => $optgroup) {
                $return .= '<optgroup label="' . get::htmlentities($groupLabel) . '">';
                foreach ($optgroup as $value => $label) {
                    $return .= '<option value="' . get::htmlentities($value) . '"';
                    if (isset($label)) {
                        $return .= ' label="' . get::htmlentities($label) . '"';
                    }
                    if(in_array($value, $values)) {
                        $return .= ' selected="selected"';
                    }
                    $return .= '>' . get::htmlentities($label) . '</option>';
                }
                $return .= '</optgroup>';
            }
        }

        if (isset($args['options'])) {
            $useValues = (key($args['options']) !== 0 || (isset($args['useValue']) && $args['useValue']));
            foreach ($args['options'] as $value => $text) {
                if (!$useValues) {
                    $value = $text;
                }
                $return .= '<option value="' . get::htmlentities($value) . '"';
                if(in_array($value, $values)) {
                    $return .= ' selected="selected"';
                }
                $return .= '>' . get::htmlentities($text) . '</option>';
            }
        }
        $return .= '</select>';
        if (!isset($args['addBreak'])) {
            $args['addBreak'] = true;
        }
        $return = $this->_getLabel($args, $return);
        if (isset($args['error'])) {
             $return .= $this->getErrorMessageContainer($args['id'], '<br />' . $args['error']);
        }
        return $return;
    }

    /**
     * Cache containing individual radio or checkbox elements in an array
     * @var array
     */
    public $radios = array(), $checkboxes = array();

    /**
     * Returns a set of radio form elements
     *
     * array(
     * 'name' => '',
     * 'value' => '',
     * 'id' => '',
     * 'legend' => '',
     * 'options' => array('value1' => 'text1', 'value2' => 'text2', 'value3' => 'text3'),
     * 'options' => array('text1', 'text2', 'text3'), //also acceptable (cannot do half this, half above syntax)
     * )
     *
     * @param array $args
     * @return str
     */
    public function getRadios(array $args) {
        $id = (isset($args['id']) ? $args['id'] : $args['name']);
        $properties = $this->_getProperties($args);
        if (isset($properties['value'])) {
            $checked = $properties['value'];
            unset($properties['value']);
        }
        $properties['type'] = (isset($args['type']) ? $args['type'] : 'radio');
        $useValues = (key($args['options']) !== 0 || (isset($args['useValue']) && $args['useValue']));
        foreach ($args['options'] as $value => $text) {
            if (!$useValues) {
                $value = $text;
            }
            $properties['id'] = $id . '_' . preg_replace('/\W/', '', $value);
            $properties['value'] = $value;
            if (isset($checked) &&
                ((($properties['type'] == 'radio' || !is_array($checked)) && $value == $checked)
                  || ($properties['type'] == 'checkbox' && is_array($checked) && in_array($value, $checked)))) {
                $properties['checked'] = 'checked';
                $rowClass = (!isset($properties['class']) ? 'checked' : $properties['class'] . ' checked');
            }
            $labelFirst = (isset($args['labelFirst']) ? $args['labelFirst'] : false);
            $labelArgs = array('label' => $text, 'id' => $properties['id'], 'labelFirst' => $labelFirst);
            $input = '<input ' . htmlHelper::formatProperties($properties) . ' />';
            $row = $this->_getLabel($labelArgs, $input);
            if (isset($rowClass)) {
                $row = '<span class="' . $rowClass . '">' . $row . '</span>';
            }
            $radios[] = $row;
            unset($properties['checked'], $rowClass);
        }
        $this->{$properties['type'] == 'radio' ? 'radios' : 'checkboxes'} = $radios;
        $break = (!isset($args['optionBreak']) ? '<br />' : $args['optionBreak']);
        $addFieldset = (isset($args['addFieldset']) ? $args['addFieldset'] : ((isset($args['label']) && $args['label']) || count($args['options']) > 1));
        if ($addFieldset) {
            $return = '<fieldset id="' . $id . '">';
            if (isset($args['label'])) {
                $return .= '<legend>' . get::htmlentities($args['label']) . '</legend>';
            }
            $return .= implode($break, $radios) . '</fieldset>';
        } else {
            $return = implode($break, $radios);
        }
        if (isset($_POST['errors']) && isset($_POST['errors'][$id])) {
            $return = $this->getErrorMessageContainer($id, $_POST['errors'][$id]) . $return;
        }
        return $return;
    }

    /**
     * Returns a set of checkbox form elements
     *
     * This method essentially extends the getRadios method and uses an identical signature except
     * that $args['value'] can also accept an array of values to be checked.
     *
     * @param array $args
     * @return str
     */
    public function getCheckboxes(array $args) {
        $args['type'] = 'checkbox';
        if (isset($args['value']) && !is_array($args['value'])) {
            $args['value'] = array($args['value']);
        }
        $nameParts = explode('[', $args['name']);
        if (!isset($args['id'])) {
            $args['id'] = $nameParts[0];
        }
        if (!isset($nameParts[1]) && count($args['options']) > 1) {
            $args['name'] .= '[]';
        }
        return $this->getRadios($args);
    }
}

/**
 * phpMoAdmin bootstrap
 */
if (!isset($_GET['db'])) {
    $_GET['db'] = 'admin';
} else if (strpos($_GET['db'], '.') !== false) {
    $_GET['db'] = $_GET['newdb'];
}
try {
    moadminComponent::$model = new moadminModel($_GET['db']);
} catch(Exception $e) {
    echo $e;
    exit(0);
}
$html = get::helper('html');
$form = new formHelper;
$mo = new moadminComponent;

class phpMoAdmin {
    const DRILL_DOWN_DEPTH_LIMIT = 8;

    public static function getArrayKeys(array $array, $path = '', $drillDownDepthCount = 0) {
        $return = array();
        if ($drillDownDepthCount) {
            $path .= '.';
        }
        if (++$drillDownDepthCount < self::DRILL_DOWN_DEPTH_LIMIT) {
            foreach ($array as $key => $val) {
                $return[$id] = $id = $path . $key;
                if (is_array($val)) {
                    $return = array_merge($return, self::getArrayKeys($val, $id, $drillDownDepthCount));
                }
            }
        }
        return $return;
    }
}

/**
 * phpMoAdmin front-end view-element
 */
$headerArgs['title'] = (isset($_GET['action']) ? 'phpMoAdmin - ' . get::htmlentities($_GET['action']) : 'phpMoAdmin');
if (THEME != 'classic') {
    $headerArgs['jqueryTheme'] = (in_array(THEME, array('swanky-purse', 'trontastic')) ? THEME : 'trontastic');
}
$headerArgs['cssInline'] = '
/* reset */
html, body, div, span, applet, object, iframe, h1, h2, h3, h4, h5, h6, p, blockquote, pre, a, abbr, acronym, address,
big, cite, code, del, dfn, em, font, img, ins, kbd, q, s, samp, small, strike, strong, sub, sup, tt, var, b, u, i,
center, dl, dt, dd, ol, ul, li, fieldset, form, label, legend, table, caption, tbody, tfoot, thead, tr, th, td {
margin: 0; padding: 0; border: 0; outline: 0; font-size: 100%; vertical-align: baseline; background: transparent;}
input, textarea {margin: 0; padding: 0;}
body {line-height: 1;}
blockquote, q {quotes: none;}
blockquote:before, blockquote:after, q:before, q:after {content: ""; content: none;}
:focus {outline: 0;}
ins {text-decoration: none;}
del {text-decoration: line-through;}
table {border-collapse: collapse; border-spacing: 0;}

html{color:#000;}
caption,th{text-align:left;}
h1,h2,h3,h4,h5,h6{font-size:100%;font-weight:normal;}
abbr,acronym{font-variant:normal;}
sup{vertical-align:text-top;}
sub{vertical-align:text-bottom;}
input,textarea,select{font-family:inherit;font-size:inherit;font-weight:inherit;}
input,textarea,select{*font-size:100%;}
legend{color:#000;}

/* \*/ html, body{height:100%;} /* */

/* initialize */
html {background: #74736d;}
body {margin: auto; width: 990px; font-family: "Arial"; font-size: small; background: #000000; color: #ffffff;}
#bodycontent {padding: 10px; border: 0px solid;}
textarea {width: 640px; height: 70px;}
a, .textLink {text-decoration: none; color: #96f226; font-weight: bold;}
a:hover, .textLink:hover {text-decoration: underline; color: #9fda58;}
a:hover pre, h1 a:hover {text-decoration: none;}
h1, h2, h3, h4 {margin-bottom: 3px;}
h1, h2 {margin-left: -1px;}
h1 {font-family: "Arial Black"; font-size: 27px; color: #b8ec79;}
h1.midpageh1 {margin-top: 10px;}
h2 {font-size: large; font-weight: bold; margin-top: 10px; color: #660000;}
h3 {font-weight: bold; color: #687d1c;}
h4 {font-weight: bold; color: #10478b;}
p {margin-bottom: 10px; line-height: 1.75;}
li {line-height: 1.5; margin-left: 15px;}
.errormessage {color: #990000; font-weight: bold; background: #ffffff; border: 1px solid #ff0000; padding: 2px;}
.rownumber {float: right; padding: 0px 5px 0px 5px; border-left: 1px dotted; border-bottom: 1px dotted; color: #ffffff; margin-top: 4px; margin-right: -1px;}
.ui-widget-header .rownumber {margin-top: 2px; margin-right: 0px;}
pre {border: 1px solid; margin: 1px; padding-left: 5px;}
li .ui-widget-content {margin: 1px 1px 3px 1px;}
#moadminlogo {color: #96f226; border: 0px solid; padding-left: 10px; font-size: 4px!important; width: 265px; height: 65px; overflow: hidden;}';

switch (THEME) {
    case 'swanky-purse':
    $headerArgs['cssInline'] .= '
html {background: #261803;}
h1, .rownumber {color: #baaa5a;}
body {background: #4c3a1d url(http://jquery-ui.googlecode.com/svn/tags/1.7.2/themes/swanky-purse/images/ui-bg_diamond_25_675423_10x8.png) 50% 50% repeat;}
#moadminlogo {color: #baaa5a;}
li .ui-widget-header {margin: 0px 1px 0px 1px;}
.ui-widget-header .rownumber {margin-top: 2px; margin-right: -1px;}';
    break;
    case 'classic':
        $headerArgs['cssInline'] .= '
html, .ui-widget-header, button {background: #ccc78c;}
.ui-widget-content, input.ui-state-hover {background: #edf2ed;}
h1, .rownumber {color: #796f54;}
body {background: #ffffcc; color: #000000;}
#bodycontent {background: #ffffcc;}
#moadminlogo, button {color: #bb0022;}
a, .textLink {color: #990000;}
a:hover, .textLink:hover {color: #7987ae;}
li .ui-widget-header {margin: 0px 1px 0px 1px;}
.rownumber {margin-top: 2px; margin-right: 0px;}
.ui-dialog {border: 3px outset;}
.ui-dialog .ui-dialog-titlebar {padding: 3px; margin: 1px; border: 3px ridge;}
.ui-dialog #confirm {padding: 10px;}
.ui-dialog .ui-icon-closethick, .ui-dialog button {float: right; margin: 4px;}
.ui-dialog .ui-icon-closethick {margin-top: -13px;}
body:first-of-type .ui-dialog .ui-icon-closethick {margin-top: -2px;} /*Chrome/Safari*/
.ui-resizable { position: relative;}
.ui-resizable-handle { position: absolute;font-size: 0.1px;z-index: 99999; display: block;}
.ui-resizable-disabled .ui-resizable-handle, .ui-resizable-autohide .ui-resizable-handle { display: none; }
.ui-resizable-n { cursor: n-resize; height: 7px; width: 100%; top: -5px; left: 0px; }
.ui-resizable-s { cursor: s-resize; height: 7px; width: 100%; bottom: -5px; left: 0px; }
.ui-resizable-e { cursor: e-resize; width: 7px; right: -5px; top: 0px; height: 100%; }
.ui-resizable-w { cursor: w-resize; width: 7px; left: -5px; top: 0px; height: 100%; }
.ui-resizable-se { cursor: se-resize; width: 12px; height: 12px; right: 1px; bottom: 1px; }
.ui-resizable-sw { cursor: sw-resize; width: 9px; height: 9px; left: -5px; bottom: -5px; }
.ui-resizable-nw { cursor: nw-resize; width: 9px; height: 9px; left: -5px; top: -5px; }
.ui-resizable-ne { cursor: ne-resize; width: 9px; height: 9px; right: -5px; top: -5px;}';
        break;
}

if (isset($accessControl) && !isset($_SESSION)) {
    session_start();
}
echo $html->header($headerArgs);

echo $html->jsLoad(array('jquery', 'jqueryui'));
$baseUrl = $_SERVER['SCRIPT_NAME'];

$db = (isset($_GET['db']) ? $_GET['db'] : (isset($_POST['db']) ? $_POST['db'] : get::$config->DB_NAME));
$dbUrl = urlencode($db);

$phpmoadmin = '<pre id="moadminlogo">
                                ,ggg, ,ggg,_,ggg,                        ,ggg,
             ,dPYb,            dP""Y8dP""Y88P""Y8b                      dP""8I           8I
             IP\'`Yb            Yb, `88\'  `88\'  `88                     dP   88           8I
             I8  8I             `"  88    88    88                    dP    88           8I                      gg
             I8  8\'                 88    88    88                   ,8\'    88           8I                      ""
 gg,gggg,    I8 dPgg,   gg,gggg,    88    88    88    ,ggggg,        d88888888     ,gggg,8I   ,ggg,,ggg,,ggg,    gg    ,ggg,,ggg,
 I8P"  "Yb   I8dP" "8I  I8P"  "Yb   88    88    88   dP"  "Y8       ,8"     88    dP"  "Y8I  ,8" "8P" "8P" "8,   88   ,8" "8P" "8,
 I8\'    ,8i  I8P    I8  I8\'    ,8i  88    88    88  i8\'    ,8 dP   ,8P      Y8   i8\'    ,8I  I8   8I   8I   8I   88   I8   8I   8I
,I8 _  ,d8\' ,d8     I8,,I8 _  ,d8\'  88    88    Y8,,d8,   ,d8 Yb,_,dP       `8b,,d8,   ,d8b,,dP   8I   8I   Yb,_,88,_,dP   8I   Yb,
PI8 YY88888P88P     `Y8PI8 YY88     88    88    `Y8P"Y8888P"   "Y8P"         `Y8P"Y8888P"`Y88P\'   8I   8I   `Y88P""Y88P\'   8I   `Y8
 I8                     I8
 I8                     I8
 I8                     I8
 I8                     I8
 I8                     I8
 I8                     I8
</pre>';
echo '<div id="bodycontent" class="ui-widget-content"><h1 style="float: right;">'
    . $html->link('http://www.phpmoadmin.com', $phpmoadmin, array('title' => 'phpMoAdmin')) . '</h1>';

if (isset($accessControl) && !isset($_SESSION['user'])) {
    if (isset($_POST['username'])) {
        $_POST = array_map('trim', $_POST);
        if (isset($accessControl[$_POST['username']]) && $accessControl[$_POST['username']] == $_POST['password']) {
            $_SESSION['user'] = $_POST['username'];
        } else {
            $_POST['errors']['username'] = 'Incorrect username or password';
        }
    }
    if (!isset($_SESSION['user'])) {
        echo $form->getFormOpen();
        echo $html->div($form->getInput(array('name' => 'username', 'focus' => true)));
        echo $html->div($form->getInput(array('type' => 'password', 'name' => 'password')));
        echo $html->div($form->getInput(array('type' => 'submit', 'value' => 'Login', 'class' => 'ui-state-hover')));
        echo $form->getFormClose();
        exit(0);
    }
}

echo '<div id="dbcollnav">';
$formArgs = array('method' => 'get');
if (isset($mo->mongo['repairDb'])) {
    $formArgs['alert'] = (isset($mo->mongo['repairDb']['ok']) && $mo->mongo['repairDb']['ok']
                          ? 'Database has been repaired and compacted' : 'Database could not be repaired');
}
echo $form->getFormOpen($formArgs);
echo $html->div($form->getSelect(array('name' => 'db', 'options' => $mo->mongo['dbs'], 'label' => '', 'value' => $db,
                                       'addBreak' => false))
              . $form->getInput(array('type' => 'submit', 'value' => 'Change database', 'class' => 'ui-state-hover'))
              . ' <span style="font-size: xx-large;">' . get::htmlentities($db)
              . '</span> [' . $html->link("javascript: mo.repairDatabase('" . get::htmlentities($db)
              . "'); void(0);", 'repair database') . '] [' . $html->link("javascript: mo.dropDatabase('"
              . get::htmlentities($db) . "'); void(0);", 'drop database') . ']');
echo $form->getFormClose() . $html->jsInline('var mo = {}
mo.urlEncode = function(str) {
    return escape(str)'
        . '.replace(/\+/g, "%2B").replace(/%20/g, "+").replace(/\*/g, "%2A").replace(/\//g, "%2F").replace(/@/g, "%40");
}
mo.repairDatabase = function(db) {
    mo.confirm("Are you sure that you want to repair and compact the " + db + " database?", function() {
        window.location.replace("' . $baseUrl . '?db=' . $dbUrl . '&action=repairDb");
    });
}
mo.dropDatabase = function(db) {
    mo.confirm("Are you sure that you want to drop the " + db + " database?", function() {
        mo.confirm("All the collections in the " + db + " database will be lost along with all the data within them!'
                . '\n\nAre you 100% sure that you want to drop this database?'
                . '\n\nLast chance to cancel!", function() {
            window.location.replace("' . $baseUrl . '?db=' . $dbUrl . '&action=dropDb");
        });
    });
}
$("select[name=db]").prepend(\'<option value="new.database">Use new ==&gt;</option>\')'
    . '.after(\'<input type="text" name="newdb" name style="display: none;" />\').change(function() {
    ($(this).val() == "new.database" ? $("input[name=newdb]").show() : $("input[name=newdb]").hide());
});
mo.confirm = function(dialog, func, title) {
    if (typeof title == "undefined") {
        title = "Please confirm:";
    }
    if (!$("#confirm").length) {
        $("#dbcollnav").append(\'<div id="confirm" style="display: none;"></div>\');
    }
    mo.userFunc = func; //overcomes JS scope issues
    $("#confirm").html(dialog).attr("title", title).dialog({modal: true, buttons: {
		"Yes": function() {$(this).dialog("close"); mo.userFunc();},
		Cancel: function() {$(this).dialog("close");}
	}}).dialog("open");
}
');

if (isset($_GET['collection'])) {
    $collection = get::htmlentities($_GET['collection']);
    unset($_GET['collection']);
}
if (isset($mo->mongo['listCollections'])) {
    echo '<div id="mongo_collections">';

    echo $form->getFormOpen(array('method' => 'get'));
    echo $html->div($form->getInput(array('name' => 'collection', 'label' => '', 'addBreak' => false))
       . $form->getInput(array('name' => 'action', 'type' => 'hidden', 'value' => 'createCollection'))
       . $form->getInput(array('type' => 'submit', 'value' => 'Add new collection', 'class' => 'ui-state-hover'))
       . $form->getInput(array('name' => 'db', 'value' => get::htmlentities($db), 'type' => 'hidden'))
       . ' &nbsp; &nbsp; &nbsp; [' . $html->link($baseUrl . '?action=getStats', 'stats') . ']');
    echo $form->getFormClose();

    if (!$mo->mongo['listCollections']) {
        echo $html->div('No collections exist');
    } else {
        echo '<ol>';
        foreach ($mo->mongo['listCollections'] as $coll) {
            $col = substr(strstr($coll, '.'), 1);
            echo $html->li($html->link($baseUrl . '?db='
                                     . $dbUrl . '&action=listRows&collection=' . urlencode($col), $col));
        }
        echo '</ol>';
        echo $html->jsInline('mo.collectionDrop = function(collection) {
    mo.confirm.collection = collection;
    mo.confirm("Are you sure that you want to drop " + collection + "?",
        function() {
            mo.confirm("All the data in the " + mo.confirm.collection + " collection will be lost;'
                    . ' are you 100% sure that you want to drop it?\n\nLast chance to cancel!",
                function() {
                    window.location.replace("' . $baseUrl . '?db=' . $dbUrl
                                          . '&action=dropCollection&collection=" + mo.urlEncode(mo.confirm.collection));
                }
            );
        }
    );
}
$(document).ready(function() {
    $("#mongo_collections li").each(function() {
        $(this).prepend("[<a href=\"javascript: mo.collectionDrop(\'" + $(this).find("a").html() + "\'); void(0);\"'
        .' title=\"drop this collection\">X</a>] ");
    });
});
');
    }
    echo '</div>';
}
echo '</div>'; //end of dbcollnav
$dbcollnavJs = '$("#dbcollnav").after(\'<a id="dbcollnavlink" href="javascript: $(\\\'#dbcollnav\\\').show();'
             . ' $(\\\'#dbcollnavlink\\\').hide(); void(0);">[Show Database &amp; Collection selection]</a>\').hide();';
if (isset($mo->mongo['listRows'])) {
    echo $html->h1($collection);
    if (isset($mo->mongo['listIndexes'])) {
        echo '<ol id="indexes" style="display: none; margin-bottom: 10px;">';
        echo $form->getFormOpen(array('method' => 'get'));
        echo '<div id="indexInput">'
           . $form->getInput(array('name' => 'index[]', 'label' => '', 'addBreak' => false))
           . $form->getCheckboxes(array('name' => 'isdescending[]', 'options' => array('Descending')))
           . '</div>'
           . '<a id="addindexcolumn" style="margin-left: 160px;" href="javascript: '
           . "$('#addindexcolumn').before('<div>' + $('#indexInput').html().replace(/isdescending_Descending/g, "
           . "'isdescending_Descending' + mo.indexCount++) + '</div>'); void(0);"
           . '">[Add another index field]</a>'
           . $form->getRadios(array('name' => 'unique', 'options' => array('Index', 'Unique'), 'value' => 'Index'))
           . $form->getInput(array('type' => 'submit', 'value' => 'Add new index', 'class' => 'ui-state-hover'))
           . $form->getInput(array('name' => 'action', 'type' => 'hidden', 'value' => 'ensureIndex'))
           . $form->getInput(array('name' => 'db', 'value' => get::htmlentities($db), 'type' => 'hidden'))
           . $form->getInput(array('name' => 'collection', 'value' => $collection, 'type' => 'hidden'))
           . $form->getFormClose();
        foreach ($mo->mongo['listIndexes'] as $indexArray) {
            $index = '';
            foreach ($indexArray['key'] as $key => $direction) {
                $index .= (!$index ? $key : ', ' . $key);
                if (!is_object($direction)) {
                    $index .= ' [' . ($direction == -1 ? 'desc' : 'asc') . ']';
                }
            }
            if (isset($indexArray['unique']) && $indexArray['unique']) {
                $index .= ' [unique]';
            }
            if (key($indexArray['key']) != '_id' || count($indexArray['key']) !== 1) {
                $index = '[' . $html->link($baseUrl . '?db=' . $dbUrl . '&collection=' . urlencode($collection)
                       . '&action=deleteIndex&index='
                       . serialize($indexArray['key']), 'X', array('title' => 'Drop Index',
                             'onclick' => "mo.confirm.href=this.href; "
                                        . "mo.confirm('Are you sure that you want to drop this index?', "
                                        . "function() {window.location.replace(mo.confirm.href);}); return false;")
                         ) . '] '
                       . $index;
            }
            echo '<li>' . $index . '</li>';
        }
        echo '</ol>';
    }
    $objCount = $mo->mongo['listRows']->count();
    echo $html->jsInline('mo.indexCount = 1;
$(document).ready(function() {
    $("#mongo_rows").prepend("<div style=\"float: right; line-height: 1.5; margin-top: -45px\">'
    . '[<a href=\"javascript: $(\'#mongo_rows\').find(\'pre\').height(\'100px\').css(\'overflow\', \'auto\');'
    . ' void(0);\" title=\"display compact view of row content\">Compact</a>] '
    . '[<a href=\"javascript: $(\'#mongo_rows\').find(\'pre\').height(\'300px\').css(\'overflow\', \'auto\');'
    . ' void(0);\" title=\"display uniform-view row content\">Uniform</a>] '
    . '[<a href=\"javascript: $(\'#mongo_rows\').find(\'pre\').height(\'auto\').css(\'overflow\', \'hidden\');'
    . ' void(0);\" title=\"display full row content\">Full</a>]'
    . '<div class=\"ui-widget-header\" style=\"padding-left: 5px;\">' . $objCount . ' objects</div></div>");
});
mo.removeObject = function(_id, idType) {
    mo.confirm("Are you sure that you want to delete this " + _id + " object?", function() {
        window.location.replace("' . $baseUrl . '?db=' . $dbUrl . '&collection=' . urlencode($collection)
                              . '&action=removeObject&_id=" + mo.urlEncode(_id) + "&idtype=" + idType);
    });
}
' . $dbcollnavJs);

    echo '<div id="mongo_rows">';

    if ($objCount > 1) {
        $sampleObject = phpMoAdmin::getArrayKeys($mo->mongo['listRows']->getNext());
        if ($objCount > 1) {
            if ($objCount > 2) {
                for ($x = 2; $x < $objCount; $x++) {
                    $mo->mongo['listRows']->next(); //move cursor to second-to-last position
                }
            }
            $sampleObject = array_merge($sampleObject, phpMoAdmin::getArrayKeys($mo->mongo['listRows']->getNext()));
        }
        if ($sampleObject) {
            echo $form->getFormOpen();
        }
    }

    echo '[' . $html->link($baseUrl . '?db=' . $dbUrl . '&collection=' . urlencode($collection) . '&action=editObject',
                          'Insert New Object') . '] ';
    if (isset($index)) {
        echo '[<a id="indexeslink" href="javascript: $(\'#indexes\').show(); void(0);">Show Indexes</a>] ';
    }
    if (isset($sampleObject) && $sampleObject) {
        $sort = array('name' => 'sort', 'id' => 'sort', 'options' => $sampleObject, 'label' => '', 'addBreak' => false);
        $sortdir = array('name' => 'sortdir', 'id' => 'sortdir', 'options' => array(1 => 'asc', -1 => 'desc'),
                         'label' => '', 'addBreak' => false);
        if (isset($_GET['sort'])) {
            $sort['value'] = key($_GET['sort']);
            $sortdir['value'] = current($_GET['sort']);
        }
        echo $form->getSelect($sort) . $form->getSelect($sortdir) . ' '
           . $html->link("javascript: document.location='" . $baseUrl . '?db=' . $dbUrl . '&collection='
           . urlencode($collection) . "&action=listRows&sort[' + document.getElementById('sort').value + ']='"
                       . " + document.getElementById('sortdir').value; void(0);", 'Sort', array('class' => 'ui-state-hover', 'style' => 'padding: 3px 8px 3px 8px;'));
        echo $form->getFormClose();
    }
    echo '<ol style="list-style: none; margin-left: -15px;">';
    $rowCount = 0;
    $isChunksTable = (substr($collection, -7) == '.chunks');
    if ($isChunksTable) {
        $chunkUrl = $baseUrl . '?db=' . $dbUrl . '&action=listRows&collection=' . urlencode(substr($collection, 0, -7))
                  . '.files#';
    }
    foreach ($mo->mongo['listRows'] as $row) {
        $showEdit = true;
        $id = $idString = $row['_id'];
        if (is_object($idString)) {
            $idString = '(' . get_class($idString) . ') ' . $idString;
            $idForUrl = serialize($id);
        } else {
            $idForUrl = urlencode($id);
        }
        $idType = gettype($row['_id']);
        $idDepth = 0; //protection against endless looping
        while (is_array($id) && ++$idDepth < 10) {
            $id = current($id);
            $idString .= '->' . $id;
        }
        if ($isChunksTable && isset($row['data']) && is_object($row['data'])
            && get_class($row['data']) == 'MongoBinData') {
            $showEdit = false;
            $row['data'] = $html->link($chunkUrl . $row['files_id'], 'MongoBinData Object',
                                       array('class' => 'MoAdmin_Reference'));
        }
        $data = explode("\n", substr(print_r($row, true), 8, -2));
        $binData = 0;
        foreach ($data as $id => $rowData) {
            $raw = trim($rowData);
            if ($binData) {
                if (strpos($rowData, '] => ') !== false) {
                    ++$binData;
                }
                unset($data[$id]);
                continue;
            }

            if ($raw === '') {
                unset($data[$id]);
            } else if ($raw === '(') { //one-true-brace
                $data[($id - 1)] .= ' (';
                unset($data[$id]);
            } else {
                if (strpos($data[$id], 'MongoBinData Object') !== false) {
                    $showEdit = false;
                    $binData = -2;
                }
                $data[$id] = str_replace('        ', '    ', (substr($rowData, 0, 4) === '    ' ? substr($rowData, 4)
                                                                                                : $rowData));
                if ($raw === ')') {
                    $data[$id] = substr($data[$id], 4);
                }
                if (strpos($data[$id], 'MoAdmin_Reference') === false) {
                    $data[$id] = get::htmlentities($data[$id]);
                }
            }
        }
        echo  $html->li('<div style="margin-top: 5px; padding-left: 5px;" class="'
           . ($html->alternator() ? 'ui-widget-header' : 'ui-widget-content') . '" id="' . $row['_id'] . '">'
           . '[' . $html->link("javascript: mo.removeObject('" . $idForUrl . "', '" . $idType
           . "'); void(0);", 'X', array('title' => 'Delete')) . '] '
           . ($showEdit ? '[' . $html->link($baseUrl . '?db=' . $dbUrl . '&collection=' . urlencode($collection)
                . '&action=editObject&_id=' . $idForUrl . '&idtype=' . $idType, 'E', array('title' => 'Edit')) . '] '
                : ' [<span title="Cannot edit objects containing MongoBinData">N/A</span>] ')
           . $idString . '<div class="rownumber">' . ++$rowCount . '</div></div><pre>'
           . wordwrap(implode("\n", $data), 136, "\n", true) . '</pre>');
    }
    echo '</ol>';
    if (!isset($idString)) {
        echo '<div class="errormessage">No records in this collection</div>';
    }
    echo '</div>';
} else if (isset($mo->mongo['editObject'])) {
    echo $form->getFormOpen(array('action' => $baseUrl . '?db=' . $dbUrl . '&collection=' . urlencode($collection)));
    if (isset($_GET['_id']) && $_GET['_id'] && $_GET['idtype'] == 'object') {
        $_GET['_id'] = unserialize($_GET['_id']);
    }
    echo $html->h1(isset($_GET['_id']) && $_GET['_id'] ? get::htmlentities($_GET['_id']) : '[New Object]');
    echo $html->div($form->getInput(array('type' => 'submit', 'value' => 'Save Changes', 'class' => 'ui-state-hover')));
    $textarea = array('name' => 'object', 'label' => '', 'type' => 'textarea');
    $textarea['value'] = ($mo->mongo['editObject'] !== '' ? var_export($mo->mongo['editObject'], true)
                                                          : 'array (' . PHP_EOL . PHP_EOL . ')');
    //MongoID as _id
    $textarea['value'] = preg_replace('/\'_id\' => \s*MongoId::__set_state\(array\(\s*\)\)/', '\'_id\' => new MongoId("'
                                      . (isset($_GET['_id']) ? $_GET['_id'] : '') . '")', $textarea['value']);
    //MongoID in all other occurrences, original ID is not maintained
    $textarea['value'] = preg_replace('/MongoId::__set_state\(array\(\s*\)\)/', 'new MongoId()', $textarea['value']);
    //MongoDate
    $textarea['value'] = preg_replace('/MongoDate::__set_state\(array\(\s*\'sec\' => (\d+),\s*\'usec\' => \d+,\s*\)\)/m',
                                      'new MongoDate($1)', $textarea['value']);
    echo $html->div($form->getInput($textarea)
       . $form->getInput(array('name' => 'action', 'type' => 'hidden', 'value' => 'editObject')));
    echo $html->div($form->getInput(array('name' => 'db', 'value' => get::htmlentities($db), 'type' => 'hidden'))
       . $form->getInput(array('type' => 'submit', 'value' => 'Save Changes', 'class' => 'ui-state-hover')));
    echo $form->getFormClose();
    echo $html->jsInline('$("textarea[name=object]").css({"min-width": "750px", "max-width": "1250px", '
        . '"min-height": "450px", "max-height": "2000px", "width": "auto", "height": "auto"}).resizable();
' . $dbcollnavJs);
} else if (isset($mo->mongo['getStats'])) {
    echo '<ul>';
    foreach ($mo->mongo['getStats'] as $key => $val) {
        echo '<li>';
        if (!is_array($val)) {
            echo $key . ': ' . $val;
        } else {
            echo $key . '<ul>';
            foreach ($val as $subkey => $subval) {
                echo $html->li($subkey . ': ' . $subval);
            }
            echo '</ul>';
        }
        echo '</li>';
    }
    echo '</ul>';
}
echo '</div>'; //end of bodycontent

echo $html->footer();