<?php

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

declare(strict_types=1);

namespace ChristophWurst\KItinerary;

use Countable;
use JsonSerializable;

class Itinerary implements Countable, JsonSerializable {

	/** @var array */
	private $entries;

	public function __construct(array $entries = []) {
		$this->entries = $entries;
	}

	public static function fromJson(string $json): self {
		return new self(json_decode($json, true));
	}

	public function merge(Itinerary $other): self {
		return new self(array_merge($this->entries, $other->entries));
	}

	public function count() {
		return count($this->entries);
	}

	public function jsonSerialize() {
		return $this->entries;
	}

}
