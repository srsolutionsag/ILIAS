# Want to Contribute? Great!

## Table of Contents

<!-- MarkdownTOC depth=0 autolink="true" bracket="round" autoanchor="true" style="ordered" indent="   " -->

1. [Who is a contributor?](#who-is-a-contributor)
1. [How to contribute?](#how-to-contribute)
   1. [Pull Request to the Repositories](#pull-request-to-the-repositories)
      1. [Rules for Contributors](#rules-for-contributors)
      1. [List of Labels](#list-of-labels)
      1. [Looking for Shepherd](#looking-for-shepherd)
      1. [Rules for Community Members assigned to PRs](#rules-for-community-members-assigned-to-prs)
   1. [Want to Contribute Something else than Commits?](#want-to-contribute-something-else-than-commits)

<!-- /MarkdownTOC -->

<a name="who-is-a-contributor"></a>
## Who is a contributor?

In general we consider everyone who takes part in the development of ILIAS a
contributor, where the contribution could take various forms, e.g. testing,
creating feature request, writing documentation, reporting security issues. We
aim to include everyone performing these or similar activities in our processes.

For practical reasons we need to define a contributor to be everyone who wants
to contribute commits to our repository for now. We trying to figure out ways to
also include Testers, Translators, Authors and other people into the processes
described here. If you want to contribute to said activities please have a look
[here](contributing.md).

As a contributor you will be named in the release notes of our major releases
with your name and your organisation as we find them in our commit history and
in your profile on GitHub. If your don't want to be listed, please write a short
mail to the [Technical Board of the ILIAS society](mailto:tb@lists.ilias.de).

<a name="how-to-contribute"></a>
## How to contribute?

<a name="pull-request-to-the-repositories"></a>
### Pull Request to the Repositories

Pull requests (PRs) without assignee will be assigned by the [Technical Board
(TB)](https://docu.ilias.de/goto.php?target=grp_5089&client_id=docu) to the
appropriate community member. The TB will also help to resolve problems with PRs and
associated processes, if you require mediation, please write a comment mentioning
via the Technical Board (`@ILIAS-eLearning/technical-board`) in the discussion
of the PR.

Please make yourself acquainted with the ILIAS Society's [process for
functional feature requests](https://docu.ilias.de/goto_docu_wiki_wpage_788_1357.html)
before starting to create your PR. Your PR should thus only contain bug fixes or
non-functional changes to our code base.

<a name="rules-for-contributors"></a>
#### Rules for Contributors

We are happy that you want to contribute. To enable us to merge your PRs in our
code please make sure:

* that your PR has a description that tells what is changed and why - with a
  size relative to the changes
* that your PRs is minimal - prefer to make two small PRs instead of one big PR
* that you discuss huge PRs with the responsible authorities in advance - this
  will save your time if the authorities do not agree with your proposed change
* that you create commits of self-contained logical units with concise commit
  messages and no unnecessary whitespace - this will help reviewers to
  understand what you did
* that your code is understandable and is documented - this will help
  reviewers as well
* that your commit follows the [ILIAS coding
  guidelines](https://docu.ilias.de/goto_docu_pg_202_42.html) - this is a
  bare minimum when it comes to style that we require for new code
* you don't introduce new code violations which could have been easily found by
  importing and running our
  [PhpStorm PHP Inspection Profile](./inspection-configs/php-storm-php-inspections.xml)
* that your are approachable for questions of reviewers

If your PR contains a bugfix please reference the number of the mantis ticket
in the title `12345 - To many spaces`, link the ticket in the description and
label the ticket with `bugfix`. You may make one PR per affected branch.

Please label non-bugfix PRs as `improvement`.

Please be prudent with PRs that are work in progress or are request for comments.
Only open these if you strictly want some feedback from the greater developer
community for a concrete proposal of some code or some guideline. Do not use PRs
to the ILIAS-repository as a general working space for incomplete features or ideas.
Prefer other measures like workshops or VCs for discussion about ideas or approaches.
If you are positive that you definitely need to open a PR as a draft, prefix the
summary with "WIP -"" for these kind of PRs to prevent them from being merged
accidentally.

<a name="list-of-labels"></a>
#### List of Labels

Currently, the following labels are used for Pull-Requests. These labels will
be assigned by the Technical Board or Authorities:

| Label           | Description                                                                                                                                                               |
|-----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| authorities     | The label `authorities` has to be assigned to PRs that contain updates to the authorities of a component.
| bugfix          | PRs with the label `bugfix` propose a solution for a reported bug in the official Bugtracker https://mantis.ilias.de                                                      |
| dependencies    | The label `dependecies` is used for PRs which propose new or updated dependencies. Please don't forget to also add the label `jour fixe`, when proposing new dependencies.|
| documentation   | The label `documentation` has to be assigned to PRs adding or updating documentation.                                                                                     |
| improvement     | The label `improvement` is used for PRs which propose a general improvement of code or documentation which is not related to a bug.                                       |
| javascript      | The label `javascript` has to be set for PRs changing Javascript code.                                                                                                    |
| jour fixe       | PRs which should be discussed during the next Jour Fixe are labeled with this `jour fixe`. Please set this label at least 2 days before the envisaged date of Jour Fixe.  |
| kitchen sink    | All contributions to the Kitchen Sink Project are labeled accordingly.                                                                                                    |
| Looking for Shepherd | The label `Looking for Shepherd` has to be set for PRs which changes made for unmaintained components.                                                      |
| php             | The label `php` has to be set for PRs changing PHP code.                                                                                                                  |
| roadmap         | The label `roadmap` is assigned to PRs that contain strategical or tactical discussions of technical topics regarding the future of a component.                          |
| technical board | This label is given for PRs which will be discussed in a meeting of the Technical Board. The label will be removed after the discussion.                                  |

<a name="looking-for-shepherd"></a>
#### Looking for Shepherd

`Looking for Shepherd` is a label in GitHub to mark PRs made for unmaintained components. As there is no developer that gets assigned such bugs due to her/his authority, pull requests with this tag can be reviewed by every ILIAS developer who can commit and decide if it is accepted and merged. We kindly ask every ILIAS developer to look into these PRs regularly and take responsibility for our shared code base.

<a name="rules-for-community-members-assigned-to-prs"></a>
#### Rules for Community Members assigned to PRs

As an FOSS community, we should be glad that people want to contribute code to
our project as this reflects usage of our project. To show this when handling
PRs, please make sure

* that you react to every PR assigned to you within 21 days - at least
  with a thank you and a target date if your schedule is tight
* that you give at least a brief statement why you close a PR if you reject one
* that you merge the changes in the PR in other branches if required

<a name="want-to-contribute-something-else-than-commits"></a>
### Want to Contribute Something else than Commits?

We are happy to get contributions that are no commits as well. There are many
other things you could contribute to ILIAS:

* **Ideas for new Features**: The development of ILIAS is driven by requirements
  from the community. Contribute your ideas via [feature requests](https://docu.ilias.de/goto.php?target=wiki_5307&client_id=docu#ilPageTocA119).
* **Bug Reports**: We do our best, but ILIAS might contain bugs we do not know
  about yet. Check out how the ILIAS Community handles [bug reports](https://docu.ilias.de/goto.php?target=wiki_5307&client_id=docu#ilPageTocA115).
* **Information about Security Issues**: Check out how the ILIAS community
  handles [security issues](https://docu.ilias.de/goto.php?target=wiki_5307&client_id=docu#ilPageTocA112).
  The Reporter of security issues will also be named in the release notes.
* **Time for Testing or Testcases**: We always need people who contribute
  testcases and carry them them before new releases. Please have a look
  [here](https://docu.ilias.de/goto_docu_pg_64423_4793.html) (German only).
  If you have questions, do not hesitate to contact our test case
  manager Fabian Kruse (fabian@ilias.de).
