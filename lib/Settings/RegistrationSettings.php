<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Pellaeon Lin <pellaeon@hs.ntnu.edu.tw>
 *
 * @author Pellaeon Lin <pellaeon@hs.ntnu.edu.tw>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Registration\Settings;

use OCA\Registration\Db\GroupMapper;
use OCA\Registration\Db\Group;
use OCA\Registration\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\Settings\ISettings;
use OCP\Util;

class RegistrationSettings implements ISettings {

	public function __construct(protected string $appName,
		private IConfig $config,
		private GroupMapper $groupMapper,
		private IGroupManager $groupManager,
		private IInitialState $initialState) {
	}

	public function getForm(): TemplateResponse {
		$this->initialState->provideInitialState(
			'registered_user_group',
			$this->getGroupDetailArray($this->config->getAppValue($this->appName, 'registered_user_group', 'none'))
		);

		$this->initialState->provideInitialState(
			'admin_approval_required',
			$this->config->getAppValue($this->appName, 'admin_approval_required', 'no') === 'yes'
		);
		$this->initialState->provideInitialState(
			'admin_approval_to_group_admin_only',
			$this->config->getAppValue($this->appName, 'admin_approval_to_group_admin_only', 'no') === 'yes'
		);

		$this->initialState->provideInitialState(
			'allowed_domains',
			$this->config->getAppValue($this->appName, 'allowed_domains')
		);
		$this->initialState->provideInitialState(
			'domains_is_blocklist',
			$this->config->getAppValue($this->appName, 'domains_is_blocklist', 'no') === 'yes'
		);
		$this->initialState->provideInitialState(
			'show_domains',
			$this->config->getAppValue($this->appName, 'show_domains', 'no') === 'yes'
		);
		$this->initialState->provideInitialState(
			'disable_email_verification',
			$this->config->getAppValue($this->appName, 'disable_email_verification', 'no') === 'yes'
		);
		$this->initialState->provideInitialState(
			'email_is_optional',
			$this->config->getAppValue($this->appName, 'email_is_optional', 'no') === 'yes'
		);
		$this->initialState->provideInitialState(
			'email_is_login',
			$this->config->getAppValue($this->appName, 'email_is_login', 'no') === 'yes'
		);
		$this->initialState->provideInitialState(
			'username_policy_regex',
			$this->config->getAppValue($this->appName, 'username_policy_regex')
		);
		$this->initialState->provideInitialState(
			'username_policy_regex',
			$this->config->getAppValue($this->appName, 'username_policy_regex')
		);
		$this->initialState->provideInitialState(
			'show_fullname',
			$this->config->getAppValue($this->appName, 'show_fullname', 'no') === 'yes'
		);
		$this->initialState->provideInitialState(
			'enforce_fullname',
			$this->config->getAppValue($this->appName, 'enforce_fullname', 'no') === 'yes'
		);
		$this->initialState->provideInitialState(
			'show_phone',
			$this->config->getAppValue($this->appName, 'show_phone', 'no') === 'yes'
		);
		$this->initialState->provideInitialState(
			'enforce_phone',
			$this->config->getAppValue($this->appName, 'enforce_phone', 'no') === 'yes'
		);

		$this->initialState->provideInitialState(
			'additional_hint',
			$this->config->getAppValue($this->appName, 'additional_hint')
		);
		$this->initialState->provideInitialState(
			'email_verification_hint',
			$this->config->getAppValue($this->appName, 'email_verification_hint')
		);

		$this->initialState->provideInitialState(
			'per_email_group_mapping',
			$this->config->getAppValue($this->appName, 'per_email_group_mapping', 'no') === 'yes'
		);

		$result = [];
		foreach ($this->groupMapper->getGroupMappings() as $mapping) {
			try {
				$result[] = [
					'emailDomains' => $mapping->getEmailDomains(),
					'groupMapping' => $this->groupManager->get($mapping->getGroupId())->getDisplayName(),
					'id' => $mapping->getId()
				];
			} catch (\Exception $e) {
				$this->logger->error('Failed to lookup group by id in groupMappings table', ['exception' => $e]);
			}
		}
		$this->initialState->provideInitialState('group_mappings', $result);

		Util::addScript('registration', 'registration-settings');
		Util::addStyle('registration', 'settings');

		return new TemplateResponse('registration', 'admin', [], TemplateResponse::RENDER_AS_BLANK);
	}

	public function getSection(): string {
		return Application::APP_ID;
	}

	public function getPriority(): int {
		return 50;
	}

	protected function getGroupDetailArray(string $gid): array {
		$group = $this->groupManager->get($gid);
		if ($group instanceof IGroup) {
			return [
				'id' => $group->getGID(),
				'displayname' => $group->getDisplayName(),
			];
		}

		return [];
	}
}
