# API Documentation

Base URL: `/api/v1`

All responses: `{ "success": true|false, "data": ..., "message": "..." }`

## Campaigns

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/campaigns/active` | List active campaigns |
| GET | `/campaigns/{slug}` | Campaign detail + theme |

## Players

| Method | Endpoint | Body |
|--------|----------|------|
| POST | `/players` | `{ "name": "Ravi" }` |

## Friend Media

| Method | Endpoint | Body |
|--------|----------|------|
| POST | `/friend-media` | multipart: `media_type` (`upload`/`avatar`/`initial`), `photo`, `avatar_id`, `initial`, `player_uuid` |
| GET | `/friend-media/avatars` | List curated avatars |
| GET | `/friend-media/{token}` | Signed upload image stream |

## Sessions (Creator Flow)

| Method | Endpoint | Auth | Body |
|--------|----------|------|------|
| POST | `/sessions/start` | — | `{ "campaign_slug", "name", "player_uuid", "referral_code", "friend_name", "challenge_title", "challenge_message", "friend_media_id" }` |
| GET | `/sessions/{uuid}/questions` | `X-Player-Session` header | — |
| POST | `/sessions/{uuid}/answers` | `X-Player-Session` | `{ "answers": [{ "question_id": 1, "question_option_id": 2 }] }` |
| POST | `/sessions/{uuid}/finalize` | `X-Player-Session` | — |

Returns `challenge_code`, `score`, and `challenge_link` on finalize.

## Challenges (Friend Flow)

| Method | Endpoint | Auth | Body |
|--------|----------|------|------|
| GET | `/challenges/{code}` | — | Returns personalization + creator score |
| POST | `/challenges/{code}/join` | — | `{ "name": "Anu", "player_uuid": null }` |
| POST | `/challenges/{code}/sessions/{uuid}/answers` | `X-Player-Session` | `{ "answers": [...] }` |
| POST | `/challenges/{code}/shares` | — | `{ "channel": "whatsapp|instagram|facebook|telegram|copy", "player_uuid": null }` |
| POST | `/challenges/{code}/rematch` | — | `{ "type": "challenge_back|rematch|new_friend", "player_uuid", "name", ... }` |
| GET | `/challenges/{code}/results` | — | Query: `?challenger_uuid=` |

## Share Cards

| Method | Endpoint |
|--------|----------|
| GET | `/share-cards/{code}` |
| GET | `/share-cards/{code}/image` |

## Leaderboard

| Method | Endpoint |
|--------|----------|
| GET | `/leaderboard/{period}` |

Periods: `daily`, `weekly`, `monthly`, `overall`

## CMS & Settings

| Method | Endpoint |
|--------|----------|
| GET | `/cms/{slug}` |
| GET | `/settings/public` |
| POST | `/analytics/visit` |

`settings/public` includes `friend_challenge` feature flags and limits.

## Rate Limits

Default API throttle applies. Stricter limits on session start, friend-media upload, submit/finalize.

## Authentication

Public player flow uses opaque `X-Player-Session` token returned on start/join.
Admin panel uses session-based `auth:admin` guard (web routes).
