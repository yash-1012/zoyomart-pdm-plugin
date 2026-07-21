<?php

namespace Zoyomart\PDM\Infrastructure\WordPress;

/**
 * Stores a short-lived, user-scoped workbook preview between admin requests.
 */
final class ImportPreviewRepository {
	private const EXPIRATION = 21600;

	public function save( int $user_id, array $preview ): void {
		set_transient( $this->key( $user_id ), $preview, self::EXPIRATION );
	}

	public function get( int $user_id ): ?array {
		$preview = get_transient( $this->key( $user_id ) );
		if ( ! is_array( $preview ) || empty( $preview['file_path'] ) || ! file_exists( $preview['file_path'] ) ) {
			return null;
		}
		return $preview;
	}

	private function key( int $user_id ): string {
		return 'zoyomart_pdm_preview_' . $user_id;
	}
}
