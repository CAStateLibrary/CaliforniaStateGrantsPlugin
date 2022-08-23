<?php
/**
 * Bulk Award Upload Background Process.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Cron;

use CaGov\Grants\Meta\Field;
use CaGov\Grants\PostTypes\EditGrantAwards;
use CaGov\Grants\PostTypes\GrantAwards;
use CaGov\Grants\PostTypes\AwardUploads;
use ElasticPress\Indexables;
use CaGov\Grants\PostTypes\Grants;
use WP_Query;

/**
 * BulkAwardImport Class
 */
class BulkAwardImport {

	/**
	 * Cron job name to check bulk award hourly.
	 *
	 * @var string
	 */
	public static $hourly_check_job = 'csl_sync_awards';

	/**
	 * Cron job name to send email about failed bulk uploads.
	 *
	 * @var string
	 */
	public static $hourly_failure_email_job = 'csl_sync_failed_email';

	/**
	 * Cron job name to import csv chunk data.
	 *
	 * @var string
	 */
	public static $import_chunk_job = 'csl_sync_award_entries';

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

		add_action( 'admin_init', array( $this, 'register_cron_jobs' ) );
		add_action( self::$hourly_check_job, array( $this, 'schedule_import_awards_queue' ) );
		add_action( self::$import_chunk_job, array( $this, 'import_award_upload_chunk' ), 10, 4 );

		add_action( self::$hourly_failure_email_job, array( $this, 'send_import_failed_email' ) );

