<?php

use Phinx\Migration\AbstractMigration;

class AddStockColumInProductTable extends AbstractMigration
{
  public function up()
  {
    $this->execute('ALTER TABLE product ADD COLUMN stock INTEGER DEFAULT 0');
  }

  public function down()
  {
    $this->execute('ALTER TABLE product DROP COLUMN stock');
  }
}