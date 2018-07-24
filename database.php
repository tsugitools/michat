<?php

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
  "drop table if exists {$CFG->dbprefix}simplechat_message",
  "drop table if exists {$CFG->dbprefix}simplechat_presence;"
);

$DATABASE_INSTALL = array(

  array( "{$CFG->dbprefix}simplechat_message",
  "CREATE TABLE `{$CFG->dbprefix}simplechat_message` (
    link_id             INTEGER NOT NULL,
    user_id             INTEGER NOT NULL,
    message             TEXT,
    micro_time          DOUBLE NOT NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    CONSTRAINT `{$CFG->dbprefix}simplechat_message_ibfk_l`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}simplechat_message_ibfk_u`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(link_id, user_id, micro_time)

  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
  "),

  // No foreign keys because this data is ephemeral and
  // does not participate in any kind of import / export
  array( "{$CFG->dbprefix}simplechat_presence",
  "CREATE TABLE `{$CFG->dbprefix}simplechat_presence` (
    link_id             INTEGER NOT NULL,
    user_id             INTEGER NOT NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    UNIQUE(link_id, user_id)

  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
  "),


);

