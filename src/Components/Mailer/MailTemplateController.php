<?php

/**
 * MailTemplateController
 * Controls everything mail-template-related
 */
class MailTemplateController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        if (isset($_POST['uploadAttachment'])) {
            if (MailAttachment::uploadAttachment()) {
                Session::add('feedback_positive', Text::get('MAIL_ATTACHMENT_UPLOAD_SUCCESSFUL'));
                Redirect::to("mail/templates/edit.php?id=".Request::get('id'));
            } else {
                Redirect::to("mail/templates/edit.php?id=".Request::get('id'));
            }
        }
        if (isset($_POST['newtemplate'])) {
            MailTemplate::new();
        }
        if (isset($_POST['edittemplate'])) {
            MailTemplate::edit();
        }
        if (isset($_POST['deleteMailTemplateAttachments'])) {
            MailAttachment::deleteById();
        }
    }
}