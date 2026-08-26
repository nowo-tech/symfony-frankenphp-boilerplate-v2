<?php

declare(strict_types=1);

namespace App\Identity;

/**
 * Catalog of recorded actions for the user activity timeline (platform shell only).
 *
 * String values are stable keys used in Twig translations (`users.activity.action.*`)
 * and stored in `user_action.action`. Product apps add their own action catalogs.
 */
enum UserActionType: string
{
    /** Instance admin created a Nowo Shell account. */
    case UserCreated = 'user.created';

    /** Instance role changed between user and admin. */
    case UserRoleChanged = 'user.role_changed';

    /** Account enabled (UserKit); user may sign in again. */
    case UserEnabled = 'user.enabled';

    /** Account disabled (UserKit); login is blocked. */
    case UserDisabled = 'user.disabled';

    /** GDPR account data export downloaded. */
    case AccountExported = 'user.account_exported';

    /** GDPR anonymize / soft-delete completed. */
    case UserAnonymized = 'user.anonymized';

    case GroupCreated = 'group.created';
    case GroupUpdated = 'group.updated';
    case GroupDeleted = 'group.deleted';
    case GroupMemberAdded = 'group.member_added';
    case GroupMemberRemoved = 'group.member_removed';

    case InstanceRoleCreated = 'instance_role.created';
    case InstanceRoleUpdated = 'instance_role.updated';
    case InstanceRoleDeleted = 'instance_role.deleted';
    case InstanceRolePermissionsUpdated = 'instance_role.permissions_updated';
    case InstanceRoleUserAdded = 'instance_role.user_added';
    case InstanceRoleUserRemoved = 'instance_role.user_removed';

    case InstancePermissionCreated = 'instance_permission.created';
    case InstancePermissionUpdated = 'instance_permission.updated';
    case InstancePermissionDeleted = 'instance_permission.deleted';

    /** Magic login link requested (email may have been sent). */
    case MagicLoginRequested = 'auth.magic_login_requested';

    /** Magic login link consumed successfully. */
    case MagicLoginConsumed = 'auth.magic_login_consumed';

    /** Password reset link/code requested (email may have been sent). */
    case PasswordResetRequested = 'auth.password_reset_requested';

    /** Instance non-secret config JSON exported. */
    case InstanceConfigExported = 'instance.config_exported';

    /** Instance non-secret config JSON imported. */
    case InstanceConfigImported = 'instance.config_imported';

    /** Instance Mailer DSN and/or From updated (context is redacted — no secrets). */
    case InstanceMailerUpdated = 'instance.mailer_updated';
}
