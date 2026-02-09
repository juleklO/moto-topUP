<?php
require_once __DIR__ . '/../models/VehicleModel.php';

class VehicleController {
    public static function getAll(PDO $pdo): array {
        return VehicleModel::getAll($pdo);
    }

    public static function create(PDO $pdo, array $input): array {
        return VehicleModel::create($pdo, $input);
    }
}