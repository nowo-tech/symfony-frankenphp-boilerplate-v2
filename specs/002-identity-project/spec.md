# Feature Specification: Identity host (users & groups)

**Feature Branch**: `002-identity-project`  
**Created**: 2026-07-19  
**Status**: Completed (platform subset)  

**Note**: Folder name retained for Spec Kit continuity. **Project tenancy** from the Beacon lineage is **out of scope** for this boilerplate — product apps add their own domains.

**Input**: AuthKit-backed users, groups, account preferences, admin CRUD, and authenticated home.

## User Scenarios & Testing

### User Story 1 - Admin users & groups (Priority: P1)

As an instance admin, I manage users and groups under Administration.

**Acceptance Scenarios**:

1. **Given** `ROLE_ADMIN`, **When** I open `/admin/users` and `/admin/groups`, **Then** I can create/edit users, toggle enablement, manage group membership, and view activity timelines with CSRF.
2. **Given** the last remaining admin, **When** demotion or anonymize is attempted, **Then** the operation is blocked with a clear flash.
3. **Given** the platform baseline, **When** I open a user show page, **Then** there is no product “projects on user” panel.

### User Story 2 - Account profile / security / display / privacy (Priority: P1)

As an authenticated user, I manage my account without `_locale` in the URL.

**Acceptance Scenarios**:

1. **Given** `ROLE_USER`, **When** I open profile / security / display / privacy routes, **Then** pages render and save preferences.
2. **Given** privacy export, **When** requested, **Then** a JSON download is produced.
3. **Given** self-service anonymize, **When** I am the last admin, **Then** anonymize is refused.

### User Story 3 - Authenticated home (Priority: P1)

As a logged-in user, AuthKit success lands on platform home.

**Acceptance Scenarios**:

1. **Given** a successful login, **When** redirected, **Then** `dashboard_home` (or configured default target) loads.
2. **Given** DashboardMenu fixture `dashboard`, **When** seeded, **Then** “Home” points at that route.

### User Story 4 - Locale model (Priority: P2)

As a guest or account user, public AuthKit/legal routes use locale prefixes; the dashboard shell does not.

**Acceptance Scenarios**:

1. **Given** a guest, **When** switching locale, **Then** public URLs stay under `/{_locale}/…`.
2. **Given** an authenticated user with `preferredLocale`, **When** using the account locale control, **Then** UI language updates without embedding locale in dashboard paths.

## Requirements

- **FR-001**: User entity integrates AuthKit + UserKit (+ password history/policy as configured).
- **FR-002**: Admin users/groups CRUD under `/admin/users` and `/admin/groups`.
- **FR-003**: Account areas: profile, security, display, privacy (GDPR export/anonymize).
- **FR-004**: Named route `dashboard_home` for authenticated home.
- **FR-005**: No Project/Ingest/Issues modules in this repository.

## Key Entities

- **User**, **UserGroup**, **UserAction**, **PasswordHistory**
- **UserUiPreferences** (embeddable display prefs)

## Success Criteria

- Admin can provision users/groups; account privacy flows work; home route has no product dependencies.

## Related

- `036-admin-identity-audit`, `037-authkit-identity-migration`, `043-gdpr-user-export`, `060-authkit-social-login`
- Workspace REQs: REQ-IDENTITY, REQ-AUTH, REQ-LOCALE, REQ-USERKIT, REQ-BP-002
