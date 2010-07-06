<?php

class CreateTableMigration extends Migration_Base {
  
  public function up() {
    $this->exec(<<<SQL
      CREATE TABLE example_records (
        id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
        value varchar(255) NOT NULL
      )
SQL
    );
  }
  
  public function down() {
    $this->exec("DROP TABLE example_records");
  }
}

?>