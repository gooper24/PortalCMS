<?php

/**
 * Class PasswordReset
 *
 * Handles all the stuff that is related to the password
 */
class PasswordReset
{
    /**
     * Perform the necessary actions to send a password reset mail
     *
     * @param $user_name_or_email string Username or user's email
     * @param $captcha string Captcha string
     *
     * @return bool success status
     */
    // public static function requestPasswordReset($user_name_or_email, $captcha)
    public static function requestPasswordReset($user_name_or_email)
    {
        // if (!CaptchaModel::checkCaptcha($captcha)) {
        //     Session::add('feedback_negative', Text::get('FEEDBACK_CAPTCHA_WRONG'));
        //     return false;
        // }
        if (empty($user_name_or_email)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_USERNAME_EMAIL_FIELD_EMPTY'));
            return false;
        }
        // check if that username exists
        $result = User::getUserDataByUserNameOrEmail($user_name_or_email);
        if (!$result) {
            Session::add('feedback_negative', Text::get('FEEDBACK_USER_DOES_NOT_EXIST'));
            return false;
        }
        // generate integer-timestamp (to see when exactly the user (or an attacker) requested the password reset mail)
        // generate random hash for email password reset verification (40 char string)
        $temporary_timestamp = date('Y-m-d H:i:s');
        $user_password_reset_hash = sha1(uniqid(mt_rand(), true));
        // set token (= a random hash string and a timestamp) into database ...
        $token_set = self::setPasswordResetDatabaseToken($result->user_name, $user_password_reset_hash, $temporary_timestamp);
        if (!$token_set) {
            return false;
        }
        // ... and send a mail to the user, containing a link with username and token hash string
        $mail_sent = self::sendPasswordResetMail($result->user_name, $user_password_reset_hash, $result->user_email);
        if ($mail_sent) {
            return true;
        }
        return false;
    }

    /**
     * Set password reset token in database (for DEFAULT user accounts)
     *
     * @param string $user_name username
     * @param string $user_password_reset_hash password reset hash
     * @param int $temporary_timestamp timestamp
     *
     * @return bool success status
     */
    public static function setPasswordResetDatabaseToken($user_name, $user_password_reset_hash, $temporary_timestamp)
    {
        $sql = "UPDATE users
                SET user_password_reset_hash = :user_password_reset_hash, user_password_reset_timestamp = :user_password_reset_timestamp
                WHERE user_name = :user_name AND user_provider_type = :provider_type LIMIT 1";
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute(
            array(
                ':user_password_reset_hash' => $user_password_reset_hash, ':user_name' => $user_name,
                ':user_password_reset_timestamp' => $temporary_timestamp, ':provider_type' => 'DEFAULT'
            )
        );
        if ($stmt->rowCount() == 1) {
            return true;
        }
        Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_RESET_TOKEN_FAIL'));
        return false;
    }
    /**
     * Send the password reset mail
     *
     * @param string $user_name username
     * @param string $user_password_reset_hash password reset hash
     * @param string $user_email user email
     *
     * @return bool success status
     */
    public static function sendPasswordResetMail($user_name, $user_password_reset_hash, $user_email)
    {
        $body = Config::get('EMAIL_PASSWORD_RESET_CONTENT').' '.Config::get('URL').
                Config::get('EMAIL_PASSWORD_RESET_URL').'?user_name='.urlencode($user_name).'&user_password_reset_hash='.urlencode($user_password_reset_hash);
        $mail = new MailSender;
        $mail_sent = $mail->sendMail(
            $user_email, Config::get('EMAIL_PASSWORD_RESET_FROM_EMAIL'),
            Config::get('EMAIL_PASSWORD_RESET_FROM_NAME'), Config::get('EMAIL_PASSWORD_RESET_SUBJECT'), $body
        );
        if ($mail_sent) {
            Session::add('feedback_positive', Text::get('FEEDBACK_PASSWORD_RESET_MAIL_SENDING_SUCCESSFUL'));
            UserActivity::registerUserActivityByUsername($user_name, 'requestPasswordReset');
            return true;
        }
        Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_RESET_MAIL_SENDING_ERROR').$mail->getError());
        return false;
    }

