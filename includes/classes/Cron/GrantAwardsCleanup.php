<?php
/**
 * Grant Award cleanup with Associated Grant trash/delete operation.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Cron;

use CaGov\Grants\PostTypes\GrantAwards;
use CaGov\Grants\PostTypes\Grants;

/**
 * GrantAwardsCleanup Class
 */
class GrantAwardsCleanup {

	/**
	 * Cron job name to delete or trash grant awards based on it's associated grant action.
	 *
	 * @var string
	 */
	public static $cleanup_job = 'csl_grant_awards_cleanup';

	/**
	 * Cron job name to restore grant awards when assocaited grant is restored.
	 *
	 * @var string
	 */
	public static $restore_job = 'csl_grant_awards_restore';

	/**
	 * Init.
	 *
	 * @var boolean
	 */
	public static $init = false;

	/**
	 * Register actions and filters with WordPress API.
	 *
	 * @return void
	 */
	public function setup() {
		if ( self::$init ) {
			return;
		}

		add_action( 'trashed_post', [ $this, 'schedule_trash_grant_awards' ] );
		add_action( 'untrashed_post', [ $this, 'schedule_untrash_grant_awards' ] );
		add_action( 'deleted_post', [ $this, 'schedule_delete_grant_awards' ], 10, 2 );

		add_action( self::$cleanup_job, [ $this, 'cleanup_grant_awards' ], 10, 3 );
		add_action( self::$restore_job, [ $this, 'restore_grant_awards' ] );

		self::$init = true;
	}

	/**
	 * Get grant awards from associated grant ids.
	 *
	 * @param int $grant_id Post id for grant.
	 *
	 * @return array
	 */
	public static function get_associated_awards( $grant_id ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT wp.ID
			FROM {$wpdb->posts} wp LEFT JOIN
					{$wpdb->postmeta} wpm
				ON ( wp.ID = wpm.post_id AND wp.post_type = %s )
			WHERE
				wpm.meta_key = 'grantID'
				AND wpm.meta_value = %d",
			GrantAwards::CPT_SLUG,
			$grant_id
		);

		return $wpdb->get_col( $sql );
	}

	/**
	 * After grant is sent to trash look for related grant awards and schedule to move all grant award also to trash.
	 *
	 * @param int $post_id Post ID.
	 */
	public function schedule_trash_grant_awards( $post_id ) {
		$post_type = get_post_type( $post_id );

		// Check if this is grant cpt trash action.
		if ( $post_type !== Grants::get_cpt_slug() ) {
			return;
		}

		$award_ids = self::get_associated_awards( $post_id );

		if ( empty( $award_ids ) ) {
			return;
		}

		wp_schedule_single_event(
			time(),
			self::$cleanup_job,
			[
				'action'    => 'trash',
				'award_ids' => $award_ids,
			]
		);
	}

	/**
	 * After grant is deleted from db look for related grant awards and schedule to delete grant awards.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post   Post object.
	 */
	public function schedule_delete_grant_awards( $post_id, $post ) {

		// Check if this is grant cpt delete action.
		if (
			empty( $post->post_type )
			|| $post->post_type !== Grants::get_cpt_slug()
		) {
			return;
		}

		$award_ids = self::get_associated_awards( $post_id );

		if ( empty( $award_ids ) ) {
			return;
		}

		wp_schedule_single_event(
			time(),
			self::$cleanup_job,
			[
				'action'    => 'delete',
				'award_ids' => $award_ids,
			]
		);
	}

	/**
	 * After grant is restored from trash schedule a cron job to restore related grant awards.
	 *
	 * @param int $post_id Post ID.
	 */
	public function schedule_untrash_grant_awards( $post_id ) {

		$post_type = get_post_type( $post_id );

		// Check if this is grant cpt trash action.
		if ( $post_type !== Grants::get_cpt_slug() ) {
			return;
		}

		$award_ids = self::get_associated_awards( $post_id );

		if ( empty( $award_ids ) ) {
			return;
		}

		wp_schedule_single_event(
			time(),
			self::$restore_job,
			[
				'award_ids' => $award_ids,
			]
		);
	}

	/**
	 * Move grant awards to trash or delete based on action.
	 *
	 * @param string $action Action to perfom for grant award.
	 * @param array  $award_ids Grant Awards ids.
	 *
	 * @return void
	 */
	public function cleanup_grant_awards( $action, $award_ids ) {
		foreach ( $award_ids as $award_id ) {
			if ( 'delete' === $action ) {
				wp_delete_post( $award_id );
			} elseif ( 'trash' === $action ) {
				wp_trash_post( $award_id );
			}
		}
	}

	/**
	 * Restore grant awards.
	 *
	 * @param array $award_ids Grant Award ids.
	 *
	 * @return void
	 */
	public function restore_grant_awards( $award_ids ) {
		foreach ( $award_ids as $award_id ) {
			wp_untrash_post( $award_id );
		}
	}
}
