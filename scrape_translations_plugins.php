<?php
/**
 * Plugins Translation Scraper
 *
 * Fetches plugin information from WordPress.org API
 * and translation status from translate.wordpress.org.
 *
 * @package PluginsTranslationScraper
 * @version 1.0.0
 * @license GPL-2.0-or-later
 *
 * Copyright (C) 2025 Jesús Amieiro
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

/**
 * Class representing a single plugin's data.
 */
class Plugin_Data {
	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * Plugin URL on WordPress.org.
	 *
	 * @var string
	 */
	public string $url;

	/**
	 * Plugin slug extracted from URL.
	 *
	 * @var string
	 */
	public string $slug;

	/**
	 * Number of active installs as integer.
	 *
	 * @var int
	 */
	public int $active_installs;

	/**
	 * Translation statistics.
	 *
	 * @var Translation_Stats|null
	 */
	public ?Translation_Stats $translations = null;

	/**
	 * Last updated date in human-readable format.
	 *
	 * @var string
	 */
	public string $last_updated = '';

	/**
	 * Constructor.
	 *
	 * @param string $name            Plugin name.
	 * @param string $slug            Plugin slug.
	 * @param int    $active_installs Number of active installs.
	 * @param string $last_updated    Last updated date string from API.
	 */
	public function __construct( string $name, string $slug, int $active_installs, string $last_updated = '' ) {
		$this->name            = $name;
		$this->slug            = $slug;
		$this->url             = 'https://wordpress.org/plugins/' . $slug . '/';
		$this->active_installs = $active_installs;
		$this->last_updated    = $this->format_relative_date( $last_updated );
	}

	/**
	 * Convert plugin data to array.
	 *
	 * @return array<string, mixed> Plugin data as array.
	 */
	public function to_array(): array {
		$data = array(
			'name'            => $this->name,
			'last_updated'    => $this->last_updated,
			'url'             => $this->url,
			'slug'            => $this->slug,
			'active_installs' => $this->active_installs,
		);

		if ( null !== $this->translations ) {
			$data['translations'] = $this->translations->to_array();
		}

		return $data;
	}

	/**
	 * Format a date string as a relative time (e.g., "3 weeks ago").
	 *
	 * @param string $date_string Date string from API (e.g., "2025-07-15 9:33am GMT").
	 * @return string Relative time string.
	 */
	private function format_relative_date( string $date_string ): string {
		if ( empty( $date_string ) ) {
			return 'N/A';
		}

		try {
			$date = new DateTime( $date_string );
			$now  = new DateTime();
			$diff = $now->diff( $date );

			if ( $diff->y > 0 ) {
				return $diff->y === 1 ? '1 year ago' : $diff->y . ' years ago';
			}
			if ( $diff->m > 0 ) {
				return $diff->m === 1 ? '1 month ago' : $diff->m . ' months ago';
			}
			if ( $diff->d >= 7 ) {
				$weeks = (int) floor( $diff->d / 7 );
				return $weeks === 1 ? '1 week ago' : $weeks . ' weeks ago';
			}
			if ( $diff->d > 0 ) {
				return $diff->d === 1 ? '1 day ago' : $diff->d . ' days ago';
			}
			if ( $diff->h > 0 ) {
				return $diff->h === 1 ? '1 hour ago' : $diff->h . ' hours ago';
			}

			return 'just now';
		} catch ( Exception $e ) {
			return 'N/A';
		}
	}
}

/**
 * Class representing translation statistics.
 *
 * Values are sums across available rows (Stable, Stable Readme,
 * Development, Development Readme).
 */
class Translation_Stats {
	/**
	 * Sum of fuzzy translations across all rows.
	 *
	 * @var int
	 */
	public int $fuzzy;

	/**
	 * Sum of untranslated strings across all rows.
	 *
	 * @var int
	 */
	public int $untranslated;

	/**
	 * Sum of strings waiting for review across all rows.
	 *
	 * @var int
	 */
	public int $waiting;

	/**
	 * Sum of strings with changes requested across all rows.
	 *
	 * @var int
	 */
	public int $changes_requested;

