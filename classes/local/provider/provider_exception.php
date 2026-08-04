<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Course AI Guide plugin.
 *
 * @package    block_courseaiguide
 * @copyright  2026 Tamas Kery <tom@tomkery.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_courseaiguide\local\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Sanitised provider failure. Message must never include request content or secrets.
 */
final class provider_exception extends \moodle_exception {
    /** @var string Safe provider failure category. */
    private $diagnostic;

    /**
     * @param string $errorcode
     * @param string $diagnostic Safe category; never provider response text.
     */
    public function __construct(string $errorcode = 'error:provider', string $diagnostic = 'provider_error') {
        $allowed = [
            'authentication',
            'invalid_response',
            'model_or_endpoint',
            'network',
            'permission',
            'provider_5xx',
            'provider_error',
            'rate_limit_or_quota',
            'request_rejected',
        ];
        $this->diagnostic = in_array($diagnostic, $allowed, true) ? $diagnostic : 'provider_error';
        parent::__construct($errorcode, 'block_courseaiguide');
    }

    /** @return string Safe provider failure category. */
    public function diagnostic(): string {
        return $this->diagnostic;
    }
}
