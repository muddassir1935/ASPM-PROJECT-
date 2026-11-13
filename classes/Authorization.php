<?php
/**
 * Class Authorization
 *
 * Central role-based access control (Sprint 5 RBAC).
 *
 * This is the ONE place that knows which role may do what. Controllers,
 * action handlers and views ask Authorization::can($role, $permission)
 * (or Auth::can($permission) for the logged-in user) instead of comparing
 * role strings inline, so adding a role or a permission means editing
 * this file only.
 *
 * Roles:
 * - student:        regular campus member — post, follow, like, reply,
 *                   bookmark, manage own content.
 * - society_admin:  student + society announcements.
 * - system_admin:   everything + moderation (delete any post/reply) and
 *                   user/role management.
 *
 * Legacy role values from before Sprint 5 ('admin', 'society') are
 * normalized to the new names so databases that have not run the
 * migration yet still behave correctly.
 */
class Authorization {
    public const ROLE_STUDENT = 'student';
    public const ROLE_SOCIETY_ADMIN = 'society_admin';
    public const ROLE_SYSTEM_ADMIN = 'system_admin';

    public const ROLES = [
        self::ROLE_STUDENT,
        self::ROLE_SOCIETY_ADMIN,
        self::ROLE_SYSTEM_ADMIN,
    ];

    /** Pre-Sprint-5 role values mapped to their new names. */
    private const LEGACY_ROLE_MAP = [
        'admin' => self::ROLE_SYSTEM_ADMIN,
        'society' => self::ROLE_SOCIETY_ADMIN,
    ];

    /** Human-readable labels for display (never show raw role keys). */
    public const DISPLAY_LABELS = [
        self::ROLE_STUDENT => 'Student',
        self::ROLE_SOCIETY_ADMIN => 'Society Admin',
        self::ROLE_SYSTEM_ADMIN => 'System Admin',
    ];

    /**
     * Permission map: permission => roles that hold it.
     * Every permission is listed explicitly per role (no hidden
     * inheritance), so the matrix reads exactly as enforced.
     */
    private const PERMISSIONS = [
        'post.create' => [self::ROLE_STUDENT, self::ROLE_SOCIETY_ADMIN, self::ROLE_SYSTEM_ADMIN],
        'post.edit_own' => [self::ROLE_STUDENT, self::ROLE_SOCIETY_ADMIN, self::ROLE_SYSTEM_ADMIN],
        'post.delete_own' => [self::ROLE_STUDENT, self::ROLE_SOCIETY_ADMIN, self::ROLE_SYSTEM_ADMIN],
        'post.delete_any' => [self::ROLE_SYSTEM_ADMIN],
        'reply.create' => [self::ROLE_STUDENT, self::ROLE_SOCIETY_ADMIN, self::ROLE_SYSTEM_ADMIN],
        'reply.delete_own' => [self::ROLE_STUDENT, self::ROLE_SOCIETY_ADMIN, self::ROLE_SYSTEM_ADMIN],
        'reply.delete_any' => [self::ROLE_SYSTEM_ADMIN],
        'announcement.create' => [self::ROLE_SOCIETY_ADMIN, self::ROLE_SYSTEM_ADMIN],
        'announcement.delete_any' => [self::ROLE_SYSTEM_ADMIN],
        'user.manage' => [self::ROLE_SYSTEM_ADMIN],
        'role.assign' => [self::ROLE_SYSTEM_ADMIN],
    ];

    /**
     * Normalize any stored role value to a current role name.
     * Unknown values fall back to 'student' (least privilege).
     */
    public static function normalizeRole($role): string {
        $clean = is_string($role) ? strtolower(trim($role)) : '';
        if (isset(self::LEGACY_ROLE_MAP[$clean])) {
            return self::LEGACY_ROLE_MAP[$clean];
        }
        return in_array($clean, self::ROLES, true) ? $clean : self::ROLE_STUDENT;
    }

    public static function isValidRole($role): bool {
        return in_array(self::normalizeRole($role), self::ROLES, true)
            && (is_string($role) && in_array(strtolower(trim($role)), self::ROLES, true));
    }

    /**
     * Does this role hold this permission?
     * Unknown permissions are denied (fail closed).
     */
    public static function can($role, string $permission): bool {
        $role = self::normalizeRole($role);
        if (!isset(self::PERMISSIONS[$permission])) {
            return false;
        }
        return in_array($role, self::PERMISSIONS[$permission], true);
    }

    /** All permissions held by a role (for the admin panel / docs). */
    public static function permissionsFor($role): array {
        $role = self::normalizeRole($role);
        $held = [];
        foreach (self::PERMISSIONS as $permission => $roles) {
            if (in_array($role, $roles, true)) {
                $held[] = $permission;
            }
        }
        return $held;
    }

    /**
     * Official accounts (system + society admins) get the official
     * visual treatment (ink avatar tone, Announcement line).
     */
    public static function isOfficialRole($role): bool {
        $role = self::normalizeRole($role);
        return $role === self::ROLE_SYSTEM_ADMIN || $role === self::ROLE_SOCIETY_ADMIN;
    }

    /** Display label for a role, e.g. 'system_admin' => 'System Admin'. */
    public static function displayLabel($role): string {
        $role = self::normalizeRole($role);
        return self::DISPLAY_LABELS[$role] ?? 'Student';
    }
}
