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
$string['activitydatelabel'] = '{$a->activity} — {$a->label}';
$string['ask'] = 'Ask';
$string['authoritativefacts'] = 'Authoritative Moodle facts';
$string['cancel'] = 'Cancel';
$string['chatheading'] = 'Ask the Course AI Guide';
$string['courseaiguide:addinstance'] = 'Add a Course AI Guide block';
$string['courseaiguide:ask'] = 'Ask the Course AI Guide';
$string['courseaiguide:manage'] = 'Manage the Course AI Guide';
$string['courseaiguide:viewreports'] = 'View Course AI Guide aggregate reports';
$string['coursecomplete'] = 'Moodle records this course as complete for you.';
$string['coursecompletion'] = 'Course completion';
$string['courseconfig'] = 'Course AI Guide settings';
$string['courseinstructions'] = 'Optional course guidance';
$string['coursenotcomplete'] = 'Moodle does not currently record this course as complete for you.';
$string['deadlineanswer'] = '{$a->activity}: {$a->dates}';
$string['deadlinesanswer'] = 'Moodle shows these course dates: {$a}';
$string['defaultdisclaimer'] = 'This AI guide may be wrong. Moodle activity dates and completion facts shown separately are authoritative. Do not enter personal or sensitive information.';
$string['deleteconversation'] = 'Delete this conversation';
$string['enabled'] = 'Enable this course configuration';
$string['error:emptyquestion'] = 'Enter a question.';
$string['error:indexlock'] = 'Course indexing is already running.';
$string['error:indexnotready'] = 'The course guide index is not ready.';
$string['error:invalidresponse'] = 'The AI service returned an invalid response. Quote request ID {$a} when asking for support.';
$string['error:notenabled'] = 'The course guide is not enabled for this course.';
$string['error:participantsdisabled'] = 'Participant access to the course guide is disabled.';
$string['error:provider'] = 'The AI service is temporarily unavailable. Quote request ID {$a} when asking for support.';
$string['error:provideradmin'] = 'Administrator diagnostic: {$a}.';
$string['error:providernotready'] = 'The AI provider is not configured.';
$string['error:ratelimited'] = 'You have reached the course guide request limit. Please try again later.';
$string['error:rateunavailable'] = 'The request limit could not be checked safely. Please try again later.';
$string['examples'] = 'Examples: “When is the quiz deadline?”, “What do I need to complete next?”, or “What does this course say about…?”';
$string['generatedexplanation'] = 'AI-generated explanation';
$string['historydeleted'] = 'The conversation was deleted.';
$string['historyenabled'] = 'Permit participant opt-in conversation history';
$string['indexstatus:disabled'] = 'Disabled';
$string['indexstatus:failed'] = 'Index failed';
$string['indexstatus:indexing'] = 'Indexing';
$string['indexstatus:pending'] = 'Index pending';
$string['indexstatus:ready'] = 'Ready';
$string['indexstatus:stale'] = 'Index stale';
$string['loading'] = 'Looking in this course…';
$string['myhistory'] = 'My saved conversations';
$string['nextanswer'] = 'The next eligible incomplete activity is {$a}.';
$string['nohistory'] = 'You have no saved conversations in this course.';
$string['notfound'] = 'I could not find this in the course.';
$string['openchat'] = 'Ask the course guide';
$string['participantsenabled'] = 'Allow enrolled participants to ask after indexing succeeds';
$string['pluginname'] = 'Course AI Guide';
$string['privacy:metadata:content'] = 'The retained plain-text message.';
$string['privacy:metadata:conversation'] = 'Optional conversations explicitly saved by participants.';
$string['privacy:metadata:conversationid'] = 'The owning conversation identifier.';
$string['privacy:metadata:courseid'] = 'The Moodle course identifier.';
$string['privacy:metadata:expiresat'] = 'The time the retained data expires.';
$string['privacy:metadata:message'] = 'Questions and answers in an opted-in retained conversation.';
$string['privacy:metadata:provider'] = 'An administrator-configured external AI provider receives a bounded question, current Moodle facts, and access-filtered course excerpts.';
$string['privacy:metadata:publictoken'] = 'An opaque conversation token.';
$string['privacy:metadata:rate'] = 'Short-lived per-user/course request counters used to prevent abuse.';
$string['privacy:metadata:requestcount'] = 'The number of requests in the window.';
$string['privacy:metadata:requestid'] = 'A non-sensitive random support correlation identifier.';
$string['privacy:metadata:role'] = 'Whether the message is a participant question or guide answer.';
$string['privacy:metadata:timecreated'] = 'The creation time.';
$string['privacy:metadata:timemodified'] = 'The last modification time.';
$string['privacy:metadata:timeoptedin'] = 'The time the participant opted into saving.';
$string['privacy:metadata:userid'] = 'The participant identifier.';
$string['privacy:metadata:windowend'] = 'The abuse-control window expiry.';
$string['privacy:metadata:windowstart'] = 'The abuse-control window start.';
$string['privacy:metadata:windowtype'] = 'The abuse-control window type.';
$string['privacy:path'] = 'Course AI Guide';
$string['question'] = 'Question';
$string['readinessnote'] = 'Participant access remains closed until the site provider is configured and a complete index succeeds.';
$string['reindex'] = 'Re-index course';
$string['reindexconfirm'] = 'Queue a fresh index of the permitted course content?';
$string['reindexqueued'] = 'Course indexing has been queued.';
$string['reportday'] = 'Day';
$string['reportdisabled'] = 'Aggregate statistics are disabled by the site administrator.';
$string['reporterrors'] = 'Errors';
$string['reportheading'] = 'Course AI Guide aggregate usage';
$string['reportlatency'] = 'Average latency (ms)';
$string['reportnotfound'] = 'Not found';
$string['reportrequests'] = 'Requests';
$string['savehistory'] = 'Save this conversation';
$string['settings:apikey'] = 'API key';
$string['settings:apikey_desc'] = 'Stored server-side. It is never returned to browsers, course managers, reports, exports, or logs.';
$string['settings:disclaimer'] = 'Participant disclaimer';
$string['settings:disclaimer_desc'] = 'Shown next to the chat interface.';
$string['settings:endpoint'] = 'HTTPS chat-completions endpoint';
$string['settings:endpoint_desc'] = 'For example, an approved OpenAI-compatible /v1/chat/completions endpoint. Moodle HTTP security checks remain enabled.';
$string['settings:model'] = 'Model name';
$string['settings:model_desc'] = 'The administrator-approved model identifier.';
$string['settings:providerheading'] = 'AI provider';
$string['settings:providerheading_desc'] = 'Administrator-only OpenAI-compatible provider settings. Course content is never sent until access filtering succeeds.';
$string['settings:ratelimitday'] = 'Requests per day';
$string['settings:ratelimitday_desc'] = 'Per participant and course. Default: 100.';
$string['settings:ratelimitshort'] = 'Requests per five minutes';
$string['settings:ratelimitshort_desc'] = 'Per participant and course. Default: 10.';
$string['settings:retentiondays'] = 'Maximum conversation retention days';
$string['settings:retentiondays_desc'] = 'Use 0 to disable server-side history. Otherwise use 1–365 days; course permission and participant opt-in are also required.';
$string['settings:statisticsenabled'] = 'Enable aggregate usage statistics';
$string['settings:statisticsenabled_desc'] = 'Stores course/day counts only, without users, questions, answers, conversations, sources, or request IDs.';
$string['source:assignment'] = 'Assignment names and descriptions';
$string['source:book'] = 'Book names and descriptions';
$string['source:bookchapter'] = 'Book chapters';
$string['source:course'] = 'Course name and summary';
$string['source:label'] = 'Text and media areas';
$string['source:page'] = 'Page resources';
$string['source:quizdescription'] = 'Quiz names and descriptions (never questions or answers)';
$string['source:section'] = 'Section names and summaries';
$string['source:url'] = 'URL names and descriptions (never remote target content)';
$string['sources'] = 'Sources';
$string['task:purge'] = 'Purge expired Course AI Guide data';
$string['task:reconcile'] = 'Reconcile Course AI Guide indexes';
$string['unavailable'] = 'The course guide is not available yet.';
$string['viewreport'] = 'Usage report';
