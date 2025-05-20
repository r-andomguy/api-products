<?php

use Phinx\Migration\AbstractMigration;

class FixCompanyIdInCategoryTable extends AbstractMigration
{
  public function up()
  {
    $this->execute('
      UPDATE category
      SET company_id = 1
      WHERE company_id IS NULL
    ');
  }

  public function down()
  {
    $this->execute('
      UPDATE category
      SET company_id = NULL
      WHERE company_id = 1
    ');
  }
}