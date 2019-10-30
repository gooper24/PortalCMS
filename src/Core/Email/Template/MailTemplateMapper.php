<?php

namespace PortalCMS\Core\Email\Template;

use PortalCMS\Core\Database\DB;
use PortalCMS\Core\Email\Template\EmailTemplate;

class MailTemplateMapper
{
    public static function getTemplates()
    {
        $stmt = DB::conn()->prepare(
            'SELECT *
                FROM mail_templates
                    ORDER BY id'
        );
        $stmt->execute([]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        return $stmt->fetchAll();
    }

    public static function getTemplatesByType($type)
    {
        $stmt = DB::conn()->prepare(
            'SELECT *
                FROM mail_templates
                    WHERE type = ?
                    ORDER BY id'
        );
        $stmt->execute([$type]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        return $stmt->fetchAll();
    }

    public static function getById($id)
    {
        $stmt = DB::conn()->prepare(
            'SELECT *
                FROM mail_templates
                    WHERE id = ?
                    LIMIT 1'
        );
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 1) {
            return $stmt->fetch();
        }
        return false;
    }

    public static function getSystemTemplateByName($name)
    {
        $stmt = DB::conn()->prepare(
            "SELECT *
                FROM mail_templates
                    WHERE type = 'system'
                    AND name = ?
                    LIMIT 1"
        );
        $stmt->execute([$name]);
        if ($stmt->rowCount() === 1) {
            return $stmt->fetch();
        }
        return false;
    }

    public function create(EmailTemplate $EmailTemplate)
    {
        $stmt = DB::conn()->prepare('INSERT INTO mail_templates(id, type, subject, body, status) VALUES (NULL,?,?,?,?)');
        $stmt->execute([$EmailTemplate->type, $EmailTemplate->emailMessage->subject, $EmailTemplate->emailMessage->body, $EmailTemplate->status]);
        if (!$stmt) {
            Session::add('feedback_negative', 'MailTemplateMapper->create() failed.');
            return false;
        }
        Session::add('feedback_positive', 'MailTemplateMapper->create() success.');
        return self::lastInsertedId();
    }

    public static function update($id, $type, $subject, $body, $status)
    {
        $stmt = DB::conn()->prepare('UPDATE mail_templates SET type = ?, subject = ?, body = ?, status = ? WHERE id = ? LIMIT 1');
        $stmt->execute([$type, $subject, $body, $status, $id]);
        return $stmt->rowCount() === 1;
    }

    public static function lastInsertedId()
    {
        return DB::conn()->query('SELECT max(id) from mail_templates')->fetchColumn();
    }
}
