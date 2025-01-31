<?php

namespace PortalCMS\Core\User;

use PDO;
use PortalCMS\Core\Database\DB;

/**
 * UserMapper
 */
class UserMapper
{
    /**
     * Checks if a username is already taken
     *
     * @param $user_name
     * @return bool
     */
    public static function usernameExists($user_name)
    {
        $stmt = DB::conn()->prepare(
            'SELECT user_id
                    FROM users
                        WHERE user_name = ?
                        LIMIT 1'
        );
        $stmt->execute([$user_name]);
        return ($stmt->rowCount() === 1 ? true : false);
    }

    /**
     * Writes new username to database
     *
     * @param int $user_id user id
     * @param string $newUsername new username
     *
     * @return bool
     */
    public static function updateUsername($user_id, $newUsername)
    {
        $stmt = DB::conn()->prepare(
            'UPDATE users
                SET user_name = :user_name
                    WHERE user_id = :user_id
                    LIMIT 1'
        );
        $stmt->execute([':user_name' => $newUsername, ':user_id' => $user_id]);
        return ($stmt->rowCount() === 1 ? true : false);
    }

    public static function updateFbid($user_id, $fbid)
    {
        $stmt = DB::conn()->prepare(
            'UPDATE users
                SET user_fbid = ?
                    WHERE user_id = ?
                    LIMIT 1'
        );
        $stmt->execute([$fbid, $user_id]);
        return ($stmt->rowCount() === 1 ? true : false);
    }

    public static function updateRememberMeToken($user_id, $token)
    {
        $stmt = DB::conn()->prepare(
            'UPDATE users
                    SET user_remember_me_token = ?
                    WHERE user_id = ?
                    LIMIT 1'
        );
        $stmt->execute([$token, $user_id]);
        return ($stmt->rowCount() === 1 ? true : false);
    }

    /**
     * update session id in database
     *
     * @access public
     * @static static method
     * @param string $userId
     * @param string $sessionId
     * @return bool
     */
    public static function updateSessionId($userId, $sessionId = null)
    {
        $stmt = DB::conn()->prepare(
            'UPDATE users
                    SET session_id = :session_id
                    WHERE user_id = :user_id'
        );
        $stmt->execute([':session_id' => $sessionId, ':user_id' => $userId]);
        return ($stmt->rowCount() === 1 ? true : false);
    }

