<?php
require_once "../config.php";

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

$max_seconds = 60000;

$LTI = LTIX::requireData();

$path = U::rest_path();

// Get microsecond time as double
$micro_now = microtime(true);

$message = U::get($_POST, 'message');
if ( is_string($message) && strlen($message) > 0 ) {

    $sql = "INSERT INTO {$CFG->dbprefix}simplechat_message
        (link_id, user_id, message, micro_time ) VALUES
        (:link_id, :user_id, :message, :micro_now )
        ON DUPLICATE KEY UPDATE message=:message
    ";
    $values = array(
        ':link_id' => $LTI->link->id,
        ':user_id' => $LTI->user->id,
        ':message' => $message,
        ':micro_now' => $micro_now
    );
    $retval = $PDOX->queryDie($sql, $values);
}

$since = U::get($_GET, 'since', 0);
if ( ! is_numeric($since) ) $since = 0;

$sql = "SELECT message, displayname, image, M.created_at, NOW() AS relative, micro_time
    FROM {$CFG->dbprefix}simplechat_message AS M
    JOIN {$CFG->dbprefix}lti_user AS U ON M.user_id = U.user_id
    WHERE link_id = :link_id 
      AND micro_time > :since AND
      M.created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL :max SECOND)
    ORDER BY micro_time ASC
";

$values = array(
    ':link_id' => $LINK->id,
    ':max' => $max_seconds,
    ':since' => $since
);

$rows = $PDOX->allRowsDie($sql, $values);

for($i=0; $i < count($rows); $i++ ) {
    $timestamp = $rows[$i]['created_at'];
    $relative = $rows[$i]['relative'];
    $m = new \Moment\Moment($timestamp);
    $relative = $m->from($relative);
    $rows[$i]['relative'] = $relative->getRelative();
}

/*
// Cleanup
$debug = false;
if ( $debug || (time() % 100) < 5 ) {
    $sql = "DELETE FROM {$CFG->dbprefix}simplechat_message
        WHERE created_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL :max SECOND)
    ";

    $values = array(
        ':max' => $max_seconds,
    );

    $PDOX->queryDie($sql, $values);
    error_log("Cleanup..");
}
*/

$retval = array();
$retval['messages'] = $rows;
$retval['presence'] = array();

echo(json_encode($retval,JSON_PRETTY_PRINT));

