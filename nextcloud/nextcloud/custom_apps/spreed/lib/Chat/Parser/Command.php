<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Joas Schilling <coding@schilljs.com>
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

namespace OCA\Talk\Chat\Parser;

use OCA\Talk\Model\Attendee;
use OCA\Talk\Model\Message;

class Command {
	/**
	 * @param Message $message
	 * @throws \OutOfBoundsException
	 */
	public function parseMessage(Message $message): void {
		$comment = $message->getComment();
		$data = json_decode($comment->getMessage(), true);
		if (!\is_array($data)) {
			throw new \OutOfBoundsException('Invalid message');
		}

		if ($data['visibility'] === \OCA\Talk\Model\Command::RESPONSE_NONE) {
			$message->setVisibility(false);
			return;
		}

		$participant = $message->getParticipant();
		if ($data['visibility'] !== \OCA\Talk\Model\Command::RESPONSE_ALL &&
			($participant->getAttendee()->getActorType() !== Attendee::ACTOR_USERS
				|| $data['user'] !== $participant->getAttendee()->getActorId())) {
			$message->setVisibility(false);
			return;
		}

		$message->setMessage($data['output'], []);
	}
}
