<?php
namespace PortalCMS\Email;

class EmailConfiguration
{
    /**
     * @var string
     */
    public $SMTPHost = 'localhost';

    /**
     * @var integer
     */
    public $SMTPPort = 25;

    /**
     * Encryption type for the SMTP connection (tls, ssl or empty)
     *
     * @var string
     */
    public $SMTPCrypto = 'tls';

    /**
     * @var boolean
     */
    public $SMTPAuth = 0;

    /**
     * @var string
     */
    public $SMTPUser;

    /**
     * @var string
     */
    public $SMTPPass;

    /**
     * @var boolean
     */
    public $SMTPDebug = 0;

    /**
     * @var string
     */
    public $fromEmail;

    /**
     * @var string
     */
    public $fromName;

    /**
     * @var boolean
     */
    public $isHTML;

    /**
     * Character set (utf-8, iso-8859-1, etc.)
     *
     * @var string
     */
    public $charset = 'UTF-8';

    /**
     * Initialize preferences
     *
     * @return EmailConfiguration
     */
    public function __construct()
    {
        $this->fromEmail = \SiteSetting::getStaticSiteSetting('MailFromEmail');
        $this->fromName = \SiteSetting::getStaticSiteSetting('MailFromName');
        $this->SMTPHost = \SiteSetting::getStaticSiteSetting('MailServer');
        $this->SMTPPort = \SiteSetting::getStaticSiteSetting('MailServerPort');
        $this->SMTPCrypto = \SiteSetting::getStaticSiteSetting('MailServerSecure');
        if (\SiteSetting::getStaticSiteSetting('MailServerAuth') === 1) {
            $this->SMTPAuth = true;
        } else {
            $this->SMTPAuth = false;
        }
        $this->SMTPUser = \SiteSetting::getStaticSiteSetting('MailServerUsername');
        $this->SMTPPass = \SiteSetting::getStaticSiteSetting('MailServerPassword');
        if (\SiteSetting::getStaticSiteSetting('MailServerDebug') === 1) {
            $this->SMTPDebug = true;
        } else {
            $this->SMTPDebug = false;
        }
        if (\SiteSetting::getStaticSiteSetting('MailIsHTML') === 1) {
            $this->isHTML = true;
        } else {
            $this->isHTML = false;
        }
        $this->charset  = strtoupper($this->charset);
        return $this;
    }
}