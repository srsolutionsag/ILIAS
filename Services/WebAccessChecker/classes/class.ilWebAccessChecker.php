<?php
// declare(strict_types=1);

use ILIAS\HTTP\Cookies\CookieFactory;
use ILIAS\HTTP\Cookies\CookieWrapper;
use ILIAS\HTTP\Services;
use Psr\Http\Message\UriInterface;

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
 * Class ilWebAccessChecker
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class ilWebAccessChecker
{
    const DISPOSITION = 'disposition';
    const STATUS_CODE = 'status_code';
    const REVALIDATE = 'revalidate';
    const CM_FILE_TOKEN = 1;
    const CM_FOLDER_TOKEN = 2;
    const CM_CHECKINGINSTANCE = 3;
    const CM_SECFOLDER = 4;
    /**
     * @var ilWACPath
     */
    protected $path_object = null;
    /**
     * @var bool
     */
    protected $checked = false;
    /**
     * @var string
     */
    protected $disposition = ilFileDelivery::DISP_INLINE;
    /**
     * @var string
     */
    protected $override_mimetype = '';
    /**
     * @var bool
     */
    protected $send_status_code = false;
    /**
     * @var bool
     */
    protected $initialized = false;
    /**
     * @var bool
     */
    protected $revalidate_folder_tokens = true;
    /**
     * @var bool
     */
    protected static $use_seperate_logfile = false;
    /**
     * @var array
     */
    protected $applied_checking_methods = array();
    private \ILIAS\HTTP\Services $http;
    private \ILIAS\HTTP\Cookies\CookieFactory $cookieFactory;


    /**
     * ilWebAccessChecker constructor.
     *
     * @param Services            $httpState
     * @param CookieFactory              $cookieFactory
     */
    public function __construct(Services $httpState, CookieFactory $cookieFactory)
    {
        $this->setPathObject(new ilWACPath($httpState->request()->getRequestTarget()));
        $this->http = $httpState;
        $this->cookieFactory = $cookieFactory;
    }


    /**
     * @return bool
     * @throws ilWACException
     */
    public function check()
    {
        if (!$this->getPathObject()) {
            throw new ilWACException(ilWACException::CODE_NO_PATH);
        }

        // Check if Path has been signed with a token
        $ilWACSignedPath = new ilWACSignedPath($this->getPathObject(), $this->http, $this->cookieFactory);
        if ($ilWACSignedPath->isSignedPath()) {
            $this->addAppliedCheckingMethod(self::CM_FILE_TOKEN);
            if ($ilWACSignedPath->isSignedPathValid()) {
                $this->setChecked(true);
                $this->sendHeader('checked using token');

                return true;
            }
        }

        // Check if the whole secured folder has been signed
        if ($ilWACSignedPath->isFolderSigned()) {
            $this->addAppliedCheckingMethod(self::CM_FOLDER_TOKEN);
            if ($ilWACSignedPath->isFolderTokenValid()) {
                if ($this->isRevalidateFolderTokens()) {
                    $ilWACSignedPath->revalidatingFolderToken();
                }
                $this->setChecked(true);
                $this->sendHeader('checked using secure folder');

                return true;
            }
        }

        // Fallback, have to initiate ILIAS
        $this->initILIAS();

        if (ilWACSecurePath::hasCheckingInstanceRegistered($this->getPathObject())) {
            // Maybe the path has been registered, lets check
            $checkingInstance = ilWACSecurePath::getCheckingInstance($this->getPathObject());
            $this->addAppliedCheckingMethod(self::CM_CHECKINGINSTANCE);
            $canBeDelivered = $checkingInstance->canBeDelivered($this->getPathObject());
            if ($canBeDelivered) {
                $this->sendHeader('checked using fallback');
                if ($ilWACSignedPath->isFolderSigned() && $this->isRevalidateFolderTokens()) {
                    $ilWACSignedPath->revalidatingFolderToken();
                }

                $this->setChecked(true);

                return true;
            } else {
                $this->setChecked(true);

                return false;
            }
        }

        // none of the checking mechanisms could have been applied. no access
        $this->setChecked(true);
        if ($this->getPathObject()->isInSecFolder()) {
            $this->addAppliedCheckingMethod(self::CM_SECFOLDER);

            return false;
        } else {
            $this->addAppliedCheckingMethod(self::CM_SECFOLDER);

            return true;
        }
    }


    protected function sendHeader(string $message): void
    {
        $response = $this->http->response()->withHeader('X-ILIAS-WebAccessChecker', $message);
        $this->http->saveResponse($response);
    }


    public function initILIAS(): void
    {
        if ($this->isInitialized()) {
            return;
        }

        $GLOBALS['COOKIE_PATH'] = '/';

        $cookie = $this->cookieFactory->create('ilClientId', $this->getPathObject()->getClient())
                                      ->withPath('/')
                                      ->withExpires(0);

        $response = $this->http->cookieJar()
                               ->with($cookie)
                               ->renderIntoResponseHeader($this->http->response());

        $this->http->saveResponse($response);

        ilContext::init(ilContext::CONTEXT_WAC);
        try {
            ilInitialisation::initILIAS();
            $this->checkUser();
            $this->checkPublicSection();
        } catch (Exception $e) {
            if ($e instanceof ilWACException
                && $e->getCode() !== ilWACException::ACCESS_DENIED_NO_LOGIN) {
                throw  $e;
            }
            if (($e instanceof Exception && $e->getMessage() == 'Authentication failed.')
                || $e->getCode() === ilWACException::ACCESS_DENIED_NO_LOGIN) {
                $this->initAnonymousSession();
                $this->checkUser();
                $this->checkPublicSection();
            }
        }
        $this->setInitialized(true);
    }


    /**
     * @throws ilWACException
     */
    protected function checkPublicSection(): void
    {
        global $DIC;
        $on_login_page = !$this->isRequestNotFromLoginPage();
        $is_anonymous = ((int) $DIC->user()->getId() === (int) ANONYMOUS_USER_ID);
        $is_null_user = ($DIC->user()->getId() === 0);
        $pub_section_activated = (bool) $DIC['ilSetting']->get('pub_section');
        $isset = isset($DIC['ilSetting']);
        $instanceof = $DIC['ilSetting'] instanceof ilSetting;

        if (!$isset || !$instanceof) {
            throw new ilWACException(ilWACException::ACCESS_DENIED_NO_PUB);
        }

        if ($on_login_page && ($is_null_user || $is_anonymous)) {
            // Request is initiated from login page
            return;
        }

        if ($pub_section_activated && ($is_null_user || $is_anonymous)) {
            // Request is initiated from an enabled public area
            return;
        }

        if ($is_anonymous || $is_null_user) {
            throw new ilWACException(ilWACException::ACCESS_DENIED_NO_PUB);
        }
    }


    protected function checkUser(): void
    {
        global $DIC;

        $is_user = $DIC->user() instanceof ilObjUser;
        $user_id_is_zero = ((int) $DIC->user()->getId() === 0);
        $not_on_login_page = $this->isRequestNotFromLoginPage();
        if (!$is_user || ($user_id_is_zero && $not_on_login_page)) {
            throw new ilWACException(ilWACException::ACCESS_DENIED_NO_LOGIN);
        }
    }


    public function isChecked(): bool
    {
        return (bool) $this->checked;
    }


    public function setChecked(bool $checked): void
    {
        assert(is_bool($checked));
        $this->checked = $checked;
    }


    public function getPathObject(): \ilWACPath
    {
        return $this->path_object;
    }


    /**
     * @param ilWACPath $path_object
     */
    public function setPathObject(ilWACPath $path_object): void
    {
        $this->path_object = $path_object;
    }


    public function getDisposition(): string
    {
        return (string) $this->disposition;
    }


    public function setDisposition(string $disposition): void
    {
        assert(is_string($disposition));
        $this->disposition = $disposition;
    }


    public function getOverrideMimetype(): string
    {
        return (string) $this->override_mimetype;
    }


    public function setOverrideMimetype(string $override_mimetype): void
    {
        assert(is_string($override_mimetype));
        $this->override_mimetype = $override_mimetype;
    }


    public function isInitialized(): bool
    {
        return (bool) $this->initialized;
    }


    public function setInitialized(bool $initialized): void
    {
        assert(is_bool($initialized));
        $this->initialized = $initialized;
    }


    public function isSendStatusCode(): bool
    {
        return (bool) $this->send_status_code;
    }


    public function setSendStatusCode(bool $send_status_code): void
    {
        assert(is_bool($send_status_code));
        $this->send_status_code = $send_status_code;
    }


    public function isRevalidateFolderTokens(): bool
    {
        return (bool) $this->revalidate_folder_tokens;
    }


    public function setRevalidateFolderTokens(bool $revalidate_folder_tokens): void
    {
        assert(is_bool($revalidate_folder_tokens));
        $this->revalidate_folder_tokens = $revalidate_folder_tokens;
    }


    public static function isUseSeperateLogfile(): bool
    {
        return (bool) self::$use_seperate_logfile;
    }


    public static function setUseSeperateLogfile(bool $use_seperate_logfile): void
    {
        assert(is_bool($use_seperate_logfile));
        self::$use_seperate_logfile = $use_seperate_logfile;
    }


    /**
     * @return int[]
     */
    public function getAppliedCheckingMethods(): array
    {
        return (array) $this->applied_checking_methods;
    }


    /**
     * @param int[] $applied_checking_methods
     */
    public function setAppliedCheckingMethods(array $applied_checking_methods): void
    {
        $this->applied_checking_methods = $applied_checking_methods;
    }


    protected function addAppliedCheckingMethod(int $method): void
    {
        assert(is_int($method));
        $this->applied_checking_methods[] = $method;
    }


    protected function initAnonymousSession(): void
    {
        global $DIC;
        session_destroy();
        ilContext::init(ilContext::CONTEXT_WAC);
        ilInitialisation::reinitILIAS();
        /**
         * @var $ilAuthSession \ilAuthSession
         */
        $ilAuthSession = $DIC['ilAuthSession'];
        $ilAuthSession->init();
        $ilAuthSession->regenerateId();
        $a_id = (int) ANONYMOUS_USER_ID;
        $ilAuthSession->setUserId($a_id);
        $ilAuthSession->setAuthenticated(false, $a_id);
        $DIC->user()->setId($a_id);
    }


    protected function isRequestNotFromLoginPage(): bool
    {
        $referrer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $not_on_login_page = (strpos($referrer, 'login.php') === false
                              && strpos($referrer, '&baseClass=ilStartUpGUI') === false);

        if ($not_on_login_page && $referrer !== '') {
            // In some scenarios (observed for content styles on login page, the HTTP_REFERER does not contain a PHP script
            $referrer_url_parts = parse_url($referrer);
            $ilias_url_parts = parse_url(ilUtil::_getHttpPath());
            if (
                $ilias_url_parts['host'] === $referrer_url_parts['host'] &&
                (
                    !isset($referrer_url_parts['path']) ||
                    strpos($referrer_url_parts['path'], '.php') === false
                )
            ) {
                $not_on_login_page = false;
            }
        }

        return $not_on_login_page;
    }
}
