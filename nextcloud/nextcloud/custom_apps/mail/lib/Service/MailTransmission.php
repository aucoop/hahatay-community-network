<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service;

use Horde_Exception;
use Horde_Imap_Client;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_DateTime;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Ids;
use Horde_Mail_Transport_Null;
use Horde_Mime_Exception;
use Horde_Mime_Headers;
use Horde_Mime_Headers_Addresses;
use Horde_Mime_Headers_Date;
use Horde_Mime_Headers_MessageId;
use Horde_Mime_Headers_Subject;
use Horde_Mime_Mail;
use Horde_Mime_Mdn;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Events\DraftSavedEvent;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Events\SaveDraftEvent;
use OCA\Mail\Exception\AttachmentNotFoundException;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\SentMailboxNotSetException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Model\IMessage;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCA\Mail\Support\PerformanceLogger;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Folder;
use Psr\Log\LoggerInterface;

class MailTransmission implements IMailTransmission {

	/** @var AccountService */
	private $accountService;

	/** @var Folder */
	private $userFolder;

	/** @var IAttachmentService */
	private $attachmentService;

	/** @var IMailManager */
	private $mailManager;

	/** @var IMAPClientFactory */
	private $imapClientFactory;

	/** @var SmtpClientFactory */
	private $smtpClientFactory;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var LoggerInterface */
	private $logger;

	/** @var PerformanceLogger */
	private $performanceLogger;

	/**
	 * @param Folder $userFolder
	 */
	public function __construct($userFolder,
								AccountService $accountService,
								IAttachmentService $attachmentService,
								IMailManager $mailManager,
								IMAPClientFactory $imapClientFactory,
								SmtpClientFactory $smtpClientFactory,
								IEventDispatcher $eventDispatcher,
								MailboxMapper $mailboxMapper,
								MessageMapper $messageMapper,
								LoggerInterface $logger,
								PerformanceLogger $performanceLogger) {
		$this->accountService = $accountService;
		$this->userFolder = $userFolder;
		$this->attachmentService = $attachmentService;
		$this->mailManager = $mailManager;
		$this->imapClientFactory = $imapClientFactory;
		$this->smtpClientFactory = $smtpClientFactory;
		$this->eventDispatcher = $eventDispatcher;
		$this->mailboxMapper = $mailboxMapper;
		$this->messageMapper = $messageMapper;
		$this->logger = $logger;
		$this->performanceLogger = $performanceLogger;
	}

	public function sendMessage(NewMessageData $messageData,
								RepliedMessageData $replyData = null,
								Alias $alias = null,
								Message $draft = null): void {
		$account = $messageData->getAccount();
		if ($account->getMailAccount()->getSentMailboxId() === null) {
			throw new SentMailboxNotSetException();
		}

		if ($replyData !== null) {
			$message = $this->buildReplyMessage($account, $messageData, $replyData);
		} else {
			$message = $this->buildNewMessage($account, $messageData);
		}

		$account->setAlias($alias);
		$fromEmail = $alias ? $alias->getAlias() : $account->getEMailAddress();
		$from = new AddressList([
			Address::fromRaw($account->getName(), $fromEmail),
		]);
		$message->setFrom($from);
		$message->setCC($messageData->getCc());
		$message->setBcc($messageData->getBcc());
		$message->setContent($messageData->getBody());
		$this->handleAttachments($account, $messageData, $message);

		$transport = $this->smtpClientFactory->create($account);
		// build mime body
		$headers = [
			'From' => $message->getFrom()->first()->toHorde(),
			'To' => $message->getTo()->toHorde(),
			'Cc' => $message->getCC()->toHorde(),
			'Bcc' => $message->getBCC()->toHorde(),
			'Subject' => $message->getSubject(),
		];

		if (($inReplyTo = $message->getInReplyTo()) !== null) {
			$headers['References'] = $inReplyTo;
			$headers['In-Reply-To'] = $inReplyTo;
		}

		if ($messageData->isMdnRequested()) {
			$headers[Horde_Mime_Mdn::MDN_HEADER] = $message->getFrom()->first()->toHorde();
		}

		$mail = new Horde_Mime_Mail();
		$mail->addHeaders($headers);
		if ($messageData->isHtml()) {
			$mail->setHtmlBody($message->getContent());
		} else {
			$mail->setBody($message->getContent());
		}

		// Append local attachments
		foreach ($message->getAttachments() as $attachment) {
			$mail->addMimePart($attachment);
		}

		// Send the message
		try {
			$mail->send($transport, false, false);
		} catch (Horde_Mime_Exception $e) {
			throw new ServiceException(
				'Could not send message: ' . $e->getMessage(),
				(int) $e->getCode(),
				$e
			);
		}

		$this->eventDispatcher->dispatch(
			MessageSentEvent::class,
			new MessageSentEvent($account, $messageData, $replyData, $draft, $message, $mail)
		);
	}