	/**
	 * Total strings not translated (sum of all above).
	 *
	 * @var int
	 */
	public int $total_not_translated;

	/**
	 * Translation page URL.
	 *
	 * @var string
	 */
	public string $translation_url;

	/**
	 * Constructor.
	 *
	 * @param int    $fuzzy             Sum of fuzzy translations.
	 * @param int    $untranslated      Sum of untranslated strings.
	 * @param int    $waiting           Sum of strings waiting.
	 * @param int    $changes_requested Sum of strings with changes requested.
	 * @param string $translation_url   URL to translation page.
	 */
	public function __construct( int $fuzzy, int $untranslated, int $waiting, int $changes_requested, string $translation_url = '' ) {
		$this->fuzzy                = $fuzzy;
		$this->untranslated         = $untranslated;
		$this->waiting              = $waiting;
		$this->changes_requested    = $changes_requested;
		$this->total_not_translated = $fuzzy + $untranslated + $waiting + $changes_requested;
		$this->translation_url      = $translation_url;
	}

	/**
	 * Convert translation stats to array.
	 *
	 * @return array<string, mixed> Translation stats as array.
	 */
	public function to_array(): array {
		return array(
			'fuzzy'                => $this->fuzzy,
			'untranslated'         => $this->untranslated,
			'waiting'              => $this->waiting,
			'changes_requested'    => $this->changes_requested,
			'total_not_translated' => $this->total_not_translated,
			'translation_url'      => $this->translation_url,
		);
	}
}

/**
 * Class for scraping web pages.
 */
class Web_Scraper {
	/**
	 * User agent string for HTTP requests.
	 *
	 * @var string
	 */
	private string $user_agent = 'Mozilla/5.0 (compatible; PluginsTranslationScraper/1.0)';

	/**
	 * Request timeout in seconds.
	 *
	 * @var int
	 */
	private int $timeout = 30;

	/**
	 * Fetch HTML content from a URL.
	 *
	 * @param string $url URL to fetch.
	 * @return string|false HTML content or false on failure.
	 */
	public function fetch( string $url ): string|false {
		$context = stream_context_create(
			array(
				'http' => array(
					'method'  => 'GET',
					'header'  => "User-Agent: {$this->user_agent}\r\n",
					'timeout' => $this->timeout,
				),
			)
		);

		$content = @file_get_contents( $url, false, $context );

		return $content;
	}

	/**
	 * Create a DOMDocument from HTML content.
	 *
	 * @param string $html HTML content.
	 * @return DOMDocument DOMDocument object.
	 */
	public function create_dom( string $html ): DOMDocument {
		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( $html, LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_clear_errors();

		return $dom;
	}
}

/**
 * Class for scraping plugins using WordPress.org API.
 */
class Plugins_Scraper {
	/**
	 * WordPress.org API URL for plugin search.
	 */
	private const API_URL = 'https://api.wordpress.org/plugins/info/1.2/';

	/**
	 * Base URL for translations (with placeholder for language and slug).
	 */
	private const TRANSLATE_URL_TEMPLATE = 'https://translate.wordpress.org/locale/%s/default/wp-plugins/%s/';

	/**
	 * Web scraper instance.
	 *
	 * @var Web_Scraper
	 */
	private Web_Scraper $scraper;

	/**
	 * Language slug for translations.
	 *
	 * @var string
	 */
	private string $language_slug;

	/**
	 * Author/developer slug.
	 *
	 * @var string
	 */
	private string $author_slug;

	/**
	 * Plugins per API page.
	 */
	private const PLUGINS_PER_PAGE = 250;

	/**
	 * Constructor.
	 *
	 * @param string $language_slug Language slug for translations (default: 'es').
	 * @param string $author_slug   Author/developer slug (default: 'automattic').
	 */
	public function __construct( string $language_slug = 'es', string $author_slug = 'automattic' ) {
		$this->scraper       = new Web_Scraper();
		$this->language_slug = $language_slug;
		$this->author_slug   = $author_slug;
	}