    public static function getProfileById($Id)
    {
        $stmt = DB::conn()->prepare(
            'SELECT user_id,
            user_name,
            session_id,
            user_email,
            user_active,
            user_deleted,
            user_account_type,
            user_failed_logins,
            user_last_login_timestamp,
            user_failed_logins,
            user_last_failed_login,
            user_provider_type,
            user_fbid,
            CreationDate,
            ModificationDate
                    FROM users
                        WHERE user_id = :user_id
                        AND user_id IS NOT NULL
                        LIMIT 1'
        );
        $stmt->execute([':user_id' => $Id]);
        if ($stmt->rowCount() === 1) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Write timestamp of this login into database (we only write a "real" login via login form into the database,
     * not the session-login on every page request
     *
     * @param $username
     * @return bool
     */
    public static function saveTimestampByUsername($username)
    {
        $stmt = DB::conn()->prepare(
            'UPDATE users
                SET user_last_login_timestamp = ?
                WHERE user_name = ?
                LIMIT 1'
        );
        $stmt->execute([date('Y-m-d H:i:s'), $username]);
        return ($stmt->rowCount() === 1 ? true : false);
    }

    /**
     * Resets the failed-login counter of a user back to 0
     *
     * @param $username
     * @return bool
     */
    public static function resetFailedLoginsByUsername($username)
    {
        $stmt = DB::conn()->prepare(
            'UPDATE users
                SET user_failed_logins = 0, user_last_failed_login = NULL
                WHERE user_name = ?
                AND user_failed_logins != 0
                LIMIT 1'
        );
        $stmt->execute([$username]);
        return ($stmt->rowCount() === 1 ? true : false);
    }

    /**
     * Increments the failed-login counter of a user
     *
     * @param $username
     * @return bool
     */
    public static function setFailedLoginByUsername($username)
    {
        $stmt = DB::conn()->prepare(
            'UPDATE users
                SET user_failed_logins = user_failed_logins+1, user_last_failed_login = :user_last_failed_login
                    WHERE user_name = :user_name
                    OR user_email = :user_name
                    LIMIT 1'
        );
        $stmt->execute([':user_name' => $username, ':user_last_failed_login' => date('Y-m-d H:i:s')]);
        return ($stmt->rowCount() === 1 ? true : false);
    }

    public static function clearRememberMeToken($user_id)
    {
        $stmt = DB::conn()->prepare(
            'UPDATE users
                    SET user_remember_me_token = :user_remember_me_token
                    WHERE user_id = :user_id
                    LIMIT 1'
        );
        $stmt->execute([':user_remember_me_token' => null, ':user_id' => $user_id]);
        return ($stmt->rowCount() === 1 ? true : false);
    }

    /**
     * Gets the user's data
     *
     * @param string $username User's name
     *
     * @return mixed Returns false if user does not exist, returns object with user's data when user exists
     */
    public static function getByUsername($username)
    {
        $stmt = DB::conn()->prepare(
            'SELECT user_id,
                    user_name,
                    user_email,
                    user_password_hash,
                    user_active,
                    user_deleted,
                    user_suspension_timestamp,
                    user_account_type,
                    user_failed_logins,
                    user_last_failed_login,
                    user_fbid
                    FROM users
                        WHERE (user_name = :user_name OR user_email = :user_name)
                            AND user_provider_type = :provider_type
                                LIMIT 1'
        );
        $stmt->execute([':user_name' => $username, ':provider_type' => 'DEFAULT']);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Gets the user's data by user's id and a token (used by login-via-cookie process)
     *
     * @param $user_id
     * @param $token
     *
     * @return mixed Returns false if user does not exist, returns object with user's data when user exists
     */
    public static function getByIdAndToken($user_id, $token)
    {
        $stmt = DB::conn()->prepare(
            'SELECT user_id,
                    user_name,
                    user_email,
                    user_password_hash,
                    user_active,
                    user_account_type,
                    user_has_avatar,
                    user_failed_logins,
                    user_last_failed_login,
                    user_fbid
                    FROM users
                        WHERE user_id = :user_id
                        AND user_remember_me_token = :user_remember_me_token
                        AND user_remember_me_token IS NOT NULL
                        AND user_provider_type = :provider_type
                        LIMIT 1'
        );
        $stmt->execute(
            [
            ':user_id' => $user_id,
            ':user_remember_me_token' => $token,
            ':provider_type' => 'DEFAULT']
        );
        if ($stmt->rowCount() === 0) {
            return false;
        }
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public static function getByFbid($user_fbid)
    {
        $stmt = DB::conn()->prepare(
            'SELECT user_id, user_name, user_email, user_password_hash, user_active,
                    user_account_type, user_has_avatar, user_failed_logins, user_last_failed_login
                    FROM users
                        WHERE user_fbid = :user_fbid
                        AND user_fbid IS NOT NULL
                        LIMIT 1'
        );
        $stmt->execute([':user_fbid' => $user_fbid]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * @param $usernameOrEmail
     *
     * @return mixed
     */
    public static function getByUsernameOrEmail($usernameOrEmail)
    {
        $stmt = DB::conn()->prepare(
            'SELECT user_id, user_name, user_email
                    FROM users
                        WHERE (user_name = :user_name_or_email
                        OR user_email = :user_name_or_email)
                        AND user_provider_type = :provider_type
                        LIMIT 1'
        );
        $stmt->execute([':user_name_or_email' => $usernameOrEmail, ':provider_type' => 'DEFAULT']);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}
