<?php
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OCA\Richdocuments\Service;

use OCP\App\IAppManager;
use OCP\Http\Client\IClientService;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;

class CapabilitiesService {

	/** @var IConfig */
	private $config;
	/** @var IClientService */
	private $clientService;
	/** @var ICache */
	private $cache;
	/** @var IAppManager */
	private $appManager;

	/** @var array */
	private $capabilities;


	public function __construct(IConfig $config, IClientService $clientService, ICacheFactory $cacheFactory, IAppManager $appManager) {
		$this->config = $config;
		$this->clientService = $clientService;
		$this->cache = $cacheFactory->createDistributed('richdocuments');
		$this->appManager = $appManager;
	}

	public function getCapabilities() {
		if (!$this->capabilities) {
			$this->capabilities = $this->cache->get('capabilities');
		}

		$isARM64 = php_uname('m') === 'aarch64';
		$CODEAppID = $isARM64 ? 'richdocumentscode_arm64' : 'richdocumentscode';
		$isCODEInstalled = $this->appManager->isEnabledForUser($CODEAppID);
		$isCODEEnabled = strpos($this->config->getAppValue('richdocuments', 'wopi_url'), 'proxy.php?req=') !== false;
		$shouldRecheckCODECapabilities = $isCODEInstalled && $isCODEEnabled && ($this->capabilities === null || count($this->capabilities) === 0);
		if($this->capabilities === null || $shouldRecheckCODECapabilities) {
			$this->refetch();
		}

		if (!is_array($this->capabilities)) {
			return [];
		}

		return $this->capabilities;
	}

	public function hasTemplateSaveAs() {
		return $this->getCapabilities()['hasTemplateSaveAs'] ?? false;
	}

	public function hasTemplateSource() {
		return $this->getCapabilities()['hasTemplateSource'] ?? false;
	}

	public function clear() {
		$this->cache->remove('capabilities');
	}

	public function refetch() {
		$remoteHost = $this->config->getAppValue('richdocuments', 'wopi_url');
		if ($remoteHost === '') {
			return [];
		}
		$capabilitiesEndpoint = rtrim($remoteHost, '/') . '/hosting/capabilities';

		$client = $this->clientService->newClient();
		$options = ['timeout' => 45, 'nextcloud' => ['allow_local_address' => true]];

		if ($this->config->getAppValue('richdocuments', 'disable_certificate_verification') === 'yes') {
			$options['verify'] = false;
		}

		try {
			$response = $client->get($capabilitiesEndpoint, $options);
			$responseBody = $response->getBody();
			$capabilities = \json_decode($responseBody, true);

			if (!is_array($capabilities)) {
				$capabilities = [];
			}


		} catch (\Exception $e) {
			$capabilities = [];
		}

		$this->capabilities = $capabilities;
		$ttl = 3600;
		if (count($capabilities) === 0)
			$ttl = 60;

		$this->cache->set('capabilities', $capabilities, $ttl);
	}
}
