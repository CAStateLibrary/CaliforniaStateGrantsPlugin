<?php
/**
 * Validation Helpers.
 *
 * @package CaGov\Grants\Helpers.
 */

namespace CaGov\Grants\Helpers;

/**
 * Validates a field
 *
 * @param  string $key       The key.
 * @param  mixed  $value     The value.
 * @param  array  $post_data All the post data fields.
 * @return bool
 */
function validate_field( $key, $value, $post_data ) {
	switch ( $key ) {
		case 'grantTitle':
		case 'uniqueID':
		case 'grantID':
		case 'categorySuggestions':
		case 'periodOfPerformance':
		case 'expectedAwardDate':
		case 'details':
		case 'agencyURL':
		case 'subscribe':
		case 'events':
		case 'anticipatedOpenDate':
			return Validators\validate_string( $value );
		case 'isForecasted':
			return Validators\validate_string_in( $value, array( 'active', 'forecasted' ) );
		case 'revenueSourceNotes':
			return Validators\validate_string( $value, 200 );
		case 'purpose':
		case 'geoLimitations':
		case 'matchingFundsNotes':
		case 'disbursementMethodNotes':
		case 'applicantTypeNotes':
			return Validators\validate_string( $value, 450 );
		case 'description':
			return Validators\validate_string( $value, 3200 );
		case 'loiRequired':
			return Validators\validate_boolean( $value );
		case 'grantCategories':
			return Validators\validate_terms_exist( $value, 'grant_categories' );
		case 'opportunityType':
			return Validators\validate_terms_exist( $value, 'opportunity_types' );
		case 'applicantType':
			if ( isset( $value['type'] ) ) {
				return Validators\validate_terms_exist( $value['type'], 'applicant_type' );
			}
			return true;
		case 'fundingMethod':
			if ( isset( $value['type'] ) ) {
				return Validators\validate_terms_exist( array( $value['type'] ), 'disbursement_method' );
			}
			return true;
		case 'fundingSource':
			if ( isset( $value['type'] ) ) {
				return Validators\validate_terms_exist( array( $value['type'] ), 'revenue_sources' );
			}
			return true;
		case 'openDate':
			return Validators\validate_date( $value );
		case 'deadline':
			return(
				Validators\validate_date( $value )
				&& Validators\validate_date_after( $value, $post_data['openDate'] )
			);
		case 'matchingFunds':
			return(
				( isset( $value['required'] ) && Validators\validate_boolean( $value['required'] ) )
				&& ( isset( $value['percent'] ) ? Validators\validate_int( $value['percent'] ) : true )
				&& ( isset( $value['notes'] ) ? Validators\validate_string( $value['notes'], 450 ) : true )
			);
		case 'totalEstimatedFunding':
		case 'estimatedAvailableFunds':
		case 'estimatedAvailableFundNotes':
			if (
				isset( $post_data['totalEstimatedFunding'] )
				&& 'fundingAmountNotes' === $post_data['totalEstimatedFunding']
			) {
				return (
					isset( $post_data['estimatedAvailableFundNotes'] )
					&& Validators\validate_string( $post_data['estimatedAvailableFundNotes'], 450 )
				);
			} elseif (
				isset( $post_data['totalEstimatedFunding'] )
				&& 'exactFundingAmount' === $post_data['totalEstimatedFunding']
			) {
				return (
					isset( $post_data['estimatedAvailableFunds'] )
					&& Validators\validate_int( $post_data['estimatedAvailableFunds'] )
				);
			}
			return true;
		case 'estimatedAwards':
			if ( isset( $value['exact'] ) ) {
				return Validators\validate_int( $value['exact'] );
			}
			if ( isset( $value['between'] ) ) {
				return(
					Validators\validate_int( $value['between'][0] )
					&& Validators\validate_int( $value['between'][1] )
				);
			}
			return true;
		case 'estimatedAmounts':
			if ( isset( $value['same'] ) ) {
				return Validators\validate_int( $value['same'] );
			}
			if ( isset( $value['range'] ) ) {
				return(
					Validators\validate_int( $value['range'][0] )
					&& Validators\validate_int( $value['range'][1] )
				);
			}
			return true;
		case 'electronicSubmission':
			return (
				Validators\validate_array( $value )
				&& ( ! $value['email'] || ! $value['url'] )
			);
		case 'contactInfo':
		case 'internalContactInfo':
			return Validators\validate_array( $value );
		case 'agency':
			return Validators\validate_terms_exist( array( $value ), 'agencies' );
		default:
			return true;
	}
}
