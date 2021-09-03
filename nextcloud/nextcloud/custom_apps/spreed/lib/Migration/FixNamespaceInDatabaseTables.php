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

namespace OCA\Talk\Migration;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class FixNamespaceInDatabaseTables implements IRepairStep {

	/** @var IDBConnection */
	protected $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	public function getName(): string {
		return 'Fix the namespace in database tables';
	}

	public function run(IOutput $output): void {
		$update = $this->connection->getQueryBuilder();
		$update->update('jobs')
			->set('class', $update->createParameter('newClass'))
			->where($update->expr()->eq('id', $update->createParameter('id')));

		$query = $this->connection->getQueryBuilder();
		$query->select('id', 'class')
			->from('jobs')
			->where($query->expr()->like('class', $query->createNamedParameter(
				'%' . $this->connection->escapeLikeParameter('Spreed'). '%'
			)));

		$result = $query->execute();
		while ($row = $result->fetch()) {
			$oldClass = $row['class'];
			if (strpos($oldClass, 'OCA\\Spreed\\') !== 0) {
				continue;
			}

			$newClass = 'OCA\\Talk\\' . substr($oldClass, strlen('OCA\\Spreed\\'));

			$update->setParameter('newClass', $newClass)
				->setParameter('id', $row['id']);
			$update->execute();
		}
		$result->closeCursor();
	}
}
