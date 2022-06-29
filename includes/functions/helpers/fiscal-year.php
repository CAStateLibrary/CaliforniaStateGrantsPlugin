<?php
/**
 * Grant Fiscal Year helpers
 *
 * @package CaGov\Grants\Helpers
 */

namespace CaGov\Grants\Helpers\FiscalYear;

/**
 * Builds a string of fiscal year slugs to filter an API request
 *
 * @param int|null $id The ID of the grant.
 *
 * @return string fiscal year slugs query string
 */
function get_fiscal_years_query_string( $id = null ) {
	$grant_id = $id ? $id : get_post_meta( get_the_ID(), 'grantID', true );

	if ( $grant_id ) {
		$grant_award_stats = get_post_meta( $grant_id, 'awardStats', true );
		if ( ! $grant_award_stats ) {
			return '&slug[]=9999';
		}
		$options = '';
		foreach ( $grant_award_stats as $stat ) {
			$options .= '&slug[]=' . $stat['fiscalYear'];
		}
		return $options;
	} else {
		return '';
	}
}
