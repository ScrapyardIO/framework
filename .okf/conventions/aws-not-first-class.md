---
type: Convention
title: AWS is not first-class
description: ScrapyardIO targets local desktop and edge devices; AWS drivers are not public framework surface.
tags: [convention, aws, edge, deployment]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T06:20:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T06:20:00Z" }
status: stable
sources:
  - id: cache
    resource: .okf/components/cache.md
    title: Cache public drivers
  - id: queue
    resource: .okf/components/queue.md
    title: Queue Phase 1 drivers
  - id: package
    resource: .okf/orientation/package.md
    title: Package orientation
---

# Rule

**AWS is not first-class** in `scrapyard-io/framework` / Fabricate 0.7.

Deployment target is **local desktop and edge / consumer devices** — not cloud web hosting.

| Do | Don't |
|----|-------|
| Public drivers: file, redis, sync, null, deferred, failover (as each domain defines) | Register SQS, DynamoDB, S3, or other AWS backends as default/public Machine surface |
| Optional donor leftovers may remain on disk unused | `require` `aws/aws-sdk-php` on the umbrella for normal boots |
| Document deferred cloud adapters as **not shipped** | Treat Laravel cloud hosting patterns as the product center |

# Implications

- **Cache** — file + redis only (no DynamoDB / S3 cache).
- **Queue** — Phase 1: sync / null / deferred / background / failover / redis (no SQS / Dynamo failed storage).
- **Filesystem** — local disks first; S3/Flysystem cloud adapters are not a first-class product story.
- Companion chip/GPIO packages stay on-device; they do not pull AWS into Core.

# Related

- [Cache](../components/cache.md)
- [Queue](../components/queue.md)
- [Dependency direction](dependency-direction.md)
