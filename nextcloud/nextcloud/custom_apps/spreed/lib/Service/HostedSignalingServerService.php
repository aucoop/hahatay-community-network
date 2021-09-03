<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Morris Jobke <hey@morrisjobke.de>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace OCA\Talk\Service;

use GuzzleHttp\Exception\ClientException;
use OCA\Talk\DataObjects\AccountId;
use OCA\Talk\DataObjects\RegisterAccountData;
use OCA\Talk\Exceptions\HostedSignalingServerAPIException;
use OCA\Talk\Exceptions\HostedSignalingServerInputException;
use OCP\AppFramework\Http;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class HostedSignalingServerService {

	/** @var IConfig */
	private $config;
	/** @var mixed */
	private $apiServerUrl;
	/** @var IClientService */
	private $clientService;
	/** @var LoggerInterface */
	private $logger;
	/** @var IL10N */
	private $l10n;
	/** @var ISecureRandom */
	private $secureRandom;

	public function __construct(IConfig $config,
								IClientService $clientService,
								LoggerInterface $logger,
								IL10N $l10n,
								ISecureRandom $secureRandom) {
		$this->config = $config;
		$this->clientService = $clientService;
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->secureRandom = $secureRandom;

		$this->apiServerUrl = $this->config->getSystemValue('talk_hardcoded_hpb_service', 'https://api.spreed.cloud');
	}

	/**
	 * @throws HostedSignalingServerAPIException
	 * @throws HostedSignalingServerInputException
	 */
	public function registerAccount(RegisterAccountData $registerAccountData): AccountId {
		try {
			$nonce = $this->secureRandom->generate(32);
			$this->config->setAppValue('spreed', 'hosted-signaling-server-nonce', $nonce);

			$client = $this->clientService->newClient();
			$response = $client->post($this->apiServerUrl . '/v1/account', [
				'json' => [
					'url' => $registerAccountData->getUrl(),
					'name' => $registerAccountData->getName(),
					'email' => $registerAccountData->getEmail(),
					'language' => $registerAccountData->getLanguage(),
					'country' => $registerAccountData->getCountry(),
				],
				'headers' => [
					'X-Account-Service-Nonce' => $nonce,
				],
				'timeout' => 10,
			]);

			// this is needed here because the delete happens in a concurrent request
			// and thus the cached value in the config object would trigger an UPDATE
			// instead of an INSERT if there is another request to the API server
			$this->config->deleteAppValue('spreed', 'hosted-signaling-server-nonce');
		} catch (ClientException $e) {
			$response = $e->getResponse();

			if ($response === null) {
				$this->logger->error('Failed to request hosted signaling server trial', ['exception' => $e]);
				$message = $this->l10n->t('Failed to request trial because the trial server is unreachable. Please try again later.');
				throw new HostedSignalingServerAPIException($message);
			}

			$status = $response->getStatusCode();
			switch ($status) {
				case Http::STATUS_UNAUTHORIZED:
					$body = $response->getBody()->getContents();
					$this->logger->error('Requesting hosted signaling server trial failed: unauthorized - HTTP status: ' . $status . ' Response body: ' . $body);

					$message = $this->l10n->t('There is a problem with the authentication of this instance. Maybe it is not reachable from the outside to verify it\'s URL.');
					throw new HostedSignalingServerAPIException($message);
				case Http::STATUS_BAD_REQUEST:
					$body = $response->getBody()->getContents();
					if ($body) {
						$parsedBody = json_decode($body, true);
						if (json_last_error() !== JSON_ERROR_NONE) {
							$this->logger->error('Requesting hosted signaling server trial failed: cannot parse JSON response - JSON error: '. json_last_error() . ' ' . json_last_error_msg() . ' HTTP status: ' . $status . ' Response body: ' . $body);

							$message = $this->l10n->t('Something unexpected happened.');
							throw new HostedSignalingServerAPIException($message);
						}
						if ($parsedBody['reason']) {
							$message = '';
							switch ($parsedBody['reason']) {
								case 'invalid_content_type':
									$log = 'The content type is invalid.';
									break;
								case 'invalid_json':
									$log = 'The JSON is invalid.';
									break;
								case 'missing_url':
									$log = 'The URL is missing.';
									break;
								case 'missing_name':
									$log = 'The name is missing.';
									break;
								case 'missing_email':
									$log = 'The email address is missing';
									break;
								case 'missing_language':
									$log = 'The language code is missing.';
									break;
								case 'missing_country':
									$log = 'The country code is missing.';
									break;
								case 'invalid_url':
									$message = $this->l10n->t('The URL is invalid.');
									$log = 'The entered URL is invalid.';
									break;
								case 'https_required':
									$message = $this->l10n->t('An HTTPS URL is required.');
									$log = 'An HTTPS URL is required.';
									break;
								case 'invalid_email':
									$message = $this->l10n->t('The email address is invalid.');
									$log = 'The email address is invalid.';
									break;
								case 'invalid_language':
									$message = $this->l10n->t('The language is invalid.');
									$log = 'The language is invalid.';
									break;
								case 'invalid_country':
									$message = $this->l10n->t('The country is invalid.');
									$log = 'The country is invalid.';
									break;
							}
							// user error
							if ($message !== '') {
								$this->logger->warning('Requesting hosted signaling server trial failed: bad request - reason: ' . $parsedBody['reason'] . ' ' . $log);
								throw new HostedSignalingServerAPIException($message);
							}
							$this->logger->error('Requesting hosted signaling server trial failed: bad request - reason: ' . $parsedBody['reason'] . ' ' . $log);

							$message = $this->l10n->t('There is a problem with the request of the trial. Please check your logs for further information.');
							throw new HostedSignalingServerAPIException($message);
						}
					}

					$message = $this->l10n->t('Something unexpected happened.');
					throw new HostedSignalingServerAPIException($message);
				case Http::STATUS_TOO_MANY_REQUESTS:
					$body = $response->getBody()->getContents();
					$this->logger->error('Requesting hosted signaling server trial failed: too many requests - HTTP status: ' . $status . ' Response body: ' . $body);

					$message = $this->l10n->t('Too many requests are send from your servers address. Please try again later.');
					throw new HostedSignalingServerInputException($message);
				case Http::STATUS_CONFLICT:
					$body = $response->getBody()->getContents();
					$this->logger->error('Requesting hosted signaling server trial failed: already registered - HTTP status: ' . $status . ' Response body: ' . $body);

					$message = $this->l10n->t('There is already a trial registered for this Nextcloud instance.');
					throw new HostedSignalingServerInputException($message);
				case Http::STATUS_INTERNAL_SERVER_ERROR:
					$body = $response->getBody()->getContents();
					$this->logger->error('Requesting hosted signaling server trial failed: internal server error - HTTP status: ' . $status . ' Response body: ' . $body);

					$message = $this->l10n->t('Something unexpected happened. Please try again later.');
					throw new HostedSignalingServerAPIException($message);
				default:
					$body = $response->getBody()->getContents();
					$this->logger->error('Requesting hosted signaling server trial failed: something else happened - HTTP status: ' . $status . ' Response body: ' . $body);

					$message = $this->l10n->t('Failed to request trial because the trial server behaved wrongly. Please try again later.');
					throw new HostedSignalingServerAPIException($message);
			}
		} catch (\Exception $e) {
			$this->logger->error('Failed to request hosted signaling server trial', ['exception' => $e]);
			$message = $this->l10n->t('Failed to request trial because the trial server is unreachable. Please try again later.');
			throw new HostedSignalingServerAPIException($message);
		}

		$status = $response->getStatusCode();

		if ($status !== Http::STATUS_CREATED) {
			$body = $response->getBody();
			$this->logger->error('Requesting hosted signaling server trial failed: something else happened - HTTP status: ' . $status . ' Response body: ' . $body);

			$message = $this->l10n->t('Something unexpected happened.');
			throw new HostedSignalingServerAPIException($message);
		}

		$body = $response->getBody();
		$data = json_decode($body, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->logger->error('Requesting hosted signaling server trial failed: cannot parse JSON response - JSON error: '. json_last_error() . ' ' . json_last_error_msg() . ' HTTP status: ' . $status . ' Response body: ' . $body);

			$message = $this->l10n->t('Something unexpected happened.');
			throw new HostedSignalingServerAPIException($message);
		}

		if (!isset($data['account_id'])) {
			$this->logger->error('Requesting hosted signaling server trial failed: no account ID transfered - HTTP status: ' . $status . ' Response body: ' . $body);

			$message = $this->l10n->t('Something unexpected happened.');
			throw new HostedSignalingServerAPIException($message);
		}

		$accountId = (string)$data['account_id'];
		$this->config->setAppValue('spreed', 'hosted-signaling-server-account-id', $accountId);

		return new AccountId($accountId);
	}

	/**
	 * @throws HostedSignalingServerAPIException
	 */
	public function fetchAccountInfo(AccountId $accountId): array {
		try {
			$nonce = $this->secureRandom->generate(32);
			$this->config->setAppValue('spreed', 'hosted-signaling-server-nonce', $nonce);

			$client = $this->clientService->newClient();
			$response = $client->get($this->apiServerUrl . '/v1/account/' . $accountId->get(), [
				'headers' => [
					'X-Account-Service-Nonce' => $nonce,
				],
				'timeout' => 10,
			]);

			// this is needed here because the delete happens in a concurrent request
			// and thus the cached value in the config object would trigger an UPDATE
			// instead of an INSERT if there is another request to the API server
			$this->config->deleteAppValue('spreed', 'hosted-signaling-server-nonce');
		} catch (ClientException $e) {
			$response = $e->getResponse();

			if ($response === null) {
				$this->logger->error('Trial requested but failed to get account information', ['exception' => $e]);
				$message = $this->l10n->t('Trial requested but failed to get account information. Please check back later.');
				throw new HostedSignalingServerAPIException($message);
			}

			$status = $response->getStatusCode();

			switch ($status) {
				case Http::STATUS_UNAUTHORIZED:
					$body = $response->getBody()->getContents();
					$this->logger->error('Getting the account information failed: unauthorized - HTTP status: ' . $status . ' Response body: ' . $body);

					$message = $this->l10n->t('There is a problem with the authentication of this request. Maybe it is not reachable from the outside to verify it\'s URL.');
					throw new HostedSignalingServerAPIException($message);
				case Http::STATUS_BAD_REQUEST:
					$body = $response->getBody()->getContents();
					if ($body) {
						$parsedBody = json_decode($body, true);
						if (json_last_error() !== JSON_ERROR_NONE) {
							$this->logger->error('Getting the account information failed: cannot parse JSON response - JSON error: '. json_last_error() . ' ' . json_last_error_msg() . ' HTTP status: ' . $status . ' Response body: ' . $body);

							$message = $this->l10n->t('Something unexpected happened.');
							throw new HostedSignalingServerAPIException($message);
						}
						if ($parsedBody['reason']) {
							switch ($parsedBody['reason']) {
								case 'missing_account_id':
									$log = 'The account ID is missing.';
									break;
								default:
									$body = $response->getBody()->getContents();
									$this->logger->error('Getting the account information failed: something else happened - HTTP status: ' . $status . ' Response body: ' . $body);

									$message = $this->l10n->t('Failed to fetch account information because the trial server behaved wrongly. Please check back later.');
									throw new HostedSignalingServerAPIException($message);
							}
							$this->logger->error('Getting the account information failed: bad request - reason: ' . $parsedBody['reason'] . ' ' . $log);

							$message = $this->l10n->t('There is a problem with fetching the account information. Please check your logs for further information.');
							throw new HostedSignalingServerAPIException($message);
						}
					}

					$message = $this->l10n->t('Something unexpected happened.');
					throw new HostedSignalingServerAPIException($message);
				case Http::STATUS_TOO_MANY_REQUESTS:
					$body = $response->getBody()->getContents();
					$this->logger->error('Getting the account information failed: too many requests - HTTP status: ' . $status . ' Response body: ' . $body);

					$message = $this->l10n->t('Too many requests are send from your servers address. Please try again later.');
					throw new HostedSignalingServerAPIException($message);
				case Http::STATUS_NOT_FOUND:
					$body = $response->getBody()->getContents();
					$this->logger->error('Getting the account information failed: account not found - HTTP status: ' . $status . ' Response body: ' . $body);

					$message = $this->l10n->t('There is no such account registered.');
					throw new HostedSignalingServerAPIException($message);
				case Http::STATUS_INTERNAL_SERVER_ERROR:
					$body = $response->getBody()->getContents();
					$this->logger->error('Getting the account information failed: internal server error - HTTP status: ' . $status . ' Response body: ' . $body);

					$message = $this->l10n->t('Something unexpected happened. Please try again later.');
					throw new HostedSignalingServerAPIException($message);
				default:
					$body = $response->getBody()->getContents();
					$this->logger->error('Getting the account information failed: something else happened - HTTP status: ' . $status . ' Response body: ' . $body);

					$message = $this->l10n->t('Failed to fetch account information because the trial server behaved wrongly. Please check back later.');
					throw new HostedSignalingServerAPIException($message);
			}
		} catch (\Exception $e) {
			$this->logger->error('Failed to request hosted signaling server trial', ['exception' => $e]);
			$message = $this->l10n->t('Failed to fetch account information because the trial server is unreachable. Please check back later.');
			throw new HostedSignalingServerAPIException($message);
		}

		$status = $response->getStatusCode();

		if ($status !== Http::STATUS_OK) {
			$body = $response->getBody();
			$this->logger->error('Getting the account information failed: something else happened - HTTP status: ' . $status . ' Response body: ' . $body);


			$message = $this->l10n->t('Something unexpected happened.');
			throw new HostedSignalingServerAPIException($message);
		}

		$body = $response->getBody();
		$data = json_decode($body, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->logger->error('Getting the account information failed: cannot parse JSON response - JSON error: '. json_last_error() . ' ' . json_last_error_msg() . ' HTTP status: ' . $status . ' Response body: ' . $body);

			$message = $this->l10n->t('Something unexpected happened.');
			throw new HostedSignalingServerAPIException($message);
		}

		if (!isset($data['status'])
			|| !isset($data['created'])
			|| ($data['status'] === 'active' && (
					!isset($data['signaling'])
					|| !isset($data['signaling']['url'])
					|| !isset($data['signaling']['secret'])
				)
			)
			|| !isset($data['owner'])
			|| !isset($data['owner']['url'])
			|| !isset($data['owner']['name'])
			|| !isset($data['owner']['email'])
			|| !isset($data['owner']['language'])
			|| !isset($data['owner']['country'])
			/* TODO they are not yet returned
			|| ($data['status'] === 'active' && (
					!isset($data['limits'])
					|| !isset($data['limits']['users'])
				)
			)
			*/
			|| (in_array($data['status'], ['error', 'blocked']) && !isset($data['reason']))
			|| !in_array($data['status'], ['error', 'blocked', 'pending', 'active', 'expired'])
		) {
			$this->logger->error('Getting the account information failed: response is missing mandatory field - data: ' . json_encode($data));

			$message = $this->l10n->t('Something unexpected happened.');
			throw new HostedSignalingServerAPIException($message);
		}

		return $data;
	}

	/**
	 * @throws HostedSignalingServerAPIException
	 */
	public function deleteAccount(AccountId $accountId) {
		try {
			$nonce = $this->secureRandom->generate(32);
			$this->config->setAppValue('spreed', 'hosted-signaling-server-nonce', $nonce);

			$client = $this->clientService->newClient();
			$response = $client->delete($this->apiServerUrl . '/v1/account/' . $accountId->get(), [
				'headers' => [
					'X-Account-Service-Nonce' => $nonce,
				],
				'timeout' => 10,
			]);

			// this is needed here because the delete happens in a concurrent request
			// and thus the cached value in the config object would trigger an UPDATE
			// instead of an INSERT if there is another request to the API server
			$this->config->deleteAppValue('spreed', 'hosted-signaling-server-nonce');
		} catch (ClientException $e) {
			$response = $e->getResponse();

			if ($response === null) {
				$this->logger->error('Deleting the hosted signaling server account failed', ['exception' => $e]);
				$message = $this->l10n->t('Deleting the hosted signaling server account failed. Please check back later.');
				throw new HostedSignalingServerAPIException($message);
			}

			$status = $response->getStatusCode();

			switch ($status) {
				case Http::STATUS_UNAUTHORIZED:
					$body = $response->getBody()->getContents();
					$this->logger->error('Deleting the hosted signaling server account failed: unauthorized - HTTP status: ' . $status . ' Response body: ' . $body);

					$message = $this->l10n->t('There is a problem with the authentication of this request. Maybe it is not reachable from the outside to verify it\'s URL.');
					throw new HostedSignalingServerAPIException($message);
				case Http::STATUS_BAD_REQUEST:
					$body = $response->getBody()->getContents();
					if ($body) {
						$parsedBody = json_decode($body, true);
						if (json_last_error() !== JSON_ERROR_NONE) {
							$this->logger->error('Deleting the hosted signaling server account failed: cannot parse JSON response - JSON error: '. json_last_error() . ' ' . json_last_error_msg() . ' HTTP status: ' . $status . ' Response body: ' . $body);

							$message = $this->l10n->t('Something unexpected happened.');
							throw new HostedSignalingServerAPIException($message);
						}
						if ($parsedBody['reason']) {
							switch ($parsedBody['reason']) {
								case 'missing_account_id':
									$log = 'The account ID is missing.';
									break;
								default:
									$body = $response->getBody()->getContents();
									$this->logger->error('Deleting the hosted signaling server account failed: something else happened - HTTP status: ' . $status . ' Response body: ' . $body);

									$message = $this->l10n->t('Failed to delete the account because the trial server behaved wrongly. Please check back later.');
									throw new HostedSignalingServerAPIException($message);
							}
							$this->logger->error('Deleting the hosted signaling server account failed: bad request - reason: ' . $parsedBody['reason'] . ' ' . $log);

							$message = $this->l10n->t('There is a problem with deleting the account. Please check your logs for further information.');
							throw new HostedSignalingServerAPIException($message);
						}
					}

					$message = $this->l10n->t('Something unexpected happened.');
					throw new HostedSignalingServerAPIException($message);
				case Http::STATUS_TOO_MANY_REQUESTS:
					$body = $response->getBody()->getContents();
					$this->logger->error('Deleting the hosted signaling server account failed: too many requests - HTTP status: ' . $status . ' Response body: ' . $body);

					$message = $this->l10n->t('Too many requests are sent from your servers address. Please try again later.');
					throw new HostedSignalingServerAPIException($message);
				case Http::STATUS_NOT_FOUND:
					$body = $response->getBody()->getContents();
					$this->logger->error('Deleting the hosted signaling server account failed: account not found - HTTP status: ' . $status . ' Response body: ' . $body);

					$message = $this->l10n->t('There is no such account registered.');
					throw new HostedSignalingServerAPIException($message);
				case Http::STATUS_INTERNAL_SERVER_ERROR:
					$body = $response->getBody()->getContents();
					$this->logger->error('Deleting the hosted signaling server account failed: internal server error - HTTP status: ' . $status . ' Response body: ' . $body);

					$message = $this->l10n->t('Something unexpected happened. Please try again later.');
					throw new HostedSignalingServerAPIException($message);
				default:
					$body = $response->getBody()->getContents();
					$this->logger->error('Deleting the hosted signaling server account failed: something else happened - HTTP status: ' . $status . ' Response body: ' . $body);

					$message = $this->l10n->t('Failed to delete the account because the trial server behaved wrongly. Please check back later.');
					throw new HostedSignalingServerAPIException($message);
			}
		} catch (\Exception $e) {
			$this->logger->error('Deleting the hosted signaling server account failed', ['exception' => $e]);
			$message = $this->l10n->t('Failed to delete the account because the trial server is unreachable. Please check back later.');
			throw new HostedSignalingServerAPIException($message);
		}

		$status = $response->getStatusCode();

		if ($status !== Http::STATUS_NO_CONTENT) {
			$body = $response->getBody();
			$this->logger->error('Deleting the hosted signaling server account failed: something else happened - HTTP status: ' . $status . ' Response body: ' . $body);


			$message = $this->l10n->t('Something unexpected happened.');
			throw new HostedSignalingServerAPIException($message);
		}
	}
}
