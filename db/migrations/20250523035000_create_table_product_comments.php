<?php

use Phinx\Migration\AbstractMigration;

class CreateTableProductComments extends AbstractMigration
{
  public function up()
  {
    $this->execute('CREATE TABLE IF NOT EXISTS product_comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        parent_id INTEGER DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES product(id),
        FOREIGN KEY (user_id) REFERENCES admin_user(id),
        FOREIGN KEY (parent_id) REFERENCES comments(id)
      );'
    );
  }

  public function down()
  {
    $this->execute("DROP TABLE IF EXISTS product_comments");
  }
}