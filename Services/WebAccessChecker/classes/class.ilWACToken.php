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
/**
 * Class ilWACToken
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class ilWACToken
{
    const SALT_FILE_PATH = './data/wacsalt.php';
    /**
     * @var string
     */
    protected static $SALT = '';
    /**
     * @var string
     */
    protected $session_id = '';
    /**
     * @var int
     */
    protected $timestamp = 0;
    /**
     * @var string
     */
    protected $ip = '';
    /**
     * @var string
     */
    protected $token = '';
    /**
     * @var string
     */
    protected $raw_token = '';
    /**
     * @var string
     */
    protected $path = '';
    /**
     * @var string
     */
    protected $id = '';
    /**
     * @var string
     */
    protected $client = '';
    /**
     * @var int
     */
    protected $ttl = 0;


    /**
     * ilWACToken constructor.
     *
     * @param string $path
     * @param string $client
     * @param int $timestamp
     * @param int $ttl
     */
    public function __construct($path, $client, $timestamp = 0, $ttl = 0)
    {
        assert(is_string($path));
        assert(is_string($client));
        assert(is_int($timestamp));
        assert(is_int($ttl));
        $this->setClient($client);
        $this->setPath($path);
        $session_id = session_id();
        $this->setSessionId($session_id ? $session_id : '-');
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $this->setIp($_SERVER['REMOTE_ADDR']);
        }
        $this->setTimestamp($timestamp !== 0 ? $timestamp : time());
        $ttl = $ttl !== 0 ? $ttl : ilWACSignedPath::getTokenMaxLifetimeInSeconds();
        $this->setTTL($ttl); //  since we do not know the type at this poit we choose the shorter duration for security reasons
        $this->generateToken();
        $this->setId($this->getPath());
    }


    public function generateToken(): void
    {
        $this->initSalt();
        $token = implode('-', array(
            self::getSALT(),
            $this->getClient(),
            $this->getTimestamp(),
            $this->getTTL(),
        ));
        $this->setRawToken($token);
        $token = sha1($token);
        $this->setToken($token);
    }


    protected function initSalt(): void
    {
        if (self::getSALT()) {
            return;
        }
        $salt = '';
        if (is_file(self::SALT_FILE_PATH)) {
            /** @noRector */
            require self::SALT_FILE_PATH;
            self::setSALT($salt);
        }

        if (strcmp($salt, '') === 0) {
            $this->generateSaltFile();
            $this->initSalt();
        }
    }


    /**
     * @throws ilWACException
     */
    protected function generateSaltFile(): void
    {
        if (is_file(self::SALT_FILE_PATH)) {
            unlink(self::SALT_FILE_PATH);
        }
        $template = file_get_contents('./Services/WebAccessChecker/wacsalt.php.template');
        $random = new \ilRandom();
        $salt = md5(time() * $random->int(1000, 9999) . self::SALT_FILE_PATH);
        self::setSALT($salt);
        $template = str_replace('INSERT_SALT', $salt, $template);
        if (is_writable(dirname(self::SALT_FILE_PATH))) {
            file_put_contents(self::SALT_FILE_PATH, $template);
        } else {
            throw new ilWACException(ilWACException::DATA_DIR_NON_WRITEABLE, self::SALT_FILE_PATH);
        }
    }


    public function getSessionId(): string
    {
        return (string) $this->session_id;
    }


    public function setSessionId(string $session_id): void
    {
        assert(is_string($session_id));
        $this->session_id = $session_id;
    }


    public function getTimestamp(): int
    {
        return (int) $this->timestamp;
    }


    public function setTimestamp(int $timestamp): void
    {
        assert(is_int($timestamp));
        $this->timestamp = $timestamp;
    }


    public function getIp(): string
    {
        return (string) $this->ip;
    }


    public function setIp(string $ip): void
    {
        assert(is_string($ip));
        $this->ip = $ip;
    }


    public function getToken(): string
    {
        return (string) $this->token;
    }


    public function setToken(string $token): void
    {
        assert(is_string($token));
        $this->token = $token;
    }


    public function getPath(): string
    {
        return (string) $this->path;
    }


    public function setPath(string $path): void
    {
        assert(is_string($path));
        $this->path = $path;
    }


    public function getId(): string
    {
        return (string) $this->id;
    }


    public function getHashedId(): string
    {
        return (string) md5($this->id);
    }


    public function setId(string $id): void
    {
        assert(is_string($id));
        $this->id = $id;
    }


    public static function getSALT(): string
    {
        return (string) self::$SALT;
    }


    public static function setSALT(string $salt): void
    {
        assert(is_string($salt));
        self::$SALT = $salt;
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


    public function getTTL(): int
    {
        return (int) $this->ttl;
    }


    public function setTTL(int $ttl): void
    {
        assert(is_int($ttl));
        $this->ttl = $ttl;
    }


    public function getRawToken(): string
    {
        return (string) $this->raw_token;
    }


    public function setRawToken(string $raw_token): void
    {
        assert(is_string($raw_token));
        $this->raw_token = $raw_token;
    }
}