	/**
	 * @param NewMessageData $message
	 * @param Message|null $previousDraft
	 *
	 * @return array
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function saveDraft(NewMessageData $message, Message $previousDraft = null): array {
		$perfLogger = $this->performanceLogger->start('save draft');
		$this->eventDispatcher->dispatch(
			SaveDraftEvent::class,
			new SaveDraftEvent($message->getAccount(), $message, $previousDraft)
		);
		$perfLogger->step('emit pre event');

		$account = $message->getAccount();
		$imapMessage = $account->newMessage();
		$imapMessage->setTo($message->getTo());
		$imapMessage->setSubject($message->getSubject());
		$from = new AddressList([
			Address::fromRaw($account->getName(), $account->getEMailAddress()),
		]);
		$imapMessage->setFrom($from);
		$imapMessage->setCC($message->getCc());
		$imapMessage->setBcc($message->getBcc());
		$imapMessage->setContent($message->getBody());

		// build mime body
		$headers = [
			'From' => $imapMessage->getFrom()->first()->toHorde(),
			'To' => $imapMessage->getTo()->toHorde(),
			'Cc' => $imapMessage->getCC()->toHorde(),
			'Bcc' => $imapMessage->getBCC()->toHorde(),
			'Subject' => $imapMessage->getSubject(),
			'Date' => Horde_Mime_Headers_Date::create(),
		];

		$mail = new Horde_Mime_Mail();
		$mail->addHeaders($headers);
		if ($message->isHtml()) {
			$mail->setHtmlBody($imapMessage->getContent());
		} else {
			$mail->setBody($imapMessage->getContent());
		}
		$mail->addHeaderOb(Horde_Mime_Headers_MessageId::create());
		$perfLogger->step('build draft message');

		// 'Send' the message
		try {
			$transport = new Horde_Mail_Transport_Null();
			$mail->send($transport, false, false);
			$perfLogger->step('create IMAP message');
			// save the message in the drafts folder
			$client = $this->imapClientFactory->getClient($account);
			$draftsMailboxId = $account->getMailAccount()->getDraftsMailboxId();
			if ($draftsMailboxId === null) {
				throw new ClientException("No drafts mailbox configured");
			}
			$draftsMailbox = $this->mailboxMapper->findById($draftsMailboxId);
			$newUid = $this->messageMapper->save(
				$client,
				$draftsMailbox,
				$mail,
				[Horde_Imap_Client::FLAG_DRAFT]
			);
			$perfLogger->step('save message on IMAP');
		} catch (DoesNotExistException $e) {
			throw new ServiceException('Drafts mailbox does not exist', 0, $e);
		} catch (Horde_Exception $e) {
			throw new ServiceException('Could not save draft message', 0, $e);
		}

		$this->eventDispatcher->dispatch(
			DraftSavedEvent::class,
			new DraftSavedEvent($account, $message, $previousDraft)
		);
		$perfLogger->step('emit post event');

		$perfLogger->end();
		return [$account, $draftsMailbox, $newUid];
	}

	private function buildReplyMessage(Account $account,
									   NewMessageData $messageData,
									   RepliedMessageData $replyData): IMessage {
		// Reply
		$message = $account->newMessage();
		$message->setSubject($messageData->getSubject());
		$message->setTo($messageData->getTo());

		$rawMessageId = $replyData->getMessage()->getMessageId();
		$message->setInReplyTo($rawMessageId);

		return $message;
	}

	private function buildNewMessage(Account $account, NewMessageData $messageData): IMessage {
		// New message
		$message = $account->newMessage();
		$message->setTo($messageData->getTo());
		$message->setSubject($messageData->getSubject());

		return $message;
	}

	/**
	 * @param Account $account
	 * @param NewMessageData $messageData
	 * @param IMessage $message
	 *
	 * @return void
	 */
	private function handleAttachments(Account $account, NewMessageData $messageData, IMessage $message): void {
		foreach ($messageData->getAttachments() as $attachment) {
			if (isset($attachment['type']) && $attachment['type'] === 'local') {
				// Adds an uploaded attachment
				$this->handleLocalAttachment($account, $attachment, $message);
			} elseif (isset($attachment['type']) && $attachment['type'] === 'message') {
				// Adds another message as attachment
				$this->handleForwardedMessageAttachment($account, $attachment, $message);
			} elseif (isset($attachment['type']) && $attachment['type'] === 'message-attachment') {
				// Adds an attachment from another email (use case is, eg., a mail forward)
				$this->handleForwardedAttachment($account, $attachment, $message);
			} else {
				// Adds an attachment from Files
				$this->handleCloudAttachment($attachment, $message);
			}
		}
	}

