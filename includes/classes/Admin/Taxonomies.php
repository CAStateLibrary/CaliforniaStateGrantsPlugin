<?php
/**
 * Customize taxonomies.
 */

namespace CaGov\Grants\Admin;

/**
 * Customize taxonomies
 */
class Taxonomies {

	/**
	 * Setup everything.
	 *
	 * @return void
	 */
	public function setup() {
		add_filter( 'ca_grants_taxonomy_args', [ $this, 'hide_taxonomy_metabox' ], 10, 2 );
	}

	/**
	 * Hide taxonomy metabox if needed.
	 *
	 * @param array  $args     Taxonomy args.
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return array Modified taxonomy args.
	 */
	public function hide_taxonomy_metabox( $args, $taxonomy ) {
		if ( ! in_array( $taxonomy, $this->taxonomies_without_metabox(), true)) {
			return $args;
		}

		$args['meta_box_cb'] = false;

		return $args;
	}

	/**
	 * List of taxonomies without metabox.
	 *
	 * @return string[]
	 */
	public function taxonomies_without_metabox() {
		return [
			'grant_categories',
			'applicant_type',
			'disbursement_method',
			'opportunity_types',
			'revenue_sources',
		];
	}
}