    /**
     * Verifies the password reset request via the verification hash token (that's only valid for one hour)
     * @param string $user_name Username
     * @param string $verification_code Hash token
     * @return bool Success status
     */
    public static function verifyPasswordReset($user_name, $verification_code)
    {
        // check if user-provided username + verification code combination exists
        $sql = "SELECT user_id, user_password_reset_timestamp
                  FROM users
                 WHERE user_name = :user_name
                       AND user_password_reset_hash = :user_password_reset_hash
                       AND user_provider_type = :user_provider_type
                 LIMIT 1";
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute(array(':user_password_reset_hash' => $verification_code, ':user_name' => $user_name, ':user_provider_type' => 'DEFAULT'));
        // if this user with exactly this verification hash code does NOT exist
        if ($stmt->rowCount() != 1) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_RESET_COMBINATION_DOES_NOT_EXIST'));
            return false;
        }
        // get result row (as an object)
        $result_user_row = $stmt->fetch();
        // 3600 seconds are 1 hour
        $timestamp_one_hour_ago = date('Y-m-d H:i:s') - 3600;
        // if password reset request was sent within the last hour (this timeout is for security reasons)
        if ($result_user_row->user_password_reset_timestamp > $timestamp_one_hour_ago) {
              Session::add('feedback_positive', Text::get('FEEDBACK_PASSWORD_RESET_LINK_VALID'));
            return true;
        } else {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_RESET_LINK_EXPIRED'));
            return false;
        }
    }

    /**
     * Writes the new password to the database
     *
     * @param string $user_name username
     * @param string $user_password_hash
     * @param string $user_password_reset_hash
     *
     * @return bool
     */
    public static function saveNewUserPassword($user_name, $user_password_hash, $user_password_reset_hash)
    {
        $sql = "UPDATE users SET user_password_hash = :user_password_hash, user_password_reset_hash = NULL,
                       user_password_reset_timestamp = NULL
                 WHERE user_name = :user_name AND user_password_reset_hash = :user_password_reset_hash
                       AND user_provider_type = :user_provider_type LIMIT 1";
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute(
            array(
                ':user_password_hash' => $user_password_hash, ':user_name' => $user_name,
                ':user_password_reset_hash' => $user_password_reset_hash, ':user_provider_type' => 'DEFAULT'
            )
        );
        return ($stmt->rowCount() == 1 ? true : false);
    }

    /**
     * Set the new password (for DEFAULT user, FACEBOOK-users don't have a password)
     * Please note: At this point the user has already pre-verified via verifyPasswordReset() (within one hour),
     * so we don't need to check again for the 60min-limit here. In this method we authenticate
     * via username & password-reset-hash from (hidden) form fields.
     *
     * @param string $user_name
     * @param string $user_password_reset_hash
     * @param string $user_password_new
     * @param string $user_password_repeat
     *
     * @return bool success state of the password reset
     */
    public static function setNewPassword($user_name, $user_password_reset_hash, $user_password_new, $user_password_repeat)
    {
        // validate the password
        if (!self::validateResetPassword($user_name, $user_password_reset_hash, $user_password_new, $user_password_repeat)) {
            return false;
        }
        // crypt the password (with the PHP 5.5+'s password_hash() function, result is a 60 character hash string)
        $user_password_hash = password_hash($user_password_new, PASSWORD_DEFAULT);
        // write the password to database (as hashed and salted string), reset user_password_reset_hash
        if (self::saveNewUserPassword($user_name, $user_password_hash, $user_password_reset_hash)) {
            Session::add('feedback_positive', Text::get('FEEDBACK_PASSWORD_CHANGE_SUCCESSFUL'));
            return true;
        } else {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_CHANGE_FAILED'));
            return false;
        }
    }

    /**
     * Validate the password submission
     *
     * @param $user_name
     * @param $user_password_reset_hash
     * @param $user_password_new
     * @param $user_password_repeat
     *
     * @return bool
     */
    public static function validateResetPassword($user_name, $user_password_reset_hash, $user_password_new, $user_password_repeat)
    {
        if (empty($user_name)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_USERNAME_FIELD_EMPTY'));
            return false;
        } else if (empty($user_password_reset_hash)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_RESET_TOKEN_MISSING'));
            return false;
        } else if (empty($user_password_new) || empty($user_password_repeat)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_FIELD_EMPTY'));
            return false;
        } else if ($user_password_new !== $user_password_repeat) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_REPEAT_WRONG'));
            return false;
        } else if (strlen($user_password_new) < 6) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_TOO_SHORT'));
            return false;
        }
        return true;
    }
}