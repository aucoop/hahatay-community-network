<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 Joas Schilling <coding@schilljs.com>
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

namespace OCA\Talk\Events;

use OCA\Talk\Room;
use OCP\IUser;

class JoinRoomUserEvent extends RoomEvent {

	/** @var IUser */
	protected $user;
	/** @var bool */
	protected $cancelJoin;
	/** @var string */
	protected $password;
	/** @var bool */
	protected $passedPasswordProtection;


	public function __construct(Room $room,
								IUser $user,
								string $password,
								bool $passedPasswordProtection) {
		parent::__construct($room);
		$this->cancelJoin = false;
		$this->user = $user;
		$this->password = $password;
		$this->passedPasswordProtection = $passedPasswordProtection;
	}

	public function setCancelJoin(bool $cancelJoin): void {
		$this->cancelJoin = $cancelJoin;
	}

	public function getCancelJoin(): bool {
		return $this->cancelJoin;
	}

	public function getUser(): IUser {
		return $this->user;
	}

	public function getPassword(): string {
		return $this->password;
	}

	public function setPassedPasswordProtection(bool $passedPasswordProtection): void {
		$this->passedPasswordProtection = $passedPasswordProtection;
	}

	public function getPassedPasswordProtection(): bool {
		return $this->passedPasswordProtection;
	}
}
