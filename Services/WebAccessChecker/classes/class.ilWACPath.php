<?php
/******************************************************************************
 *
 * This file is part of ILIAS, a powerful learning management system.
 *
 * ILIAS is licensed with the GPL-3.0, you should have received a copy
 * of said license along with the source code.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 *      https://www.ilias.de
 *      https://github.com/ILIAS-eLearning
 *
 *****************************************************************************/
// declare(strict_types=1);
/**
 * Class ilWACPath
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class ilWACPath
{
    const DIR_DATA = "data";
    const DIR_SEC = "sec";
    /**
     * Copy this without to regex101.com and test with some URL of files
     */
    const REGEX = "(?<prefix>.*?)(?<path>(?<path_without_query>(?<secure_path_id>(?<module_path>\/data\/(?<client>[\w\-\.]*)\/(?<sec>sec\/|)(?<module_type>.*?)\/(?<module_identifier>.*\/|)))(?<appendix>[^\?\n]*)).*)";
    /**
     * @var string[]
     */
    protected static $image_suffixes = array(
        'png',
        'jpg',
        'jpeg',
        'gif',
        'svg',
    );
    /**
     * @var string[]
     */
    protected static $video_suffixes = array(
        'mp4',
        'm4v',
        'mov',
        'wmv',
        'webm',
    );
    /**
     * @var string[]
     */
    protected static $audio_suffixes = array(
        'mp3',
        'aiff',
        'aif',
        'm4a',
        'wav',
    );
    /**
     * @var string
     */
    protected $client = '';
    /**
     * @var array
     */
    protected $parameters = array();
    /**
     * @var bool
     */
    protected $in_sec_folder = false;
    /**
     * @var string
     */
    protected $token = '';
    /**
     * @var int
     */
    protected $timestamp = 0;
    /**
     * @var int
     */
    protected $ttl = 0;
    /**
     * @var string
     */
    protected $secure_path = '';
    /**
     * @var string
     */
    protected $secure_path_id = '';
    /**
     * @var string
     */
    protected $original_request = '';
    /**
     * @var string
     */
    protected $file_name = '';
    /**
     * @var string
     */
    protected $query = '';
    /**
     * @var string
     */
    protected $suffix = '';
    /**
     * @var string
     */
    protected $prefix = '';
    /**
     * @var string
     */
    protected $appendix = '';
    /**
     * @var string
     */
    protected $module_path = '';
    /**
     * @var string
     */
    protected $path = '';
    /**
     * @var string
     */
    protected $module_type = '';
    /**
     * @var string
     */
    protected $module_identifier = '';
    /**
     * @var string
     */
    protected $path_without_query = '';


    /**
     * ilWACPath constructor.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        assert(is_string($path));
        $this->setOriginalRequest($path);
        $re = '/' . self::REGEX . '/';
        preg_match($re, $path, $result);

        foreach ($result as $k => $v) {
            if (is_numeric($k)) {
                unset($result[$k]);
            }
        }

        $moduleId = strstr(
            !isset($result['module_identifier']) || is_null($result['module_identifier']) ? '' : $result['module_identifier'],
            '/',
            true
        );
        $moduleId = $moduleId === false ? '' : $moduleId;

        $this->setPrefix(!isset($result['prefix']) || is_null($result['prefix']) ? '' : $result['prefix']);
        $this->setClient(!isset($result['client']) || is_null($result['client']) ? '' : $result['client']);
        $this->setAppendix(!isset($result['appendix']) || is_null($result['appendix']) ? '' : $result['appendix']);
        $this->setModuleIdentifier($moduleId);
        $this->setModuleType(!isset($result['module_type']) || is_null($result['module_type']) ? '' : $result['module_type']);

        $modulePath = null;

        if ($this->getModuleIdentifier()) {
            $modulePath = strstr(
                !isset($result['module_path']) || is_null($result['module_path']) ? '' : $result['module_path'],
                $this->getModuleIdentifier(),
                true
            );
            $modulePath = '.' . ($modulePath === false ? '' : $modulePath);
        } else {
            $modulePath = ('.' . (!isset($result['module_path']) || is_null($result['module_path']) ? '' : $result['module_path']));
        }

        $this->setModulePath("$modulePath");
        $this->setInSecFolder(isset($result['sec']) && $result['sec'] === 'sec/');
        $this->setPathWithoutQuery(
            '.' . (!isset($result['path_without_query']) || is_null($result['path_without_query']) ? '' : $result['path_without_query'])
        );
        $this->setPath('.' . (!isset($result['path']) || is_null($result['path']) ? '' : $result['path']));
        $this->setSecurePath(
            '.' . (!isset($result['secure_path_id']) || is_null($result['secure_path_id']) ? '' : $result['secure_path_id'])
        );
        $this->setSecurePathId(!isset($result['module_type']) || is_null($result['module_type']) ? '' : $result['module_type']);
        // Pathinfo
        $parts = parse_url($path);
        $this->setFileName(basename($parts['path']));
        if (isset($parts['query'])) {
            $parts_query = $parts['query'];
            $this->setQuery($parts_query);
            parse_str($parts_query, $query);
            $this->setParameters($query);
        }
        $this->setSuffix(pathinfo($parts['path'], PATHINFO_EXTENSION));
        $this->handleParameters();
    }


    protected function handleParameters(): void
    {
        $param = $this->getParameters();
        if (isset($param[ilWACSignedPath::WAC_TOKEN_ID])) {
            $this->setToken($param[ilWACSignedPath::WAC_TOKEN_ID]);
        }
        if (isset($param[ilWACSignedPath::WAC_TIMESTAMP_ID])) {
            $this->setTimestamp(intval($param[ilWACSignedPath::WAC_TIMESTAMP_ID]));
        }
        if (isset($param[ilWACSignedPath::WAC_TTL_ID])) {
            $this->setTTL(intval($param[ilWACSignedPath::WAC_TTL_ID]));
        }
    }


    /**
     * @return mixed[]
     */
    public function getParameters(): array
    {
        return (array) $this->parameters;
    }


    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }


    /**
     * @return mixed[]
     */
    public static function getAudioSuffixes(): array
    {
        return (array) self::$audio_suffixes;
    }


    /**
     * @param array $audio_suffixes
     */
    public static function setAudioSuffixes(array $audio_suffixes): void
    {
        self::$audio_suffixes = $audio_suffixes;
    }


    /**
     * @return mixed[]
     */
    public static function getImageSuffixes(): array
    {
        return (array) self::$image_suffixes;
    }


    /**
     * @param array $image_suffixes
     */
    public static function setImageSuffixes(array $image_suffixes): void
    {
        self::$image_suffixes = $image_suffixes;
    }


    /**
     * @return mixed[]
     */
    public static function getVideoSuffixes(): array
    {
        return (array) self::$video_suffixes;
    }


    /**
     * @param array $video_suffixes
     */
    public static function setVideoSuffixes(array $video_suffixes): void
    {
        self::$video_suffixes = $video_suffixes;
    }


    public function getPrefix(): string
    {
        return (string) $this->prefix;
    }


    public function setPrefix(string $prefix): void
    {
        assert(is_string($prefix));
        $this->prefix = $prefix;
    }


    public function getAppendix(): string
    {
        return (string) $this->appendix;
    }


    public function setAppendix(string $appendix): void
    {
        assert(is_string($appendix));
        $this->appendix = $appendix;
    }


    public function getModulePath(): string
    {
        return (string) $this->module_path;
    }


    public function setModulePath(string $module_path): void
    {
        assert(is_string($module_path));
        $this->module_path = $module_path;
    }


    public function getDirName(): string
    {
        return (string) dirname($this->getPathWithoutQuery());
    }


    public function getPathWithoutQuery(): string
    {
        return (string) $this->path_without_query;
    }


    public function setPathWithoutQuery(string $path_without_query): void
    {
        assert(is_string($path_without_query));
        $this->path_without_query = $path_without_query;
    }


    public function isImage(): bool
    {
        return (bool) in_array(strtolower($this->getSuffix()), self::$image_suffixes);
    }


    public function getSuffix(): string
    {
        return (string) $this->suffix;
    }


    public function setSuffix(string $suffix): void
    {
        assert(is_string($suffix));
        $this->suffix = $suffix;
    }


    public function isStreamable(): bool
    {
        return (bool) ($this->isAudio() || $this->isVideo());
    }


    public function isAudio(): bool
    {
        return (bool) in_array(strtolower($this->getSuffix()), self::$audio_suffixes);
    }


    public function isVideo(): bool
    {
        return (bool) in_array(strtolower($this->getSuffix()), self::$video_suffixes);
    }


    public function fileExists(): bool
    {
        return (bool) is_file($this->getPathWithoutQuery());
    }


    public function hasToken(): bool
    {
        return (bool) ($this->token !== '');
    }


    public function hasTimestamp(): bool
    {
        return (bool) ($this->timestamp !== 0);
    }


    public function hasTTL(): bool
    {
        return (bool) ($this->ttl !== 0);
    }


    public function getToken(): string
    {
        return (string) $this->token;
    }


    public function setToken(string $token): void
    {
        assert(is_string($token));
        $this->parameters[ilWACSignedPath::WAC_TOKEN_ID] = $token;
        $this->token = $token;
    }


    public function getTimestamp(): int
    {
        return (int) $this->timestamp;
    }


    public function setTimestamp(int $timestamp): void
    {
        assert(is_int($timestamp));
        $this->parameters[ilWACSignedPath::WAC_TIMESTAMP_ID] = $timestamp;
        $this->timestamp = $timestamp;
    }


    public function getTTL(): int
    {
        return (int) $this->ttl;
    }


    public function setTTL(int $ttl): void
    {
        $this->parameters[ilWACSignedPath::WAC_TTL_ID] = $ttl;
        $this->ttl = $ttl;
    }


    public function getClient(): string
    {
        return (string) $this->client;
    }


    public function setClient(string $client): void
    {
        assert(is_string($client));
        $this->client = $client;
    }


    public function getSecurePathId(): string
    {
        return (string) $this->secure_path_id;
    }


    public function setSecurePathId(string $secure_path_id): void
    {
        assert(is_string($secure_path_id));
        $this->secure_path_id = $secure_path_id;
    }


    public function getPath(): string
    {
        return (string) $this->path;
    }


    /**
     * Returns a clean (everything behind ? is removed and rawurldecoded path
     */
    public function getCleanURLdecodedPath(): string
    {
        $path = explode("?", (string) $this->path); // removing everything behind ?
        $path_to_file = rawurldecode($path[0]);

        return $path_to_file;
    }


    public function setPath(string $path): void
    {
        assert(is_string($path));
        $this->path = $path;
    }


    public function getQuery(): string
    {
        return (string) $this->query;
    }


    public function setQuery(string $query): void
    {
        assert(is_string($query));
        $this->query = $query;
    }


    public function getFileName(): string
    {
        return (string) $this->file_name;
    }


    public function setFileName(string $file_name): void
    {
        assert(is_string($file_name));
        $this->file_name = $file_name;
    }


    public function getOriginalRequest(): string
    {
        return (string) $this->original_request;
    }


    public function setOriginalRequest(string $original_request): void
    {
        assert(is_string($original_request));
        $this->original_request = $original_request;
    }


    public function getSecurePath(): string
    {
        return (string) $this->secure_path;
    }


    public function setSecurePath(string $secure_path): void
    {
        assert(is_string($secure_path));
        $this->secure_path = $secure_path;
    }


    public function isInSecFolder(): bool
    {
        return (bool) $this->in_sec_folder;
    }


    public function setInSecFolder(bool $in_sec_folder): void
    {
        assert(is_bool($in_sec_folder));
        $this->in_sec_folder = $in_sec_folder;
    }


    public function getModuleType(): string
    {
        return (string) $this->module_type;
    }


    public function setModuleType(string $module_type): void
    {
        assert(is_string($module_type));
        $this->module_type = $module_type;
    }


    public function getModuleIdentifier(): string
    {
        return (string) $this->module_identifier;
    }


    public function setModuleIdentifier(string $module_identifier): void
    {
        assert(is_string($module_identifier));
        $this->module_identifier = $module_identifier;
    }
}
