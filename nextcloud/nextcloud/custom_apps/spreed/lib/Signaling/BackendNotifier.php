<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Joachim Bauch <bauch@struktur.de>
 *
 * @author Joachim Bauch <bauch@struktur.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Talk\Signaling;

use OCA\Talk\Config;
use OCA\Talk\Model\Attendee;
use OCA\Talk\Model\Session;
use OCA\Talk\Participant;
use OCA\Talk\Room;
use OCA\Talk\Service\ParticipantService;
use OCP\Http\Client\IClientService;
use OCP\IURLGenerator;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class BackendNotifier {
	/** @var Config */
	private $config;
	/** @var LoggerInterface */
	private $logger;
	/** @var IClientService */
	private $clientService;
	/** @var ISecureRandom */
	private $secureRandom;
	/** @var Manager */
	private $signalingManager;
	/** @var ParticipantService */
	private $participantService;
	/** @var IUrlGenerator */
	private $urlGenerator;

	public function __construct(Config $config,
								LoggerInterface $logger,
								IClientService $clientService,
								ISecureRandom $secureRandom,
								Manager $signalingManager,
								ParticipantService $participantService,
								IURLGenerator $urlGenerator) {
		$this->config = $config;
		$this->logger = $logger;
		$this->clientService = $clientService;
		$this->secureRandom = $secureRandom;
		$this->signalingManager = $signalingManager;
		$this->participantService = $participantService;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * Perform actual network request to the signaling backend.
	 * This can be overridden in tests.
	 *
	 * @param string $url
	 * @param array $params
	 * @throws \Exception
	 */
	protected function doRequest(string $url, array $params): void {
		if (defined('PHPUNIT_RUN')) {
			// Don't perform network requests when running tests.
			return;
		}

		$client = $this->clientService->newClient();
		try {
			$client->post($url, $params);
		} catch (\Exception $e) {
			$this->logger->error('Failed to send message to signaling server', ['exception' => $e]);
		}
	}

	/**
	 * Perform a request to the signaling backend.
	 *
	 * @param Room $room
	 * @param array $data
	 * @throws \Exception
	 */
	private function backendRequest(Room $room, array $data): void {
		if ($this->config->getSignalingMode() === Config::SIGNALING_INTERNAL) {
			return;
		}

		// FIXME some need to go to all HPBs, but that doesn't scale, so bad luck for now :(
		$signaling = $this->signalingManager->getSignalingServerForConversation($room);
		$signaling['server'] = rtrim($signaling['server'], '/');

		$url = '/api/v1/room/' . $room->getToken();
		$url = $signaling['server'] . $url;
		if (strpos($url, 'wss://') === 0) {
			$url = 'https://' . substr($url, 6);
		} elseif (strpos($url, 'ws://') === 0) {
			$url = 'http://' . substr($url, 5);
		}
		$body = json_encode($data);
		$headers = [
			'Content-Type' => 'application/json',
		];

		$random = $this->secureRandom->generate(64);
		$hash = hash_hmac('sha256', $random . $body, $this->config->getSignalingSecret());
		$headers['Spreed-Signaling-Random'] = $random;
		$headers['Spreed-Signaling-Checksum'] = $hash;
		$headers['Spreed-Signaling-Backend'] = $this->urlGenerator->getBaseUrl();

		$params = [
			'headers' => $headers,
			'body' => $body,
			'nextcloud' => [
				'allow_local_address' => true,
			],
		];
		if (empty($signaling['verify'])) {
			$params['verify'] = false;
		}
		$this->doRequest($url, $params);
	}

	/**
	 * The given users are now invited to a room.
	 *
	 * @param Room $room
	 * @param array[] $users
	 * @throws \Exception
	 */
	public function roomInvited(Room $room, array $users): void {
		$userIds = [];
		foreach ($users as $user) {
			if ($user['actorType'] === Attendee::ACTOR_USERS) {
				$userIds[] = $user['actorId'];
			}
		}
		$start = microtime(true);
		$this->backendRequest($room, [
			'type' => 'invite',
			'invite' => [
				'userids' => $userIds,
				// TODO(fancycode): We should try to get rid of 'alluserids' and
				// find a better way to notify existing users to update the room.
				'alluserids' => $this->participantService->getParticipantUserIds($room),
				'properties' => $room->getPropertiesForSignaling('', false),
			],
		]);
		$duration = microtime(true) - $start;
		$this->logger->debug('Now invited to {token}: {users} ({duration})', [
			'token' => $room->getToken(),
			'users' => print_r($users, true),
			'duration' => sprintf('%.2f', $duration),
			'app' => 'spreed-hpb',
		]);
	}

	/**
	 * The given users are no longer invited to a room.
	 *
	 * @param Room $room
	 * @param string[] $userIds
	 * @throws \Exception
	 */
	public function roomsDisinvited(Room $room, array $userIds): void {
		$start = microtime(true);
		$this->backendRequest($room, [
			'type' => 'disinvite',
			'disinvite' => [
				'userids' => $userIds,
				// TODO(fancycode): We should try to get rid of 'alluserids' and
				// find a better way to notify existing users to update the room.
				'alluserids' => $this->participantService->getParticipantUserIds($room),
				'properties' => $room->getPropertiesForSignaling('', false),
			],
		]);
		$duration = microtime(true) - $start;
		$this->logger->debug('No longer invited to {token}: {users} ({duration})', [
			'token' => $room->getToken(),
			'users' => print_r($userIds, true),
			'duration' => sprintf('%.2f', $duration),
			'app' => 'spreed-hpb',
		]);
	}

	/**
	 * The given sessions have been removed from a room.
	 *
	 * @param Room $room
	 * @param string[] $sessionIds
	 * @throws \Exception
	 */
	public function roomSessionsRemoved(Room $room, array $sessionIds): void {
		$start = microtime(true);
		$this->backendRequest($room, [
			'type' => 'disinvite',
			'disinvite' => [
				'sessionids' => $sessionIds,
				// TODO(fancycode): We should try to get rid of 'alluserids' and
				// find a better way to notify existing users to update the room.
				'alluserids' => $this->participantService->getParticipantUserIds($room),
				'properties' => $room->getPropertiesForSignaling('', false),
			],
		]);
		$duration = microtime(true) - $start;
		$this->logger->debug('Removed from {token}: {users} ({duration})', [
			'token' => $room->getToken(),
			'users' => print_r($sessionIds, true),
			'duration' => sprintf('%.2f', $duration),
			'app' => 'spreed-hpb',
		]);
	}

	/**
	 * The given room has been modified.
	 *
	 * @param Room $room
	 * @throws \Exception
	 */
	public function roomModified(Room $room): void {
		$start = microtime(true);
		$this->backendRequest($room, [
			'type' => 'update',
			'update' => [
				'userids' => $this->participantService->getParticipantUserIds($room),
				'properties' => $room->getPropertiesForSignaling(''),
			],
		]);
		$duration = microtime(true) - $start;
		$this->logger->debug('Room modified: {token} ({duration})', [
			'token' => $room->getToken(),
			'duration' => sprintf('%.2f', $duration),
			'app' => 'spreed-hpb',
		]);
	}

	/**
	 * The given room has been deleted.
	 *
	 * @param Room $room
	 * @param string[] $userIds
	 * @throws \Exception
	 */
	public function roomDeleted(Room $room, array $userIds): void {
		$start = microtime(true);
		$this->backendRequest($room, [
			'type' => 'delete',
			'delete' => [
				'userids' => $userIds,
			],
		]);
		$duration = microtime(true) - $start;
		$this->logger->debug('Room deleted: {token} ({duration})', [
			'token' => $room->getToken(),
			'duration' => sprintf('%.2f', $duration),
			'app' => 'spreed-hpb',
		]);
	}

	/**
	 * The participant list of the given room has been modified.
	 *
	 * @param Room $room
	 * @param string[] $sessionIds
	 * @throws \Exception
	 */
	public function participantsModified(Room $room, array $sessionIds): void {
		$changed = [];
		$users = [];
		$participants = $this->participantService->getSessionsAndParticipantsForRoom($room);
		foreach ($participants as $participant) {
			$attendee = $participant->getAttendee();
			if ($attendee->getActorType() !== Attendee::ACTOR_USERS
				&& $attendee->getActorType() !== Attendee::ACTOR_GUESTS) {
				continue;
			}

			$data = [
				'inCall' => Participant::FLAG_DISCONNECTED,
				'lastPing' => 0,
				'sessionId' => '0',
				'participantType' => $attendee->getParticipantType(),
				'publishingPermissions' => Attendee::PUBLISHING_PERMISSIONS_NONE,
			];
			if ($attendee->getActorType() === Attendee::ACTOR_USERS) {
				$data['userId'] = $attendee->getActorId();
			}

			$session = $participant->getSession();
			if ($session instanceof Session) {
				$data['inCall'] = $session->getInCall();
				$data['lastPing'] = $session->getLastPing();
				$data['sessionId'] = $session->getSessionId();
				$data['publishingPermissions'] = $attendee->getPublishingPermissions();
				$users[] = $data;

				if (\in_array($session->getSessionId(), $sessionIds, true)) {
					$data['permissions'] = [];
					if ($attendee->getPublishingPermissions() & (Attendee::PUBLISHING_PERMISSIONS_AUDIO | Attendee::PUBLISHING_PERMISSIONS_VIDEO)) {
						$data['permissions'][] = 'publish-media';
					}
					if ($attendee->getPublishingPermissions() & Attendee::PUBLISHING_PERMISSIONS_SCREENSHARING) {
						$data['permissions'][] = 'publish-screen';
					}
					if ($attendee->getParticipantType() === Participant::OWNER ||
						$attendee->getParticipantType() === Participant::MODERATOR) {
						$data['permissions'][] = 'control';
					}
					$changed[] = $data;
				}
			} else {
				$users[] = $data;
			}
		}

		$start = microtime(true);
		$this->backendRequest($room, [
			'type' => 'participants',
			'participants' => [
				'changed' => $changed,
				'users' => $users
			],
		]);
		$duration = microtime(true) - $start;
		$this->logger->debug('Room participants modified: {token} {users} ({duration})', [
			'token' => $room->getToken(),
			'users' => print_r($sessionIds, true),
			'duration' => sprintf('%.2f', $duration),
			'app' => 'spreed-hpb',
		]);
	}

	/**
	 * The "in-call" status of the given session ids has changed..
	 *
	 * @param Room $room
	 * @param int $flags
	 * @param string[] $sessionIds
	 * @throws \Exception
	 */
	public function roomInCallChanged(Room $room, int $flags, array $sessionIds): void {
		$changed = [];
		$users = [];

		$participants = $this->participantService->getParticipantsForAllSessions($room);
		foreach ($participants as $participant) {
			$session = $participant->getSession();
			if (!$session instanceof Session) {
				continue;
			}

			$attendee = $participant->getAttendee();
			if ($attendee->getActorType() !== Attendee::ACTOR_USERS
				&& $attendee->getActorType() !== Attendee::ACTOR_GUESTS) {
				continue;
			}

			$data = [
				'inCall' => $session->getInCall(),
				'lastPing' => $session->getLastPing(),
				'sessionId' => $session->getSessionId(),
				'nextcloudSessionId' => $session->getSessionId(),
				'participantType' => $attendee->getParticipantType(),
			];
			if ($attendee->getActorType() === Attendee::ACTOR_USERS) {
				$data['userId'] = $attendee->getActorId();
			}

			if ($session->getInCall() !== Participant::FLAG_DISCONNECTED) {
				$users[] = $data;
			}

			if (\in_array($session->getSessionId(), $sessionIds, true)) {
				$changed[] = $data;
			}
		}

		$start = microtime(true);
		$this->backendRequest($room, [
			'type' => 'incall',
			'incall' => [
				'incall' => $flags,
				'changed' => $changed,
				'users' => $users
			],
		]);
		$duration = microtime(true) - $start;
		$this->logger->debug('Room in-call status changed: {token} {flags} {users} ({duration})', [
			'token' => $room->getToken(),
			'flags' => $flags,
			'users' => print_r($sessionIds, true),
			'duration' => sprintf('%.2f', $duration),
			'app' => 'spreed-hpb',
		]);
	}

	/**
	 * Send a message to all sessions currently joined in a room. The message
	 * will be received by "processRoomMessageEvent" in "signaling.js".
	 *
	 * @param Room $room
	 * @param array $message
	 * @throws \Exception
	 */
	public function sendRoomMessage(Room $room, array $message): void {
		$start = microtime(true);
		$this->backendRequest($room, [
			'type' => 'message',
			'message' => [
				'data' => $message,
			],
		]);
		$duration = microtime(true) - $start;
		$this->logger->debug('Send room message: {token} {message} ({duration})', [
			'token' => $room->getToken(),
			'message' => $message,
			'duration' => sprintf('%.2f', $duration),
			'app' => 'spreed-hpb',
		]);
	}
}
