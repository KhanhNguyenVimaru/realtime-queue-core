# Realtime Queue Core

Realtime Queue Core is a full-stack event enrollment platform with realtime updates. It supports authentication and role-based access (admin/user), event management with participant limits, and live attendee tracking. The dashboard shows join/leave activity and capacity signals powered by realtime broadcasting. Recent changes: added login rate limiting (IP + email) and enabled Redis-backed sessions for better login stability under high load.

**Tech Stack**
- Backend: Laravel 13, PHP 8.3, Laravel Passport (OAuth), Laravel Queue
- Realtime: Soketi (WebSocket), Redis, Laravel Broadcasting
- Database: MySQL 8
- Frontend: Nuxt 4, Nuxt UI, Pinia, Tailwind CSS
- Tooling: Vite, TypeScript

**Highlights**
- Event CRUD with limit and enrollment status
- Realtime attendee count + join/leave timeline
- Admin user management
- Filtering and pagination in admin tables


Setup guide: `SETUP.md`  
Security guide: `AUTH_REFRESH_TOKEN_SETUP.md`