		self::$init = true;
	}

	/**
	 * Registers the cron-jobs.
	 *
	 * @return void
	 */
	public function register_cron_jobs() {
		if ( ! wp_next_scheduled( self::$hourly_check_job ) ) {
			wp_schedule_event( time(), 'hourly', self::$hourly_check_job );
		}

		if ( ! wp_next_scheduled( self::$hourly_failure_email_job ) ) {
			wp_schedule_event( time(), 'hourly', self::$hourly_failure_email_job );
		}
	}

	/**
	 * Check for award bulk upload record and schedule
	 * data import from csv.
	 * ( Schedule single event with csv data chunk of 10 record )
	 *
	 * @param WP_Post[] $posts Array of award posts.
	 *
	 * @return void
	 */
	public static function schedule_import_awards_queue( $posts = [] ) {
		$award_uploads = empty( $posts ) ? self::get_import_records() : $posts;

		foreach ( $award_uploads as $award_upload ) {
			if ( ! $award_upload instanceof \WP_Post ) {
				return;
			}

			$meta_keys         = array(
				'csl_grant_id',
				'csl_award_csv',
				'csl_fiscal_year',
			);
			$award_upload_data = get_post_meta( $award_upload->ID );
			$award_upload_data = wp_array_slice_assoc( $award_upload_data, $meta_keys );
			$award_upload_data = array_map(
				function( $meta_value ) {
					return ( ! empty( $meta_value ) && is_array( $meta_value ) ) ? $meta_value[0] : $meta_value;
				},
				$award_upload_data
			);

			$grant_post_type_obj = get_post_type_object( Grants::get_cpt_slug() );
			$edit_cap            = ! empty( $grant_post_type_obj->cap->edit_post ) ? $grant_post_type_obj->cap->edit_post : 'edit_post';

			if ( ! user_can( $award_upload->post_author, $edit_cap, absint( $award_upload_data['csl_grant_id'] ) ) ) {
				return;
			}

			$is_scheduled = self::schedule_csv_chunk_import( $award_upload_data['csl_award_csv'], $award_upload, $award_upload_data );

			if ( $is_scheduled ) {
				wp_trash_post( $award_upload->ID );
			} else {
				wp_update_post(
					array(
						'ID'          => $award_upload->ID,
						'post_status' => 'csl_failed',
					)
				);
			}
		}
	}

	/**
	 * Check if there are any failed imports. If yes then send an email.
	 *
	 * @return void
	 */
	public function send_import_failed_email() {
		$failed_upload_ids = $this->get_failed_uploads();

		foreach ( $failed_upload_ids as $failed_upload_id ) {
			/**
			 * Bulk Award Import failed.
			 */
			do_action( 'csl_grants_bulk_award_import_failed', $failed_upload_id );

			update_post_meta( $failed_upload_id, 'failure_email_sent', time() );
		}
	}

	/**
	 * Get award upload records to schedule csv import.
	 *
	 * @return \WP_Post[]
	 */
	public static function get_import_records() {

		$query_args = array(
			'post_type'              => AwardUploads::CPT_SLUG,
			'post_status'            => 'pending',
			'posts_per_page'         => 100,
			'no_found_rows'          => true,
			'orderby'                => 'date',
			'order'                  => 'ASC',
			'update_post_term_cache' => false,
		);

		$posts = new WP_Query( $query_args );

		return empty( $posts->posts ) ? [] : $posts->posts;
	}

	/**
	 * Read csv data and schedule single cron event for
	 * csv chunk of 10 record.
	 *
	 * @param int     $file_id Attachment file ID.
	 * @param WP_Post $award_upload Award Upload Post Data.
	 * @param array   $award_upload_data Award
	 *
	 * @return boolean
	 */
	public static function schedule_csv_chunk_import( $file_id, $award_upload, $award_upload_data ) {
		$csv_file_path = get_attached_file( $file_id );
		$data          = [
			'grantID'    => $award_upload_data['csl_grant_id'] ?: 0,
			'fiscalYear' => $award_upload_data['csl_fiscal_year'] ?: '',
		];

		if ( empty( $csv_file_path ) || is_wp_error( AwardUploads::validate_csv_file( $csv_file_path, $data ) ) ) {
			return false;
		}

		$csv_data = AwardUploads::read_csv( $csv_file_path );

		if ( empty( $csv_data ) ) {
			return false;
		}

		// Save total csv data count to award uploads.
		update_post_meta( $award_upload->ID, 'csl_award_count', count( $csv_data ) );

		$csv_chunks       = array_chunk( $csv_data, 10 );
		$schedule_time    = time();
		$scheduled_chunks = 0;

		foreach ( $csv_chunks as $csv_chunk ) {
			if ( empty( $csv_chunk ) ) {
				continue;
			}

			$result = wp_schedule_single_event(
				$schedule_time,
				self::$import_chunk_job,
				array(
					'csv_chunk'    => wp_json_encode( $csv_chunk ),
					'award_upload' => $award_upload,
					'grant_id'     => $award_upload_data['csl_grant_id'] ?: 0,
					'fiscal_year'  => $award_upload_data['csl_fiscal_year'] ?: '',
				),
				true
			);

			if ( true !== $result ) {
				return false;
				break;
			}

			$scheduled_chunks++;

			// Next event schedule after 5 min.
			$schedule_time = $schedule_time + ( 5 * MINUTE_IN_SECONDS );
		}

		return count( $csv_chunks ) === $scheduled_chunks;
	}

	/**
	 * Import award upload chunk to Grant Award CPT.
	 *
	 * @param string  $csv_chunk CSV Data.
	 * @param WP_Post $award_upload Award Bulk Upload post object.
	 * @param int     $grant_id Grant ID.
	 * @param string  $fiscal_year Fiscal Year data.
	 *
	 * @return void
	 */
	public function import_award_upload_chunk( $csv_chunk, $award_upload, $grant_id, $fiscal_year = null ) {
		if ( 'csl_failed' === get_post_status( $award_upload ) ) {
			return;
		}

		$total_imported = get_post_meta( $award_upload->ID, 'csl_imported_awards', true );
		$total_imported = $total_imported ? (int) $total_imported : 0;
		$total_count    = get_post_meta( $award_upload->ID, 'csl_award_count', true );
		$total_count    = $total_count ? (int) $total_count : 0;
		$csv_chunk      = json_decode( $csv_chunk, true );

		if ( empty( $csv_chunk ) ) {
			return;
		}

		foreach ( $csv_chunk as $grant_award ) {
			$award_data = wp_parse_args(
				array(
					'grantID'    => $grant_id,
					'fiscalYear' => $fiscal_year,
				),
				$grant_award
			);

			$args = array(
				'post_author' => $award_upload->post_author,
				'post_title'  => $award_upload->post_title,
				'post_type'   => GrantAwards::CPT_SLUG,
				'post_status' => 'publish',
			);

			$grant_award_id = wp_insert_post( $args );

			if ( is_wp_error( $grant_award_id ) ) {
				wp_update_post(
					array(
						'ID'          => $award_upload->ID,
						'post_status' => 'csl_failed',
					)
				);
				continue;
			}

			$meta_fields = EditGrantAwards::get_all_meta_fields();

			if ( ! empty( $meta_fields ) ) {
				Field::sanitize_and_save_fields( $meta_fields, $grant_award_id, $award_data );
				EditGrantAwards::update_grant_award_data( $grant_award_id );
			}

			$total_imported = $total_imported + 1;
			update_post_meta( $award_upload->ID, 'csl_imported_awards', $total_imported );

			if ( class_exists( Indexables::class ) ) {
				Indexables::factory()->get( 'post' )->index( $grant_award_id, true );
			}
		}

		if ( $total_count === $total_imported ) {
			$this->cleanup_award_upload( $award_upload->ID );
		}
	}

	/**
	 * Cleanup award bulk upload cpt data.
	 *
	 * @param int $award_upload_id Award Upload post id.
	 *
	 * @return void
	 */
	public function cleanup_award_upload( $award_upload_id ) {
		// Do cleanup only if it's moved to trash.
		if ( 'trash' === get_post_status( $award_upload_id ) ) {
			return;
		}

		/**
		 * Bulk Award Import was successful.
		 */
		do_action( 'csl_grants_bulk_award_import_success', $award_upload_id );
	}

	/**
	 * Get the list of failed uploads for which the email has not been sent yet.
	 *
	 * @return int[] Failed upload ids.
	 */
	protected function get_failed_uploads() {
		$query_args = array(
			'post_type'      => AwardUploads::CPT_SLUG,
			'post_status'    => 'csl_failed',
			'posts_per_page' => 100, // If there are more than 100 failed uploads then they will be processed in the next batch.
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'     => 'failure_email_sent',
					'compare' => 'NOT EXISTS',
				],
			],
			'no_found_rows'  => true,
		);

		return ( new WP_Query( $query_args ) )->posts;
	}
}
