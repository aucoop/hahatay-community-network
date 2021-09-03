<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
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

namespace OCA\Talk\Activity;

use OCA\Talk\Chat\ChatManager;
use OCA\Talk\Events\AddParticipantsEvent;
use OCA\Talk\Events\ModifyParticipantEvent;
use OCA\Talk\Events\RoomEvent;
use OCA\Talk\Model\Attendee;
use OCA\Talk\Participant;
use OCA\Talk\Room;
use OCA\Talk\Service\ParticipantService;
use OCP\Activity\IManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class Listener {

	/** @var IManager */
	protected $activityManager;

	/** @var IUserSession */
	protected $userSession;

	/** @var ChatManager */
	protected $chatManager;

	/** @var ParticipantService */
	protected $participantService;

	/** @var LoggerInterface */
	protected $logger;

	/** @var ITimeFactory */
	protected $timeFactory;

	public function __construct(IManager $activityManager,
								IUserSession $userSession,
								ChatManager $chatManager,
								ParticipantService $participantService,
								LoggerInterface $logger,
								ITimeFactory $timeFactory) {
		$this->activityManager = $activityManager;
		$this->userSession = $userSession;
		$this->chatManager = $chatManager;
		$this->participantService = $participantService;
		$this->logger = $logger;
		$this->timeFactory = $timeFactory;
	}

	public static function register(IEventDispatcher $dispatcher): void {
		$listener = static function (ModifyParticipantEvent $event) {
			/** @var self $listener */
			$listener = \OC::$server->query(self::class);
			$listener->setActive($event->getRoom(), $event->getParticipant());
		};
		$dispatcher->addListener(Room::EVENT_AFTER_SESSION_JOIN_CALL, $listener);

		$listener = static function (RoomEvent $event) {
			/** @var self $listener */
			$listener = \OC::$server->query(self::class);
			$listener->generateCallActivity($event->getRoom());
		};
		$dispatcher->addListener(Room::EVENT_AFTER_PARTICIPANT_REMOVE, $listener);
		$dispatcher->addListener(Room::EVENT_AFTER_USER_REMOVE, $listener);
		$dispatcher->addListener(Room::EVENT_AFTER_SESSION_LEAVE_CALL, $listener, -100);
		$dispatcher->addListener(Room::EVENT_AFTER_ROOM_DISCONNECT, $listener, -100);

		$listener = static function (AddParticipantsEvent $event) {
			/** @var self $listener */
			$listener = \OC::$server->query(self::class);
			$listener->generateInvitationActivity($event->getRoom(), $event->getParticipants());
		};
		$dispatcher->addListener(Room::EVENT_AFTER_USERS_ADD, $listener);
	}

	public function setActive(Room $room, Participant $participant): void {
		$room->setActiveSince(
			$this->timeFactory->getDateTime(),
			$participant->getSession() ? $participant->getSession()->getInCall() : Participant::FLAG_DISCONNECTED,
			$participant->getAttendee()->getActorType() !== Attendee::ACTOR_USERS
		);
	}

	/**
	 * Call activity: "You attended a call with {user1} and {user2}"
	 *
	 * @param Room $room
	 * @return bool True if activity was generated, false otherwise
	 */
	public function generateCallActivity(Room $room): bool {
		$activeSince = $room->getActiveSince();
		if (!$activeSince instanceof \DateTime || $this->participantService->hasActiveSessionsInCall($room)) {
			return false;
		}

		$duration = $this->timeFactory->getTime() - $activeSince->getTimestamp();
		$userIds = $this->participantService->getParticipantUserIds($room, $activeSince);
		$numGuests = $this->participantService->getGuestCount($room, $activeSince);

		$message = 'call_ended';
		if ((\count($userIds) + $numGuests) === 1) {
			if ($room->getType() !== Room::ONE_TO_ONE_CALL) {
				// Single user pinged or guests only => no summary/activity
				$room->resetActiveSince();
				return false;
			}
			$message = 'call_missed';
		}

		if (!$room->resetActiveSince()) {
			// Race-condition, the room was already reset.
			return false;
		}

		$actorId = $userIds[0] ?? 'guests-only';
		$actorType = $actorId !== 'guests-only' ? Attendee::ACTOR_USERS : Attendee::ACTOR_GUESTS;
		$this->chatManager->addSystemMessage($room, $actorType, $actorId, json_encode([
			'message' => $message,
			'parameters' => [
				'users' => $userIds,
				'guests' => $numGuests,
				'duration' => $duration,
			],
		]), $this->timeFactory->getDateTime(), false);

		if (empty($userIds)) {
			return false;
		}

		$event = $this->activityManager->generateEvent();
		try {
			$event->setApp('spreed')
				->setType('spreed')
				->setAuthor('')
				->setObject('room', $room->getId())
				->setTimestamp($this->timeFactory->getTime())
				->setSubject('call', [
					'room' => $room->getId(),
					'users' => $userIds,
					'guests' => $numGuests,
					'duration' => $duration,
				]);
		} catch (\InvalidArgumentException $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return false;
		}

		foreach ($userIds as $userId) {
			try {
				$event->setAffectedUser($userId);
				$this->activityManager->publish($event);
			} catch (\BadMethodCallException $e) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
			} catch (\InvalidArgumentException $e) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
			}
		}

		return true;
	}

	/**
	 * Invitation activity: "{actor} invited you to {call}"
	 *
	 * @param Room $room
	 * @param array[] $participants
	 */
	public function generateInvitationActivity(Room $room, array $participants): void {
		$actor = $this->userSession->getUser();
		if (!$actor instanceof IUser) {
			return;
		}
		$actorId = $actor->getUID();

		$event = $this->activityManager->generateEvent();
		try {
			$event->setApp('spreed')
				->setType('spreed')
				->setAuthor($actorId)
				->setObject('room', $room->getId())
				->setTimestamp($this->timeFactory->getTime())
				->setSubject('invitation', [
					'user' => $actor->getUID(),
					'room' => $room->getId(),
				]);
		} catch (\InvalidArgumentException $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return;
		}

		foreach ($participants as $participant) {
			if ($participant['actorType'] !== Attendee::ACTOR_USERS) {
				// No user => no activity
				continue;
			}

			if ($actorId === $participant['actorId']) {
				// No activity for self-joining and the creator
				continue;
			}

			try {
				$roomName = $room->getDisplayName($participant['actorId']);
				$event
					->setObject('room', $room->getId(), $roomName)
					->setSubject('invitation', [
						'user' => $actor->getUID(),
						'room' => $room->getId(),
						'name' => $roomName,
					])
					->setAffectedUser($participant['actorId']);
				$this->activityManager->publish($event);
			} catch (\InvalidArgumentException $e) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
			} catch (\BadMethodCallException $e) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
			}
		}
	}
}
