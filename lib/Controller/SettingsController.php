<?php
/**
 * ownCloud - registration
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pellaeon Lin <pellaeon@cnmc.tw>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @copyright Pellaeon Lin 2015
 */

namespace OCA\Registration\Controller;

use OCA\Registration\Db\GroupMapper;
use OCA\Registration\Db\Group;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;

class SettingsController extends Controller {

	private IL10N $l10n;
	private IConfig $config;
	private IGroupManager $groupmanager;
	private GroupMapper $groupMapper;
	/** @var string */
	protected $appName;

	public function __construct($appName, IRequest $request, IL10N $l10n, IConfig $config, IGroupManager $groupmanager, GroupMapper $groupmapper) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->config = $config;
		$this->groupmanager = $groupmanager;
		$this->appName = $appName;
		$this->groupMapper = $groupmapper;
	}

	/**
	 * @AdminRequired
	 *
	 * @param string|null $registered_user_group all newly registered user will be put in this group
	 * @param string $allowed_domains Registrations are only allowed for E-Mailadresses with these domains
	 * @param string $additional_hint show Text at user-creation form
	 * @param string $email_verification_hint if filled embed Text in Verification mail send to user
	 * @param string $username_policy_regex optional regex to check usernames against a pattern
	 * @param bool|null $admin_approval_required newly registered users have to be validated by an admin
	 * @param bool|null $admin_approval_to_group_admin_only only send the activation email to the group admins the new user will be a member of
	 * @param bool|null $email_is_optional email address is not required
	 * @param bool|null $email_is_login email address is forced as user id
	 * @param bool|null $domains_is_blocklist is the domain list an allow or block list
	 * @param bool|null $show_domains should the email list be shown to the user or not
	 * @param bool|null $per_email_group_mapping should the group to email mapping feature be enabled or not
	 * @return DataResponse
	 */
	public function admin(?string $registered_user_group,
		string $allowed_domains,
		string $additional_hint,
		string $email_verification_hint,
		string $username_policy_regex,
		?bool $admin_approval_required,
		?bool $admin_approval_to_group_admin_only,
		?bool $email_is_optional,
		?bool $email_is_login,
		?bool $show_fullname,
		?bool $enforce_fullname,
		?bool $show_phone,
		?bool $enforce_phone,
		?bool $domains_is_blocklist,
		?bool $show_domains,
		?bool $disable_email_verification,
		?bool $per_email_group_mapping): DataResponse {
		// handle domains
		if (($allowed_domains === '') || ($allowed_domains === null)) {
			$this->config->deleteAppValue($this->appName, 'allowed_domains');
		} else {
			$this->config->setAppValue($this->appName, 'allowed_domains', $allowed_domains);
		}

		// handle hints
		if (($additional_hint === '') || ($additional_hint === null)) {
			$this->config->deleteAppValue($this->appName, 'additional_hint');
		} else {
			$this->config->setAppValue($this->appName, 'additional_hint', $additional_hint);
		}

		if (($email_verification_hint === '') || ($email_verification_hint === null)) {
			$this->config->deleteAppValue($this->appName, 'email_verification_hint');
		} else {
			$this->config->setAppValue($this->appName, 'email_verification_hint', $email_verification_hint);
		}

		//handle regex
		if (($username_policy_regex === '') || ($username_policy_regex === null)) {
			$this->config->deleteAppValue($this->appName, 'username_policy_regex');
		} elseif ((@preg_match($username_policy_regex, null) === false)) {
			// validate regex
			return new DataResponse([
				'data' => [
					'message' => $this->l10n->t('Invalid username policy regex'),
				],
				'status' => 'error',
			], Http::STATUS_BAD_REQUEST);
		} else {
			$this->config->setAppValue($this->appName, 'username_policy_regex', $username_policy_regex);
		}

		$this->config->setAppValue($this->appName, 'admin_approval_required', $admin_approval_required ? 'yes' : 'no');
		$this->config->setAppValue($this->appName, 'admin_approval_to_group_admin_only', $admin_approval_to_group_admin_only ? 'yes' : 'no');
		$this->config->setAppValue($this->appName, 'email_is_optional', $email_is_optional ? 'yes' : 'no');
		$this->config->setAppValue($this->appName, 'email_is_login', !$email_is_optional && $email_is_login ? 'yes' : 'no');
		$this->config->setAppValue($this->appName, 'show_fullname', $show_fullname ? 'yes' : 'no');
		$this->config->setAppValue($this->appName, 'enforce_fullname', $enforce_fullname ? 'yes' : 'no');
		$this->config->setAppValue($this->appName, 'show_phone', $show_phone ? 'yes' : 'no');
		$this->config->setAppValue($this->appName, 'enforce_phone', $enforce_phone ? 'yes' : 'no');
		$this->config->setAppValue($this->appName, 'domains_is_blocklist', $domains_is_blocklist ? 'yes' : 'no');
		$this->config->setAppValue($this->appName, 'show_domains', $show_domains ? 'yes' : 'no');
		$this->config->setAppValue($this->appName, 'disable_email_verification', $disable_email_verification ? 'yes' : 'no');
		$this->config->setAppValue($this->appName, 'per_email_group_mapping', $per_email_group_mapping ? 'yes' : 'no');

		if ($registered_user_group === null) {
			$this->config->deleteAppValue($this->appName, 'registered_user_group');
			return new DataResponse([
				'data' => [
					'message' => $this->l10n->t('Saved'),
				],
				'status' => 'success',
			]);
		}

		$group = $this->groupmanager->get($registered_user_group);
		if ($group instanceof IGroup) {
			$this->config->setAppValue($this->appName, 'registered_user_group', $registered_user_group);
			return new DataResponse([
				'data' => [
					'message' => $this->l10n->t('Saved'),
				],
				'status' => 'success',
			]);
		}

		return new DataResponse([
			'data' => [
				'message' => $this->l10n->t('No such group'),
			],
			'status' => 'error',
		], Http::STATUS_NOT_FOUND);
	}

	/**
	 * @AdminRequired
	 *
	 * @param string $email_domains the email domains to match for this rule
	 * @param string $group_name the group to assign the users that match to
	 * @return DataResponse
	 */
	public function addGroupMapping(string $email_domains,
		string $group_name): DataResponse {
		// Check email domains is valid string
		if (($email_domains === '') || ($email_domains === null)) {
			return new DataResponse([
				'data' => [
					'message' => $this->l10n->t('Invalid email domain list'),
				],
				'status' => 'error',
			], Http::STATUS_BAD_REQUEST);
		}

		// Check group is valid
		$group = $this->groupmanager->get($group_name);
		if (!($group instanceof IGroup)) {
			return new DataResponse([
				'data' => [
					'message' => $this->l10n->t('No such group'),
				],
				'status' => 'error',
			], Http::STATUS_NOT_FOUND);
		}

		// Add new mapping
		$groupMapping = new Group();
		$groupMapping->setEmailDomains($email_domains);
		$groupMapping->setGroupId($group_name);
		$newMapping = $this->groupMapper->insert($groupMapping);

		// Return result
		return new DataResponse([
			'data' => [
				'message' => $this->l10n->t('Saved'),
				'id' => $newMapping->getId()
			],
			'status' => 'success',
		]);
	}

	/**
	 * @AdminRequired
	 *
	 * @param int $id The ID of the row to delete
	 * @return DataResponse
	 */
	public function deleteGroupMapping(int $id): DataResponse {
		// Check mapping is valid
		$groupMapping = $this->groupMapper->getById($id);
		if (!($groupMapping instanceof Group)) {
			return new DataResponse([
				'data' => [
					'message' => $this->l10n->t('No such group mapping'),
				],
				'status' => 'error',
			], Http::STATUS_NOT_FOUND);
		}

		// Delete mapping
		$this->groupMapper->delete($groupMapping);
		return new DataResponse([
			'data' => [
				'message' => $this->l10n->t('Deleted')
			],
			'status' => 'success',
		]);
	}
}