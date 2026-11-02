<?php

return static function (PDO $pdo, Schema $schema): void {
    if (!$schema->tableExists('setting')) {
        return;
    }

    if (!$schema->hasColumn('setting', 'text_edit')) {
        $pdo->exec("ALTER TABLE `setting` ADD `text_edit` LONGTEXT NULL");
        return;
    }

    $column = $pdo->query("SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'setting' AND COLUMN_NAME = 'text_edit'")->fetch(PDO::FETCH_ASSOC);
    $type = strtolower((string) ($column['DATA_TYPE'] ?? ''));
    if ($type !== 'longtext' && $type !== 'json') {
        $pdo->exec("ALTER TABLE `setting` MODIFY `text_edit` LONGTEXT NULL");
    }
};
