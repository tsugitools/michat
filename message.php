<?php
require_once "../config.php";

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

$max_seconds = 60000;
$present_max = 60;

$LTI = LTIX::requireData();

$path = U::rest_path();

// Get microsecond time as double
$micro_now = microtime(true);

// Post a message
$message = U::get($_POST, 'message');
if ( is_string($message) && strlen($message) > 0 ) {

    $sql = "INSERT INTO {$CFG->dbprefix}michat_message
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
    return;
}

// Update present
$sql = "INSERT INTO {$CFG->dbprefix}michat_present
    (link_id, user_id, updated_at ) VALUES
    (:link_id, :user_id, NOW() )
    ON DUPLICATE KEY UPDATE updated_at=NOW()
";
$values = array(
    ':link_id' => $LTI->link->id,
    ':user_id' => $LTI->user->id,
);
$retval = $PDOX->queryDie($sql, $values);

// Might be from get or POST
$since = U::get($_GET, 'since', 0);
$since = U::get($_POST, 'since', $since);

if ( ! is_numeric($since) ) $since = 0;

$sql = "SELECT message, displayname, image, M.created_at, NOW() AS relative, micro_time,
        DATE_FORMAT(M.created_at, '%Y-%m-%dT%T') AS created_iso8601
    FROM {$CFG->dbprefix}michat_message AS M
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


$sql = "SELECT displayname, image
    FROM {$CFG->dbprefix}michat_present AS P
    JOIN {$CFG->dbprefix}lti_user AS U ON P.user_id = U.user_id
    WHERE link_id = :link_id
      AND P.user_id <> :user_id
      AND P.updated_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL :pres_max SECOND)
";

$values = array(
    ':link_id' => $LTI->link->id,
    ':user_id' => $LTI->user->id,
    ':pres_max' => $present_max,
);

$present = $PDOX->allRowsDie($sql, $values);

// Cleanup
$debug = false;
if ( $debug || (time() % 100) < 5 ) {
    $sql = "DELETE FROM {$CFG->dbprefix}michat_message
        WHERE created_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL :max SECOND)
          AND link_id = :link_id
    ";

    $values = array(
        ':link_id' => $LTI->link->id,
        ':max' => $max_seconds,
    );
    $PDOX->queryDie($sql, $values);

    // Presence should never be > 60 seconds anywhere
    $sql = "DELETE FROM {$CFG->dbprefix}michat_present
        WHERE updated_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL :max SECOND)
    ";

    $values = array(
        ':max' => $present_max,
    );
    $PDOX->queryDie($sql, $values);
    error_log("Cleanup..");
}

$retval = array();
$retval['messages'] = $rows;
$retval['present'] = $present;

echo(json_encode($retval,JSON_PRETTY_PRINT));

