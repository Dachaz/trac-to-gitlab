<?php

namespace Trac2GitLab;

define('USER_FETCH_PAGE_SIZE', 1000);
define('USER_FETCH_MAX_PAGES', 50);

use Gitlab\Client;
use Gitlab\Model\Issue;

/**
 * GitLab communicator class
 *
 * @author  dachaz
 */
class GitLab
{
    private $client;
    private $url;
    private $isAdmin;

    /**
     * Constructor
     *
     * @param  string    $url         GitLab URL
     * @param  string    $token       GitLab API private token
     * @param  boolean   $isAdmin     Indicates that the GitLab token is from an admin user
     */
	public function __construct($url, $token, $isAdmin) {
		$this->url = $url;
		$this->client = new Client($url . "/api/v3/");
		$this->client->authenticate($token, Client::AUTH_URL_TOKEN);
		$this->isAdmin = $isAdmin;
	}


	/**
 	 * Tries to fetch all the users from GitLab. Will get at most 
 	 * (USER_FETCH_PAGE_SIZE * USER_FETCH_MAX_PAGES) users. Returns
 	 * a map of {username => userInfoObject}
 	 *
 	 * @return  array
 	 */
	public function listUsers() {
		$users = array();
		$gotAll = false;
		$page = 1;
		// Stop when we either have all users or we have exhausted the sane number of attempts
		while (!$gotAll && $page < USER_FETCH_MAX_PAGES) {
			$response = $this->client->api('users')->all(null, $page++, USER_FETCH_PAGE_SIZE);
			foreach($response as $user) {
				$users[$user['username']] = $user;
				// We assume that 'Administrator' user is in there
				$gotAll = $user['id'] == 1;
			}
		}
		return $users;
	}

	/**
	 * Creates a new issue in the given project. When working in admin mode, tries to create the issue
	 * as the given author (SUDO) and if that fails, tries creating the ticket again as the admin.
	 * @param  mixed    $projectId    Numeric project id (e.g. 17) or the unique string identifier (e.g. 'dachaz/trac-to-gitlab')
     * @param  string   $title        Title of the new issue
     * @param  string   $description  Description of the new issue
     * @param  int      $assigneeId   Numeric user id of the user asigned to the issue. Can be null.
     * @param  int      $authorId     Numeric user id of the user who created the issue. Only used in admin mode. Can be null.
     * @param  array    $labels       Array of string labels to be attached to the issue. Analoguous to trac keywords.
     * @return  Gitlab\Model\Issue
	 */
	public function createIssue($projectId, $title, $description, $assigneeId, $authorId, $labels) {
		try {
			// Try to add, potentially as an admin (SUDO authorId)
			$issue = $this->doCreateIssue($projectId, $title, $description, $assigneeId, $authorId, $labels, $this->isAdmin);
		} catch (\Gitlab\Exception\RuntimeException $e) {
			// If adding has failed because of SUDO (author does not have access to the project), create an issue without SUDO (as the Admin user whose token is configured)
			if ($this->isAdmin) {
				$issue = $this->doCreateIssue($projectId, $title, $description, $assigneeId, $authorId, $labels, false);
			} else {
				// If adding has failed for some other reason, propagate the exception back
				throw $e;
			}
		}
		return $issue;
	}

	/**
	 * Creates a new note in the given project and on the given issue id (NOTE: id, not iid). When working in admin mode, tries to create the note
	 * as the given author (SUDO) and if that fails, tries creating the note again as the admin.
	 * @param  mixed    $projectId    Numeric project id (e.g. 17) or the unique string identifier (e.g. 'dachaz/trac-to-gitlab')
     * @param  int      $issueId      Unique identifier of the issue
     * @param  string   $text         Text of the note
     * @param  int      $authorId     Numeric user id of the user who created the issue. Only used in admin mode. Can be null.
     * @return  Gitlab\Model\Note
	 */
	public function createNote($projectId, $issueId, $text, $authorId) {
		try {
			// Try to add, potentially as an admin (SUDO authorId)
			$note = $this->doCreateNote($projectId, $issueId, $text, $authorId, $this->isAdmin);
		} catch (\Gitlab\Exception\RuntimeException $e) {
			// If adding has failed because of SUDO (author does not have access to the project), create an issue without SUDO (as the Admin user whose token is configured)
			if ($this->isAdmin) {
				$note = $this->doCreateNote($projectId, $issueId, $text, $authorId, false);
			} else {
				// If adding has failed for some other reason, propagate the exception back
				throw $e;
			}
		}
		return $note;
	}

	// Actually creates the issue
	private function doCreateIssue($projectId, $title, $description, $assigneeId, $authorId, $labels, $isAdmin) {
		$issueProperties = array(
			'title' => $title,
			'description' => $description,
			'assignee_id' => $assigneeId,
			'labels' => $labels
		);
		if ($isAdmin) {
			$issueProperties['sudo'] = $authorId;
		}
		return $this->client->api('issues')->create($projectId, $issueProperties);
	}

	// Actually creates the note
	private function doCreateNote($projectId, $issueId, $text, $authorId, $isAdmin) {
		$noteProperties = array(
			'body' => $text
		);
		if ($isAdmin) {
			$noteProperties['sudo'] = $authorId;
		}
		return $this->client->api('issues')->addComment($projectId, $issueId, $noteProperties);
	}

	/**
	 * Returns the URL of this GitLab installation.
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}
}
?>