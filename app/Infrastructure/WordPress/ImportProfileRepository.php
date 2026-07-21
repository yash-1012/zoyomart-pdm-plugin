<?php

namespace Zoyomart\PDM\Infrastructure\WordPress;

use Zoyomart\PDM\Domain\Import\ImportProfile;

final class ImportProfileRepository {
	private const OPTION_NAME = 'zoyomart_pdm_import_profiles';

	public function all(): array {
		$profiles = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $profiles ) ) {
			return array();
		}
		return array_map( array( ImportProfile::class, 'from_array' ), $profiles );
	}

	public function find( string $id ): ?ImportProfile {
		foreach ( $this->all() as $profile ) {
			if ( hash_equals( $profile->id(), $id ) ) {
				return $profile;
			}
		}
		return null;
	}

	public function save( ImportProfile $profile ): void {
		$profiles = $this->all();
		$stored   = array();
		$replaced = false;
		foreach ( $profiles as $existing ) {
			if ( $existing->id() === $profile->id() ) {
				$stored[] = $profile->to_array();
				$replaced = true;
			} else {
				$stored[] = $existing->to_array();
			}
		}
		if ( ! $replaced ) {
			$stored[] = $profile->to_array();
		}
		update_option( self::OPTION_NAME, $stored, false );
	}

	public function delete( string $id ): void {
		$profiles = array_filter(
			$this->all(),
			static fn( ImportProfile $profile ): bool => $profile->id() !== $id
		);
		update_option( self::OPTION_NAME, array_map( static fn( ImportProfile $profile ): array => $profile->to_array(), $profiles ), false );
	}
}
