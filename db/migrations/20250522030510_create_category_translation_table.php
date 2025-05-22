<?php

use Phinx\Migration\AbstractMigration;

class CreateCategoryTranslationTable extends AbstractMigration
{
public function up(): void
    {
        $this->execute("
            CREATE TABLE category_translation (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INT NOT NULL,
                lang_code VARCHAR(10) NOT NULL,
                label VARCHAR(255) NOT NULL,
                UNIQUE (category_id, lang_code),
                FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE
            )
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS category_translation");
    }
}