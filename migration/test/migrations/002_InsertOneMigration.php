<?php

class InsertOneMigration extends Migration_Base {
  
  public function up() {
    $this->exec("INSERT INTO example_records (value) VALUES ('one')");
  }
  
  public function down() {
    $this->exec("DELETE FROM example_records WHERE value='one'");
  }
}

?>