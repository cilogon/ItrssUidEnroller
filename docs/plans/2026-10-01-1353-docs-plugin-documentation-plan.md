---
title: ItrssUidEnroller Documentation - Plan
type: docs
date: 2026-10-01
topic: plugin-documentation
artifact_contract: ce-unified-plan/v1
product_contract_source: ce-brainstorm
execution: code
---

# ItrssUidEnroller Documentation - Plan

## Goal Capsule

- **Objective:** A CILogon staff member who runs the ITRSS Registry can learn how the ItrssUidEnroller plugin gives each enrollee their uid, what to expect when it works, what they will see when it fails, and what to do next, without reading the PHP or asking the maintainer.
- **Means:** A short root README that links to one reference page at `docs/README.md`, following the layout of the sibling ItrsscilogonAssigner plugin (KTD1).
- **Product authority:** The repository maintainer confirmed this scope on 2026-10-01. The Product Contract outranks the Planning Contract. The code in `Controller/ItrssUidEnrollerCoPetitionsController.php` is the authority on behavior. The ITRSS policy document `ItrssUidEnroller Plugin.md` in `cilogon/itrss-policies` is the authority on why the uid format is what it is. Code fixes, documentation for other ITRSS plugins, and the ITRSS solution architecture document are not active scope.
- **Execution profile:** Documentation only. No PHP, configuration, or schema changes.
- **Stop conditions:** Stop and report if the code contradicts a statement the Product Contract requires the page to make, or if writing the page needs a fact that is neither in the code nor under Maintainer-supplied facts.
- **Who finishes:** The implementing agent writes and checks the files and opens the pull request on `cilogon/ItrssUidEnroller` from the `bot` remote. The maintainer reviews and merges.
- **Open blockers:** None.

---

## Product Contract

### Summary

Replace the one-line README with a short front page, and add a single reference page in `docs/` that explains how the plugin builds the enrollee's uid when an approver finalizes a petition. The page also covers the error messages, what staff see when the plugin fails, the defects in the current code, and the maintainer's reasons for its design choices. It links to the ITRSS policy document for the reasoning behind the uid format.

### Problem Frame

The repository's only documentation is a README with a single link to a policy document in another repository. Staff who need to know why a person got a particular uid, why an approval failed, or why a person has two uid Identifiers must read `execute_plugin_finalize()` or ask the maintainer. Some answers are not in the code at all, such as which enrollment flows use the plugin and why the uid skips provisioning.

The code also has defects that change what staff see, and those defects are written down nowhere. A malformed email address produces a PHP error instead of the plugin's message. A validator rejection looks like a uid collision. A failed save can leave the person with no uid.

The maintainer is documenting each ITRSS plugin in the same way. The ItrsscilogonAssigner plugin's documentation is the model.

### Key Decisions

