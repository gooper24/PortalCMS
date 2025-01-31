<?php

namespace PortalCMS\Core\Email\Message\Attachment;

use PortalCMS\Core\View\Text;
use PortalCMS\Core\Config\Config;
use PortalCMS\Core\Session\Session;

class EmailAttachment
{
    public $path;
    public $name;
    public $extension;
    public $encoding = 'base64';
    public $type = 'application/octet-stream';

    public function __construct(array $file)
    {
        $this->path = Config::get('PATH_ATTACHMENTS');
        $this->processUpload($file);
        return $this;
    }

    public function processUpload(array $file) : bool
    {
        if (empty($file)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_AVATAR_IMAGE_UPLOAD_FAILED'));
            return false;
        }
        if (!$this->isFolderWritable($this->path)) {
            return false;
        }
        // if (!$this->validateType($file)) {
        //     return false;
        // }
        // if (!$this->validateFileSize($file)) {
        //     return false;
        // }
        if (!move_uploaded_file($file['tmp_name'], DIR_ROOT . $this->path . $file['name'])) {
            Session::add('feedback_negative', Text::get('FEEDBACK_AVATAR_IMAGE_UPLOAD_FAILED'));
            return false;
        }
        $this->name = pathinfo($this->path . $file['name'], PATHINFO_FILENAME);
        $this->extension = pathinfo($this->path . $file['name'], PATHINFO_EXTENSION);
        $this->type = self::getMIMEType(DIR_ROOT . $this->path . $file['name']);
        return true;
    }

    public function store(int $mailId = null, int $templateId = null)
    {
        if (!empty($this->path) && !empty($this->name) && !empty($this->extension) && !empty($this->encoding) && !empty($this->type)) {
            if (!empty($templateId) && !empty($mailId)) {
                Session::add('feedback_negative', Text::get('FEEDBACK_MAIL_ATTACHMENT_UPLOAD_FAILED'));
                return false;
            }
            if (!empty($templateId)) {
                if (EmailAttachmentMapper::createForTemplate($templateId, $this)) {
                    Session::add('feedback_positive', Text::get('FEEDBACK_MAIL_ATTACHMENT_UPLOAD_SUCCESSFUL'));
                    return true;
                }
                Session::add('feedback_negative', Text::get('FEEDBACK_MAIL_ATTACHMENT_UPLOAD_FAILED'));
                return false;
            }
            if (!empty($mailId)) {
                Session::add('feedback_negative', Text::get('FEEDBACK_MAIL_ATTACHMENT_UPLOAD_FAILED'));
                return false;
            }
        }
    }

    public function getMIMEType($filename)
    {
        $realpath = realpath($filename);
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $realpath);
    }

    /**
     * Checks if the upload folder exists and if it is writable
     *
     * @var string $path Path of the target upload folder
     * @return bool success status
     */
    public function isFolderWritable($path) : bool
    {
        if (!is_dir(DIR_ROOT . $path)) {
            Session::add('feedback_negative', 'Directory ' . $path . ' doesnt exist');
            return false;
        }
        if (!is_writable(DIR_ROOT . $path)) {
            Session::add('feedback_negative', 'Directory ' . $path . ' is not writeable');
            return false;
        }
        return true;
    }

    /**
     * Validates is the file size of the attachment is within range.
     *
     * @return bool
     */
    public function validateFileSize($attachmentFile) : bool
    {
        if ($attachmentFile['size'] > 5000000) {
            Session::add('feedback_negative', Text::get('FEEDBACK_AVATAR_UPLOAD_TOO_BIG'));
            return false;
        }
        // $image_proportions = getimagesize($attachmentFile['tmp_name']);
        // if ($image_proportions[0] < Config::get('AVATAR_SIZE') or $image_proportions[1] < Config::get('AVATAR_SIZE')) {
        //     return false;
        // }
        return true;
    }

    public function validateType($attachmentFile) : bool
    {
        if ($attachmentFile['type'] === 'image/jpeg') {
            return true;
        }
        Session::add('feedback_negative', Text::get('FEEDBACK_AVATAR_UPLOAD_WRONG_TYPE'));
        return false;
    }

    /**
     * Delete attachment(s)
     *
     * @param array|int $attachmentIds
     *
     * @return bool
     */
    public static function deleteById($attachmentIds) : bool
    {
        $deleted = 0;
        $error = 0;
        if (empty($attachmentIds)) {
            Session::add('feedback_negative', 'Verwijderen mislukt. Ongeldig verzoek');
            return false;
        }
        foreach ($attachmentIds as $attachmentId) {
            if (!EmailAttachmentMapper::deleteById($attachmentId)) {
                ++$error;
            } else {
                ++$deleted;
            }
        }
        return self::deleteFeedbackHandler($deleted, $error);
    }

    /**
     * Handle feedback for the deleteById method
     *
     * @param int $deleted
     * @param int $error
     *
     * @return bool
     */
    public static function deleteFeedbackHandler($deleted, $error) : bool
    {
        if ($deleted > 0 && $error === 0) {
            if ($deleted > 1) {
                Session::add('feedback_positive', 'Er zijn ' . $deleted . ' bijlagen verwijderd.');
            } else {
                Session::add('feedback_positive', 'Er is ' . $deleted . ' bijlage verwijderd.');
            }
            return true;
        }
        if ($deleted > 0 && $error > 0) {
            Session::add('feedback_warning', 'Aantal bijlagen verwijderd: ' . $deleted . '. Aantal bijlagen met problemen: ' . $error);
            return true;
        }
        Session::add('feedback_negative', 'Verwijderen mislukt. Aantal bijlagen met problemen: ' . $error);
        return false;
    }
}
