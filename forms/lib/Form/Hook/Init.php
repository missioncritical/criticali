<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

$GLOBALS['INCLUDE_PATH'] .= $GLOBALS['PATH_SEPARATOR'] . "$GLOBALS[ROOT_DIR]/forms";
ini_set('include_path', $GLOBALS['INCLUDE_PATH']);

$GLOBALS['CRITICALI_RUNTIME_SEARCH_DIRECTORIES'][] = "$GLOBALS[ROOT_DIR]/forms";

?>