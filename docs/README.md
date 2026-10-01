# ItrssUidEnroller reference

ItrssUidEnroller is a COmanage Registry enrollment flow plugin for the ITRSS deployment. ITRSS is part of the University of Missouri System. When an approver approves a self-signup petition in the ITRSS CO, the plugin builds a uid for the new CO Person from their email address, for example `example-shayna` from `shayna@example.edu`, and stores it as the person's only Identifier of type uid.

This page is for CILogon staff who run the Registry and administer the ITRSS CO. It describes the code on `main` as it is now. The plugin is small, so this one page does the work that the separate pages under `docs/` do for larger plugins such as EntraSource, and its sections use the same names.

## Related documentation

- [ITRSS custom uid plugin policy](https://github.com/cilogon/itrss-policies/blob/main/ItrssUidEnroller%20Plugin.md): the policy behind the uid format, and the authority on why the format is what it is. This page describes how the code carries it out.
- [ITRSS solution architecture overview](https://github.com/cilogon/itrss-policies/blob/main/ITRSS-Solution-Architecture.md): how this plugin fits with the other ITRSS Registry plugins and services. The overview is in a private repository for ITRSS and CILogon staff.

## How it works

### Where it fits

- The ITRSS CO is the only CO in the deployment.
- Several enrollment flows in the ITRSS CO use the plugin. All of them follow the "self signup with approval" pattern, and each has the plugin attached as an enrollment flow wedge.
- The plugin runs at the petition's finalize step, after an approver approves the petition and after Registry's own finalize processing. It does nothing at any other step.
- The uid it creates is meant to be the person's single Identifier of type uid. Where any earlier uid came from does not matter: the plugin replaces it (but see [Assumptions and known gaps](#only-the-first-existing-uid-is-replaced)).

### How the uid is built

The plugin takes the email address that the enrollment flow collected on the petition (the petition's `mail` attribute) and works on it in these steps, in this order:

1. Split the address at the `@` into a user part and a domain.
2. Reduce the domain to its core name:
   - If the last label is two letters long (a country code) and the label before it is one of `ac`, `edu`, `com`, `co`, `org`, `net`, `gov`, `sch`, `res`, `unizg`, or `univ`, use the label before that. For example, `my.example.edu.au` becomes `example`.
   - Otherwise, use the second-to-last label. This also drops any subdomains. For example, `cs.example.edu` becomes `example`.
3. Remove every character that is not a letter or a digit from both the user part and the core domain.
4. Join them as `<domain>-<user>`.
5. Lowercase the result.

Because lowercasing is the last step, the country-code test in step 2 is case-sensitive. See [Assumptions and known gaps](#the-country-code-rule-is-case-sensitive).

| Email address | uid |
|---|---|
| `shayna@example.edu` | `example-shayna` |
| `jdoe@cs.example.edu` | `example-jdoe` |
| `j.doe@my.example.edu.au` | `example-jdoe` |
| `jdoe@mail.example.co.uk` | `example-jdoe` |
| `jdoe@example.de` | `example-jdoe` |
| `Jane_Doe@Example.EDU` | `example-janedoe` |

### Collisions

If the uid is already in use in the CO, the plugin tries the same uid with a number added, starting at 2: `example-shayna2`, then `example-shayna3`, and so on up to `example-shayna100`. It uses the first one that is free. If all of them are taken, the plugin fails with the "too many collisions" error described under [Troubleshooting](#error-messages).

### What changes on the CO Person record

1. If the person already has an Identifier of type uid, the plugin deletes it.
2. It adds the new uid as an Identifier of type uid, with status Active and login turned off, so it cannot be used to log in to Registry.
3. Both changes appear in the CO Person's history. The actor recorded is the person whose action finalized the petition, which in these flows is the approver.

The new uid is saved with provisioning skipped. The enrollment flow's provision step, which runs next, provisions it.

## Configuration

The plugin has no settings of its own. To use it, attach it as an enrollment flow wedge to each enrollment flow that should assign ITRSS uids. Its configuration page only says that there is nothing to configure.

The list of second-level labels used by the country-code rule is fixed in the plugin code. Changing it means changing the code and redeploying.

## Troubleshooting

### When the plugin fails

When the plugin cannot create the uid, the approver sees one of the error messages listed below, along with this notice:

> There was an exception in the ITRSS Uid Enroller Plugin while finalizing the petition.

Registry also records the failure in the petition's history as a failed step, with the same message. The enrollment stops there and does not continue to the provision step, so the person is not provisioned.

To recover, consult ITRSS, fix what can be fixed, and then add the uid to the CO Person record by hand.

### Error messages

The message text is in `Lib/lang.php`.

| Message | Meaning | What to check |
|---|---|---|
| `Unable to create a custom ITRSS uid due to a missing email address in the Petition Attributes.` | The petition has no email address. | That the enrollment flow collects an email address. Then follow [When the plugin fails](#when-the-plugin-fails). |
| `Unable to create a custom ITRSS uid due to too many collisions.` | The base uid and every numbered variant up to 100 were unavailable. | Which uids are taken. A rejection by an Identifier Validator also counts as a collision (see [Assumptions and known gaps](#validator-rejections-look-like-collisions)). |
| `Unable to create a custom ITRSS uid. Unable to delete the current uid on the Co Person.` | The person's existing uid could not be removed, so no new uid was added. | The person's existing uid Identifier. |
| `Unable to save the new custom ITRSS uid.` | The new uid could not be saved. Any old uid has already been deleted, so the person may now have no uid at all. | The CO Person's Identifiers, then follow [When the plugin fails](#when-the-plugin-fails). |

### An approval ends in a Registry error page

If the email address contains more than one `@`, approving the petition produces a Registry error page instead of one of the messages above. The plugin's own message for this case ("Unable to create a custom ITRSS uid due to a poorly formed email address in the Petition Attributes") is never shown, because of a defect in the code. The failure may also not be recorded as a failed step in the petition's history. Check the email address on the petition, and follow [When the plugin fails](#when-the-plugin-fails).

### Why does this person have an unexpected uid?

- A uid ending in a number is a collision, or the result of finalizing the petition again (see [Assumptions and known gaps](#running-finalize-again-adds-a-number)).
- A uid that starts with `-` or ends with `-` came from an email address with no `@` or with nothing before the `@`.
- A uid such as `edu-jdoe` for a country-code address came from a mixed-case address.
- Two uid Identifiers on one person: see [Only the first existing uid is replaced](#only-the-first-existing-uid-is-replaced).

## Assumptions and known gaps

### Malformed addresses with more than one @

- **What the code does:** Tries to report a "poorly formed email address" error but passes its arguments incorrectly, which raises a PHP error instead.
- **Gap:** Staff see a Registry error page rather than the plugin's message. See [An approval ends in a Registry error page](#an-approval-ends-in-a-registry-error-page).

### Addresses with no @ or an empty user part

- **What the code does:** Does not reject them. An address with no `@` gives a uid such as `-jdoe`, and `@example.edu` gives `example-`.
- **Gap:** The malformed uid is saved without any error.

### Only the first existing uid is replaced

- **What the code does:** Deletes only the first uid Identifier it finds on the person before adding the new one.
- **Gap:** A person who already had two uid Identifiers keeps one of them alongside the new uid. That contradicts the intent that the plugin's uid is the person's only uid.

### Validator rejections look like collisions

- **What the code does:** Treats any failure of Registry's availability check as a collision, including a rejection by an Identifier Validator configured for the uid type.
- **Gap:** A validator rejection produces a numbered uid, or the "too many collisions" error, even when no uid is actually in use.

### Running finalize again adds a number

- **What the code does:** Checks whether the uid is available before deleting the person's existing uid.
- **Gap:** If finalize runs again for a person who already has their base uid, for example `example-shayna`, their own uid counts as a collision and they get `example-shayna2`.

### Delete and save are separate steps

- **What the code does:** Deletes the old uid and then saves the new one, without making the two a single change.
- **Gap:** If the save fails, the person is left with no uid. See the "Unable to save" row under [Error messages](#error-messages).

### The country-code rule is case-sensitive

- **What the code does:** Compares the second-level label with its list before lowercasing.
- **Gap:** `j.doe@my.example.EDU.au` gives `edu-jdoe` instead of `example-jdoe`.

### More than one email address on the petition

- **What the code does:** Uses the first `mail` attribute the database returns.
- **Gap:** If a petition has more than one, which address is used is not defined.

### The uid skips provisioning when saved

- **What the code does:** Saves the new uid with provisioning turned off.
- **Why:** The enrollment flow's provision step runs afterwards and provisions the new uid then. If the plugin fails, that step never runs (see [When the plugin fails](#when-the-plugin-fails)).

### Login is turned off

- **What the code does:** Creates the uid with login set to false.
- **Why:** In this deployment every Identifier is created with login set to false except the OIDC sub.

## For maintainers

All behavior is in `execute_plugin_finalize()` in `Controller/ItrssUidEnrollerCoPetitionsController.php`. The messages are in `Lib/lang.php`, and the failure notice is shown by `View/ItrssUidEnrollerCoPetitions/finalize.ctp`. When a change alters the plugin's behavior, update this page in the same pull request. If it changes the uid format, the ITRSS policy document may need updating too. `docs/plans/` holds planning artifacts and is not part of the staff documentation.
