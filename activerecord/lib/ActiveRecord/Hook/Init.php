<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

Support_Resources::register_db_connection(new ActiveRecord_Connection_Provider(), 'activerecord');

?>