	/**
	 * @param Account $account
	 * @param array $attachment
	 * @param IMessage $message
	 *
	 * @return int|null
	 */
	private function handleLocalAttachment(Account $account, array $attachment, IMessage $message) {
		if (!isset($attachment['id'])) {
			$this->logger->warning('ignoring local attachment because its id is unknown');
			return null;
		}

		$id = (int)$attachment['id'];

		try {
			[$localAttachment, $file] = $this->attachmentService->getAttachment($account->getMailAccount()->getUserId(), $id);
			$message->addLocalAttachment($localAttachment, $file);
		} catch (AttachmentNotFoundException $ex) {
			$this->logger->warning('ignoring local attachment because it does not exist');
			// TODO: rethrow?
			return null;
		}
	}

	/**
	 * Adds an attachment that's coming from another message's attachment (typical use case: email forwarding)
	 *
	 * @param Account $account
	 * @param mixed[] $attachment
	 * @param IMessage $message
	 */
	private function handleForwardedMessageAttachment(Account $account, array $attachment, IMessage $message): void {
		// Gets original of other message
		$userId = $account->getMailAccount()->getUserId();
		$attachmentMessage = $this->mailManager->getMessage($userId, (int)$attachment['id']);
		$mailbox = $this->mailManager->getMailbox($userId, $attachmentMessage->getMailboxId());

		$fullText = $this->messageMapper->getFullText(
			$this->imapClientFactory->getClient($account),
			$mailbox->getName(),
			$attachmentMessage->getUid()
		);

		$message->addRawAttachment(
			$attachment['displayName'] ?? $attachmentMessage->getSubject() . '.eml',
			$fullText
		);
	}

	/**
	 * Adds an attachment that's coming from another message's attachment (typical use case: email forwarding)
	 *
	 * @param Account $account
	 * @param mixed[] $attachment
	 * @param IMessage $message
	 */
	private function handleForwardedAttachment(Account $account, array $attachment, IMessage $message): void {
		// Gets attachment from other message
		$userId = $account->getMailAccount()->getUserId();
		$attachmentMessage = $this->mailManager->getMessage($userId, (int)$attachment['messageId']);
		$mailbox = $this->mailManager->getMailbox($userId, $attachmentMessage->getMailboxId());
		$attachments = $this->messageMapper->getRawAttachments(
			$this->imapClientFactory->getClient($account),
			$mailbox->getName(),
			$attachmentMessage->getUid(),
			[
				$attachment['id']
			]
		);

		// Attaches attachment to new message
		$message->addRawAttachment($attachment['fileName'], $attachments[0]);
	}

