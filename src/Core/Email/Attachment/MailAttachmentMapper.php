<?php

namespace PortalCMS\Core\Email\Attachment;

use PortalCMS\Core\Database\DB;

class MailAttachmentMapper
{
    public static function create($mail_id, $path, $name, $extension, $encoding = 'base64', $type = 'application/octet-stream')
    {
        $stmt = DB::conn()->prepare(
            'INSERT INTO mail_attachments(id, mail_id, path, name, extension, encoding, type) VALUES (NULL,?,?,?,?,?,?)'
        );
        $stmt->execute([$mail_id, $path, $name, $extension, $encoding, $type]);
        if (!$stmt) {
            return false;
        }
        return true;
    }

    public static function createForTemplate($template_id, $path, $name, $extension, $encoding = 'base64', $type = 'application/octet-stream')
    {
        $stmt = DB::conn()->prepare(
            'INSERT INTO mail_attachments(id, template_id, path, name, extension, encoding, type) VALUES (NULL,?,?,?,?,?,?)'
        );
        $stmt->execute([$template_id, $path, $name, $extension, $encoding, $type]);
        if (!$stmt) {
            return false;
        }
        return true;
    }

    public static function getByMailId($mailId)
    {
        $stmt = DB::conn()->prepare('SELECT * FROM mail_attachments where mail_id = ?');
        $stmt->execute([$mailId]);
        if ($stmt->rowCount() > 0) {
            return $stmt->fetchAll();
        }
        return false;
    }

    public static function getByTemplateId($templateId)
    {
        $stmt = DB::conn()->prepare('SELECT * FROM mail_attachments where template_id = ?');
        $stmt->execute([$templateId]);
        if ($stmt->rowCount() > 0) {
            return $stmt->fetchAll();
        }
        return false;
    }

    /**
     * @return mixed
     */
    public static function lastInsertedId()
    {
        return DB::conn()->query('SELECT max(id) from mail_attachments')->fetchColumn();
    }

    /**
     * @param $id
     * @return bool
     */
    public static function deleteById($id)
    {
        $stmt = DB::conn()->prepare('DELETE FROM mail_attachments WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return ($stmt->rowCount() === 1);
    }

    public static function deleteByMailId($id)
    {
        $stmt = DB::conn()->prepare('DELETE FROM mail_attachments WHERE mail_id = ? LIMIT 1');
        $stmt->execute([$id]);
        return ($stmt->rowCount() === 1 ? true : false);
    }
}
