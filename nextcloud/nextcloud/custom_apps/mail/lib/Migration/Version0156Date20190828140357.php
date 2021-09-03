<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version0156Date20190828140357 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$accountsTable = $schema->getTable('mail_accounts');
		$accountsTable->addColumn('last_mailbox_sync', 'integer', [
			'default' => 0,
		]);

		$mailboxTable = $schema->createTable('mail_mailboxes');
		$mailboxTable->addColumn('id', 'string', [
			'notnull' => true,
			'length' => 255,
		]);
		$mailboxTable->addColumn('account_id', 'integer', [
			'notnull' => true,
			'length' => 4,
		]);
		$mailboxTable->addColumn('sync_token', 'string', [
			'notnull' => true,
			'length' => 255,
		]);
		$mailboxTable->addColumn('attributes', 'string', [
			'length' => 255,
			'default' => '[]',
		]);
		$mailboxTable->addColumn('delimiter', 'string', [
			'notnull' => true,
			'length' => 1,
		]);
		$mailboxTable->addColumn('messages', 'integer', [
			'notnull' => true,
			'length' => 4,
		]);
		$mailboxTable->addColumn('unseen', 'integer', [
			'notnull' => true,
			'length' => 4,
		]);
		$mailboxTable->addColumn('selectable', 'boolean', [
			'notnull' => true,
			'length' => 1,
			'default' => true,
		]);
		// We allow each mailbox name just once
		$mailboxTable->setPrimaryKey([
			'account_id',
			'id',
		]);

		return $schema;
	}
}
