<?php
/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2013
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2013, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

class Mailer extends CComponent
{
    /**
     * Can be mail, smtp, sendmail. If empty then mail is disabled and messages are dropped silently
     * @var string
     */
    public $mode;

    /**
     * Addresses to which we should divert emails to. If empty then no diversion.
     * @var array
     */
    public $divert = array();

    public $sendmail_command = '/usr/sbin/sendmail -bs';

    /**
     * Configuration for SMTP
     */
    public $host;
    public $port = 25;

    /**
     * SSL or TLS
     */
    public $security;
    public $username;
    public $password;

    protected $_transport;
    protected $_mailer;

    /**
     * Initialise the component by pulling in the appropriate SwiftMailer classes
     */
    public function init()
    {
        spl_autoload_unregister(array('YiiBase', 'autoload'));
        require_once(Yii::getPathOfAlias('application.vendors.SwiftMailer') . '/swift_required.php');
        spl_autoload_register(array('YiiBase', 'autoload'));
    }

    /**
     * return the transport object for the configured mail type
     *
     * @throws Exception
     * @return Transport object
     */
    protected function getTransport()
    {
        if (!$this->_transport && $this->mode) {
            if ($this->mode == 'sendmail') {
                $this->_transport = Swift_SendmailTransport::newInstance($this->sendmail_command);
            } elseif ($this->mode == 'smtp') {
                $this->_transport = Swift_SmtpTransport::newInstance($this->host, $this->port);
                if ($this->security) {
                    $this->setEncryption($this->security);
                }
                if ($this->username) {
                    $this->setUsername($this->username);
                }
                if ($this->password) {
                    $this->setPassword($this->password);
                }
            } elseif ($this->mode == 'mail') {
                $this->_transport = Swift_MailTransport::newInstance();
            } else {
                throw new CException('Unrecognised email mode ' . $this->mode);
            }
        }

        return $this->_transport;
    }

    /**
     * Get the SwiftMailer object with the configured transport
     * @return Swift_Mailer
     */
    protected function getMailer()
    {
        if (!$this->_mailer && $this->mode) {
            $this->_mailer = Swift_Mailer::newInstance($this->getTransport());
        }
        return $this->_mailer;
    }

    /**
     * Instantiate an appropriate SwiftMailer email message object
     * @return Swift_Message
     */
    public function newMessage()
    {
        return Swift_Message::newInstance();
    }

    /**
     * If we deem a mail address to be insecure, we should censor the message, otherwise returns intact
     * @param Swift_Message $message
     * @return Swift_Message
     */
    protected function censorMessage($message)
    {
        if ($this->recipientForbidden($message)) {
            $message->setBody("This message was generated by the OpenEyes instance at: " . Yii::app()->getBaseUrl(true) . "/\n\n"
                . "The content has been removed as this email address is deemed insecure.\n\n"
                . "Please log into OpenEyes to view your messages.");
            $message->setChildren(array());
        }
        return $message;
    }

    /**
     * Sends a message to the recipient, censors if they are forbidden
     * @param Swift_Message $message
     * @return bool
     */
    protected function directlySendMessage($message)
    {
        $mailer = $this->getMailer();
        if ($mailer) {
            Yii::trace("Sending message to: " . print_r($message->getTo(), true), 'oe.Mailer');
            $message = $this->censorMessage($message);
            return $mailer->send($message);
        } else {
            Yii::log("No mailer configured, message sending suppressed");
            return true;
        }
    }

    /**
     * Diverts an email from its original destination. Useful for testing things in nearlive
     * @param Swift_Message $message
     * @return bool
     */
    protected function divertMessage($message)
    {
        $orig_rcpts = implode(', ', array_keys($message->getTo()));
        $message->setBody("!! OpenEyes Mailer: Original recipients: $orig_rcpts\n\n" . $message->getBody());
        Yii::log("Diverting message from: $orig_rcpts, to: " . implode(', ', $this->divert));
        $message->setTo($this->divert);
        return $this->directlySendMessage($message);
    }

    /**
     * Send an email
     * @param Swift_Message $message
     * @return bool
     */
    public function sendMessage($message)
    {
        if (!empty($this->divert)) {
            return $this->divertMessage($message);
        } else {
            return $this->directlySendMessage($message);
        }
    }

    /**
     * Checks the email recipients are in domains that are allowed.
     * @param Swift_Message $message
     * @return bool
     */
    protected function recipientForbidden($message)
    {
        if (!empty(Yii::app()->params['restrict_email_domains'])) {
            $to = $message->getTo();
            $cc = $message->getCc();
            $bcc = $message->getBcc();
            $to = ($to ? $to : array());
            $cc = ($cc ? $cc : array());
            $bcc = ($bcc ? $bcc : array());
            $addresses = array_merge($to, $cc, $bcc);
            foreach ($addresses as $email => $name) {
                $domain = preg_replace('/^.*?@/', '', $email);
                if (!in_array($domain, Yii::app()->params['restrict_email_domains'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Mailer:mail is intended as a more robust simple replacement for php mail(),
     * @param array $to address eg array('helpdesk@example.com'=>'OpenEyes')
     * @param string $subject
     * @param string $body
     * @param array $from address eg array('helpdesk@example.com'=>'OpenEyes')
     * @return bool mail sent without error
     */
    public static function mail($to, $subject, $body, $from)
    {
        try {
            $message = Yii::app()->mailer->newMessage();
            $message->setSubject($subject);
            $message->setFrom($from);
            $message->setTo($to);
            $message->setBody($body);
            Yii::app()->mailer->sendMessage($message);
        } catch (Exception $Exception) {
            OELog::logException($Exception);
            return false;
        }
        return true;
    }
}
