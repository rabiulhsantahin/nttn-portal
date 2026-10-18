# NTTN Service Management Portal — MVP Blueprint

Target stack: Laravel 12 + MySQL 8 + Blade/Tailwind + Laravel Reverb/WebSockets (later for live chat).

Core workflow:
Reseller Survey -> Transmission Survey/NTTN Feedback -> MD Approval -> Work Order ->
Transmission Work -> NOC Handover -> NOC Verification -> Billing -> MD Final Approval ->
Final Handover -> ACTIVE -> TT / Upgrade / Downgrade / NOC Chat.

Roles:
super_admin, md, reseller, transmission, noc, billing.

Important rule:
Every action is recorded in activity_logs and every business object has a status.

Recommended next implementation order:
1. Authentication + RBAC
2. Reseller + customer + survey
3. Transmission + NTTN feedback
4. MD approval + work order
5. NOC handover/testing
6. Billing + final approval
7. Active links + TT
8. Upgrade/downgrade
9. Link-specific NOC chat
10. Notifications + reports