	/**
	 * @param array $attachment
	 * @param IMessage $message
	 *
	 * @return File|null
	 */
	private function handleCloudAttachment(array $attachment, IMessage $message) {
		if (!isset($attachment['fileName'])) {
			$this->logger->warning('ignoring cloud attachment because its fileName is unknown');
			return null;
		}

		$fileName = $attachment['fileName'];
		if (!$this->userFolder->nodeExists($fileName)) {
			$this->logger->warning('ignoring cloud attachment because the node does not exist');
			return null;
		}

		$file = $this->userFolder->get($fileName);
		if (!$file instanceof File) {
			$this->logger->warning('ignoring cloud attachment because the node is not a file');
			return null;
		}

		$message->addAttachmentFromFiles($file);
	}

	public function sendMdn(Account $account, Mailbox $mailbox, Message $message): void {
		$imapClient = $this->imapClientFactory->getClient($account);

		$query = new Horde_Imap_Client_Fetch_Query();
		$query->flags();
		$query->uid();
		$query->imapDate();
		$query->headerText([
			'cache' => true,
			'peek' => true,
		]);

		/** @var Horde_Imap_Client_Data_Fetch[] $fetchResults */
		$fetchResults = iterator_to_array($imapClient->fetch($mailbox->getName(), $query, [
			'ids' => new Horde_Imap_Client_Ids([$message->getUid()]),
		]), false);

		if (count($fetchResults) < 1) {
			throw new ServiceException('Message "' .$message->getId() . '" not found.');
		}

		/** @var Horde_Imap_Client_DateTime $imapDate */
		$imapDate = $fetchResults[0]->getImapDate();
		/** @var Horde_Mime_Headers $headers */
		$mdnHeaders = $fetchResults[0]->getHeaderText('0', Horde_Imap_Client_Data_Fetch::HEADER_PARSE);
		/** @var Horde_Mime_Headers_Addresses|null $dispositionNotificationTo */
		$dispositionNotificationTo = $mdnHeaders->getHeader('disposition-notification-to');
		/** @var Horde_Mime_Headers_Addresses|null $originalRecipient */
		$originalRecipient = $mdnHeaders->getHeader('original-recipient');

		if ($dispositionNotificationTo === null) {
			throw new ServiceException('Message "' .$message->getId() . '" has no disposition-notification-to header.');
		}

		$headers = new Horde_Mime_Headers();
		$headers->addHeaderOb($dispositionNotificationTo);

		if ($originalRecipient instanceof Horde_Mime_Headers_Addresses) {
			$headers->addHeaderOb($originalRecipient);
		}

		$headers->addHeaderOb(new Horde_Mime_Headers_Subject(null, $message->getSubject()));
		$headers->addHeaderOb(new Horde_Mime_Headers_Addresses('From', $message->getFrom()->toHorde()));
		$headers->addHeaderOb(new Horde_Mime_Headers_Addresses('To', $message->getTo()->toHorde()));
		$headers->addHeaderOb(new Horde_Mime_Headers_MessageId(null, $message->getMessageId()));
		$headers->addHeaderOb(new Horde_Mime_Headers_Date(null, $imapDate->format('r')));

		$smtpClient = $this->smtpClientFactory->create($account);

		$mdn = new Horde_Mime_Mdn($headers);
		try {
			$mdn->generate(
				true,
				true,
				'displayed',
				$account->getMailAccount()->getOutboundHost(),
				$smtpClient,
				[
					'from_addr' => $account->getEMailAddress(),
					'charset' => 'UTF-8',
				]
			);
		} catch (Horde_Mime_Exception $e) {
			throw new ServiceException('Unable to send mdn for message "' . $message->getId() . '" caused by: ' . $e->getMessage(), 0, $e);
		}
	}
}
