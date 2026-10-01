# CLAUDE.md

## Project Overview
This project is an enrollment flow (enroller) plugin for COmanage Registry
version 4.x. The COmanage Registry code repository for version 4.x is at
https://github.com/Internet2/comanage-registry and the technical manual is in
the wiki at
https://spaces.at.internet2.edu/spaces/COmanage/pages/17105978/COmanage+Registry+Technical+Manual
Version 4.x of COmanage Registry uses the CakePHP version 2.x model view
controller (MVC) framework.

The plugin is attached as a wedge to an enrollment flow used by ITRSS. When the
petition is finalized it builds a custom `uid` Identifier for the enrollee CO
Person from the `mail` petition attribute, in the form
`<core domain>-<username>` (for example `shayna@example.edu` becomes
`example-shayna`). Subdomains, top-level domains, and country-code
second-level domains (such as `.edu.au` or `.ac.uk`) are stripped,
non-alphanumeric characters are removed, and the result is lowercased. If the
uid is taken, a collision number from 2 to 100 is appended. Any existing `uid`
Identifier on the CO Person is replaced. The policy behind this format is in
the ITRSS policy document linked from `README.md` (in the separate
`cilogon/itrss-policies` repository).

## Directory and File Structure & Key Details
- `Config/Schema/schema.xml`: database table definitions in AdoDb XML format.
- `Controller`: controllers used in the MVC framework.
  `ItrssUidEnrollerCoPetitionsController.php` extends the Registry
  `CoPetitionsController` and holds the plugin logic in
  `execute_plugin_finalize()`. `ItrssUidEnrollersController.php` is the
  `SEWController` for the (empty) per-wedge configuration.
- `Lib/lang.php`: text localization file for the plugin since COmanage Registry
  does not use the standard CakePHP 2.x approach to text localization. Plugin
  keys use the `er.iue.` and `in.iue.` prefixes.
- `Model`: models used in the MVC framework. `ItrssUidEnroller.php` declares
  `cmPluginType = 'enroller'` and belongs to `CoEnrollmentFlowWedge`.
- `View`: view files used in the MVC framework, following the Registry
  conventions (a single `fields.inc` used as the template for add and edit).
  `View/ItrssUidEnrollers/edit.ctp` is a symlink to Registry's
  `app/View/Standard/edit.ctp` and only resolves when the plugin is installed
  under `local/Plugin` in a Registry checkout.
- `docs/README.md`: the staff reference page, indexed from `README.md`.
  `docs/plans/` holds planning artifacts and is not part of the staff
  documentation.
- The remaining directories (`Console`, `Test`, `Locale`, `webroot`, and so on)
  hold only `empty` placeholder files from the plugin skeleton.

## Coding Style & Conventions
- Language: PHP version 8.3 is preferred.
- Naming convention: Follow the convention used by COmanage Registry 4.x.
- Use jQuery for dynamic HTML in view files. More but shorter lines of jQuery
  are preferred over long lines of jQuery code.
- Double slashes are preferred for comments.
- Put user-facing strings in `Lib/lang.php` rather than hard-coding them in
  controllers or views.

## Testing & Verification
- There is no automated test suite yet. Lint changed PHP with `php -l <file>`
  to catch syntax errors before treating a change as complete.
- The uid derivation (domain stripping, character filtering, lowercasing) is
  pure string handling; when changing it, check representative addresses by
  hand (for example a plain `.edu` address, a subdomain, and a country-code
  address like `user@my.uq.edu.au`).
- Behavior that touches the database (collision checks, Identifier save and
  delete, history records) cannot be verified from this repository alone;
  validate such changes manually in a running COmanage Registry with an
  enrollment flow that uses this plugin.

## Do's & Don'ts
- Do: Respect existing code style and patterns but suggest alternatives
  that provide generally cleaner and more maintainable code.
- Do: When a change alters plugin behavior, update `docs/README.md` in the
  same pull request. Cite code by file and function name, not line number.
  If the change alters the generated uid format or collision handling, also
  say so to the developer so the ITRSS policy document in
  `cilogon/itrss-policies` can be updated; that repository is not edited from
  here.
- Don't: Put hostnames, credentials, or real user email addresses or uids in
  the repository; it is public.
- Don't: Introduce new dependencies without approval.
- Don't: Commit credentials or secrets.

## Git, Remotes, and Pushing
This repository is set up for the GitHub machine account `skoranda-agent`; the
global "Machine account" rules apply. It has three remotes (confirm with
`git remote -v`; all HTTPS):
- `bot` -> `https://github.com/skoranda-agent/ItrssUidEnroller.git`, the
  machine account's fork. Claude pushes feature branches here.
- `upstream` -> `https://github.com/cilogon/ItrssUidEnroller.git`, the
  canonical repository. Pull requests target it.
- `origin` -> `https://github.com/skoranda/ItrssUidEnroller.git`, the
  developer's personal fork. Claude does not push here.

The machine account has read-only access to `cilogon/ItrssUidEnroller` and
write access only to its own fork, and GitHub enforces that. The limit on
writing upstream is therefore held by GitHub, not only by these instructions.

Rules:
- **Never commit to `main`.** Create a branch first and commit there. If a
  commit lands on `main` by mistake, move it onto a branch.
- Before any push or pull request, verify both: `gh api user --jq .login`
  prints `skoranda-agent`, and `git remote get-url bot` is
  `https://github.com/skoranda-agent/ItrssUidEnroller.git`. If either check
  fails, stop and tell the developer; never log in or switch `gh` accounts.
- **Shipping flow:** push the feature branch to `bot`, then open a
  ready-for-review pull request on `upstream`:
  `gh pr create --repo cilogon/ItrssUidEnroller --base main --head skoranda-agent:<branch>`.
  The developer reviews and merges there. There is no `origin` pull request
  step and no second pull request.
- Claude may manage that pull request: edit its title and body, push follow-up
  commits to its branch, reply to review comments, and read CI results.
  Force-pushing a `bot` branch, closing a pull request, or deleting a `bot`
  branch needs the developer's approval each time.
- **Upstream CI on bot pull requests:** a pull request from a fork may wait for
  the developer's approval before Actions run, and it gets no repository
  secrets. A pull request showing no checks is not green.
- **Never** push to `upstream` or `origin` (by remote name or by URL), push or
  force-push `main` on any remote, or approve or merge any pull request. Those
  stay with the developer.

Recording where work landed:
- When recording where work landed -- in a plan, a doc, or a commit message --
  cite the **upstream** pull request, owner-qualified
  (`cilogon/ItrssUidEnroller#N`), and only once it has merged. While the work
  is still unmerged, name the branch or the pull request and say the merge is
  pending. Recover merged numbers from `git log --merges main`.
