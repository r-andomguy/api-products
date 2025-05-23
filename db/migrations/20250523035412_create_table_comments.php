<?php

use Phinx\Migration\AbstractMigration;

class CreateTableComments extends AbstractMigration
{
  public function up()
  {
    $this->execute('CREATE TABLE IF NOT EXISTS comment_likes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        comment_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (comment_id, user_id),
        FOREIGN KEY (comment_id) REFERENCES comments(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
      );'
    );
  }

  public function down()
  {
    $this->execute("DROP TABLE IF EXISTS comments");
  }
}