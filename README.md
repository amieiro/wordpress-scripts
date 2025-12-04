<!--
README for Plugins Translation Scraper

Copyright (C) 2025 Jesús Amieiro

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
-->

# Plugins Translation Scraper

A PHP CLI tool to scrape plugin information from WordPress.org and their translation status from translate.wordpress.org.

## Features

- Fetches plugins by any author from the WordPress.org API
- Retrieves translation statistics for each plugin (Fuzzy, Untranslated, Waiting, Changes Requested)
- Supports multiple output formats: console table, JSON, CSV
- Configurable language for translation status
- Option to limit the number of plugins processed
- Sorted by active installs (descending)

## Requirements

- PHP 8.0 or higher
- PHP extensions: `dom`, `libxml`, `json`

## Installation

No installation required. Simply run the script from the command line.

## Usage

```bash
php scrape_translations_plugins.php [options]
```

### Options

```bash
| Option              | Description                                | Default      |
|---------------------|--------------------------------------------|--------------|
| `--author=<slug>`   | Developer/author slug                      | `automattic` |
| `--format=<format>` | Output format: `console`, `json`, `csv`    | `console`    |
| `--output=<file>`   | Save output to file (for json/csv formats) | stdout       |
| `--limit=<n>`       | Process only the first n plugins           | all          |
| `--lang=<slug>`     | Language slug for translations             | `es`         |
| `--no-translations` | Skip fetching translation stats (faster)   | false        |
| `--help`, `-h`      | Show help message                          | -            |
```

### Examples

#### Basic usage (all plugins with Spanish translations)

```bash
php scrape_translations_plugins.php
```

#### Limit to top 10 plugins

```bash
php scrape_translations_plugins.php --limit=10
```

#### Get French translations for top 5 plugins

```bash
php scrape_translations_plugins.php --lang=fr --limit=5
```

#### Export to JSON file

```bash
php scrape_translations_plugins.php --format=json --output=plugins.json
```

#### Export to CSV file

```bash
php scrape_translations_plugins.php --format=csv --output=plugins.csv
```

#### Quick scan without translation stats

```bash
php scrape_translations_plugins.php --no-translations --limit=20
```

#### German translations in JSON format

```bash
php scrape_translations_plugins.php --lang=de --limit=5 --format=json
```

## Output Format

### Console Output

The console output displays a formatted table with the following columns:

- **Plugin Name**: Truncated to 45 characters with "..." if longer
- **Installs**: Active installs in compact format (e.g., 7.0M, 500K)
- **Fuzzy**: Sum of fuzzy translations across all sub-projects
- **Untrans**: Sum of untranslated strings
- **Wait**: Sum of strings waiting for review
- **ChgReq**: Sum of strings with changes requested
- **Total**: Total strings not translated (sum of above)
- **Translation URL**: Direct link to the translation page

### JSON Output

```json
[
    {
        "name": "WooCommerce",
        "url": "https://wordpress.org/plugins/woocommerce/",
        "slug": "woocommerce",
        "active_installs": 7000000,
        "translations": {
            "fuzzy": 0,
            "untranslated": 170,
            "waiting": 5,
            "changes_requested": 0,
            "total_not_translated": 175,
            "translation_url": "https://translate.wordpress.org/locale/es/default/wp-plugins/woocommerce/"
        }
    }
]
```

### CSV Output

CSV includes all fields with headers:
- Plugin Name, Plugin URL, Slug, Active Installs, Fuzzy, Untranslated, Waiting, Changes Requested, Total Not Translated, Translation URL

## Translation Statistics

The script collects translation stats from four sub-projects for each plugin:

1. **Stable (latest release)** - Main plugin strings
2. **Stable Readme (latest release)** - Plugin readme/description
3. **Development (trunk)** - Development version strings
4. **Development Readme (trunk)** - Development readme

Values are **summed** across all available sub-projects. If a sub-project doesn't exist, it contributes 0 to the sum.

## Language Codes

Common language codes for the `--lang` parameter:

| Code | Language |
|------|----------|
| `es` | Spanish (Spain) |
| `fr` | French (France) |
| `de` | German |
| `it` | Italian |
| `pt-br` | Portuguese (Brazil) |
| `ja` | Japanese |
| `zh-cn` | Chinese (Simplified) |
| `ru` | Russian |
| `nl` | Dutch |

For a complete list, visit [translate.wordpress.org](https://translate.wordpress.org/).

## Rate Limiting

The script includes built-in delays to avoid rate limiting:
- 0.2 seconds between API requests
- 0.3 seconds between translation page fetches

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.

See the [GNU General Public License](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html) for more details.
