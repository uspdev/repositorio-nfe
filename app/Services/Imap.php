<?php

namespace App\Services;

use App\Models\Email;
use PhpImap\Mailbox;

class Imap
{
	/** guarda a conexão imap para reuso (singleton) */
	public static $mailbox = null;

	public static function getMailbox()
	{
		if (SELF::$mailbox == null) {
			$host = config('imap.host');
			$port = config('imap.port');
			$encryption = config('imap.encryption');
			$hostString = '{' . $host . ':' . $port . '/' . $encryption . '/novalidate-cert/imap}INBOX';
			SELF::$mailbox = new Mailbox(
				$hostString,
				config('imap.username'),
				config('imap.password'),
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
		$mailIds = $mailbox->searchMailbox('UNSEEN SINCE "' . $datetime->format('Ymd') . '"');
		$messages = collect();
		foreach ($mailIds as $mailId) {
			$head = $mailbox->getMailHeader($mailId);
			$messages[] = $head;
		}
		return $messages;
	}

	/**
	 * Retorna o conteúdo bruto (raw) de um e-mail pelo ID da caixa IMAP.
	 */
	public static function getRawMail(int $mailId): string
	{
		return (string) self::getMailbox()->getRawMail($mailId);
	}

	/**
	 * Consulta o servidor IMAP por novos e-mails e os armazena no banco de dados.
	 *
	 * @return array{lastMessageDate: \Illuminate\Support\Carbon, totalMessages: int, newMessages: int}
	 */
	public static function verificarNovos(): array
	{
		$lastMessageDate = Email::obterDataUltimoEmail();
		$messages = self::getMessagesSince($lastMessageDate);
		$count = 0;

		foreach ($messages as $message) {
			Email::guardarNovo($message) && $count++;
		}

		self::disconnect();

		return [
			'lastMessageDate' => $lastMessageDate,
			'totalMessages' => $messages->count(),
			'newMessages' => $count,
		];
	}
}
