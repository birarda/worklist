<?php
/**
 * Sms backend interface
 *
 * @package Sms
 * @version $Id$
 */
require_once 'lib/Sms/Message.php';
require_once 'lib/Sms/Backend/Exception.php';
require_once 'lib/Sms/Backend/Email.php';
/**
 * Sms backend interface
 *
 * @package Sms
 */
interface Sms_Backend
{
    public function __construct(Array $options = null);
    public function send(Sms_Message $message);
    public static function getType();
}
