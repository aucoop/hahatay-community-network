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

namespace OCA\Talk\Controller;

use OCA\Talk\Room;
use OCA\Talk\Service\RoomService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IManager as IShareManager;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IShare;

class PublicShareAuthController extends OCSController {

	/** @var IUserManager */
	private $userManager;
	/** @var IShareManager */
	private $shareManager;
	/** @var IUserSession */
	private $userSession;
	/** @var RoomService */
	private $roomService;

	public function __construct(
			string $appName,
			IRequest $request,
			IUserManager $userManager,
			IShareManager $shareManager,
			IUserSession $userSession,
			RoomService $roomService
	) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->shareManager = $shareManager;
		$this->userSession = $userSession;
		$this->roomService = $roomService;
	}

	/**
	 * @PublicPage
	 *
	 * Creates a new room for requesting the password of a share.
	 *
	 * The new room is a public room associated with a "share:password" object
	 * with the ID of the share token. Unlike normal rooms in which the owner is
	 * the user that created the room these are special rooms always created by
	 * a guest or user on behalf of a registered user, the sharer, who will be
	 * the owner of the room.
	 *
	 * The share must have "send password by Talk" enabled; an error is returned
	 * otherwise.
	 *
	 * @param string $shareToken
	 * @return DataResponse the status code is "201 Created" if a new room is
	 *         created, "200 OK" if an existing room is returned, or "404 Not
	 *         found" if the given share was invalid.
	 */
	public function createRoom(string $shareToken): DataResponse {
		try {
			$share = $this->shareManager->getShareByToken($shareToken);
		} catch (ShareNotFound $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		if (!$share->getSendPasswordByTalk()) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$sharerUser = $this->userManager->get($share->getSharedBy());

		if (!$sharerUser instanceof IUser) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		if ($share->getShareType() === IShare::TYPE_EMAIL) {
			$roomName = $share->getSharedWith();
		} else {
			$roomName = trim($share->getTarget(), '/');
		}
		$roomName = $this->roomService->prepareConversationName($roomName);

		// Create the room
		$room = $this->roomService->createConversation(Room::PUBLIC_CALL, $roomName, $sharerUser, 'share:password', $shareToken);

		$user = $this->userSession->getUser();
		$userId = $user instanceof IUser ? $user->getUID() : '';

		return new DataResponse([
			'token' => $room->getToken(),
			'name' => $room->getName(),
			'displayName' => $room->getDisplayName($userId),
		], Http::STATUS_CREATED);
	}
}
