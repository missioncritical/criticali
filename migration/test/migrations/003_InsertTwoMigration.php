<?php

class InsertTwoMigration extends Migration_Base {
  
  public function up() {
    $this->exec("INSERT INTO example_records (value) VALUES ('two')");
  }
  
  public function down() {
    $this->exec("DELETE FROM example_records WHERE value='two'");
  }
}

?>