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

use OCP\EventDispatcher\Event;

class CreateRoomTokenEvent extends Event {

	/** @var int */
	protected $entropy;
	/** @var string */
	protected $chars;
	/** @var string */
	protected $token;


	public function __construct(int $entropy,
								string $chars) {
		parent::__construct();
		$this->entropy = $entropy;
		$this->chars = $chars;
		$this->token = '';
	}

	public function getEntropy(): int {
		return $this->entropy;
	}

	public function getChars(): string {
		return $this->chars;
	}

	public function setToken(string $token): void {
		$this->token = $token;
	}

	public function getToken(): string {
		return $this->token;
	}
}
