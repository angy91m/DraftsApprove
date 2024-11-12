<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserIdentity;

/**
 * Class representing a single draft.
 *
 * @file
 * @ingroup Extensions
 */
class Draft {
	/** @var bool */
	private $exists = false;
	/** @var int */
	private $id;
	/** @var string */
	private $token;
	/** @var int */
	private $userID;

	/** @var Title */
	private $title;
	/** @var int */
	private $section;
	/** @var string */
	private $starttime;
	/** @var string */
	private $edittime;
	/** @var string */
	private $savetime;
	/** @var int */
	private $scrolltop;
	/** @var string */
	private $text;
	/** @var string */
	private $summary;
	/** @var bool */
	private $minoredit;
	/** @var string */
	private $status;

	/**
	 * Creates a new Draft object from a draft ID
	 *
	 * @param int $id ID of draft
	 * @param bool $autoload Whether to load draft information
	 * @return Draft
	 */
	public static function newFromID( $id, $autoload = true ) {
		return new Draft( $id, $autoload );
	}

	/**
	 * Creates a new Draft object from a database row
	 *
	 * @param stdClass $row Database row object to create Draft object with
	 * @return Draft
	 */
	public static function newFromRow( $row ) {
		$draft = new Draft( $row->draft_id, false );
		$draft->setToken( $row->draft_token );
		$draft->setTitle(
			Title::makeTitle( $row->draft_namespace, $row->draft_title )
		);
		$draft->setSection( $row->draft_section );
		$draft->setStartTime( $row->draft_starttime );
		$draft->setEditTime( $row->draft_edittime );
		$draft->setSaveTime( $row->draft_savetime );
		$draft->setScrollTop( $row->draft_scrolltop );
		$draft->setText( $row->draft_text );
		$draft->setSummary( $row->draft_summary );
		$draft->setMinorEdit( $row->draft_minoredit );
		$draft->setStatus( $row->draft_status );
		return $draft;
	}

	/**
	 * @return bool Whether draft exists in database
	 */
	public function exists() {
		return $this->exists;
	}

	/**
	 * @return int Draft ID
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * @return string Edit token
	 */
	public function getToken() {
		return $this->token;
	}

	/**
	 * Sets the edit token, like one generated by wfGenerateToken()
	 * @param string $token
	 */
	public function setToken( $token ) {
		$this->token = $token;
	}

	/**
	 * @return int User ID of draft creator
	 */
	public function getUserID() {
		return $this->userID;
	}

	/**
	 * Sets user ID of draft creator
	 * @param int $userID User ID
	 */
	public function setUserID( $userID ) {
		$this->userID = $userID;
	}

	/**
	 * @return Title of article of draft
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets title of article of draft
	 * @param Title $title
	 */
	public function setTitle( $title ) {
		$this->title = $title;
	}

	/**
	 * @return int Section of the article of draft
	 */
	public function getSection() {
		return $this->section;
	}

	/**
	 * Sets section of the article of draft
	 * @param int $section
	 */
	public function setSection( $section ) {
		$this->section = $section;
	}

	/**
	 * @return string Time when draft of the article started
	 */
	public function getStartTime() {
		return $this->starttime;
	}

	/**
	 * Sets time when draft of the article started
	 * @param string $starttime
	 */
	public function setStartTime( $starttime ) {
		$this->starttime = $starttime;
	}

	/**
	 * @return string Time of most recent revision of article when this draft started
	 */
	public function getEditTime() {
		return $this->edittime;
	}

	/**
	 * Sets time of most recent revision of article when this draft started
	 * @param string $edittime
	 */
	public function setEditTime( $edittime ) {
		$this->edittime = $edittime;
	}

	/**
	 * @return string Time when draft was last modified
	 */
	public function getSaveTime() {
		return $this->savetime;
	}

	/**
	 * Sets time when draft was last modified
	 * @param string $savetime
	 */
	public function setSaveTime( $savetime ) {
		$this->savetime = $savetime;
	}

	/**
	 * @return int Scroll position of editor when draft was last modified
	 */
	public function getScrollTop() {
		return $this->scrolltop;
	}

	/**
	 * Sets scroll position of editor when draft was last modified
	 * @param int $scrolltop
	 */
	public function setScrollTop( $scrolltop ) {
		$this->scrolltop = $scrolltop;
	}

	/**
	 * @return string Text of draft version of article
	 */
	public function getText() {
		return $this->text;
	}

	/**
	 * Sets text of draft version of article
	 * @param string $text
	 */
	public function setText( $text ) {
		$this->text = $text;
	}

	/**
	 * @return string Summary of changes
	 */
	public function getSummary() {
		return $this->summary;
	}

	/**
	 * Sets summary of changes
	 * @param string $summary
	 */
	public function setSummary( $summary ) {
		$this->summary = $summary;
	}

	/**
	 * @return bool Whether edit is considdered to be a minor change
	 */
	public function getMinorEdit() {
		return $this->minoredit;
	}

	/**
	 * Sets whether edit is considdered to be a minor change
	 * @param bool $minoredit
	 */
	public function setMinorEdit( $minoredit ) {
		$this->minoredit = $minoredit;
	}

