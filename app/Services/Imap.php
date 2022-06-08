<?php

namespace App\Services;

use PhpImap\Mailbox;

class Imap
{
	/** guarda a conexão imap para reuso (singleton) */
	public static $mailbox = null;

	public static function getMailbox()
	{
		if (SELF::$mailbox == null) {
			SELF::$mailbox = new Mailbox(
				'{imap.gmail.com:993/ssl/novalidate-cert/imap}INBOX',
				'delos-nfe@eesc.usp.br',
				'Limc2932()',
				false // when $attachmentsDir is false we don't save attachments
			);
		}
		return SELF::$mailbox;
	}

	public static function disconnect()
	{
		if (SELF::$mailbox != null) {
			SELF::$mailbox->disconnect();
			SELF::$mailbox = null;
		}
	}

	/**
	 * Retorna mensagens desde $datetime
	 * 
	 * @param \Datetime $datetime
	 * @return Collection
	 */
	public static function getMessagesSince(\Datetime $datetime)
	{
		$mailbox = SELF::getMailbox();
		$mailIds = $mailbox->searchMailbox('SINCE "' . $datetime->format('Ymd') . '"');
		$messages = collect();
		foreach ($mailIds as $mailId) {
			$head = $mailbox->getMailHeader($mailId);
			$messages[] = $head;
		}
		return $messages;
	}
}
