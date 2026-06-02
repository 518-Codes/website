# Auth + Member Profiles — Design Spec
**Date:** 2026-06-02  
**Status:** Approved

---

## Overview

Add user authentication and public member profiles to 518.codes. Regular attendees can register, log in, and build a profile showcasing their skills, experience, projects, and socials. The RSVP flow optionally creates an account in one step. Profiles are public; editing requires auth.

---

## Data Model

### `users` table additions
| Column | Type | Notes |
|---|---|---|
| `username` | `string`, unique | User-chosen handle; auto-generated from name on RSVP account creation |
| `is_admin` | `boolean`, default `false` | Gates Filament panel access; replaces blanket `canAccessPanel` |
| `bio` | `text`, nullable | Free-form about text |
| `headline` | `string`, nullable | e.g. "Full-stack dev at Acme" |
| `company` | `string`, nullable | Current company name |
| `avatar_path` | `string`, nullable | Stored file path |
| `website_url` | `string`, nullable | |
| `github_url` | `string`, nullable | |
| `twitter_url` | `string`, nullable | |
| `linkedin_url` | `string`, nullable | |

### `skills` table (new)
| Column | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `name` | `string`, unique | e.g. "Laravel" |
| `slug` | `string`, unique | e.g. "laravel" |
| `timestamps` | | |

### `skill_user` pivot (new)
- `user_id` FK → `users`
- `skill_id` FK → `skills`

### `experiences` table (new)
| Column | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `user_id` | FK → `users` | |
| `title` | `string` | e.g. "Senior Engineer" |
| `company` | `string` | |
| `start_year` | `unsignedSmallInteger` | |
| `end_year` | `unsignedSmallInteger`, nullable | null = current position |
| `description` | `text`, nullable | |
| `timestamps` | | |

### `projects` table (new)
| Column | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `user_id` | FK → `users` | |
| `title` | `string` | |
| `url` | `string`, nullable | |
| `description` | `text`, nullable | |
| `timestamps` | | |

### `rsvps` table addition
- `user_id` — nullable FK → `users` (existing anonymous RSVPs unaffected)

---

## Auth Flow

### Routes
| Route | Description |
|---|---|
| `GET /register` | Registration form |
| `POST /register` | Create account, redirect to `/members/{username}/edit` |
| `GET /login` | Login form |
| `POST /login` | Authenticate, redirect to intended URL |
| `POST /logout` | Destroy session |

### Registration
Fields: name, email, username, password. Username is unique and validated. After registration, redirect to `/members/{username}/edit` to complete profile.

Password reset is out of scope for this phase.

### RSVP → Account Creation
The existing RSVP form (name + email) gains an optional third step:

1. User fills in name + email and clicks RSVP.
2. **Email matches existing user account:** RSVP is saved with `user_id` set. No password prompt. Roster entry links to their profile.
3. **Email not in `users`:** After saving the RSVP, show an inline "create a password to save your profile" prompt. If they fill it in, an account is created (username auto-generated from name, de-duped if taken), they're logged in, and the RSVP `user_id` is updated.
4. **Skip password prompt:** RSVP saves anonymously, exactly as today.

---

## Public Profile Page

**URL:** `/members/{username}`  
**Visibility:** Public — no auth required to view.

### Layout
Two-column on desktop, single column on mobile. Mirrors the event detail page structure.

**Left column (main):**
- Name, headline, bio
- Skills as chips (using existing `.chip .chip-accent` styles)
- Experience — chronological list, current position (null `end_year`) accented
- Projects — list with title, description, optional URL link

**Right column (sidebar):**
- Avatar — square, `var(--fg)` border; falls back to initials badge if no avatar
- Company
- Social links — GitHub, Twitter, LinkedIn, Website (icon + handle)
- Events attended — count + list of public meetups RSVPed to

**Edit button:** Visible only to the authenticated owner of the profile. Links to `/members/{username}/edit`.

### Edit Profile Page
**URL:** `/members/{username}/edit`  
**Auth:** Required; must be the profile owner.  
Fields: name, headline, bio, company, avatar upload, website/github/twitter/linkedin URLs, skills (multi-select from global list), experiences (repeatable), projects (repeatable).

### Members Index
**URL:** `/members`  
**Visibility:** Public.  
Searchable grid of all users with a username. No requirement for a complete profile to appear.

---

## Roster Integration

The "who's going" roster on `/events/{slug}` gets two upgrades:

- **Linked names:** RSVPs with a `user_id` render the name as `<a href="/members/{username}">`. Anonymous RSVPs render as plain text, unchanged.
- **Avatar/initials badge:** RSVPs linked to a user with an `avatar_path` show a small square avatar. All others show the existing initials badge.

No structural changes to the roster layout.

---

## Admin Panel

### `canAccessPanel` gate
Changed from `return true` to `return $this->is_admin`. A migration sets `is_admin = true` on the first user (or this is handled manually post-deploy).

### New: Users Resource
Filament resource for `users`: list, view, edit name/email/username/`is_admin`. No password editing from admin.

### New: Skills Resource
Filament resource for the global `skills` list: create, edit, delete skill name + slug. Admins curate the taxonomy; users pick from it on their profile.

---

## Out of Scope (this phase)
- Password reset / forgot password
- Email verification
- OAuth / social login
- Profile privacy settings
- Following / connections between members
- Direct messaging
