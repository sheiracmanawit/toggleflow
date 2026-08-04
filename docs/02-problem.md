# Product Problem

## Release decisions are coupled to deployment

Teams increasingly deploy continuously, but feature availability is still commonly
controlled through environment variables, configuration files, long-lived branches,
or emergency code changes. Deployment and release become one risky event even when
the code could have been shipped safely behind a decision point.

## Small teams face an awkward choice

Home-grown flag tables begin simply but accumulate inconsistent evaluation,
unreviewed changes, leaked configuration, missing audit history, and operational
burden. Large commercial platforms solve broader problems but can introduce cost,
complexity, vendor dependence, and governance machinery that a small team does not
yet need.

## The affected users

- Developers need predictable flag evaluation and immediate rollback without another
  deployment.
- QA engineers need isolated environments where unfinished behavior can be verified.
- Release and product collaborators need understandable state and accountable changes.
- Platform engineers need a self-hosted service they can install, secure, upgrade,
  recover, and integrate without reverse-engineering it.

## ToggleFlow's response

ToggleFlow makes feature availability an explicit, environment-scoped release
decision. It combines a clear management dashboard, stable evaluation boundary,
secure credentials, auditability, and a deliberately staged path toward progressive
delivery, team governance, and dependable operation.

The product optimizes for understandable and reversible behavior before breadth. It
does not claim to replace every enterprise release, experimentation, analytics, or
identity capability.

## Desired change

A team should be able to deploy code independently, release it gradually when ready,
understand why a subject received a value, reverse the decision quickly, and operate
the control plane on infrastructure it owns.
