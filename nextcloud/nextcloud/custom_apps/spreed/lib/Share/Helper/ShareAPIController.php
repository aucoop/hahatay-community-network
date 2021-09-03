<?php

declare(strict_types=1);
/**
 *
 * @copyright Copyright (c) 2018, Daniel Calviño Sánchez (danxuliu@gmail.com)
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

namespace OCA\Talk\Share\Helper;

use OCA\Talk\Exceptions\ParticipantNotFoundException;
use OCA\Talk\Exceptions\RoomNotFoundException;
use OCA\Talk\Manager;
use OCA\Talk\Room;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Share\IShare;

/**
 * Helper of OCA\Files_Sharing\Controller\ShareAPIController for room shares.
 *
 * The methods of this class are called from the ShareAPIController to perform
 * actions or checks specific to room shares.
 */
class ShareAPIController {

	/** @var string */
	private $userId;
	/** @var Manager */
	private $manager;
	/** @var ITimeFactory */
	protected $timeFactory;
	/** @var IL10N */
	private $l;
	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(
			string $UserId,
			Manager $manager,
			ITimeFactory $timeFactory,
			IL10N $l10n,
			IURLGenerator $urlGenerator
	) {
		$this->userId = $UserId;
		$this->manager = $manager;
		$this->timeFactory = $timeFactory;
		$this->l = $l10n;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * Formats the specific fields of a room share for OCS output.
	 *
	 * The returned fields override those set by the main ShareAPIController.
	 *
	 * @param IShare $share
	 * @return array
	 */
	public function formatShare(IShare $share): array {
		$result = [];

		try {
			$room = $this->manager->getRoomByToken($share->getSharedWith(), $this->userId);
		} catch (RoomNotFoundException $e) {
			return $result;
		}

		$result['share_with_displayname'] = $room->getDisplayName($this->userId);
		try {
			$room->getParticipant($this->userId, false);
		} catch (ParticipantNotFoundException $e) {
			// Removing the conversation token from the leaked data if not a participant.
			// Adding some unique but reproducable part to the share_with here
			// so the avatars for conversations are distinguishable
			$result['share_with'] = 'private_conversation_' . substr(sha1($room->getName() . $room->getId()), 0, 6);
		}
		if ($room->getType() === Room::PUBLIC_CALL) {
			$result['token'] = $share->getToken();
		}
		$result['share_with_link'] = $this->urlGenerator->linkToRouteAbsolute('spreed.Page.showCall', ['token' => $room->getToken()]);

		return $result;
	}

	/**
	 * Prepares the given share to be passed to OC\Share20\Manager::createShare.
	 *
	 * @param IShare $share
	 * @param string $shareWith
	 * @param int $permissions
	 * @param string $expireDate
	 * @throws OCSNotFoundException
	 */
	public function createShare(IShare $share, string $shareWith, int $permissions, string $expireDate): void {
		$share->setSharedWith($shareWith);
		$share->setPermissions($permissions);

		if ($expireDate !== '') {
			try {
				$expireDate = $this->parseDate($expireDate);
				$share->setExpirationDate($expireDate);
			} catch (\Exception $e) {
				throw new OCSNotFoundException($this->l->t('Invalid date, date format must be YYYY-MM-DD'));
			}
		}
	}

	/**
	 * Make sure that the passed date is valid ISO 8601
	 * So YYYY-MM-DD
	 * If not throw an exception
	 *
	 * Copied from \OCA\Files_Sharing\Controller\ShareAPIController::parseDate.
	 *
	 * @param string $expireDate
	 * @return \DateTime
	 * @throws \Exception
	 */
	private function parseDate(string $expireDate): \DateTime {
		try {
			$date = $this->timeFactory->getDateTime($expireDate);
		} catch (\Exception $e) {
			throw new \Exception('Invalid date. Format must be YYYY-MM-DD');
		}

		if ($date === false) {
			throw new \Exception('Invalid date. Format must be YYYY-MM-DD');
		}

		$date->setTime(0, 0, 0);

		return $date;
	}

	/**
	 * Returns whether the given user can access the given room share or not.
	 *
	 * A user can access a room share only if she is a participant of the room.
	 *
	 * @param IShare $share
	 * @param string $user
	 * @return bool
	 */
	public function canAccessShare(IShare $share, string $user): bool {
		try {
			$room = $this->manager->getRoomByToken($share->getSharedWith(), $user);
		} catch (RoomNotFoundException $e) {
			return false;
		}

		try {
			$room->getParticipant($user, false);
		} catch (ParticipantNotFoundException $e) {
			return false;
		}

		return true;
	}
}
