<?php
/**
 * პლაგინის წაშლისას ბაზის გასუფთავება
 */
if (!defined('OK_LOADED')) exit;

global $db;

// წავშალოთ ცხრილი
$db->query("DROP TABLE IF EXISTS ok_advertisements");