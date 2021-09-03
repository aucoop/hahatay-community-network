<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
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
namespace OCA\Talk\Migration;

use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version2000Date20170707093535 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('spreedme_messages')) {
			$table = $schema->createTable('spreedme_messages');

			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
			]);
			$table->addColumn('sender', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('recipient', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('sessionId', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('object', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addColumn('timestamp', Types::INTEGER, [
				'notnull' => true,
				'length' => 11,
			]);

			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('spreedme_rooms')) {
			$table = $schema->createTable('spreedme_rooms');

			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
			]);
			$table->addColumn('name', Types::STRING, [
				'notnull' => false,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('token', Types::STRING, [
				'notnull' => false,
				'length' => 32,
				'default' => '',
			]);
			$table->addColumn('type', Types::INTEGER, [
				'notnull' => true,
				'length' => 11,
			]);

			$table->setPrimaryKey(['id']);
			// FIXME: Couldn't make it unique because the migration only ran afterwards on updates
			$table->addIndex(['token'], 'unique_token');
		}

		if (!$schema->hasTable('spreedme_room_participants')) {
			$table = $schema->createTable('spreedme_room_participants');

			$table->addColumn('userId', Types::STRING, [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('roomId', Types::INTEGER, [
				'notnull' => true,
				'length' => 11,
			]);
			$table->addColumn('lastPing', Types::INTEGER, [
				'notnull' => true,
				'length' => 11,
			]);
			$table->addColumn('sessionId', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
		}

		return $schema;
	}
}
