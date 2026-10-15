# Role-permission matrix

All roles remain limited to assigned plants. Admin does not automatically approve. Supervisor and manager cannot approve their own reports.

| Role | Permissions |
|---|---|
| operator | equipment.view, daily-report.create, daily-report.submit, breakdown.create |
| supervisor | equipment.view, daily-report.view-all, daily-report.create, daily-report.submit, daily-report.verify, daily-report.approve, daily-report.override-meter, daily-report.revise, assignment.manage, assignment.override, report.export, breakdown.create, maintenance.close |
| admin | equipment.view, equipment.manage, daily-report.view-all, sap.import, sap.reconcile, report.export, user.manage, role.assign, audit.view |
| maintenance | equipment.view, maintenance.manage, maintenance.close, breakdown.create, report.export |
| manager | equipment.view, daily-report.view-all, daily-report.approve, daily-report.revise, report.export, audit.view |
