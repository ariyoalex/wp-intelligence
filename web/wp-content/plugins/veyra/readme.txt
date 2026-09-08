=== Veyra ===
Contributors: ariyoalex
Tags: site health, diagnostics, monitoring, change tracking, client management
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Website-aware intelligence layer for WordPress. Diagnose errors, track changes, monitor health, document sites, and manage client requests.

== Description ==

Veyra is a website-aware intelligence layer for WordPress. Unlike generic AI chatbot plugins, it inspects your actual WordPress installation — your plugins, themes, configuration, errors, and business data — to provide actionable, evidence-based insights.

= What It Does =

* **Website Health Score** — See at-a-glance how healthy your WordPress site is, with a score based on real technical, security, performance, and update data.
* **Emergency Doctor** — Detects, groups, and explains PHP errors and plugin conflicts. Every diagnostic includes evidence and confidence level.
* **Change Recorder** — Tracks plugin updates, theme changes, content modifications, and user activity. Know who changed what and when.
* **Website Analyzer** — Scans your WordPress environment and builds a human-readable map of how your site works — plugins, themes, dependencies, integrations.
* **Client Handover** — Generates documentation for your WordPress site based on what is actually installed. Export as HTML or printable format.
* **Client Request Inbox** — A ticket system where clients can submit change requests without WordPress admin access. Track status and communicate around requests.

= Key Principles =

* **Evidence-based** — Every diagnostic shows what evidence supports the conclusion. No guessing.
* **Website-specific** — AI answers use your actual site data, not generic advice.
* **Works without AI** — Core functionality (health score, error detection, change tracking, documentation) works without any AI configuration.
* **Security-first** — Nonces, capability checks, prepared queries, output escaping. AI outputs are never executed as code.

= Who It's For =

* **Website Owners** — Understand what's happening with your site without technical knowledge.
* **Developers** — Get debugging information, error history, and diagnostics for client sites.
* **Agencies** — Generate handover documentation, manage client requests, and track changes across projects.
* **Freelancers** — Document completed websites, monitor client sites, and prove maintenance activity.

= Privacy =

Veyra stores all data locally in your WordPress database. No data is sent to external servers unless you configure an optional AI provider (such as OpenAI or Gemini). When AI is configured, only relevant website context is sent to the provider for analysis. API keys are stored encrypted and never exposed in frontend code.

== Installation ==

1. Upload the `veyra` folder to `/wp-content/plugins/`
2. Activate Veyra through the Plugins screen
3. Navigate to Veyra in your admin menu
4. Run your first scan to see your website health score

== Frequently Asked Questions ==

= Does this plugin require AI to work? =

No. All core features — health scoring, error detection, change tracking, website analysis, and documentation generation — work without any AI configuration. AI is optional and enhances diagnostics when configured.

= Will this plugin slow down my site? =

No. Veyra only loads assets on its own admin pages. No frontend code is added to your public site. Scans run on a schedule, not on every page load.

= What data does this plugin collect? =

All data is stored locally in your WordPress database. The plugin collects: plugin and theme information, error logs, change events, scan results, and (optionally) WooCommerce business metrics. No personal user data is collected.

= Is my data sent anywhere? =

Only if you configure an AI provider. When configured, relevant website context (plugin list, errors, changes) is sent to the AI provider for analysis. API keys are stored encrypted. No data is sent by default.

= Does this plugin execute AI-generated code? =

No. All AI outputs are displayed as text only. The plugin includes safety guardrails that strip executable code patterns from AI responses before display.

== Screenshots ==

1. Dashboard showing website health score and attention items
2. Emergency Doctor with grouped errors and diagnostics
3. Change Recorder showing recent activity
4. Website Analyzer with plugin dependency map
5. Client Handover document preview
6. Settings page with AI configuration

== Changelog ==

= 1.0.0 =
* Initial release
* Website health scoring
* Emergency Doctor with error detection and grouping
* Change Recorder with event tracking
* Website Analyzer with plugin and theme scanning
* Client Handover documentation generator
* Client Request Inbox
* Settings system with General, Monitoring, Notifications, Security, and Advanced tabs
* REST API endpoints
* Structured logging system

== Upgrade Notice ==

= 1.0.0 =
Initial release.
