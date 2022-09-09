<?php
/**
 * Validators.
 *
 * @package Grantsportal
 */

namespace CaGov\Grants\Helpers\Validators;

/**
 * Validate boolean.
 *
 * @param mixed $value The value.
 * @return boolean
 */
function validate_boolean( $value ) : bool {
	return is_bool( $value );
}

/**
 * Validate integer.
 *
 * @param mixed $value The value.
 * @return boolean
 */
function validate_int( $value ) : bool {
	return is_int( $value );
}

/**
 * Validate string
 *
 * @param mixed $value     The value.
 * @param int   $max_chars The maximum amount of characters.
 * @return boolean
 */
function validate_string( $value, $max_chars = null ) : bool {
	if ( ! is_string( $value ) ) {
		return false;
	}

	if ( $max_chars ) {
		return strlen( $value ) <= $max_chars;
	}

	return true;
}

/**
 * Validate string
 *
 * @param mixed $value        The value.
 * @param array $valid_values Array of valid values.
 * @return boolean
 */
function validate_string_in( $value, $valid_values = array() ) : bool {
	return validate_string( $value ) && in_array( $value, $valid_values, true );
}

/**
 * Validate date.
 *
 * @param  mixed $value The value.
 * @return boolean
 */
function validate_date( $value ) : bool {
	// Handle timestamp values.
	if ( is_numeric( $value ) ) {
		$value = '@' . $value;
	}

	$date = new \DateTime( $value );
	return $date && $date->format( 'c' );
}

/**
 * Validates that a given date occurs after a compare date.
 *
 * @param string $value   A date formatted string.
 * @param string $compare The date formatted string to compare to.
 * @return boolean
 */
function validate_date_after( $value, $compare ) : bool {
	if ( ! validate_date( $value ) || ! validate_date( $compare ) ) {
		return false;
	}

	// Handle timestamp values.
	if ( is_numeric( $value ) ) {
		$value = '@' . $value;
	}

	// Handle timestamp values.
	if ( is_numeric( $compare ) ) {
		$compare = '@' . $compare;
	}

	$date  = new \DateTime( $value );
	$after = new \DateTime( $compare );

	return $date > $after;
}

/**
 * Validate terms exist.
 *
 * @param  mixed  $value    The value.
 * @param  string $taxonomy The taxonomy.
 * @return boolean
 */
function validate_terms_exist( $value, $taxonomy ) : bool {
	if ( ! validate_array( $value ) ) {
		return false;
	}

	foreach ( $value as $term ) {
		if ( ! term_exists( $term, $taxonomy ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Validates array.
 *
 * @param  mixed $value The value.
 * @return bool
 */
function validate_array( $value ) : bool {
	return is_array( $value );
}
