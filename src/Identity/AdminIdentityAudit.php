<?php

declare(strict_types=1);

namespace App\Identity;

/**
 * Allowlisted {@see UserActionType} values for Admin identity audit timelines (`036`).
 */
final class AdminIdentityAudit
{
    public const int TIMELINE_LIMIT = 100;

    /**
     * Actions shown on Admin → User → Activity (subject or actor).
     *
     * @return list<UserActionType>
     */
    public static function userTimelineActions(): array
    {
        return [
            UserActionType::UserCreated,
            UserActionType::UserRoleChanged,
            UserActionType::UserEnabled,
            UserActionType::UserDisabled,
            UserActionType::AccountExported,
            UserActionType::UserAnonymized,
            UserActionType::GroupMemberAdded,
            UserActionType::GroupMemberRemoved,
            UserActionType::MagicLoginRequested,
            UserActionType::MagicLoginConsumed,
            UserActionType::PasswordResetRequested,
        ];
    }

    /**
     * Actions shown on Admin → Group show (matched via `context.group_uuid`).
     *
     * @return list<UserActionType>
     */
    public static function groupTimelineActions(): array
    {
        return [
            UserActionType::GroupCreated,
            UserActionType::GroupUpdated,
            UserActionType::GroupDeleted,
            UserActionType::GroupMemberAdded,
            UserActionType::GroupMemberRemoved,
        ];
    }
}
