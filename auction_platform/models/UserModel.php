<?php
// models/UserModel.php

require_once __DIR__ . '/../config/database.php';

class UserModel {

    public static function findByEmail(string $email): ?array {
        $stmt = Database::query("SELECT * FROM users WHERE email = ? AND is_banned = 0 LIMIT 1", [$email]);
        return $stmt->fetch() ?: null;
    }

    public static function findByUsername(string $username): ?array {
        $stmt = Database::query("SELECT * FROM users WHERE username = ? LIMIT 1", [$username]);
        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array {
        $stmt = Database::query("SELECT * FROM users WHERE id = ? LIMIT 1", [$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int {
        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        Database::query(
            "INSERT INTO users (username, email, password_hash, role, full_name, phone) VALUES (?,?,?,?,?,?)",
            [
                $data['username'],
                $data['email'],
                $hash,
                $data['role'] ?? 'buyer',
                $data['full_name'],
                $data['phone'] ?? null
            ]
        );
        return (int)Database::getInstance()->lastInsertId();
    }

    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    public static function getAll(int $limit = 50, int $offset = 0): array {
        return Database::query(
            "SELECT id, username, email, role, full_name, is_banned, fraud_score, created_at, last_login FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        )->fetchAll();
    }

    public static function ban(int $userId, string $reason): bool {
        Database::query("UPDATE users SET is_banned = 1, ban_reason = ? WHERE id = ?", [$reason, $userId]);
        return true;
    }

    public static function unban(int $userId): bool {
        Database::query("UPDATE users SET is_banned = 0, ban_reason = NULL WHERE id = ?", [$userId]);
        return true;
    }

    public static function incrementFraudScore(int $userId, int $amount = 1): void {
        Database::query("UPDATE users SET fraud_score = fraud_score + ? WHERE id = ?", [$amount, $userId]);
    }

    public static function emailExists(string $email): bool {
        $stmt = Database::query("SELECT id FROM users WHERE email = ? LIMIT 1", [$email]);
        return (bool)$stmt->fetch();
    }

    public static function usernameExists(string $username): bool {
        $stmt = Database::query("SELECT id FROM users WHERE username = ? LIMIT 1", [$username]);
        return (bool)$stmt->fetch();
    }

    public static function getTotalCount(): int {
        return (int)Database::query("SELECT COUNT(*) as cnt FROM users")->fetchColumn();
    }
}
