<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Joas Schilling <coding@schilljs.com>
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

use OCA\Talk\Participant;
use OCA\Talk\Room;
use OCP\AppFramework\OCSController;

abstract class AEnvironmentAwareController extends OCSController {

	/** @var int */
	protected $apiVersion = 1;
	/** @var Room */
	protected $room;
	/** @var Participant */
	protected $participant;

	public function setAPIVersion(int $apiVersion): void {
		$this->apiVersion = $apiVersion;
	}

	public function getAPIVersion(): int {
		return $this->apiVersion;
	}

	public function setRoom(Room $room): void {
		$this->room = $room;
	}

	public function getRoom(): ?Room {
		return $this->room;
	}

	public function setParticipant(Participant $participant): void {
		$this->participant = $participant;
	}

	public function getParticipant(): ?Participant {
		return $this->participant;
	}
}
