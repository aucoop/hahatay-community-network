<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Joas Schilling <coding@schilljs.com>
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

use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version3002Date20180319104030 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/**
		 * The table had to be redone so it contains a primary key
		 * @see Version11000Date20201209142525
		 *
		 * $schema = $schemaClosure();
		 *
		 * if (!$schema->hasTable('talk_guests')) {
		 * $table = $schema->createTable('talk_guests');
		 *
		 * $table->addColumn('session_hash', Type::STRING, [
		 * 'notnull' => false,
		 * 'length' => 64,
		 * ]);
		 * $table->addColumn('display_name', Type::STRING, [
		 * 'notnull' => false,
		 * 'length' => 64,
		 * 'default' => '',
		 * ]);
		 *
		 * $table->addUniqueIndex(['session_hash'], 'tg_session_hash');
		 * }
		 *
		 * return $schema;
		 */
		return null;
	}
}
