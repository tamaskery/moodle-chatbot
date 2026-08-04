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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course AI Guide plugin.
 *
 * @package    block_courseaiguide
 * @copyright  2026 Tamas Kery <tom@tomkery.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_courseaiguide\local\retrieval;

/**
 * Database-neutral bounded lexical retrieval.
 */
final class database_lexical_backend implements retrieval_backend_interface {
    /** @var int Maximum database candidates scored per request. */
    private const MAXCANDIDATES = 200;
    /** @var int Maximum chunks returned after scoring. */
    private const MAXRESULTS = 8;
    /** @var int Maximum reference-context characters returned. */
    private const MAXCONTEXTCHARS = 12000;

    /** @var permission_filter User-specific source permission filter. */
    private $filter;

    /**
     * Create the database-backed retriever.
     */
    public function __construct() {
        $this->filter = new permission_filter();
    }

    /**
     * Retrieve user-visible course chunks using bounded lexical scoring.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param string $query Plain-text query.
     * @return array Ranked, permission-filtered chunks.
     */
    public function retrieve(int $courseid, int $userid, string $query): array {
        global $DB;

        $terms = $this->terms($query);
        if (!$terms) {
            return [];
        }
        $likes = [];
        $params = ['courseid' => $courseid];
        foreach ($terms as $index => $term) {
            $name = 'term' . $index;
            $likes[] = $DB->sql_like('c.searchtext', ':' . $name, false);
            $params[$name] = '%' . $DB->sql_like_escape($term) . '%';
        }
        $sql = 'SELECT c.id AS chunkid, c.content, c.searchtext, c.chunkno,
                       s.id AS sourceid, s.courseid, s.contextid, s.cmid, s.sectionid,
                       s.searcharea, s.sourceitem, s.sourcetype, s.title, s.url, s.generation
                  FROM {block_courseaiguide_chunk} c
                  JOIN {block_courseaiguide_source} s ON s.id = c.sourceid
                 WHERE c.courseid = :courseid
                   AND s.courseid = :courseid2
                   AND (' . implode(' OR ', $likes) . ')
              ORDER BY c.id ASC';
        $params['courseid2'] = $courseid;
        $records = $DB->get_records_sql($sql, $params, 0, self::MAXCANDIDATES);
        $scored = [];
        foreach ($records as $record) {
            $source = $this->filter->authorise($record, $courseid, $userid);
            if (!$source) {
                continue;
            }
            $sourcecontext = \context::instance_by_id((int) $record->contextid, MUST_EXIST);
            $formattedcontent = format_text((string) $record->content, FORMAT_PLAIN, [
                'context' => $sourcecontext,
                'filter' => false,
                'para' => false,
            ]);
            $haystack = \core_text::strtolower((string) $record->searchtext);
            $title = \core_text::strtolower((string) $record->title);
            $score = 0;
            foreach ($terms as $term) {
                $score += min(10, substr_count($haystack, $term));
                if (strpos($title, $term) !== false) {
                    $score += 3;
                }
            }
            if ($score > 0) {
                $scored[] = [
                    'score' => $score,
                    'chunkid' => (int) $record->chunkid,
                    'content' => \core_text::substr(trim(strip_tags($formattedcontent)), 0, 12000),
                    'source' => $source,
                ];
            }
        }
        usort($scored, static function (array $a, array $b): int {
            return $b['score'] <=> $a['score'] ?: $a['chunkid'] <=> $b['chunkid'];
        });

        $results = [];
        $characters = 0;
        $seenchunks = [];
        foreach ($scored as $result) {
            if (count($results) >= self::MAXRESULTS) {
                break;
            }
            if (isset($seenchunks[$result['chunkid']])) {
                continue;
            }
            $remaining = self::MAXCONTEXTCHARS - $characters;
            if ($remaining <= 0) {
                break;
            }
            $result['content'] = \core_text::substr($result['content'], 0, $remaining);
            if ($result['content'] === '') {
                continue;
            }
            $characters += \core_text::strlen($result['content']);
            $seenchunks[$result['chunkid']] = true;
            $results[] = $result;
        }
        return $results;
    }

    /**
     * Normalise bounded Unicode query terms.
     *
     * @param string $query
     * @return array
     */
    private function terms(string $query): array {
        $query = \core_text::strtolower(\core_text::substr(clean_param($query, PARAM_TEXT), 0, 1000));
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_values(array_unique(array_filter($tokens, static function (string $token): bool {
            return \core_text::strlen($token) >= 2;
        })));
        return array_slice($tokens, 0, 12);
    }
}