- **One reference page, following ItrsscilogonAssigner's layout and EntraSource's section names.** The plugin is a single method, so separate pages would mostly be empty. (session-settled: user-approved — chosen over EntraSource's six separate pages: the plugin is too small to need them.) Governs R1, R2.
- **The policy document stays the authority on why the uid format is what it is; this page describes how the code behaves.** (session-settled: user-directed — chosen over folding the policy document's content into the page and over treating the link as a placeholder: one authority for each, so the two cannot drift apart.) Governs R4, R5.
- **Defects are documented as known gaps, not fixed.** (session-settled: user-directed — chosen over fixing the email-parsing defects in this work and over leaving them out of the page: a docs-only change, with fixes as separate work.) Governs R11, R12.
- **Recovery guidance is to consult ITRSS, not to work out the uid by hand.** (session-settled: user-directed — chosen over the page explaining how to work out the correct uid manually: ITRSS owns the correction.) Governs R10.

### Requirements

**Entry point**

- R1. The root README names the plugin, says in one or two sentences what it does for the ITRSS deployment, says who the documentation is for, and links to the reference page and to the ITRSS policy document.
- R2. All other documentation is on a single reference page under `docs/`. A reader can find it without confusing it with the planning files in `docs/plans/`, and it uses the section names of the sibling plugins' documentation (Related documentation, How it works, Configuration, Troubleshooting, Assumptions and known gaps, For maintainers).

**Role in the deployment**

- R3. The page explains that the plugin is an enrollment flow wedge on the ITRSS CO's "self signup with approval" enrollment flows, as set out under Maintainer-supplied facts. It runs at the finalize step, after an approver approves the petition, and its uid becomes the person's single Identifier of type uid.
- R4. A Related documentation section links to the ITRSS policy document as the authority on why the uid format is what it is. It also marks where the future ITRSS solution architecture overview will be linked, without inventing a URL.

**How the uid is built**

- R5. The page explains how the uid is built from the email address in the petition's `mail` attribute: the user part and domain are separated, the domain is reduced to its core name, all non-alphanumeric characters are removed, and the result `<domain>-<user>` is lowercased. It gives the exact list of second-level labels (`ac`, `edu`, `com`, `co`, `org`, `net`, `gov`, `sch`, `res`, `unizg`, `univ`) that trigger the country-code rule, and gives worked examples.
- R6. The page explains collisions: if the uid is already in use in the CO, the plugin tries the same uid with a suffix from 2 to 100, and it fails if none of those is free.
- R7. The page explains what happens to the person's record: an existing uid Identifier is removed, the new uid is added as Active and cannot be used to log in to Registry, and both changes appear in the person's history with the approver as the actor.

**Configuration**

- R8. The page states that the plugin has no settings of its own. It is configured by attaching it as a wedge to each enrollment flow. The domain rules are fixed in the code.

**Troubleshooting**

- R9. The page lists each error the plugin reports, quoting the message exactly as Registry shows it, with what it means and what to check.
- R10. The page describes what staff see when the plugin fails: the error message, a failed step in the petition history, and an enrollment that stops before provisioning. It then says to consult ITRSS, fix what can be fixed, and add the uid by hand.
- R11. The page describes the malformed-email symptom separately from the plugin's own error messages. An address with more than one `@` produces a PHP error page rather than the plugin's message, and the step may not be recorded as failed in the petition history.

**Assumptions and known gaps**

- R12. The page records each known gap below, stating what the code does and, where the maintainer gave one, why. Each gap is described in terms of what staff will see:
  - An address with more than one `@` produces a PHP error (see R11).
  - An address with no `@`, or an empty user part, is not rejected, and the plugin saves a malformed uid such as `-jdoe`.
  - Only the first existing uid Identifier is removed, so a person who already had two keeps one alongside the new uid. This contradicts the intent that the plugin's uid is the only one.
  - A rejection by an Identifier Validator is treated as a collision, so it can produce a suffixed uid, or the too-many-collisions error, even when no uid is in use.
  - Running finalize again for a person who already has their base uid gives them a suffixed uid, because the check happens before the old uid is removed.
  - Removing the old uid and saving the new one is not done as a single step. If the save fails, the person is left with no uid.
  - If the petition has more than one `mail` attribute, which one is used is not defined.
- R13. The page records the maintainer's design reasons under Maintainer-supplied facts for skipping provisioning and for setting login to false.

**Maintainers**

- R14. A For maintainers section says where the logic and the error messages live, and asks that the page be updated in the same pull request as any behavior change.

### Maintainer-supplied facts

These facts came from the maintainer and are not in the code. The page must use them as given.

- **Where it is used:** Several enrollment flows use the plugin as a wedge. All of them follow the "self signup with approval" pattern. The ITRSS CO is the only CO in the deployment (as on the ItrsscilogonAssigner page).
- **One uid per person:** The plugin creates the uid that must be the person's single Identifier of type uid. Where any earlier uid came from does not matter.
- **Recovery:** Staff consult ITRSS, fix what they can, and add the uid by hand.
- **Why provisioning is skipped:** The enrollment flow's provision step runs afterwards and provisions the new uid then.
- **Why login is false:** In this deployment, every Identifier is created with login set to false except the OIDC sub.

### Acceptance Examples

- AE1. **Covers R5.** **Given** an approved petition with email `shayna@example.edu`, **then** the page lets the reader work out the uid `example-shayna`.
- AE2. **Covers R5.** **Given** email `j.doe@my.uq.edu.au`, **then** the page lets the reader work out `uq-jdoe`.
- AE3. **Covers R5.** **Given** email `jdoe@cs.wisc.edu`, **then** the page lets the reader work out `wisc-jdoe`, with the subdomain dropped.
- AE4. **Covers R6.** **Given** `example-shayna` is already in use in the CO, **then** the page tells the reader to expect `example-shayna2`.
- AE5. **Covers R9, R10.** **Given** an approver sees "Unable to create a custom ITRSS uid due to too many collisions.", **then** the page explains what happened, that the enrollment stopped before provisioning, and that staff should consult ITRSS.
- AE6. **Covers R11.** **Given** an approval that ends in a PHP error page for a petition whose email contains two `@` signs, **then** the page explains the cause.
- AE7. **Covers R12.** **Given** a person with two uid Identifiers, **then** the known-gaps section explains how that can happen.

### Success Criteria

- The maintainer reviews the page and finds no factual errors against the code or the Maintainer-supplied facts.
- Using only the README and the reference page, a reader who has not seen the PHP can work out AE1 through AE7.

### Scope Boundaries

- Fixing any defect listed in R12. Those fixes are separate work.
- Explaining how to work out the correct uid by hand. ITRSS handles the correction.
- Editing the ITRSS policy document, or restating its reasons on this page.
- The ITRSS solution architecture document and documentation for the other ITRSS plugins.
- Installing the plugin into the Registry image.
- Moving Compound Engineering artifacts out of `docs/`.

<!-- ce-section: work-relationships -->
### How This Work Fits Together

This plan covers documentation for the ItrssUidEnroller plugin only. The broader effort below is the maintainer's current understanding, not a committed roadmap.

- Documentation for the other ITRSS COmanage Registry plugins: can proceed independently of this plan, and shares the README-plus-`docs/` layout first used in ItrsscilogonAssigner.
- The ITRSS solution architecture document: depends on the per-plugin docs, and links to this page at the place R4 provides.
- Fixes for the R12 defects: can proceed independently. When one lands, the page's known-gaps entry for it is updated in the same pull request (per R14).

### Sources / Research

- `Controller/ItrssUidEnrollerCoPetitionsController.php`, `execute_plugin_finalize()`: all plugin behavior.
- `Lib/lang.php`: the text of each error message and of the failure notice shown by `View/ItrssUidEnrollerCoPetitions/finalize.ctp`.
- `View/ItrssUidEnrollers/fields.inc`: shows that the plugin has no per-wedge settings.
- COmanage Registry 4.x `app/Controller/CoPetitionsController.php` (plugin step dispatch): a plugin's exception is shown to the user, recorded as a failed step in the petition history, and stops the flow before provisioning. A PHP `Error` such as `TypeError` is not caught there.
- COmanage Registry 4.x `app/Model/AppModel.php`, `checkAvailability()`: checks uid uniqueness within the CO and runs any active Identifier Validators, so a validator rejection reaches the plugin the same way as a collision.
- Sibling plugin `../ItrsscilogonAssigner`: `README.md`, `docs/README.md`, and `docs/plans/2026-10-01-1253-docs-plugin-documentation-plan.md` are the layout and planning model. Sibling plugin `../EntraSource` `docs/` supplies the section names.

---

## Planning Contract

**Product Contract preservation:** Product Contract unchanged.

### Key Technical Decisions

- KTD1. **The reference page is `docs/README.md`.** GitHub shows a folder's README when the folder is opened, so a reader who opens `docs/` lands on the page and does not have to tell it apart from `docs/plans/`. The sibling ItrsscilogonAssigner plugin uses the same name. Implements R2.
- KTD2. **Quote messages exactly as Registry shows them, and list only the messages staff can actually see.** The errors table covers `er.iue.no_email_attribute`, `er.iue.too_many_collisions`, `er.iue.no_delete`, and `er.iue.cant_save`, each quoted from `Lib/lang.php`. The notice from `View/ItrssUidEnrollerCoPetitions/finalize.ctp` (`in.iue.plugin_exception_finalize`) is quoted in the failure description. `er.iue.bad_email_address` is mentioned only in the malformed-address subsection, which explains that it is never displayed. Implements R9, R10, R11.
- KTD3. **Describe behavior in staff terms.** The page names petitions, approvers, CO Person records, Identifiers, enrollment flows, and provisioning. It names PHP files and functions only in the For maintainers section, citing them by file and function and never by line number, as the sibling pages do. Implements R2, R14.
- KTD4. **Show the uid rules with a table of worked examples using placeholder domains.** The repository is public and `CLAUDE.md` forbids real user addresses, so the examples use `example`-style domains. They cover a plain domain, a subdomain, a country-code domain with a listed second-level label, a country-code domain without one, punctuation in the user part, and a collision. Every example is worked out from the code by hand. Implements R5, R6.
- KTD5. **Update `CLAUDE.md` to point at the new docs.** Both sibling plugins' `CLAUDE.md` files list `docs/README.md` in their file map and require doc updates alongside behavior changes. This repository's `CLAUDE.md` currently says there are no docs to update. Implements R14.

### Assumptions

- The staff audience already knows general COmanage Registry terms (CO, CO Person, Identifier, petition, enrollment flow, wedge, approval, provisioning) and how plugins are installed, so the page does not define them.
- The policy document link keeps the URL the current README uses. The machine account cannot read that private repository, so the page links to it without summarizing it, which the settled policy-document decision requires anyway.

---

## Implementation Units

### U1. Reference page

**Goal:** Write the single reference page that answers every staff question in the Product Contract.

**Requirements:** R2 through R14; AE1 through AE7. Implements the one-page, policy-authority, known-gaps, and recovery Key Decisions through their governed R-IDs (R1, R2, R4, R5, R10, R11, R12). KTD1 through KTD4.

**Dependencies:** None.

**Files:**
- Create `docs/README.md`

**Approach:**
1. Opening: what the plugin is, who the page is for, and that code on `main` is described, mirroring the opening paragraphs of `../ItrsscilogonAssigner/docs/README.md` (R3).
2. Related documentation: the policy-document link as the authority for the uid format's rationale, and a sentence saying the ITRSS architecture overview will be linked once it exists (R4).
3. How it works: where it fits (wedge, approval flows, finalize step, single uid; R3), how the uid is built with the worked-examples table (R5, KTD4), collisions (R6), and what changes on the CO Person record (R7).
4. Configuration: no per-wedge settings, attach as a wedge per flow, domain list fixed in code (R8).
5. Troubleshooting: what staff see when the plugin fails and the consult-ITRSS recovery (R10), the errors table (R9, KTD2), and a malformed-address subsection (R11).
6. Assumptions and known gaps: one item per R12 bullet, plus provisioning skipped and login false with the maintainer's reasons (R13). Use the sibling's "What the code does" / "Why" (or "Gap") shape. Also record that the country-code rule is checked before lowercasing, so the label match is case-sensitive: `j.doe@my.example.EDU.au` gives `edu-jdoe`, not `example-jdoe`. The settled known-gaps decision covers this, and the page would otherwise misstate the R5 rule (how the uid is built). Make the R5 explanation give lowercasing as the last step.
7. For maintainers (R14).

**Patterns to follow:** `../ItrsscilogonAssigner/docs/README.md` for tone, section names, heading anchors, and the known-gaps item shape. Use the Product Contract's Maintainer-supplied facts as given.

**Test expectation:** none -- documentation-only change, and the repository has no test suite. Checked by the verification below.

**Verification:**
- Every behavior statement matches `execute_plugin_finalize()`, and every quoted message matches `Lib/lang.php`.
- Each worked example's uid is what the code produces for that address.
- A reader using only the page can work out AE1 through AE7.
- Every Maintainer-supplied fact appears, and no design reason appears that is not among them or in the policy document link.

### U2. Root README

**Goal:** Replace the one-line README with a short front page.

**Requirements:** R1.

**Dependencies:** U1.

**Files:**
- Modify `README.md`

**Approach:** A title naming the plugin, one or two sentences on what it does for ITRSS, a "Who the documentation is for" paragraph, and a Documentation list linking `docs/README.md` and the policy document. Mirror `../ItrsscilogonAssigner/README.md`. Nothing else, so the two files cannot drift apart.

**Test expectation:** none -- documentation-only change.

**Verification:** Both links are correct, and the description agrees with the opening of the reference page.

### U3. CLAUDE.md docs pointers

**Goal:** Make the project instructions point at the new docs, as the sibling plugins' instructions do.

**Requirements:** R14; KTD5.

**Dependencies:** U1.

**Files:**
- Modify `CLAUDE.md`

**Approach:**
1. Add a `docs/README.md` entry to the directory list, noting that `docs/plans/` holds planning artifacts and is not part of the staff documentation.
2. Replace the "say so to the developer" item in Do's & Don'ts with a rule to update `docs/README.md` in the same pull request when behavior changes, keeping the reminder that a change to the uid format may need the policy document updated.
3. Change only those lines. No reflowing of other text.

**Patterns to follow:** The equivalent lines in `../ItrsscilogonAssigner/CLAUDE.md`.

**Test expectation:** none -- documentation-only change.

**Verification:** The diff touches only the directory-list entry and the Do's & Don'ts item.

---

## Verification Contract

The repository has no test suite, linter, or formatter for Markdown, so verification is by review:

- **Fact check:** compare every behavior statement and quoted message in `docs/README.md` with `Controller/ItrssUidEnrollerCoPetitionsController.php` and `Lib/lang.php`.
- **Example check:** work each uid in the examples table through the code's rules by hand.
- **Acceptance check:** walk through AE1 through AE7 using only `README.md` and `docs/README.md`.
- **Link check:** every relative link resolves to a file in the repository. In-page anchors match the headings.
- **Scope check:** the diff adds `docs/README.md` and this plan, changes `README.md` and `CLAUDE.md`, and touches no PHP. `php -l` is not needed because no PHP changes.
- **Public-repo check:** no hostnames, credentials, or real user email addresses or uids appear.

---

## Definition of Done

- U1, U2, and U3 are complete and their verification passes.
- All checks in the Verification Contract pass.
- No placeholder text, TODO, or invented URL remains. The architecture overview is described in words as still to come.
- No abandoned drafts or extra documentation files are left in the diff.
