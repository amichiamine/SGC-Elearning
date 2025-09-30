<?php

namespace SGC\Core;

class RoleManager
{
    private static $roles;

    /**
     * Load roles and permissions from the JSON config file.
     */
    public static function loadRoles()
    {
        if (self::$roles === null) {
            $configPath = __DIR__ . '/../config/roles.json';
            if (!file_exists($configPath)) {
                // Handle error: config file not found
                // For now, we can log this or throw an exception.
                // To keep it simple, we'll just load empty roles.
                self::$roles = [];
                return;
            }
            $json = file_get_contents($configPath);
            $config = json_decode($json, true);
            self::$roles = $config['roles'] ?? [];
        }
    }

    /**
     * Check if a role has a specific permission.
     *
     * @param string $role The role to check.
     * @param string $permission The permission to check for.
     * @return bool True if the role has the permission, false otherwise.
     */
    public static function hasPermission(string $role, string $permission): bool
    {
        self::loadRoles();

        if (!isset(self::$roles[$role])) {
            return false; // Role does not exist
        }

        $permissions = self::$roles[$role]['permissions'];

        // Super Admin has all permissions
        if (in_array('*', $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }

    /**
     * Get all defined roles.
     *
     * @return array A list of role names.
     */
    public static function getAllRoles(): array
    {
        self::loadRoles();
        return array_keys(self::$roles);
    }
}