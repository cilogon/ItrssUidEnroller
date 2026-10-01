# ItrssUidEnroller Plugin

ItrssUidEnroller is an enrollment flow plugin for COmanage Registry 4.x, used by the ITRSS deployment. When an approver approves a self-signup petition in the ITRSS CO, it builds a uid such as `example-shayna` from the person's email address and stores it as their only uid Identifier.

## Who the documentation is for

The documentation under `docs/` is written for CILogon staff who operate the Registry and administer the ITRSS CO. It describes the plugin as the code behaves today, including its known defects.

## Documentation

- [ItrssUidEnroller reference](docs/README.md): how the uid is built, its configuration, troubleshooting and error messages, and the assumptions and known gaps in the code. The plugin is small enough that one page covers what larger plugins split across several.
- [ITRSS custom uid plugin policy](https://github.com/cilogon/itrss-policies/blob/main/ItrssUidEnroller%20Plugin.md): the policy behind the uid format.