	/**
	 * @return string Status of draft
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Sets status of draft
	 * @param string $status
	 */
	public function setStatus( $status ) {
		$this->status = $status;
	}

	/* Functions */

	/**
	 * Generic constructor
	 * @param int|null $id [optional] ID to use
	 * @param bool $autoload [optional] Whether to load from database
	 */
	public function __construct( $id = null, $autoload = true ) {
		// If an ID is a number the existence is actually checked on load
		// If an ID is false the existance is always false during load
		$this->id = $id;
		// Load automatically
		if ( $autoload ) {
			$this->load();
		}
	}

	/**
	 * Selects draft row from database and populates object properties
	 */
	private function load() {
		// Checks if the ID of the draft was set
		if ( $this->id === null ) {
			// Exists immediately
			return;
		}
		// Gets database connection
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		// Gets drafts for this article and user from database
		$row = $dbw->selectRow(
			'drafts',
			[ '*' ],
			[
				'draft_id' => (int)$this->id
			],
			__METHOD__
		);
		// Checks if query returned any results
		if ( $row === false ) {
			// Exists immediately
			return;
		}
		// Synchronizes data
		$this->token = $row->draft_token;
		$this->userID = (int)$row->draft_user;
		$this->title = Title::makeTitle(
			$row->draft_namespace, $row->draft_title
		);
		$this->section = $row->draft_section;
		$this->starttime = $row->draft_starttime;
		$this->edittime = $row->draft_edittime;
		$this->savetime = $row->draft_savetime;
		$this->scrolltop = $row->draft_scrolltop;
		$this->text = $row->draft_text;
		$this->summary = $row->draft_summary;
		$this->minoredit = $row->draft_minoredit;
		$this->status = $row->draft_status;
		// Updates state
		$this->exists = true;
	}

	/**
	 * Inserts or updates draft row in database
	 * @return bool
	 */
	public function save() {
		$userId = RequestContext::getMain()->getUser()->getId();
		// Gets database connection
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->startAtomic( __METHOD__ );
		// Builds insert/update information
		$data = [
			'draft_token' => (string)$this->getToken(),
			'draft_user' => $userId,
			'draft_namespace' => $this->title->getNamespace(),
			'draft_title' => $this->title->getDBkey(),
			'draft_page' => (int)$this->title->getArticleID(),
			'draft_section' => $this->section == '' ? null : (int)$this->section,
			'draft_starttime' => $dbw->timestamp( $this->starttime ),
			'draft_edittime' => $dbw->timestamp( $this->edittime ),
			'draft_savetime' => $dbw->timestamp( $this->savetime ),
			'draft_scrolltop' => (int)$this->scrolltop,
			'draft_text' => (string)$this->text,
			'draft_summary' => (string)$this->summary,
			'draft_minoredit' => (int)$this->minoredit,
			'draft_status' => (string)$this->status
		];
		// Checks if draft already exists
		if ( $this->exists === true ) {
			// Updates draft information
			$dbw->update(
				'drafts',
				$data,
				[
					'draft_id' => (int)$this->id,
					'draft_user' => $userId
				],
				__METHOD__
			);
		} else {
			// Gets a draft token exists for the current user and article
			$existingRow = $dbw->selectField(
				'drafts',
				'draft_token',
				[
					'draft_user' => $data['draft_user'],
					'draft_namespace' => $data['draft_namespace'],
					'draft_title' => $data['draft_title'],
					'draft_token' => $data['draft_token'],
					'draft_status' => $data['draft_status']
				],
				__METHOD__
			);
			// Checks if token existed, meaning it has been used already for
			// this article
			if ( $existingRow === false ) {
				// Inserts row in the database
				$dbw->insert( 'drafts', $data, __METHOD__ );
				// Gets the id of the newly inserted row
				$this->id = $dbw->insertId();
				// Updates state
				$this->exists = true;
			}
		}
		// Commits any processed changes
		$dbw->endAtomic( __METHOD__ );
		// Returns success
		return true;
	}

	/**
	 * Deletes draft row from database
	 * @param UserIdentity|null $user User object, defaults to current user
	 */
	public function discard( $user = null ) {
		// Uses RequestContext user as a fallback
		$user = $user === null ? RequestContext::getMain()->getUser() : $user;
		// Gets database connection
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		// Deletes draft from database verifying propper user to avoid hacking!
		$dbw->delete(
			'drafts',
			[
				'draft_id' => $this->id,
				'draft_user' => $user->getId()
			],
			__METHOD__
		);
		// Updates state
		$this->exists = false;
	}

	/**
	 * Set draft row status to 'refused'
	 * @param UserIdentity|null $user User object, defaults to current user
	 */
	public function refuse( $user = null ) {
		// Uses RequestContext user as a fallback
		$user = $user === null ? RequestContext::getMain()->getUser() : $user;
		if(!$user->isAllowed('drafts-approve')) {
			return;
		}
		// Gets database connection
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->update(
			'drafts',
			[
				'draft_status' => 'refused'
			],
			[
				'draft_id' => $this->id
			],
			__METHOD__
		);
	}
}