	/**
	 * Fetch all plugins from WordPress.org API.
	 *
	 * @param bool     $include_translations Whether to include translation stats.
	 * @param int|null $limit                Maximum number of plugins to process (null for all).
	 * @return array<Plugin_Data> Array of Plugin_Data objects.
	 */
	public function scrape_plugins( bool $include_translations = true, ?int $limit = null ): array {
		$plugins = array();
		$page    = 1;

		// Fetch all pages of plugins.
		do {
			$url  = self::API_URL . '?' . http_build_query(
				array(
					'action'               => 'query_plugins',
					'request[author]'      => $this->author_slug,
					'request[per_page]'    => self::PLUGINS_PER_PAGE,
					'request[page]'        => $page,
				)
			);
			$json = $this->scraper->fetch( $url );

			if ( false === $json ) {
				throw new RuntimeException( 'Failed to fetch plugins from WordPress.org API.' );
			}

			$data = json_decode( $json, true );

			if ( ! isset( $data['plugins'] ) || ! is_array( $data['plugins'] ) ) {
				break;
			}

			foreach ( $data['plugins'] as $plugin_data ) {
				$plugins[] = new Plugin_Data(
					html_entity_decode( $plugin_data['name'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					$plugin_data['slug'] ?? '',
					$plugin_data['active_installs'] ?? 0,
					$plugin_data['last_updated'] ?? ''
				);
			}

			$total_pages = (int) ceil( ( $data['info']['results'] ?? 0 ) / self::PLUGINS_PER_PAGE );
			++$page;

			// Small delay between API requests.
			if ( $page <= $total_pages ) {
				usleep( 200000 ); // 0.2 second delay.
			}
		} while ( $page <= $total_pages );

		// Sort by active installs descending.
		usort(
			$plugins,
			function ( Plugin_Data $a, Plugin_Data $b ) {
				return $b->active_installs <=> $a->active_installs;
			}
		);

		// Apply limit after sorting.
		if ( null !== $limit && $limit > 0 ) {
			$plugins = array_slice( $plugins, 0, $limit );
		}

		if ( $include_translations ) {
			$total = count( $plugins );
			foreach ( $plugins as $index => $plugin ) {
				echo "Fetching translations for plugin " . ( $index + 1 ) . "/{$total}: {$plugin->name}...\n";
				$this->add_translation_stats( $plugin );
				// Add delay to avoid rate limiting.
				usleep( 300000 ); // 0.3 second delay.
			}
		}

		return $plugins;
	}

	/**
	 * Add translation stats to a plugin.
	 *
	 * @param Plugin_Data $plugin Plugin to add translation stats to.
	 * @return void
	 */
	private function add_translation_stats( Plugin_Data $plugin ): void {
		$url  = sprintf( self::TRANSLATE_URL_TEMPLATE, $this->language_slug, $plugin->slug );
		$html = $this->scraper->fetch( $url );

		if ( false === $html ) {
			return;
		}

		$plugin->translations = $this->parse_translation_page( $html, $url );
	}

	/**
	 * Parse translation page to extract stats from all four rows.
	 *
	 * Extracts stats from:
	 * - Stable (latest release)
	 * - Stable Readme (latest release)
	 * - Development (trunk)
	 * - Development Readme (trunk)
	 *
	 * Sums values for each category across all available rows.
	 *
	 * @param string $html            HTML content.
	 * @param string $translation_url URL of the translation page.
	 * @return Translation_Stats|null Translation stats or null if parsing fails.
	 */
	private function parse_translation_page( string $html, string $translation_url = '' ): ?Translation_Stats {
		$dom   = $this->scraper->create_dom( $html );
		$xpath = new DOMXPath( $dom );

		// Define the four row patterns to search for.
		$row_patterns = array(
			'stable'             => 'Stable (latest release)',
			'stable_readme'      => 'Stable Readme (latest release)',
			'development'        => 'Development (trunk)',
			'development_readme' => 'Development Readme (trunk)',
		);

		// Sum stats from each row.
		$totals = array(
			'fuzzy'             => 0,
			'untranslated'      => 0,
			'waiting'           => 0,
			'changes_requested' => 0,
		);

		foreach ( $row_patterns as $key => $pattern ) {
			$row_stats = $this->extract_row_stats( $xpath, $pattern, $key );

			if ( null !== $row_stats ) {
				$totals['fuzzy']             += $row_stats['fuzzy'];
				$totals['untranslated']      += $row_stats['untranslated'];
				$totals['waiting']           += $row_stats['waiting'];
				$totals['changes_requested'] += $row_stats['changes_requested'];
			}
		}

		return new Translation_Stats(
			$totals['fuzzy'],
			$totals['untranslated'],
			$totals['waiting'],
			$totals['changes_requested'],
			$translation_url
		);
	}

	/**
	 * Extract stats from a specific row.
	 *
	 * @param DOMXPath $xpath       XPath object.
	 * @param string   $row_pattern Text pattern to identify the row.
	 * @param string   $row_key     Key identifier for the row type.
	 * @return array<string, int>|null Array with fuzzy, untranslated, waiting, changes_requested or null.
	 */
	private function extract_row_stats( DOMXPath $xpath, string $row_pattern, string $row_key ): ?array {
		// Build XPath query based on row type.
		// For non-readme rows, we need to exclude readme variants.
		if ( 'stable' === $row_key ) {
			$query = '//tr[contains(., "Stable (latest release)") and not(contains(., "Stable Readme"))]';
		} elseif ( 'development' === $row_key ) {
			$query = '//tr[contains(., "Development (trunk)") and not(contains(., "Development Readme"))]';
		} else {
			$query = '//tr[contains(., "' . $row_pattern . '")]';
		}

		$rows = $xpath->query( $query );

		$target_row = null;
		foreach ( $rows as $row ) {
			// Verify it's a valid row with stats cells.
			$cells = $xpath->query( './/td[contains(@class, "stats")]', $row );
			if ( $cells->length >= 4 ) {
				$target_row = $row;
				break;
			}
		}

		if ( null === $target_row ) {
			return null;
		}

		// Extract values from cells with specific classes.
		$stats = array(
			'fuzzy'             => 0,
			'untranslated'      => 0,
			'waiting'           => 0,
			'changes_requested' => 0,
		);

		// Get fuzzy count.
		$fuzzy_cell = $xpath->query( './/td[contains(@class, "fuzzy")]//a', $target_row )->item( 0 );
		if ( null !== $fuzzy_cell ) {
			$stats['fuzzy'] = (int) trim( $fuzzy_cell->textContent );
		}

		// Get untranslated count.
		$untrans_cell = $xpath->query( './/td[contains(@class, "untranslated")]//a', $target_row )->item( 0 );
		if ( null !== $untrans_cell ) {
			$stats['untranslated'] = (int) trim( $untrans_cell->textContent );
		}

		// Get waiting count.
		$waiting_cell = $xpath->query( './/td[contains(@class, "waiting")]//a', $target_row )->item( 0 );
		if ( null !== $waiting_cell ) {
			$stats['waiting'] = (int) trim( $waiting_cell->textContent );
		}

		// Get changes requested count.
		$changes_cell = $xpath->query( './/td[contains(@class, "changesrequested")]//a', $target_row )->item( 0 );
		if ( null !== $changes_cell ) {
			$stats['changes_requested'] = (int) trim( $changes_cell->textContent );
		}

		return $stats;
	}
}

/**
 * Class for outputting results.
 */
class Results_Output {
	/**
	 * Output plugins as JSON.
	 *
	 * @param array<Plugin_Data> $plugins Array of Plugin_Data objects.
	 * @return string JSON string.
	 */
	public function to_json( array $plugins ): string {
		$data = array_map(
			function ( Plugin_Data $plugin ) {
				return $plugin->to_array();
			},
			$plugins
		);

		return json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Output plugins as formatted table to console.
	 *
	 * @param array<Plugin_Data> $plugins Array of Plugin_Data objects.
	 * @return void
	 */
	public function to_console( array $plugins ): void {
		echo "\n";
		echo str_pad( 'Plugin Name', 48 ) . ' | ';
		echo str_pad( 'Last Updated', 14 ) . ' | ';
		echo str_pad( 'Installs', 10 ) . ' | ';
		echo str_pad( 'Fuzzy', 7 ) . ' | ';
		echo str_pad( 'Untrans', 7 ) . ' | ';
		echo str_pad( 'Wait', 6 ) . ' | ';
		echo str_pad( 'ChgReq', 6 ) . ' | ';
		echo str_pad( 'Total', 7 ) . ' | ';
		echo "Translation URL\n";
		echo str_repeat( '-', 197 ) . "\n";

		// Initialize totals.
		$total_installs    = 0;
		$total_fuzzy       = 0;
		$total_untrans     = 0;
		$total_waiting     = 0;
		$total_changes_req = 0;
		$total_not_trans   = 0;

		foreach ( $plugins as $plugin ) {
			$name = $this->truncate_name( $plugin->name, 45 );
			echo $this->mb_str_pad( $name, 48 ) . ' | ';
			echo str_pad( $plugin->last_updated, 14 ) . ' | ';
			echo str_pad( $this->format_installs( $plugin->active_installs ), 10 ) . ' | ';

			// Accumulate totals.
			$total_installs += $plugin->active_installs;

			if ( null !== $plugin->translations ) {
				$t = $plugin->translations;
				echo str_pad( $this->format_stat( $t->fuzzy ), 7 ) . ' | ';
				echo str_pad( $this->format_stat( $t->untranslated ), 7 ) . ' | ';
				echo str_pad( $this->format_stat( $t->waiting ), 6 ) . ' | ';
				echo str_pad( $this->format_stat( $t->changes_requested ), 6 ) . ' | ';
				echo str_pad( $this->format_stat( $t->total_not_translated ), 7 ) . ' | ';
				echo $t->translation_url;

				// Accumulate translation totals.
				$total_fuzzy       += $t->fuzzy;
				$total_untrans     += $t->untranslated;
				$total_waiting     += $t->waiting;
				$total_changes_req += $t->changes_requested;
				$total_not_trans   += $t->total_not_translated;
			} else {
				echo str_pad( 'N/A', 7 ) . ' | ';
				echo str_pad( 'N/A', 7 ) . ' | ';
				echo str_pad( 'N/A', 6 ) . ' | ';
				echo str_pad( 'N/A', 6 ) . ' | ';
				echo str_pad( 'N/A', 7 ) . ' | ';
				echo 'N/A';
			}
			echo "\n";
		}

		// Print totals row.
		echo str_repeat( '-', 197 ) . "\n";
		echo str_pad( 'TOTAL', 48 ) . ' | ';
		echo str_pad( '', 14 ) . ' | ';
		echo str_pad( $this->format_installs( $total_installs ), 10 ) . ' | ';
		echo str_pad( $this->format_stat( $total_fuzzy ), 7 ) . ' | ';
		echo str_pad( $this->format_stat( $total_untrans ), 7 ) . ' | ';
		echo str_pad( $this->format_stat( $total_waiting ), 6 ) . ' | ';
		echo str_pad( $this->format_stat( $total_changes_req ), 6 ) . ' | ';
		echo str_pad( $this->format_stat( $total_not_trans ), 7 ) . " |\n";

		echo "\n";
		echo 'Total plugins: ' . count( $plugins ) . "\n";
	}

	/**
	 * Multibyte-safe string padding.
	 *
	 * @param string $string    The input string.
	 * @param int    $length    The desired length.
	 * @param string $pad_string The string to pad with.
	 * @param int    $pad_type  STR_PAD_RIGHT, STR_PAD_LEFT, or STR_PAD_BOTH.
	 * @return string Padded string.
	 */
	private function mb_str_pad( string $string, int $length, string $pad_string = ' ', int $pad_type = STR_PAD_RIGHT ): string {
		$str_len = mb_strlen( $string, 'UTF-8' );
		$pad_len = $length - $str_len;

		if ( $pad_len <= 0 ) {
			return $string;
		}

		switch ( $pad_type ) {
			case STR_PAD_LEFT:
				return str_repeat( $pad_string, $pad_len ) . $string;
			case STR_PAD_BOTH:
				$left  = (int) floor( $pad_len / 2 );
				$right = $pad_len - $left;
				return str_repeat( $pad_string, $left ) . $string . str_repeat( $pad_string, $right );
			case STR_PAD_RIGHT:
			default:
				return $string . str_repeat( $pad_string, $pad_len );
		}
	}

	/**
	 * Truncate name to specified length with ellipsis.
	 *
	 * @param string $name      The name to truncate.
	 * @param int    $max_length Maximum length before truncation.
	 * @return string Truncated name with "..." if needed.
	 */
	private function truncate_name( string $name, int $max_length ): string {
		if ( mb_strlen( $name ) <= $max_length ) {
			return $name;
		}
		return mb_substr( $name, 0, $max_length - 3 ) . '...';
	}

	/**
	 * Format a stat value.
	 *
	 * @param int $value The stat value.
	 * @return string Formatted value.
	 */
	private function format_stat( int $value ): string {
		return (string) $value;
	}

	/**
	 * Format install count in a compact way.
	 *
	 * @param int $installs Number of installs.
	 * @return string Formatted installs.
	 */
	private function format_installs( int $installs ): string {
		if ( $installs >= 1000000 ) {
			return number_format( $installs / 1000000, 1 ) . 'M';
		}
		if ( $installs >= 1000 ) {
			return number_format( $installs / 1000, 0 ) . 'K';
		}
		return (string) $installs;
	}

	/**
	 * Output plugins as CSV.
	 *
	 * @param array<Plugin_Data> $plugins Array of Plugin_Data objects.
	 * @return string CSV string.
	 */
	public function to_csv( array $plugins ): string {
		$output = "Plugin Name,Last Updated,Plugin URL,Slug,Active Installs,Fuzzy,Untranslated,Waiting,Changes Requested,Total Not Translated,Translation URL\n";

		foreach ( $plugins as $plugin ) {
			$row = array(
				'"' . str_replace( '"', '""', $plugin->name ) . '"',
				'"' . str_replace( '"', '""', $plugin->last_updated ) . '"',
				$plugin->url,
				$plugin->slug,
				$plugin->active_installs,
			);

			if ( null !== $plugin->translations ) {
				$t     = $plugin->translations;
				$row[] = $t->fuzzy;
				$row[] = $t->untranslated;
				$row[] = $t->waiting;
				$row[] = $t->changes_requested;
				$row[] = $t->total_not_translated;
				$row[] = $t->translation_url;
			} else {
				$row[] = '';
				$row[] = '';
				$row[] = '';
				$row[] = '';
				$row[] = '';
				$row[] = '';
			}

			$output .= implode( ',', $row ) . "\n";
		}

		return $output;
	}
}

/**
 * Main application class.
 */
class Plugins_App {
	/**
	 * Scraper instance.
	 *
	 * @var Plugins_Scraper
	 */
	private Plugins_Scraper $scraper;

	/**
	 * Output handler.
	 *
	 * @var Results_Output
	 */
	private Results_Output $output;

	/**
	 * Language slug for translations.
	 *
	 * @var string
	 */
	private string $language_slug;

	/**
	 * Author/developer slug.
	 *
	 * @var string
	 */
	private string $author_slug;

	/**
	 * Constructor.
	 *
	 * @param string $language_slug Language slug for translations (default: 'es').
	 * @param string $author_slug   Author/developer slug (default: 'automattic').
	 */
	public function __construct( string $language_slug = 'es', string $author_slug = 'automattic' ) {
		$this->language_slug = $language_slug;
		$this->author_slug   = $author_slug;
		$this->scraper       = new Plugins_Scraper( $language_slug, $author_slug );
		$this->output        = new Results_Output();
	}

	/**
	 * Run the application.
	 *
	 * @param array<string> $args Command line arguments.
	 * @return void
	 */
	public function run( array $args ): void {
		$format               = $this->get_option( $args, '--format', 'console' );
		$include_translations = ! in_array( '--no-translations', $args, true );
		$output_file          = $this->get_option( $args, '--output', null );
		$limit                = $this->get_option( $args, '--limit', null );
		$limit                = null !== $limit ? (int) $limit : null;

		echo "Scraping plugins for author: {$this->author_slug}...\n";
		echo "Language: {$this->language_slug}\n";

		if ( null !== $limit ) {
			echo "Limiting to first {$limit} plugins...\n";
		}

		if ( $include_translations ) {
			echo "Including translation stats (this may take a while)...\n";
		}

		try {
			$plugins = $this->scraper->scrape_plugins( $include_translations, $limit );

			switch ( $format ) {
				case 'json':
					$result = $this->output->to_json( $plugins );
					if ( null !== $output_file ) {
						file_put_contents( $output_file, $result );
						echo "Output saved to: {$output_file}\n";
					} else {
						echo $result . "\n";
					}
					break;

				case 'csv':
					$result = $this->output->to_csv( $plugins );
					if ( null !== $output_file ) {
						file_put_contents( $output_file, $result );
						echo "Output saved to: {$output_file}\n";
					} else {
						echo $result;
					}
					break;

				case 'console':
				default:
					$this->output->to_console( $plugins );
					break;
			}
		} catch ( RuntimeException $e ) {
			echo 'Error: ' . $e->getMessage() . "\n";
			exit( 1 );
		}
	}

	/**
	 * Get option value from arguments.
	 *
	 * @param array<string> $args    Arguments array.
	 * @param string        $option  Option name (e.g., '--format').
	 * @param mixed         $default Default value.
	 * @return mixed Option value or default.
	 */
	private function get_option( array $args, string $option, mixed $default ): mixed {
		foreach ( $args as $key => $arg ) {
			if ( str_starts_with( $arg, $option . '=' ) ) {
				return substr( $arg, strlen( $option ) + 1 );
			}

			if ( $arg === $option && isset( $args[ $key + 1 ] ) ) {
				return $args[ $key + 1 ];
			}
		}

		return $default;
	}

	/**
	 * Show usage help.
	 *
	 * @return void
	 */
	public static function show_help(): void {
		echo <<<HELP
Plugins Translation Scraper
===========================

Usage: php scrape_translations_plugins.php [options]

Options:
  --author=<slug>       Developer/author slug (default: automattic)
  --format=<format>     Output format: console, json, csv (default: console)
  --output=<file>       Save output to file (for json/csv formats)
  --limit=<n>           Process only the first n plugins (default: all)
  --lang=<slug>         Language slug for translations (default: es)
  --no-translations     Skip fetching translation stats (faster)
  --help                Show this help message

Examples:
  php scrape_translations_plugins.php
  php scrape_translations_plugins.php --author=developer --limit=10
  php scrape_translations_plugins.php --limit=10
  php scrape_translations_plugins.php --lang=fr --limit=5
  php scrape_translations_plugins.php --format=json
  php scrape_translations_plugins.php --format=csv --output=plugins.csv
  php scrape_translations_plugins.php --no-translations --format=json
  php scrape_translations_plugins.php --author=developer --limit=5 --lang=de --format=json

HELP;
	}
}

// Run the application if called directly.
if ( php_sapi_name() === 'cli' && basename( __FILE__ ) === basename( $argv[0] ?? '' ) ) {
	if ( in_array( '--help', $argv ?? array(), true ) || in_array( '-h', $argv ?? array(), true ) ) {
		Plugins_App::show_help();
		exit( 0 );
	}

	// Parse options before creating the app.
	$language_slug = 'es';
	$author_slug   = 'automattic';

	foreach ( $argv ?? array() as $arg ) {
		if ( str_starts_with( $arg, '--lang=' ) ) {
			$language_slug = substr( $arg, 7 );
		}
		if ( str_starts_with( $arg, '--author=' ) ) {
			$author_slug = substr( $arg, 9 );
		}
	}

	$app = new Plugins_App( $language_slug, $author_slug );
	$app->run( $argv ?? array() );
}
