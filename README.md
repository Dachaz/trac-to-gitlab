# What is it?

This command line utility migrates Trac tickets to GitLab issues using Trac and GitLab APIs. 


# Features

* Migrates open tickets of a single Trac component
* Migrates any tickets matching a specific Trac query
* Keeps the original author and assignee when migrating
* Supports mapping Trac usernames to GitLab usernames
* Converts WikiFormatting into GitLab Flavoured Markdown
* Optionally generates a link back to the original Ticket in the GitLab issue


# Known limitations

* The ticket creation date is not migrated (all issues get current date as creation date)
* There is no deduplication (running the script n times with the same parameters will create n copies of each issue)
* The GitLab API token user must have access to the target GitLab project
* Migrating the original author requires a GitLab API token from an administrator user. Even then, the original author must have access to the target GitLab project. If they don't, ticket will be migrated with the token user (administrator) set as as the ticket's author.
* WikiFormating to GFM conversion does not cover every possible feature, but tries to cover the most common cases


# Requirements

* PHP >=5.3.0, with mbstring and json extensions
* Trac with [JSON-RPC plugin](http://trac-hacks.org/wiki/XmlRpcPlugin) enabled
* GitLab supporting web API v3


# Usage

All configuration options are passed as arguments on the command line. To get an overview, just run the script without any parameters.

## As a pre-compiled phar

```bash
curl -O http://apps.dachaz.net/trac-to-gitlab/bin/1.0.0/trac-to-gitlab.phar
php trac-to-gitlab.phar
```

On a UNIX-based system, you can also:

```bash
chmod +x trac-to-gitlab.phar
mv trac-to-gitlab.phar trac-to-gitlab
./trac-to-gitlab
```

## From git

```bash
git clone https://github.com/Dachaz/trac-to-gitlab.git
php migrate.php
```

On a UNIX-based system, you can also:

```bash
chmod +x migrate.php
mv migrate.php migrate
./migrate
```


# License

WTFPL

# Additional notes

The idea has been adapted from https://gitlab.dyomedea.com/vdv/trac-to-gitlab/

Contributions welcome.