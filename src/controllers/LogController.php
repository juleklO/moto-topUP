<?php
require_once __DIR__ . '/../models/LogModel.php';

class LogController {
    public static function add(PDO $pdo, array $input): array {
        return LogModel::add($pdo, $input);
    }
}